# Pemicu operasi melalui cron-job.org

Phase 2 mengaktifkan satu task: `expire-enrollments`. Aplikasi memproses maksimal `OPERATIONS_BATCH_SIZE` baris dalam satu request, dengan batas `OPERATIONS_MAX_SECONDS`. Tidak ada Laravel Scheduler atau queue worker server.

## Konfigurasi aplikasi

1. Buat token acak di terminal Anda: `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Simpan hasilnya hanya di `.env` sebagai `OPERATIONS_CRON_TOKEN`; jangan kirim token ke chat, commit, log, atau URL.
2. Set `OPERATIONS_BATCH_SIZE=20` dan `OPERATIONS_MAX_SECONDS=10`. `CACHE_STORE=database` dan `QUEUE_CONNECTION=sync` tetap dipakai. Jalankan migration Phase 2 dan `php artisan optimize:clear` sesuai [PHASE-2.md](phases/PHASE-2.md). Konfigurasikan SMTP untuk fitur reset password; mailer `log` ditolak agar token reset tidak masuk log.
3. Pastikan domain aplikasi memakai HTTPS. Endpoint akan mengembalikan `503` bila token kosong atau kurang dari 32 karakter.

## Konfigurasi di cron-job.org

- URL: `https://DOMAIN-ANDA/operations/jobs/expire-enrollments`
- Method: `POST`
- Header: `Authorization: Bearer <token dari .env>`
- Jadwal: setiap jam; zona cron-job.org boleh `Asia/Jakarta` karena intervalnya per jam. Aplikasi membandingkan timestamp dalam UTC.
- Body: kosong
- Aktifkan notifikasi kegagalan di dashboard cron-job.org. Jangan letakkan token di URL.

Respons JSON `completed` berarti saat itu tidak ada akses kedaluwarsa yang tersisa; `partial` berarti pemicu berikutnya perlu melanjutkan batch; `busy` berarti request lain masih memegang lock. Ketiganya memakai HTTP 200 dengan `processed` dan `remaining`. Token salah menghasilkan 401, task tidak dikenal 404, throttle 429, dan konfigurasi/dependensi gagal 503. GET tidak menjalankan task.

Pantau respons, `operation_states.last_succeeded_at`, `operation_states.last_error`, serta `system_logs`. Checkpoint tersimpan setelah tiap enrollment berhasil. Bila backlog bertambah, periksa kapasitas hosting dan frekuensi jadwal; jangan menjalankan `schedule:run`, `queue:work`, atau membuat cron cPanel untuk aplikasi ini.

Pencabutan sesi pemutaran setelah expiry disambungkan pada Phase 4 saat tabel playback tersedia. Semua pemeriksaan akses harus memakai `EnrollmentService::activeFor()`, yang menolak `expires_at <= now()` walaupun batch cron belum berjalan.
