# Operasi pembayaran Phase 5

Status: implementasi siap untuk UAT sandbox oleh user. Agent tidak memanggil API Midtrans dan tidak menjalankan transaksi pengujian.

## Konfigurasi awal

1. Isi `.env`: `APP_URL` HTTPS yang dapat diakses Midtrans, `APP_TIMEZONE=UTC`, `QUEUE_CONNECTION=sync`, koneksi database, `MIDTRANS_IS_PRODUCTION=false`, `MIDTRANS_MERCHANT_ID`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_ENABLED_CHANNELS` sesuai kanal yang benar-benar aktif, serta `COURSE_PENDING_ORDER_TTL_HOURS=24`. `MIDTRANS_CLIENT_KEY` boleh kosong untuk UI Core API ini karena tidak dipakai di browser.
2. Isi SMTP (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `MAIL_TIMEOUT=5`). `MAIL_MAILER=log`/`array` tidak mengirim email sungguhan; aplikasi tidak menandai email sebagai terkirim dengan kedua mailer itu.
3. Jalankan `php artisan migrate`, kemudian `php artisan optimize:clear`. Agent tidak menjalankan kedua perintah tersebut.
   Jalankan `composer install --no-dev --optimize-autoloader` ketika men-deploy perubahan invoice agar dependensi `dompdf/dompdf` tersedia. Isi `INVOICE_SELLER_NAME`, `INVOICE_SELLER_ADDRESS`, `INVOICE_SELLER_EMAIL`, dan `INVOICE_SELLER_TAX_ID` (opsional) sebelum invoice pertama diunduh. Nilai penjual disalin ke snapshot invoice pertama dan tidak berubah oleh perubahan `.env` berikutnya.
4. Pada dashboard merchant **sandbox**, arahkan HTTP Notification URL ke `https://DOMAIN-ANDA/webhooks/midtrans`. URL ini menerima POST tanpa CSRF, tetapi memverifikasi tanda tangan dan mengecek ulang Get Status sebelum mengubah akses. Jangan memasukkan server key di URL.
   Tombol uji notifikasi di dashboard mengirim `order_id` contoh (`payment_notif_test_...`), bukan pembayaran aplikasi. Jika signature dan Merchant ID sesuai, endpoint membalas HTTP 200 dan mencatatnya sebagai `dashboard_test`; tidak ada order atau akses course yang berubah. Lanjutkan dengan transaksi sandbox nyata untuk menguji alur pembayaran.
