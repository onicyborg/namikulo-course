# Checklist kesiapan rilis

Isi bukti, tanggal, dan penanggung jawab setelah UAT user. **Dokumen ini belum menyatakan aplikasi siap rilis.** Saat Phase 7 dimulai, UAT Phase 0 masih belum dinyatakan lulus dan staging MySQL/production belum diverifikasi.

| Gerbang | Bukti/hasil yang perlu dicatat | Status |
|---|---|---|
| UAT Phase 0–7 | Persetujuan user per phase; khusus Phase 0 yang masih terbuka perlu keputusan jelas | Belum lengkap |
| Fitur wajib MVP | Register/login, course, akses, video, checkout, email, invoice, progres, operasi berjalan sesuai PRD/SRS | Menunggu UAT integrasi |
| Staging MySQL 8 | Migration portabel, data lama aman, alur utama ulang pada MySQL | Belum diverifikasi |
| Keamanan akses | Role, ownership order/enrollment/progres, CSRF, upload, XSS, rate limit, stream token/expiry, CSP diperiksa | Menunggu UAT |
| Payload notifikasi lama | Pratinjau dan penerapan `payments:redact-notifications`; tidak ada signature tersimpan pada record lama/baru | Menunggu operator |
| Pembayaran sandbox | Webhook signature + Get Status, idempotensi, ganti metode, cancel, rekonsiliasi, timeout gateway | Menunggu bukti user |
| Email dan invoice | SMTP nyata, QR/VA, konfirmasi, snapshot invoice PDF dan identitas penjual | Menunggu bukti user |
| Video dan browser | ffmpeg.wasm lokal, AES-128, Range stream, playback, satu sesi, perangkat target, batas memori cPanel | Menunggu bukti user |
| Cron-job.org | Lima task aktif, partial/busy/error, alarm, checkpoint, backlog, recovery | Belum diverifikasi |
| Backup/restore | Dump DB, `APP_KEY`, HLS privat, file publik; restore berhasil di lingkungan terisolasi | Belum diverifikasi |
| Konfigurasi production | HTTPS, `APP_DEBUG=false`, MySQL, SMTP, Midtrans production, URL webhook, token cron berbeda | Belum diaktifkan |
| Operasi dan rollback | [Deployment](DEPLOYMENT.md) dan [operasi](OPERATIONS.md) dipahami operator; kompatibilitas skema rollback ditinjau | Menunggu review |
| Fitur opsional | F-509 pengingat H-7 ditunda; tidak ada endpoint/jadwal aktif | Keputusan roadmap saat ini |
| Lisensi aset | Kewajiban distribusi ffmpeg.wasm GPL-2.0-or-later dan aset pihak ketiga ditinjau sebelum rilis | Belum diverifikasi |

## Keputusan rilis

- Tanggal UAT akhir: belum diisi.
- Lingkungan/versi kode: belum diisi.
- Blocker dan penanggung jawab: belum diisi.
- Keputusan user: **belum menyetujui rilis**.

Rilis ditahan jika ada kebutuhan wajib, keamanan akses/pembayaran, pemulihan backup, atau integrasi yang belum lulus. Agent tidak mengubah status menjadi “siap rilis” tanpa persetujuan eksplisit user.
