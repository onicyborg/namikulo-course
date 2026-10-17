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

## Operasi dan rilis

Kontrak endpoint, daftar task, interval, token, serta batas batch tercantum di [SRS bagian 5.10](docs/SRS.md#510-pemicu-berkala-melalui-cron-joborg). MVP tidak membutuhkan worker server/FFmpeg hosting. Web Worker ffmpeg.wasm hanya berjalan di browser admin. Respons completed/partial dan checkpoint batch harus dipantau.

Detail batas cPanel, penyimpanan privat, versi asset lokal, konfigurasi dan pemulihan impor ada di [panduan operasi video](docs/video-operations.md). Dukungan HLS AES-128 pada build dan perangkat admin harus dikonfirmasi melalui UAT user.

[Phase 7](docs/phases/PHASE-7.md) menetapkan UAT staging MySQL, konfigurasi production, backup/pemulihan, dan persetujuan rilis. Database, file privat, serta `APP_KEY` harus dicadangkan; secret tidak boleh masuk repository.

Target awal persiapan video adalah browser desktop kompatibel; batas usulan sumber 200 MiB/15 menit/maksimum 720p perlu UAT. Belum menjamin semua OS/browser atau perangkat mampu memprosesnya.

Agent boleh menguji aplikasi lokal melalui [Chrome DevTools MCP](docs/BROWSER-TESTING.md) atau lingkungan test terpisah. Migration/seed pada database user tetap dijalankan user, dan user tetap menetapkan kelulusan UAT phase aktif.
