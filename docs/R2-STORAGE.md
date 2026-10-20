# Penyimpanan media di Cloudflare R2

Implementasi mendukung satu bucket **privat** `namikulo-course`, tanpa public URL atau custom domain bucket. `MEDIA_STORAGE=r2` mengalihkan tiga disk aplikasi berikut:

| Disk aplikasi | Direktori lokal sebelum cutover | Prefix R2 |
|---|---|---|
| `public` | `storage/app/public/` | `public/` |
| `video_private` | `storage/app/video-private/` | `video-private/` |
| `editor_private` | `storage/app/private/` | `private/` |

Path relatif di database tidak berubah. Thumbnail dan avatar memakai `/media/public/...` melalui Laravel. Endpoint hanya menerima path gambar avatar/thumbnail yang diizinkan; tidak menerima prefix video atau editor. Gambar rich text tetap memakai endpoint editor yang memeriksa scope/akses, sedangkan HLS dan kunci tetap melalui sesi playback yang sah. Kredensial tidak diberikan ke browser. Cache, session database, log, invoice sementara, dan kode tetap di cPanel.

## Konfigurasi

Di dashboard R2, gunakan token **Object Read & Write** yang dibatasi ke bucket ini. Simpan Access Key ID, Secret Access Key, dan endpoint S3 dari dashboard dalam `.env`. Jangan mengaktifkan Public Development URL/custom domain untuk bucket ini. Panduan resmi: [token R2](https://developers.cloudflare.com/r2/api/tokens/) dan [endpoint S3](https://developers.cloudflare.com/r2/get-started/s3/).

```dotenv
MEDIA_STORAGE=local
FILESYSTEM_DISK=local
VIDEO_DISK=video_private
AWS_ACCESS_KEY_ID=isi_dari_dashboard
AWS_SECRET_ACCESS_KEY=isi_dari_dashboard
AWS_DEFAULT_REGION=auto
AWS_BUCKET=namikulo-course
AWS_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Gunakan endpoint persis dari dashboard jika bucket memakai jurisdiction khusus. `AWS_URL` tidak diperlukan; URL thumbnail dan avatar menggunakan path relatif `/media/public/...` pada host yang sedang diakses. `APP_URL` tetap harus disetel sesuai domain aplikasi untuk tautan absolut seperti email. Nama `VIDEO_DISK` tetap `video_private`, sehingga switch mengikuti `MEDIA_STORAGE`. Jangan menggantinya menjadi `s3`. Driver S3 sudah ditambahkan pada Composer. Hosting membutuhkan koneksi HTTPS keluar ke endpoint R2 dan PHP >=8.2 beserta ekstensi SDK, termasuk SimpleXML. Composer memakai platform PHP 8.2 agar dependency yang dikunci kompatibel dengan batas minimum hosting.

## Perintah untuk operator — memindahkan file lama

Perintah berikut dijalankan user pada server yang memiliki **seluruh file lokal lama**, setelah backup database, `.env`/`APP_KEY`, dan file media. Agen tidak memindahkan file bucket secara otomatis. Tidak ada migration database baru.

1. Deploy kode dan dependency: `composer install --no-dev --optimize-autoloader`.
2. Isi `.env` di atas, pertahankan `MEDIA_STORAGE=local`, kemudian jalankan `php artisan config:cache`.
3. Inventaris tanpa mengakses R2 atau menulis file tujuan:

   ```bash
   php artisan storage:migrate-r2
   ```

4. Selesaikan/hentikan upload yang sedang berjalan, jeda seluruh job cron-job.org, aktifkan maintenance, dan tunggu request aplikasi yang sedang berjalan selesai. Ini mencegah file berubah atau terhapus saat penyalinan. Kemudian:

   ```bash
   php artisan down
   php artisan storage:migrate-r2 --copy
   php artisan storage:migrate-r2 --verify
   ```

   `--copy` menyalin objek yang belum ada lalu membandingkan ukuran dan SHA-256 setiap file. Objek yang sudah ada diverifikasi, bukan ditimpa. Proses berhenti jika isi berbeda atau akses R2 gagal; ulangi perintah setelah penyebabnya diperbaiki. `--verify` tidak menulis ke R2. Keduanya hanya diizinkan saat maintenance. `--group=public`, `--group=video`, atau `--group=private` dapat dipakai untuk membagi pekerjaan; pastikan seluruh grup selesai sebelum cutover. `.gitignore` tidak disalin. Perintah tidak menghapus sumber, mengubah `.env`, atau menulis database.
5. Jika seluruh file sudah terverifikasi, ubah **hanya** `MEDIA_STORAGE=r2`, kemudian:

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan up
   ```

6. Lakukan UAT di bawah, lalu aktifkan kembali cron-job.org. Simpan file lokal sebagai backup sampai hasil diperiksa dan periode pemulihan selesai. Penghapusan salinan lokal dilakukan operator setelah memastikan backup; aplikasi tidak menghapusnya otomatis. Symlink `public/storage` lama boleh tetap ada selama transisi, tetapi URL baru memakai route media pada mode R2.

Inventaris nol file bukan bukti bahwa semua file yang dirujuk database tersedia. Jalankan pada server sumber yang benar, bandingkan inventaris dengan backup dan data yang digunakan, lalu periksa course lama saat UAT. Hindari mengaktifkan mode R2 pada mesin berbeda yang belum menerima hasil penyalinan. Jangan menjalankan `migrate:fresh`, seed, atau normalisasi timestamp untuk perpindahan storage ini.

## Pemrosesan dan batas hosting

Browser tetap menyiapkan HLS terenkripsi. Laravel menyimpan chunk di R2, membaca chunk via stream, dan menggabungkan **satu segmen** ke file temporer lokal yang ditutup/dihapus sesudah validasi. Hash, AES, dan struktur MPEG-TS diverifikasi sebelum segmen akhir ditulis. Disk temporer PHP harus dapat ditulis dan cukup untuk ukuran segmen yang diizinkan, bukan ukuran keseluruhan course. Memory tetap dibutuhkan untuk dekripsi satu segmen. Lock impor diperpanjang menjadi 300 detik untuk memberi ruang pada I/O remote; lock dilepas setelah request selesai.

R2 mengurangi penyimpanan permanen cPanel. Unggahan dan streaming tetap melewati Laravel, sehingga CPU, koneksi, bandwidth hosting, serta operasi R2 masih dipakai. Request storage memakai connect timeout 3 detik, timeout 10 detik, dan tanpa retry SDK otomatis; kegagalan mengikuti error/retry aplikasi. Finalisasi dan cleanup memerlukan UAT dengan jaringan hosting nyata. Jangan menambahkan lifecycle yang menghapus semua objek video berdasarkan umur: video aktif tidak boleh ikut terhapus.

## UAT dan pemulihan

- Buka thumbnail/avatar lama di admin, katalog, detail course, dashboard member, dan profil. Unggah pengganti dan periksa objek masuk prefix `public/`.
- Buka gambar editor lama pada deskripsi/diskusi. Unggah gambar baru; akun tanpa akses harus tetap ditolak untuk gambar materi privat.
- Putar video lama, seek/range, heartbeat, completion, dan akses materi berurutan. Session/access yang tidak sah harus ditolak.
- Unggah video baru sampai siap, pratinjau, ganti video, batalkan sesi, dan periksa cleanup stale/replaced. Video lama tetap tersedia sampai pengganti siap.
- Periksa bucket langsung tidak dapat diakses publik dan `/media/public/...` tidak menyajikan objek dari prefix video/editor.
- Periksa task cron dan log setelah cutover. Uji timeout/kegagalan R2 di staging; jangan menonaktifkan kredensial production sekadar pengujian.

Jika belum ada tulisan baru setelah cutover, operator dapat kembali ke `MEDIA_STORAGE=local` saat maintenance lalu membangun cache ulang. Setelah upload/perubahan baru di R2, **jangan langsung kembali ke lokal**: perubahan tersebut belum ada di salinan lokal. Sinkronkan dan verifikasi dulu atau pulihkan backup konsisten database + media + APP_KEY. Perintah migrasi ini hanya menyalin lokal ke R2, tidak menyediakan reverse sync atau menghapus objek tujuan.

## Pemeriksaan implementasi

Enam pengujian terarah (`tests/Feature/R2StorageTest.php`) lulus dengan 21 assertions pada storage tiruan: URL media, pemisahan prefix publik/privat, penyusunan segmen melalui stream, penolakan hash rusak, copy/verifikasi yang mempertahankan sumber, serta penolakan overwrite objek berbeda. Pengujian memakai konfigurasi testing dan tidak menyentuh database user atau bucket nyata. Pemeriksaan sintaks PHP, route media, dan diff juga dijalankan. Koneksi R2, upload/video nyata, performa cPanel, dan UAT browser belum diuji; operator memeriksanya setelah mengisi kredensial.
