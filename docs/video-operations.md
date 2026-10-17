# Operasi video Phase 4

Implementasi Phase 4 menunggu UAT langsung oleh user. Server cPanel tidak menjalankan FFmpeg atau queue worker. Browser admin menyiapkan satu rendition HLS AES-128 dari MP4 lokal; server menerima potongan HLS terenkripsi, memvalidasi, lalu menyimpan hasil pada disk privat. Video sumber tidak dikirim. Kemampuan build dan perangkat untuk menghasilkan serta memutar HLS AES-128 harus dibuktikan dengan video nyata sebelum dipakai untuk konten produksi.

## Persiapan cPanel dan `.env`

1. Pastikan aplikasi disajikan lewat HTTPS, PHP memiliki OpenSSL, dan direktori `storage/app/video-private` dapat ditulis oleh PHP. Letakkan root proyek dan direktori tersebut di luar document root; document root hanya menunjuk ke `public/`. Jangan buat symlink publik ke disk privat.
2. Pastikan server mengirim `public/vendor/ffmpeg/ffmpeg-core.wasm` dengan MIME `application/wasm`. File `.htaccess` pada folder tersebut menyediakan aturan Apache; pada web server lain sesuaikan konfigurasi MIME. Ukuran asset core sekitar 31 MiB, sehingga koneksi browser harus mampu memuatnya.
3. Pastikan batas request body pada PHP/web server/WAF menerima potongan sebesar 5 MiB ditambah overhead. Nilai `post_max_size` dan batas reverse proxy harus lebih besar dari itu. `upload_max_filesize` tidak mengatur unggahan potongan biner ini, tetapi boleh disetel konsisten. Pastikan `max_execution_time` cukup untuk satu validasi segment dan operasi storage; proses finalisasi dipotong menjadi request singkat.
4. Pertahankan `APP_KEY` lama karena kunci video di database memakai enkripsi Laravel. Pastikan `APP_TIMEZONE=UTC`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=database`, dan database/session existing tetap terkonfigurasi. Tambahkan atau samakan nilai berikut:

```dotenv
VIDEO_DISK=video_private
VIDEO_SOURCE_MAX_SIZE_MB=200
VIDEO_SOURCE_MAX_DURATION_SECONDS=900
VIDEO_UPLOAD_MAX_SIZE_MB=250
VIDEO_SEGMENT_MAX_SIZE_MB=10
VIDEO_MAX_SEGMENTS=2000
VIDEO_SESSION_TTL_MINUTES=10
VIDEO_HEARTBEAT_SECONDS=60
VIDEO_ACCEL_REDIRECT=false
OPERATIONS_PLAYBACK_RETENTION_DAYS=30
OPERATIONS_UPLOAD_STALE_HOURS=24
```

`VIDEO_ACCEL_REDIRECT` harus tetap `false`: stream saat ini dialirkan oleh PHP. Batas 200 MiB/15 menit/720p adalah target UAT awal, bukan jaminan cPanel atau semua perangkat. Tidak ada variabel path FFmpeg server. Dua migration Phase 4 menambah tabel baru dan tidak mengubah timestamp/data lama.

## Perintah untuk user

Jalankan pada instalasi yang sudah memakai database proyek ini, berurutan:

```bash
php artisan migrate
php artisan optimize:clear
```

Tidak perlu seed ulang, `storage:link` baru, instalasi FFmpeg server, `queue:work`, atau `schedule:run` untuk fitur video. Jika sebelumnya belum ada `storage:link` untuk thumbnail/avatar publik, ikuti instruksi Phase 0 untuk kebutuhan tersebut. Backup database, `APP_KEY`, dan `storage/app/video-private` sebagai satu set; pemulihan satu tanpa yang lain tidak dapat menjamin video tetap terbaca.

## Pemicu cron-job.org

Gunakan endpoint yang sudah ada: `POST https://DOMAIN/operations/jobs/{task}` dengan header `Authorization: Bearer <OPERATIONS_CRON_TOKEN>`. Simpan token di konfigurasi cron-job.org, bukan di URL. Tambahkan dua task berikut, masing-masing tiap 15 menit:

- `prune-playback-sessions`: hapus sesi berakhir/dicabut setelah retensi 30 hari.
- `cleanup-video-uploads`: hapus impor batal/gagal/kedaluwarsa dan versi yang diganti setelah tidak dipakai sesi aktif. Aset aktif tidak dihapus.

Pemicu `expire-enrollments` dari Phase 2 tetap berjalan tiap jam. Respons `partial` berarti masih ada data dan pemicu berikutnya perlu berjalan. Pantau status gagal/503, backlog, kapasitas disk, dan `operation_states`. Jangan aktifkan Laravel Scheduler.

