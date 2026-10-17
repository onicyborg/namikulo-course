# Deployment cPanel dan MySQL

Panduan ini untuk instalasi **setelah UAT staging MySQL lulus dan user menyetujui deployment**. Deployment production tidak dilakukan oleh agent. Aplikasi memakai Laravel 12, PHP 8.2+, MySQL 8, Blade, queue `sync`, dan cron-job.org. Tidak diperlukan FFmpeg server, worker queue, atau Laravel Scheduler.

## 1. Prasyarat hosting

- Domain HTTPS dengan document root mengarah hanya ke `public/`. Simpan root proyek, `.env`, `storage/`, dan `vendor/` di luar document root. Jika panel tidak mengizinkan document root ini, siapkan pemetaan direktori yang setara tanpa mengekspos root proyek.
- PHP 8.2+ dan ekstensi yang dibutuhkan Laravel/Dompdf: OpenSSL, PDO MySQL, mbstring, DOM/XML, fileinfo, ctype, tokenizer, filter, iconv, dan ZIP untuk Composer. Pastikan fungsi penyimpanan dan HTTP client dapat dipakai.
- MySQL 8 dengan charset `utf8mb4`. Database, user, dan hak akses dibuat oleh operator. Atur zona waktu koneksi/aplikasi ke UTC; data lama mengikuti [panduan cutover UTC](timezone-cutover.md), bukan di-reset.
- Proses PHP dapat menulis `storage/`, `bootstrap/cache/`, `storage/app/public/`, dan `storage/app/video-private/`. Disk video privat harus tetap di luar document root dan tidak disymlink.
- `public/vendor/ffmpeg/ffmpeg-core.wasm` disajikan sebagai `application/wasm`; Worker/core lokal dan browser admin kompatibel. Browser harus mampu memuat aset sekitar 31 MiB. Tidak perlu COOP/COEP global atau FFmpeg di server.
- Batas body request PHP, Apache/proxy, dan WAF menerima potongan video 5 MiB plus overhead. Beri waktu cukup untuk satu request finalisasi, tetapi target task cron aplikasi tetap di bawah 10 detik.
- Asset Otika berasal dari `ASSET_URL`; asset aplikasi dan font lokal berasal dari domain aplikasi. Pastikan host Otika dapat diakses lewat HTTPS.

## 2. Persiapan dan urutan deploy

1. Ambil backup **konsisten** database MySQL, `.env` termasuk `APP_KEY`, `storage/app/video-private/`, dan `storage/app/public/`. Simpan di luar document root dengan akses terbatas. Catat versi kode dan waktu backup. Uji pemulihan pada lingkungan terisolasi sebelum menyebutnya cadangan siap pakai.
2. Di staging MySQL, gunakan salinan data yang diizinkan dan jalankan semua migration baru; user menguji alur daftar, bayar sandbox, email, invoice, unggah, stream, progres, dan task cron. Jangan memakai `migrate:fresh` pada database berisi data.
3. Deploy kode dan `composer.lock` yang sesuai. Jalankan `composer install --no-dev --optimize-autoloader`. Skrip Composer yang ada menjalankan `artisan package:discover`; tidak menjalankan migration/seed.
4. Isi `.env` production. Pada instalasi baru dengan `APP_KEY` kosong, user menjalankan `php artisan key:generate` sekali. Pada pembaruan, pertahankan `APP_KEY` lama; menggantinya membuat kunci video terenkripsi tidak dapat dibaca. Simpan kredensial hanya di `.env`/secret manager, bukan repository.
5. Pastikan document root, HTTPS, izin direktori, dan link publik. Jalankan `php artisan migrate --force` **hanya setelah backup dan staging lulus**. Migration Phase 7 menambah indeks pada tabel yang sudah ada; pada data besar penambahan indeks dapat mengunci/menambah beban sementara, jadi jadwalkan saat trafik rendah. Semua perubahan skema harus migration baru yang non-destruktif terhadap data lama.
6. Untuk instalasi pertama saja, isi `SEED_ADMIN_NAME`, `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` di `.env`, lalu jalankan `php artisan db:seed --class=AdminUserSeeder --force`. Hapus nilai password seed dari `.env` setelah akun dibuat. Jangan seed ulang setiap deploy.
7. Jalankan `php artisan storage:link` bila belum ada. Link ini hanya untuk avatar dan thumbnail; jangan menghubungkan disk video privat.
8. Jalankan `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` setelah `.env` final. Pada instalasi dengan notifikasi pembayaran lama, jalankan `php artisan payments:redact-notifications` untuk melihat jumlah snapshot yang mengandung field di luar daftar aman, lalu `php artisan payments:redact-notifications --apply` setelah backup. Saat mengubah `.env` nanti, bangun ulang cache konfigurasi.
9. Pasang URL webhook Midtrans production dan task cron-job.org sesuai [OPERATIONS.md](OPERATIONS.md). Gunakan token/kunci production yang berbeda dari sandbox. Aktifkan notifikasi kegagalan di cron-job.org.
10. User memeriksa [RELEASE-CHECKLIST.md](RELEASE-CHECKLIST.md) dan memberi persetujuan rilis. Jangan menganggap deploy teknis sebagai kelulusan UAT.

