# Phase 8: Notifikasi aplikasi dan rekap harian

Status: **Aktif; implementasi menunggu UAT user.** User telah menginstruksikan pengembangan Phase 8. Keputusan email transaksi tercatat di PRD Q11. Phase dinyatakan lulus hanya sesudah hasil UAT disetujui user.

Acuan: [PRD F-509, F-801–F-804 dan BR-15–BR-18](../PRD.md), [SRS 3.1, 5.10–5.11](../SRS.md), [design.md 19.11](../../design.md), [AGENTS.md](../../AGENTS.md).

## Tujuan dan dependensi

Member mengetahui status pesanan/akses dari aplikasi tanpa bergantung pada email, dan menerima satu rekap harian mulai pukul 07.00 WIB bila ada item belum dibaca. Admin memiliki pusat notifikasi sendiri. Status pembayaran dan hak akses tetap ditentukan oleh alur lama yang tervalidasi, bukan oleh notifikasi.

Prasyarat: Phase 7 dinyatakan lulus oleh user dan user menginstruksikan mulai Phase 8; database test terpisah dan staging MySQL tersedia; SMTP staging dan cron-job.org test dapat digunakan untuk UAT. Pastikan migration pembayaran/enrollment yang sudah berjalan tidak diedit. Diskusi materi Phase 9 belum dikerjakan.

## Urutan implementasi

1. Tambahkan migration portabel `user_notifications` dan `notification_digests`, enum/model dengan UUID v7, relasi, indeks, dan `down()` benar. Jangan mengubah migration lama atau memakai tabel notifikasi framework tanpa menyesuaikan aturan AGENTS.md.
2. Buat `NotificationService` dengan unique `(user_id,dedupe_key)` dan tautan internal yang ditentukan server. Integrasikan ke transaksi order/payment/enrollment yang sudah ada: tagihan berhasil, pembayaran terverifikasi, expiry pesanan, akses manual diberikan/dicabut. Admin aktif menerima pembelian lunas; pending hanya perlu dikirim ke member yang harus membayar. Pembuatan ulang webhook, sync, atau perubahan metode tidak menggandakan item. Jangan menambah notifikasi pembelian dan akses yang artinya sama.
3. Buat daftar notifikasi berhalaman untuk admin/member, hitungan belum dibaca di navbar, tautan ringkas dari player, aksi tandai satu/semua dibaca dengan POST+CSRF dan ownership server. Sediakan state kosong, terbaca, target tidak tersedia, dan akun nonaktif.
4. Buat task `create-access-expiry-notifications` dalam batch dengan dedupe per enrollment+tanggal akhir UTC. H-7 dihitung dalam zona member tersimpan; bila cron terlambat, buat sekali sebelum akses berakhir. Pengingat tidak memberi akses atau mengubah expiry.
5. Buat template rekap HTML dan teks polos; service `send-notification-digests` memakai batas 07.00 WIB, unique user+tanggal WIB, klaim sebelum satu percobaan SMTP, batch kecil, lock, checkpoint, status gagal/tidak pasti, serta pantauan admin. Hanya item belum dibaca sebelum cutoff yang masuk; item baru sesudah cutoff menunggu hari berikutnya. Email instruksi/konfirmasi pembayaran tetap dikirim segera, sementara notifikasi status pembayaran yang belum dibaca ikut rekap.
6. Perbarui konfigurasi operasi, allow-list endpoint cron, dokumentasi deployment/operasi, dan instruksi cron-job.org. Jangan menambah Laravel Scheduler, worker, queued job, WebSocket, atau polling berkelanjutan.

## Batas scope

Tipe awal: `payment_pending`, `payment_paid`, `payment_expired`, `access_granted`, `access_revoked`, `access_expiring`. Belum ada notifikasi diskusi, promosi, push browser, pengaturan preferensi email, atau penghapusan retensi otomatis. Data historis lama tidak dibuatkan notifikasi secara massal. Kegagalan SMTP tidak mengubah hasil pembayaran. Jam 07.00 WIB adalah awal pengiriman; pengiriman seluruh akun dapat berlangsung kemudian sesuai backlog/batas cPanel.

