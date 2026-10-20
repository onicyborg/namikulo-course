# Namikulo Course

Platform course video berbayar dengan area admin dan member, pembayaran Midtrans Core API melalui UI custom, serta akses belajar enam bulan. Nama tampilan aplikasi mengikuti `APP_NAME`.

**Status:** Phase 0 menunggu UAT user; Phase 1–6 lulus UAT user; Phase 7–9 diimplementasikan dan menunggu UAT user. Belum siap rilis.

## Pedoman proyek

| Dokumen | Isi |
|---|---|
| [AGENTS.md](AGENTS.md) | Aturan pengembangan, batas kewenangan agent, dan serah-terima |
| [SRS](docs/SRS.md) | Skema, rute, service, keamanan, pembayaran, video, operasi |
| [PRD](docs/PRD.md) | Kebutuhan produk, prioritas, aturan bisnis, batas MVP |
| [Design](design.md) | Otika, lima layout, komponen dan alur halaman |
| [Roadmap phase](docs/phases/README.md) | Urutan implementasi, dependensi, UAT dan gerbang rilis |

Prioritas bila bertentangan: AGENTS.md > SRS > PRD > design. Pekerjaan hanya pada phase yang diaktifkan user. Phase berikutnya dimulai setelah user menyatakan phase sebelumnya lulus dan menginstruksikan lanjut.

## Stack yang dituju

- Laravel 12, PHP sesuai `composer.json` (saat dokumen dibuat: `^8.2`).
- Blade murni, Bootstrap 4, jQuery dan Otika; tanpa SPA atau bundler frontend.
- PostgreSQL development, MySQL 8 production; tabel aplikasi UUID v7.
- Timestamp dan logika expiry disimpan dalam UTC; tampilan dan input waktu mengikuti zona browser. Zona member terakhir dipakai untuk hitungan enam bulan kalender.
- MVP tanpa worker server: queue sync sebagai konfigurasi, service sinkron; cache/session database.
- Midtrans Core API via Laravel HTTP client; SMTP untuk email.
- Dompdf untuk invoice PDF order lunas; identitas penjual diatur melalui `INVOICE_SELLER_*`.
- Persiapan video di browser admin dengan ffmpeg.wasm, HLS AES-128 satu kualitas, disk privat, token sesi, watermark dan hls.js pada Phase 4.
- cron-job.org memicu endpoint HTTPS bertoken; service memproses batch kecil langsung dengan lock/checkpoint. Tidak menggunakan Laravel Scheduler/crontab aplikasi.

Asset Otika memakai `asset()` dan `ASSET_URL`; asset aplikasi memakai `url()`/`Storage::url()`. Video tidak disimpan di storage publik. Perlindungan web menghambat penyebaran, bukan menjamin pencegahan screenshot atau rekaman layar.

## Memulai pengembangan