5. Di cron-job.org, tambah POST HTTPS ke `/operations/jobs/expire-pending-orders` setiap 10 menit dan `/operations/jobs/reconcile-payments` setiap 15 menit. Header keduanya `Authorization: Bearer <OPERATIONS_CRON_TOKEN>`. Token minimal 32 karakter dan berbeda untuk sandbox/production. Lihat juga [SRS 5.10](SRS.md#510-pemicu-berkala-melalui-cron-joborg).

Tidak diperlukan queue worker, Laravel Scheduler, FFmpeg server, atau Midtrans SDK.

Aktivasi Core API untuk production dan ketersediaan tiap kanal mengikuti akun merchant; konfirmasi di dashboard/dukungan Midtrans sebelum mengubah `MIDTRANS_IS_PRODUCTION=true`.

## Perilaku saat gateway lambat

- Setiap percobaan memiliki `midtrans_order_id` tersendiri. Jika charge timeout atau respons tidak lengkap, percobaan masuk status `unknown`. Member melihat pesan menunggu; sistem tidak langsung membuat percobaan baru.
- Rekonsiliasi menanyakan Get Status. Jika gateway menyatakan transaksi belum ditemukan, sistem menunggu setidaknya 10 menit sejak charge dan dua pemeriksaan terpisah sebelum menandai percobaan gagal. Kegagalan koneksi/API tetap ditahan untuk dicoba lagi.
- Pergantian metode membatalkan percobaan pending di gateway sebelum membuat percobaan baru pada order yang sama. Jika Cancel API gagal atau responsnya tidak lengkap, aplikasi memeriksa Get Status. Bila masih pending, aplikasi mencoba Expire API; hasilnya diperiksa terhadap ID pesanan, nominal, dan status gateway. Percobaan baru hanya dibuat bila status lama sudah `cancel`, `expire`, `deny`, atau `failure`; status pending/tidak pasti tetap menahan proses. Jika pembayaran ternyata lunas, member diarahkan memeriksa pesanan. Kegagalan kedua API dicatat pada `system_logs` tanpa respons mentah atau kredensial.
- Pembayaran lunas hanya berasal dari verifikasi server. Polling halaman `/orders/{order}/status` hanya membaca database.
- Refund/partial refund/chargeback ditandai untuk peninjauan di admin; akses yang sudah diberikan tidak dicabut otomatis.
- Email dikirim sinkron setelah transaksi database berhasil. Jika SMTP timeout, hasil pengiriman tidak pasti; admin perlu memeriksa kotak masuk/log sebelum menekan kirim ulang. Tidak ada klaim exactly-once untuk email.
- Setelah tagihan siap, sistem mencoba mengirim email instruksi HTML dan teks polos. Untuk QRIS/GoPay, sistem mencoba menanam PNG QR dari Midtrans ke email; bila unduhan gagal, email memakai URL gambar dan tetap menyediakan tautan halaman bayar. Jika email belum tercatat terkirim, halaman pembayaran menampilkan pemberitahuan dan instruksi tetap dapat dipakai. Admin dapat melihat penanda pengiriman serta jenis kegagalan di system log, lalu mengirim ulang dari detail pesanan jika belum tercatat terkirim. Email instruksi hanya dicoba otomatis satu kali per percobaan pembayaran. Email konfirmasi setelah lunas memakai desain yang sama.

## UAT sandbox oleh user

1. Akun member tanpa akses aktif memilih tiap kanal yang telah diaktifkan merchant. Periksa nomor VA, kode QR, deeplink, nominal, dan batas bayar pada halaman dan email instruksi. QRIS perlu menampilkan gambar QR yang dapat dipindai pada email HTML. Kanal yang belum aktif harus dikeluarkan dari `MIDTRANS_ENABLED_CHANNELS`.
2. Klik checkout berulang atau lewat dua tab. Hasilnya satu order pending untuk member dan course itu, dengan satu percobaan pembayaran aktif.
3. Ganti metode dan batalkan pesanan. Periksa percobaan lama berstatus `cancel` atau `expire` di gateway serta nomor gateway baru berbeda. Jika gateway tidak dapat memastikan status final, aplikasi harus menahan pembuatan percobaan baru.
4. Bayar di sandbox. Setelah webhook terverifikasi, pesanan menjadi lunas, satu enrollment dibuat, tombol belajar tersedia, dan email konfirmasi diterima dengan masa akses enam bulan kalender dalam zona member.
5. Ulangi notifikasi yang sama, kirim notifikasi terlambat, dan coba order/nominal/signature tidak cocok. Tidak boleh ada enrollment ganda, penurunan status lunas, atau akses dari payload palsu.
6. Simulasikan gangguan Get Status dan biarkan batas bayar lewat. Job harus mengembalikan status parsial untuk dicoba lagi; jangan menandai pembayaran lunas/kedaluwarsa hanya dari jam lokal atau polling.
7. Uji akses URL pesanan milik member lain (harus 404), aksi admin, tampilan 375/768/1024 px, fokus keyboard, dan browser console.
8. Dari menu Transaksi, buka ulang tagihan pending dan unduh invoice untuk order lunas. Periksa PDF A4 pada pembaca PDF: nomor invoice, penjual, pembeli, course, nominal, metode, waktu bayar, dan teks panjang tidak terpotong. Ulangi unduhan; nomor dan data snapshot harus tetap. Order belum lunas dan order milik member lain harus 404.

Dokumentasi parameter dan respons yang dipakai: [Charge API](https://docs.midtrans.com/reference/charge-transactions-1), [VA bank](https://docs.midtrans.com/docs/coreapi-core-api-bank-transfer-integration), [QRIS](https://docs.midtrans.com/reference/qris), [GoPay](https://docs.midtrans.com/reference/gopay-1), [ShopeePay](https://docs.midtrans.com/reference/shopeepay-1), [Get Status](https://docs.midtrans.com/reference/get-transaction-status), [Cancel](https://docs.midtrans.com/reference/cancel-transaction), [Expire](https://docs.midtrans.com/reference/expire-transaction), dan [webhook/signature](https://docs.midtrans.com/docs/https-notification-webhooks).
