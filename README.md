# Namikulo Course

Platform course video berbayar dengan area admin dan member, pembayaran Midtrans Core API melalui UI custom, serta akses belajar enam bulan. Nama tampilan aplikasi mengikuti `APP_NAME`.

**Status:** perencanaan. Roadmap Phase 0–7 telah disusun; belum ada phase yang dinyatakan lulus. Fitur dalam dokumen merupakan target pengembangan, bukan klaim fitur sudah tersedia.

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
- MVP tanpa worker server: queue sync sebagai konfigurasi, service sinkron; cache/session database.
- Midtrans Core API via Laravel HTTP client; SMTP untuk email.
- Persiapan video di browser admin dengan ffmpeg.wasm, HLS AES-128 satu kualitas, disk privat, token sesi, watermark dan hls.js pada Phase 4.
- cron-job.org memicu endpoint HTTPS bertoken; service memproses batch kecil langsung dengan lock/checkpoint. Tidak menggunakan Laravel Scheduler/crontab aplikasi.

Asset Otika memakai `asset()` dan `ASSET_URL`; asset aplikasi memakai `url()`/`Storage::url()`. Video tidak disimpan di storage publik. Perlindungan web menghambat penyebaran, bukan menjamin pencegahan screenshot atau rekaman layar.

## Memulai pengembangan

1. Baca pedoman dan [Phase 0](docs/phases/PHASE-0.md).
2. User menentukan phase aktif dan menyiapkan database PostgreSQL, SMTP, serta konfigurasi lokal.
3. Implementasikan phase aktif; jalankan hanya perkakas pengembangan yang diizinkan AGENTS.md.
4. Agent menyerahkan perubahan, daftar perintah user dan variabel lingkungan, UAT, asumsi, serta keterbatasan.
5. User menjalankan migration/seeder, menguji, lalu memberi keputusan lulus atau daftar perbaikan.

Repository awal masih membawa skrip setup/dev Laravel yang memanggil migrate dan Vite. Penyesuaiannya termasuk Phase 0; jangan memakai `composer setup`/`composer dev` sebagai petunjuk instalasi proyek sebelum diselaraskan. Tidak ada kebutuhan `npm install` atau build frontend dalam rancangan ini.

Perintah pada dokumen phase adalah rencana serah-terima setelah implementasi tersedia. Jangan menjalankan semuanya sekarang. Seeder, endpoint, job, konfigurasi, dan halaman yang disebut belum tentu sudah dibuat.

## Operasi dan rilis

Kontrak endpoint, daftar task, interval, token, serta batas batch tercantum di [SRS bagian 5.10](docs/SRS.md#510-pemicu-berkala-melalui-cron-joborg). MVP tidak membutuhkan worker server/FFmpeg hosting. Web Worker ffmpeg.wasm hanya berjalan di browser admin. Respons completed/partial dan checkpoint batch harus dipantau.

[Phase 7](docs/phases/PHASE-7.md) menetapkan UAT staging MySQL, konfigurasi production, backup/pemulihan, dan persetujuan rilis. Database, file privat, serta `APP_KEY` harus dicadangkan; secret tidak boleh masuk repository.

Target awal persiapan video adalah browser desktop kompatibel; batas usulan sumber 200 MiB/15 menit/maksimum 720p perlu UAT. Belum menjamin semua OS/browser atau perangkat mampu memprosesnya.

Belum ada perintah instalasi, migration, seed, atau test yang dijalankan dalam penyusunan dokumentasi ini.