1. Salin `.env.example` menjadi `.env`, lalu isi koneksi PostgreSQL, SMTP, dan `SEED_ADMIN_*`.
2. Jalankan `composer install` bila folder `vendor` belum tersedia.
3. Jalankan `php artisan key:generate` hanya bila `APP_KEY` pada instalasi baru masih kosong.
4. Jalankan migration dan seeder sesuai urutan pada [Phase 0](docs/phases/PHASE-0.md#perintah-untuk-user), lalu perintah [Phase 1](docs/phases/PHASE-1.md#perintah-untuk-user). PHP extension DOM diperlukan untuk membersihkan HTML deskripsi course.
   Bila database sudah memiliki timestamp dari konfigurasi Asia/Jakarta, ikuti [panduan cutover UTC](docs/timezone-cutover.md) sebelum memakai konfigurasi baru.
5. Untuk Phase 4, lihat [panduan operasi video](docs/video-operations.md). Untuk Phase 5, isi kredensial sandbox dan ikuti [panduan pembayaran](docs/payment-operations.md). Untuk Phase 6, jalankan migration baru dan ikuti [panduan UAT area member](docs/phases/PHASE-6.md#panduan-uji-manual-uat). UAT Phase 0 masih menunggu konfirmasi bila diperlukan.

Proyek tidak memakai npm, Vite, atau build frontend. Asset Otika dimuat dari `ASSET_URL`; CSS dan JavaScript aplikasi berada di `public/`.

Untuk persiapan staging/production, baca [deployment](docs/DEPLOYMENT.md), [operasi](docs/OPERATIONS.md), dan [checklist rilis](docs/RELEASE-CHECKLIST.md). Pengingat akses H-7, pusat notifikasi, dan rekap email ada di [Phase 8](docs/phases/PHASE-8.md); aktifkan dua task cron barunya setelah migration dan UAT lulus. Diskusi materi dan moderasi admin ada di [Phase 9](docs/phases/PHASE-9.md), juga menunggu UAT user.

## Deployment ke cPanel

Langkah ini dijalankan **operator setelah UAT staging MySQL dan persetujuan rilis**. Gunakan [panduan deployment lengkap](docs/DEPLOYMENT.md) dan [checklist rilis](docs/RELEASE-CHECKLIST.md) untuk backup, pemulihan, dan pemeriksaan akhir.

1. Siapkan domain **HTTPS**, PHP 8.2+ beserta ekstensi Laravel/Dompdf, MySQL 8, Composer, dan akses terminal. Arahkan document root domain ke direktori `public/`; tempatkan root proyek, `.env`, `vendor/`, dan `storage/` di luar document root. Pastikan PHP dapat menulis `storage/` dan `bootstrap/cache/`. Video pada `storage/app/video-private/` tidak boleh dibuat publik atau di-symlink.
2. Sebelum memperbarui instalasi yang sudah berisi data, cadangkan **satu set** database, `.env`/`APP_KEY`, `storage/app/public/`, dan `storage/app/video-private/`. Pertahankan `APP_KEY` lama. Lakukan penyesuaian timestamp lama lewat [panduan cutover UTC](docs/timezone-cutover.md) bila instalasi sebelumnya menyimpan waktu dalam Asia/Jakarta.
3. Upload/checkout kode sesuai `composer.lock`, lalu jalankan dari root proyek:

   ```bash
   composer install --no-dev --optimize-autoloader
   cp -n .env.example .env  # hanya instalasi baru; tidak menimpa .env lama
   ```

4. Isi `.env` di server: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://DOMAIN-ANDA`, `APP_TIMEZONE=UTC`, `DB_CONNECTION=mysql` dan `DB_*`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync`, `VIDEO_DISK=video_private`, `MAIL_*` SMTP, `MIDTRANS_*`, identitas `INVOICE_SELLER_*`, serta `OPERATIONS_CRON_TOKEN`. Gunakan `SESSION_SECURE_COOKIE=true` pada domain HTTPS. Sesuaikan batas PHP/upload video dengan [panduan video](docs/video-operations.md). Secret hanya di `.env`, tidak di repository.
5. **Instalasi baru saja:** jika `APP_KEY` kosong, jalankan `php artisan key:generate`. Pada pembaruan, lewati langkah ini. Setelah backup dan staging siap, jalankan perintah berikut sebagai operator:

   ```bash
   php artisan migrate --force
   php artisan storage:link
   ```

   `storage:link` hanya untuk thumbnail/avatar publik. Pada instalasi pertama, isi `SEED_ADMIN_*`, jalankan `php artisan db:seed --class=AdminUserSeeder --force`, lalu hapus password seed dari `.env`. Jangan seed ulang pada pembaruan. Terakhir, setelah semua nilai `.env` final, jalankan:

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. Atur notification URL Midtrans ke `https://DOMAIN-ANDA/webhooks/midtrans`. Mulai dengan sandbox sampai alur pembayaran diverifikasi; aktifkan kredensial production hanya setelah merchant dan kanal siap. Ikuti [panduan pembayaran](docs/payment-operations.md).
7. Buat task eksternal di **cron-job.org** sesuai [setup cron-job.org](docs/operations-cron.md). Endpoint menggunakan POST HTTPS dan header Bearer; tidak memakai cron cPanel, `schedule:run`, atau `queue:work`.
8. Periksa login, email, pembayaran, invoice, video, penyimpanan, webhook, serta **Admin → Tugas & log**. Aktifkan trafik production setelah checklist rilis dan UAT disetujui. Setiap perubahan `.env` setelah `config:cache` perlu diikuti `php artisan config:cache` lagi.

### Ringkasan cron-job.org

Setiap baris adalah **job terpisah** dengan URL `https://DOMAIN-ANDA/operations/jobs/{task}`, method `POST`, body kosong, dan header `Authorization: Bearer <OPERATIONS_CRON_TOKEN>`. Zona jadwal `Asia/Jakarta` diperlukan untuk batas pukul 07.00 WIB. Buat token acak dengan `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`, simpan di `.env`, dan jangan letakkan di URL. Setelah menyimpan token, bangun ulang cache konfigurasi.

| Task | Jadwal Asia/Jakarta |
|---|---|
| `expire-enrollments` | Setiap jam |
| `prune-playback-sessions` | Setiap 15 menit |
| `cleanup-video-uploads` | Setiap 15 menit |
| `expire-pending-orders` | Setiap 10 menit |
| `reconcile-payments` | Setiap 15 menit |
| `create-access-expiry-notifications` | Setiap jam; aktifkan setelah skema/fitur Phase 8 siap dan UAT lulus |
| `send-notification-digests` | Setiap 5 menit mulai 07.00 sampai 23.55 WIB; aktifkan setelah skema/fitur Phase 8 siap dan UAT lulus |

Aktifkan notifikasi kegagalan untuk tiap job. Respons HTTP 200 dengan `status=completed`, `partial`, atau `busy` dibedakan saat pemantauan; `partial` dilanjutkan oleh pemicu berikutnya. Lihat [panduan cron-job.org](docs/operations-cron.md) untuk isian lengkap dan penanganan 401/404/429/503.

## Storage Cloudflare R2

Media dapat menggunakan bucket R2 privat dengan `MEDIA_STORAGE=r2`. Video, thumbnail/avatar, dan gambar editor memakai prefix terpisah; pemeriksaan akses tetap melalui Laravel. Ikuti [setup dan migrasi R2](docs/R2-STORAGE.md) sebelum mengganti driver. `storage:migrate-r2` menyediakan inventaris, copy, serta verifikasi SHA-256 tanpa menghapus file lokal. `FILESYSTEM_DISK=local` dan `VIDEO_DISK=video_private` tetap dipakai; log/cache/file temporer tetap lokal. Tidak ada migration database baru.

## Operasi dan rilis

Kontrak endpoint, daftar task, interval, token, serta batas batch tercantum di [SRS bagian 5.10](docs/SRS.md#510-pemicu-berkala-melalui-cron-joborg). MVP tidak membutuhkan worker server/FFmpeg hosting. Web Worker ffmpeg.wasm hanya berjalan di browser admin. Respons completed/partial dan checkpoint batch harus dipantau.

Detail batas cPanel, penyimpanan privat, versi asset lokal, konfigurasi dan pemulihan impor ada di [panduan operasi video](docs/video-operations.md). Dukungan HLS AES-128 pada build dan perangkat admin harus dikonfirmasi melalui UAT user.

[Phase 7](docs/phases/PHASE-7.md) menetapkan UAT staging MySQL, konfigurasi production, backup/pemulihan, dan persetujuan rilis. Database, file privat, serta `APP_KEY` harus dicadangkan; secret tidak boleh masuk repository.

Target awal persiapan video adalah browser desktop kompatibel; batas usulan sumber 200 MiB/15 menit/maksimum 720p perlu UAT. Belum menjamin semua OS/browser atau perangkat mampu memprosesnya.

Agent boleh menguji aplikasi lokal melalui [Chrome DevTools MCP](docs/BROWSER-TESTING.md) atau lingkungan test terpisah. Migration/seed pada database user tetap dijalankan user, dan user tetap menetapkan kelulusan UAT phase aktif.
