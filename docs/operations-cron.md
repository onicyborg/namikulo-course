# Setup cron-job.org untuk Namikulo Course

Panduan ini mengisi jadwal operasi dari luar cPanel. Aplikasi menjalankan batch langsung melalui `POST /operations/jobs/{task}`. **Operator membuat job pada akun cron-job.org miliknya** setelah domain HTTPS, migration, konfigurasi, dan UAT task terkait siap. Tidak ada Laravel Scheduler, cron cPanel, atau queue worker.

## 1. Siapkan aplikasi

1. Pastikan `APP_URL=https://DOMAIN-ANDA`, `APP_TIMEZONE=UTC`, `CACHE_STORE=database`, dan `QUEUE_CONNECTION=sync`. Tabel operasi/cache harus sudah dibuat oleh migration. Jangan aktifkan job sebelum tabel yang dipakai task tersedia.
2. Buat token acak **di terminal operator**, bukan di browser atau chat:

   ```bash
   php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
   ```

   Simpan hasilnya di `.env` sebagai `OPERATIONS_CRON_TOKEN`. Gunakan token terpisah untuk staging dan production. Nilai minimal 32 karakter; perintah di atas menghasilkan 64 karakter heksadesimal. Jangan memasukkan token ke URL, repository, screenshot, atau laporan.
3. Atur nilai awal `OPERATIONS_BATCH_SIZE=20`, `OPERATIONS_MAX_SECONDS=10`, `MAIL_TIMEOUT=5`, dan `NOTIFICATION_DIGEST_BATCH_SIZE=3`. Setelah `.env` berubah, jalankan `php artisan config:cache`. Rekap email mengirim batch kecil agar tidak melampaui batas request hosting.

## 2. Buat satu job untuk setiap task

Di [dashboard cron-job.org](https://console.cron-job.org/), buat cronjob baru dengan nama yang menyebut environment dan task, misalnya `production - expire-enrollments`. Isi konfigurasi berikut pada **setiap** job:

| Pengaturan | Nilai |
|---|---|
| URL | `https://DOMAIN-ANDA/operations/jobs/NAMA-TASK` |
| Request method | `POST` |
| Request body | Kosong |
| Custom header name | `Authorization` |
| Custom header value | `Bearer TOKEN_DARI_ENV` (satu spasi setelah `Bearer`) |
| Zona waktu jadwal | `Asia/Jakarta` |
| Execution schedule | Sesuai tabel di bawah, setiap hari |
| Failure notification | Aktifkan untuk kegagalan/timeout dan job yang nonaktif |

Jangan memakai Basic Auth sebagai pengganti header Bearer. `GET` tidak menjalankan task. Jika tersedia pengaturan mengikuti redirect, cek bahwa URL sudah langsung menuju endpoint HTTPS aplikasi; respons redirect login/HTTP bukan hasil task yang benar. cron-job.org mendukung method POST, custom header, dan riwayat respons menurut [FAQ resminya](https://cron-job.org/en/faq/).

| Nama task pada URL | Execution schedule di Asia/Jakarta | Aktivasi |
|---|---|---|
| `expire-enrollments` | Menit `0`, setiap jam | Setelah migration Phase 2 |
| `prune-playback-sessions` | Menit `0,15,30,45`, setiap jam | Setelah migration Phase 4 |
| `cleanup-video-uploads` | Menit `0,15,30,45`, setiap jam | Setelah migration Phase 4 |
| `expire-pending-orders` | Menit `0,10,20,30,40,50`, setiap jam | Setelah migration Phase 5 dan Midtrans siap |
| `reconcile-payments` | Menit `0,15,30,45`, setiap jam | Setelah migration Phase 5 dan Midtrans siap |
| `create-access-expiry-notifications` | Menit `0`, setiap jam | Setelah migration Phase 8 dan UAT lulus |
| `send-notification-digests` | Setiap 5 menit, **jam `07` sampai `23`** | Setelah migration Phase 8, SMTP siap, dan UAT lulus |

Untuk jadwal rekap terakhir, pilih jam 07–23 serta menit `0,5,10,15,20,25,30,35,40,45,50,55` pada setiap hari. Pemicu pertama terjadi sekitar 07.00 WIB; keterlambatan jaringan/layanan dapat menggeser waktu aktual. Aplikasi menolak pengiriman sebelum batas 07.00 WIB. Pengulangan memberi kesempatan batch berikutnya menyelesaikan backlog, bukan mengirim rekap duplikat ke akun yang sama pada hari WIB tersebut.

Staging memakai domain HTTPS dan token staging sendiri. Jangan menunjuk cron-job.org ke `localhost` atau `127.0.0.1`; layanan eksternal tidak dapat mengakses mesin lokal tanpa tunnel HTTPS yang sengaja disediakan untuk UAT.

## 3. Periksa hasil pemicu

Setelah menyimpan job, gunakan fitur **test run** atau tunggu jadwal pertamanya. Test run benar-benar menjalankan task dan dapat mengubah data yang memenuhi syarat, jadi lakukan hanya pada staging atau saat aktivasi production yang telah disetujui. Riwayat eksekusi cron-job.org menampilkan status HTTP dan respons; cocokkan dengan **Admin → Tugas & log** serta `operation_states`.

| Hasil | Makna / tindakan |
|---|---|
| HTTP `200`, `status=completed` | Kandidat yang memenuhi syarat pada batch itu telah habis |
| HTTP `200`, `status=partial` | Masih ada pekerjaan; pemicu berikutnya melanjutkan dari checkpoint |
| HTTP `200`, `status=busy` | Task yang sama sedang memegang lock; amati jadwal berikutnya |
| HTTP `401` | Token/header tidak cocok dengan konfigurasi aplikasi |
| HTTP `404` | Nama task/URL salah atau route belum terdeploy |
| HTTP `429` | Batas frekuensi tercapai; periksa job ganda atau pemicu manual berulang |
| HTTP `503` | Token belum dikonfigurasi atau dependency task gagal; periksa log dan konfigurasi |

`200` saja tidak membuktikan backlog selesai. Pantau `last_succeeded_at`, `last_error`, `remaining`, waktu respons, dan kegagalan SMTP/gateway. cron-job.org tidak membaca cursor dan tidak memanggil batch lanjutan secara otomatis di luar jadwal yang Anda buat. Jaga target request aplikasi sekitar 10 detik; [FAQ cron-job.org](https://cron-job.org/en/faq/) menyebut batas kunjungan 30 detik, sehingga tetap diperlukan margin untuk hosting dan jaringan.

## 4. Rotasi token dan perubahan jadwal

Untuk merotasi token, buat token baru, perbarui `.env` aplikasi, jalankan `php artisan config:cache`, lalu ganti header pada **semua** job di cron-job.org. Periksa hasil tiap job setelah perubahan; token lama tidak lagi berlaku. Jika jadwal dihentikan saat maintenance, aktifkan kembali setelah aplikasi dan skema siap. Tidak perlu menjalankan `schedule:run`, `queue:work`, atau membuat cron cPanel.

Kontrak lengkap dan retensi task: [SRS §5.10](SRS.md#510-pemicu-berkala-melalui-cron-joborg). Pemantauan rutin dan pemulihan: [OPERATIONS.md](OPERATIONS.md).