## 3. Konfigurasi `.env` production

| Kelompok | Nilai yang harus diperiksa |
|---|---|
| Aplikasi | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `APP_TIMEZONE=UTC`, `APP_LOCALE=id`, `APP_KEY` tetap |
| Database | `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`; koneksi dan hak akses benar |
| Penyimpanan | `FILESYSTEM_DISK=local`, `VIDEO_DISK=video_private`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync` |
| Email | `MAIL_MAILER=smtp`, host/port/skema/akun/sandi/from, `MAIL_TIMEOUT=5`; hindari mailer `log` di production |
| Pembayaran | `MIDTRANS_IS_PRODUCTION=true` hanya setelah sandbox/merchant siap, Merchant ID, Server Key, daftar kanal aktif; `MIDTRANS_CLIENT_KEY` tidak dipakai oleh UI Core API MVP |
| Invoice | `INVOICE_SELLER_NAME`, alamat, email, nomor pajak hanya jika benar-benar berlaku; isi sebelum invoice pertama dibuat karena snapshot bersifat tetap |
| Operasi | `OPERATIONS_CRON_TOKEN` acak minimal 32 byte dan berbeda dari staging, `OPERATIONS_BATCH_SIZE=20`, `OPERATIONS_MAX_SECONDS=10`, `NOTIFICATION_DIGEST_BATCH_SIZE=3` untuk Phase 8 |
| Video | Nilai `VIDEO_*` mengikuti [operasi video](video-operations.md); `VIDEO_ACCEL_REDIRECT=false` pada cPanel saat ini |

`APP_DEBUG=false` mencegah stack trace mentah di respons publik. Gunakan `SESSION_SECURE_COOKIE=true` bila HTTPS sudah benar pada seluruh jalur. Pastikan `ASSET_URL` mengarah ke host Otika HTTPS yang disetujui.

## 4. Rollback dan pemulihan

- Backup **sebelum** migration. Migration production hanya dijalankan oleh user. Jangan memakai `migrate:fresh`, `db:wipe`, atau rollback skema otomatis pada data production.
- Jika rilis bermasalah, hentikan trafik/aktifkan maintenance sesuai prosedur hosting, lalu kembalikan kode ke versi sebelumnya **hanya bila skema baru kompatibel** dengan kode lama. Jika tidak, pulihkan satu set backup DB + `APP_KEY` + folder video/public pada lingkungan terisolasi terlebih dahulu, kemudian rencanakan pemulihan production.
- Jangan memulihkan DB tanpa `APP_KEY` yang cocok dan file HLS privat yang dirujuk DB. Kunci HLS persisten terenkripsi oleh Laravel; kombinasi yang tidak cocok membuat video tidak terbaca.
- Setelah rollback kode, jalankan `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` pada versi kode yang dipakai. Periksa webhook, cron, stream, serta log operasi sebelum membuka trafik kembali.
- Backup dan restore penuh merupakan tanggung jawab user/provider hosting. Contoh cara ekspor MySQL: `mysqldump --single-transaction --routines --triggers -u USER -p DATABASE > backup.sql` di lokasi privat. Pemulihan SQL mengubah data; lakukan hanya melalui prosedur operator yang sudah disetujui dan dicoba di lingkungan terisolasi.

## 5. Batas yang harus diterima sebelum rilis

PHP menyajikan segmen HLS dari disk privat; kapasitas serentak cPanel harus dinilai melalui UAT nyata. Proteksi web tidak dapat menjamin pemblokiran screenshot/rekam layar. Email SMTP yang timeout dapat memiliki hasil tidak pasti. Pembayaran memberi akses hanya setelah verifikasi server; gangguan gateway dapat menunda akses sampai rekonsiliasi berhasil.
