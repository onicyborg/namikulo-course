# Operasi harian dan pemulihan

Panduan ini menjelaskan operasi MVP di cPanel. Lihat [deployment](DEPLOYMENT.md), [pembayaran](payment-operations.md), [video](video-operations.md), dan [kontrak task](SRS.md#510-pemicu-berkala-melalui-cron-joborg). Agent tidak menjalankan task atau transaksi uji.

## Pemicu cron-job.org

Setiap task adalah **POST HTTPS** ke `https://DOMAIN/operations/jobs/{task}` dengan header `Authorization: Bearer <OPERATIONS_CRON_TOKEN>` dan body kosong. Simpan token di header, jangan URL. GET tidak mengeksekusi task. Aktifkan alarm untuk 401/429/503, timeout, dan pemicu yang tidak berjalan. Tidak ada Laravel Scheduler atau worker server.

| Task | Jadwal | Yang diproses |
|---|---|---|
| `expire-enrollments` | Tiap jam | Akses aktif yang melewati `expires_at`, termasuk pencabutan sesi |
| `prune-playback-sessions` | Tiap 15 menit | Sesi video kedaluwarsa/dicabut yang melewati retensi |
| `cleanup-video-uploads` | Tiap 15 menit | Unggahan lama yang tidak lagi dirujuk aset/sesi aktif |
| `expire-pending-orders` | Tiap 10 menit | Pesanan pending lewat batas, setelah status gateway diperiksa bila perlu |
| `reconcile-payments` | Tiap 15 menit | Pembayaran pending/unknown/initiating yang perlu Get Status |

F-509, pengingat akses H-7, **ditunda** sesuai default roadmap dan belum memiliki task aktif. Jangan menjadwalkan `send-access-expiry-reminders` sampai user memilih fitur itu dan implementasi berikutnya lulus UAT.

Respons `completed` berarti kandidat saat itu habis; `partial` berarti pemicu berikutnya perlu melanjutkan; `busy` berarti lock task masih dipakai request lain. Respons 503 menandakan kegagalan/ketergantungan tidak tersedia, bukan keberhasilan. Satu request membatasi batch dan waktu menurut `OPERATIONS_BATCH_SIZE`/`OPERATIONS_MAX_SECONDS`. cron-job.org tidak mengikuti cursor otomatis; interval jadwal harus tetap aktif untuk melanjutkan partial.

## Pemeriksaan rutin

1. Buka **Admin → Tugas & log**. Periksa “Mulai terakhir”, “Selesai terakhir”, kondisi, dan calon tertunda setiap task. Halaman menampilkan snapshot saat dibuka, bukan pembaruan langsung. Calon tertunda adalah hitungan awal, bukan jaminan semua record bisa langsung dibersihkan.
2. Filter log `operation_failed`, `operation`, `orders`, `payments`, dan `enrollments`. Halaman tidak menampilkan snapshot old/new agar data sensitif historis tidak terbuka lewat UI. Cocokkan kegagalan dengan log server yang aksesnya terbatas.
3. Periksa kapasitas `storage/app/video-private`, `storage/app/public`, database, dan `storage/logs`. Simpan backup DB, `.env`/`APP_KEY`, video privat, dan file publik sebagai set yang konsisten. Lindungi backup dari akses web.
4. Periksa webhook Midtrans dan notifikasi cron-job.org. Pembayaran tidak boleh dinyatakan lunas dari redirect/polling browser. Gunakan sinkronisasi admin bila webhook tertunda, lalu periksa satu enrollment per order.
   Snapshot notifikasi baru hanya menyimpan field status yang diperlukan. Untuk snapshot lama, operator menjalankan `php artisan payments:redact-notifications` lalu `--apply` setelah backup; perintah tidak mencetak payload.
5. Jika backlog naik, periksa `last_error`, `next_attempt_at`, durasi request, dan batas hosting. Biarkan retry/backoff berjalan; jangan menjalankan task berulang paralel atau menambah worker diam-diam.

## Gangguan umum

| Kejadian | Langkah operator |
|---|---|
| Cron berhenti/401 | Periksa jadwal, HTTPS, header Bearer dan token yang cocok. Rotasi token dilakukan dengan mengganti `.env`, membangun ulang config cache, lalu memperbarui seluruh job cron-job.org. Token lama tidak dipakai lagi. |
| Task `partial` terus-menerus | Baca backlog dan cursor melalui pemantauan internal; periksa record yang gagal/dirujuk aset aktif. Jangan menghapus data manual tanpa backup dan analisis. Sesuaikan interval atau rencanakan VPS jika kapasitas cPanel tidak cukup. |
| Task 503 | Periksa database/cache lock, SMTP atau gateway terkait, dan `last_error`. Batch menyimpan checkpoint per unit. Setelah penyebab pulih, pemicu berikutnya mencoba lagi sesuai backoff. |
| Midtrans lambat/tidak tersedia | Jangan memberikan akses dari status halaman browser. Biarkan webhook tervalidasi/reconcile/admin sync memeriksa Get Status. `unknown` tetap tertahan; pembatalan/metode baru hanya saat status lama final. |
| Email instruksi/konfirmasi tidak pasti | Periksa kotak masuk dan log pengiriman. Timeout SMTP dapat berarti email sudah diterima; kirim ulang hanya melalui aksi admin yang tersedia setelah pemeriksaan, dengan risiko duplikasi yang diketahui. |
| Tab impor video tertutup | Sumber tetap di komputer admin. Pilih ulang dan proses dari awal setelah sesi impor lama batal/kedaluwarsa; jangan menandai hasil parsial siap atau menghapus video lama sebelum pengganti final. |
| Video tidak dapat diputar | Periksa enrollment aktif, sesi tunggal, video `ready`, MIME HLS/WASM, disk privat, dan kecocokan `APP_KEY` dengan backup DB. Jangan memindahkan HLS ke `public/`. |

## Backup, restore, dan rollback

Backup terjadwal dikelola user/provider hosting, bukan endpoint aplikasi. Simpan versi kode, dump MySQL, `.env`/`APP_KEY`, `storage/app/video-private/`, dan `storage/app/public/` bersama; lindungi backup dan batasi retensinya. Uji restore secara berkala pada lingkungan terisolasi: sambungkan kode dan `APP_KEY` yang sesuai, pulihkan DB/file, lalu user memeriksa login, invoice, course, video, dan pembayaran tanpa menghubungi gateway production. Jangan melakukan restore langsung di production tanpa rencana perubahan. Rollback kode mengikuti [DEPLOYMENT.md](DEPLOYMENT.md#4-rollback-dan-pemulihan).