## Perintah untuk user

Setelah backup database dan deployment kode Phase 8, user menjalankan `php artisan migrate` lalu `php artisan optimize:clear` pada lingkungan UAT. Jalankan UAT sebelum mengaktifkan dua task baru pada cron-job.org production. Pada staging, konfigurasikan task `create-access-expiry-notifications` tiap jam dan `send-notification-digests` mulai 07.00 WIB tiap 5 menit sampai akhir hari; periksa timeout SMTP dan hasil batch di **Admin → Tugas & log**. Jangan mengaktifkan endpoint task sebelum skema tersedia.

## Variabel lingkungan terencana

`APP_TIMEZONE=UTC`, `QUEUE_CONNECTION=sync`, `MAIL_*`, dan `OPERATIONS_CRON_TOKEN` tetap. Tambahan `NOTIFICATION_DIGEST_BATCH_SIZE=3` dibaca lewat config operasi; `MAIL_TIMEOUT=5` serta `OPERATIONS_MAX_SECONDS=10` adalah nilai awal yang harus dijaga konsisten dengan batas hosting. Zona rekap `Asia/Jakarta` ditetapkan produk, tidak mengganti timezone aplikasi.

## Panduan Uji Manual (UAT)

| Langkah | Hasil yang diharapkan |
|---|---|
| Buat tagihan, ganti metode, lunasi lewat sandbox, ulangi webhook/sync | Member mendapat satu notifikasi menunggu per order dan satu lunas; admin mendapat satu pembelian lunas. Tidak ada akses dari notifikasi/polling dan tidak ada duplikasi. |
| Buat tagihan dan lunasi lewat sandbox, lalu biarkan notifikasi pembayaran belum dibaca hingga rekap | Email instruksi pembayaran dan konfirmasi lunas tetap datang segera lewat alur lama. Rekap sesudah 07.00 WIB juga memuat status pembayaran yang belum dibaca, dengan batas satu email rekap per akun per tanggal WIB. |
| Kedaluwarsakan pesanan, beri/cabut akses manual, perpanjang tanggal akhir | Item relevan dibuat sekali dengan tautan/status benar; pengingat baru hanya bila tanggal akhir benar-benar berubah. |
| Buka daftar sebagai dua akun dan coba ID notifikasi akun lain | Masing-masing melihat miliknya saja; akses silang ditolak; aksi tandai dibaca memperbarui hitungan. |
| Buat unread sebelum 07.00 WIB dan sesudahnya, jalankan pemicu sebelum/selepas batas hari | Sebelum 07 tidak ada rekap; sesudah 07 hanya item sebelum cutoff yang masuk, maksimal satu email/akun/tanggal WIB. |
| Tandai semua dibaca sebelum batch email, lalu ulangi pemicu hari yang sama | Tidak ada email bila kosong; pemicu ulang tidak mengirim ulang. Item yang masih unread dapat tampil lagi esok hari. |
| Simulasikan SMTP gagal/timeout, cron tertunda dan backlog lebih besar dari satu batch | Notifikasi tetap ada; hasil gagal/tidak pasti terlihat operator; tidak ada retry otomatis kedua pada hari sama; batch berikutnya melanjutkan akun lain. |
| Uji zona member berbeda, akses H-7, responsive 375/768/1024/desktop dan keyboard | H-7 mengikuti zona member, rekap tetap WIB; UI dapat dioperasikan dan tidak mengganggu player. |
| Migrasi dan ulangi alur utama di MySQL staging | Skema/index portabel dan alur pembayaran lama tetap benar. |

## Kriteria kelulusan dan risiko

User menilai bukti UAT, terutama idempotensi pembayaran, batas 07.00 WIB, akses silang, SMTP yang hasilnya tidak pasti, dan beban cPanel. Phase 8 lulus hanya atas feedback eksplisit user. Asumsi awal: skala ratusan akun, pengiriman sinkron kecil per pemicu; bila backlog tidak selesai dalam hari yang sama, frekuensi/batas hosting dievaluasi sebelum memilih VPS. Jangan mengklaim email diterima hanya dari respons SMTP yang tidak pasti.