## Alur admin dan pemulihan

Di kurikulum, pilih **Siapkan video** pada materi. Gunakan MP4 dengan satu video H.264 8-bit `yuv420p`, audio AAC bila ada, maksimum 1280×720 atau 720×1280, 15 menit, dan 200 MiB. Keyframe pendek sekitar 6 detik membantu menjaga segmen di bawah 10 MiB. Browser desktop perlu HTTPS, WebAssembly, Web Worker, Web Crypto, memori dan waktu CPU yang cukup. Biarkan tab terbuka. Browser menilai codec dan durasi melalui metadata lokal; server memeriksa struktur playlist, hash, ukuran, dekripsi AES, dan paket MPEG-TS, tetapi tidak memiliki decoder untuk membuktikan codec secara mandiri.

Potongan diunggah maksimal 5 MiB/request, dengan retry idempoten. Jika tab ditutup atau di-refresh, hasil sementara di memori browser hilang; pilih ulang sumber dan proses ulang setelah sesi lama dibatalkan atau kedaluwarsa. Sesi impor berlaku 24 jam. Hasil lama tetap aktif sampai hasil baru seluruhnya siap. Jangan menghapus sumber lokal sebelum UAT playback dan backup selesai.

Satu user hanya dapat memiliki satu sesi playback aktif. Membuka sesi kedua mencabut sesi pertama. Token stream dikirim ke player lewat respons playback dan diverifikasi bersama sesi login, enrollment, serta expiry di setiap request. URL stream adalah endpoint terotorisasi, bukan URL file. Watermark dan pemblokiran tindakan browser hanya menghambat penyebaran; screenshot atau rekaman layar tidak bisa dijamin terblokir.

## UAT yang diperlukan

1. Sebagai admin, proses MP4 pendek representatif dan amati browser console/network. Hasil yang diharapkan: file WASM dan Worker lokal termuat, tahap mencapai **Siap**, kartu status berubah menjadi **Siap** tanpa refresh, dan tersedia aksi **Pratinjau video** serta **Siapkan video pengganti**. `video_assets.status=ready` dan HLS dapat diputar dengan audio/video normal. Inilah gate utama dukungan AES-128 build.
2. Coba Windows/macOS/Linux dan Chrome/Edge yang tersedia; catat browser/perangkat yang berhasil serta memori/waktu untuk video mendekati batas. Hasil yang diharapkan: browser yang tidak didukung mendapat pesan jelas. Jangan menganggap perangkat yang belum dicoba kompatibel.
3. Coba ekstensi/codec/dimensi/durasi/ukuran salah, segmen rusak/kurang, dan request manifest/path asing. Hasil yang diharapkan: ditolak, tanpa aset parsial menjadi siap. Periksa log tidak berisi kunci atau sumber biner.
4. Putus jaringan saat unggah lalu pulihkan, batal, refresh, dan ganti video yang sudah siap. Hasil yang diharapkan: retry tidak menggandakan potongan, pembatalan/pemulihan dijelaskan, video lama tetap tersedia sampai pengganti siap.
5. Terbitkan course dengan video siap, buka landing/detail course, lalu beri enrollment manual kepada member. Hasil yang diharapkan: publikasi lolos, member aktif dapat membuka player; member tanpa akses dan token milik user lain ditolak.
6. Buka sesi kedua, cabut/akhiri akses, tunggu expiry, lalu coba playlist/key/segmen lagi. Hasil yang diharapkan: sesi lama dan akses kedaluwarsa ditolak meski tab player masih terbuka. Arsip course tidak mencabut akses member lama; penghapusan materi menghentikan stream dan membersihkan berkas privat.
7. Periksa kontrol player dengan keyboard dan pada lebar 375/768/1024 px; pindah tab, coba cetak, buka modal/admin di viewport kecil. Hasil yang diharapkan: kontrol dapat digunakan, pause/blur bekerja, watermark terlihat, tanpa klaim proteksi rekaman absolut.
8. Picu cron-job.org sesuai jadwal dan amati respons `completed`/`partial`. Hasil yang diharapkan: sesi/impor stale dibersihkan sesuai retensi; aset aktif dan impor yang sedang berjalan tetap aman.

Asset lokal yang dipin: `@ffmpeg/ffmpeg` 0.12.15, `@ffmpeg/core` 0.12.10, `hls.js` 1.7.3. Pemberitahuan lisensi dan sumber upstream ada di `public/vendor/ffmpeg/README.md` serta `public/vendor/hls/README.md`. `@ffmpeg/core` berlisensi GPL-2.0-or-later; tinjau kewajiban distribusi sebelum rilis publik.
