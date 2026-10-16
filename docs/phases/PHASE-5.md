# Phase 5: Midtrans, checkout dan akses pembelian

Status: **Direncanakan, belum diimplementasikan atau lulus UAT**.

Acuan: PRD F-306–F-307, F-501–F-508, F-605, BR-02/04–07; SRS 5.5–5.6/5.10; design 19.6. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

Phase 4 lulus dan user menginstruksikan lanjut. Akun sandbox dan kanal aktif disediakan user. Agent membaca dokumentasi resmi Core API terbaru tanpa memanggil API untuk mencoba.

## Scope dan urutan implementasi

1. Gunakan orders Phase 2; buat payments/payment_notifications, enum/model, OrderService/MidtransClient/PaymentService dan createFromOrder idempoten. Kunci transaksi harus melindungi kasus belum ada order juga (mis. lock baris user), bukan hanya lock baris pending yang mungkin kosong.
2. Checkout satu course, harga snapshot, larang membeli akses aktif, maksimal satu pending user-course. Metode sesuai config, TTL 24 jam, Midtrans HTTP facade. Tangani timeout charge/cancel dan status tidak pasti tanpa membuat pembayaran ganda.
3. UI custom VA/QRIS/e-wallet, salin, countdown, instruksi, loading klik ganda, cancel/ganti metode. Ikuti SRS: ganti metode membatalkan payment lama dan membuat attempt baru pada order yang sama; setiap attempt memakai ID gateway unik. Jangan menciptakan order baru hanya karena teks design lama menyebut membatalkan pesanan.
4. Webhook signature hash_equals dan Get Status, cocokkan order/amount, lock payment/order, idempoten unique enrollment.order_id, status tidak turun dari paid oleh notifikasi terlambat. paid_at berasal pembayaran terkonfirmasi; akses addMonthsNoOverflow.
5. Polling hanya baca DB milik user; hentikan saat tab tidak aktif/status final. Webhook/verifikasi server yang memberi akses. Sinkron admin dan rekonsiliasi memakai service verifikasi yang sama.
6. Buat admin daftar/detail pesanan, filter/pencarian/sinkron, audit log dan email sukses sinkron setelah commit (S), timeout SMTP, penanda pengiriman/percobaan pada orders dan retry manual admin. Kegagalan SMTP tidak menggagalkan pembayaran; hasil pengiriman saat timeout dapat tidak pasti.
7. Batch service expire-pending-orders tiap 10 menit dan reconcile-payments tiap 15 menit (S). Get Status gagal tidak boleh dianggap expired/paid; retry terbatas, log dan pemulihan. Refund/partial_refund/chargeback untuk review admin, tidak mencabut akses otomatis.
8. Hubungkan CTA publik, pending state, hasil sukses ke player, pembelian ulang setelah expiry. Riwayat member lengkap Phase 6.

## Artefak yang direncanakan dan batas scope

Migration payments/notifications (orders sudah ada); service/controllers checkout/webhook/admin orders; config midtrans; Blade/JS bayar; service batch expiry/reconcile/email; perluasan enrollment; integrasi public/player. Tanpa Snap, SDK, kartu kredit, keranjang atau refund otomatis.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

COURSE_PENDING_ORDER_TTL_HOURS=24; MIDTRANS_IS_PRODUCTION=false, MIDTRANS_MERCHANT_ID, MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, MIDTRANS_ENABLED_CHANNELS sesuai akun. Server key hanya backend; client key tidak otomatis dikirim ke UI bila tidak diperlukan. MAIL_* dan token operasi tetap berlaku.

## Perintah untuk user

Perintah berikut **baru dijalankan sesudah implementasi tersedia**, dengan urutan final diperiksa kembali pada serah-terima. Agent tidak menjalankan migrate/seed atau pengujian.

1. Isi konfigurasi sandbox/kanal/SMTP.
2. `php artisan migrate`
3. `php artisan optimize:clear`
4. Verifikasi batas batch/timeout HTTP dan SMTP; tidak menjalankan queue worker.
5. User mendaftarkan HTTPS /webhooks/midtrans di dashboard merchant.
6. User menambahkan cron-job.org expire-pending-orders tiap 10 menit dan reconcile-payments tiap 15 menit jika S diterapkan.
7. Seluruh transaksi sandbox dilakukan user.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Checkout tanpa login, akses aktif, course draft, dan klik bersamaan | Ditolak sesuai aturan atau diarahkan login; maksimal satu pending order, harga dari server. |
| 2. Bayar tiap kanal aktif sandbox; buka kembali instruksi dan ganti metode | Nominal/VA/QR/deeplink benar; attempt lama dibatalkan aman, tidak ada tagihan ganda. |
| 3. Kirim webhook sah berulang dan urutan terbalik; token/signature/amount salah | Satu enrollment/order; paid tidak turun; request invalid tidak memberi akses. |
| 4. Simulasikan status sukses hanya di browser/polling | Tidak memberi akses; akses hanya setelah verifikasi backend. |
| 5. Biarkan batas bayar lewat, webhook tertunda dan API gagal; gunakan sync/cron | Server konfirmasi status; tidak salah expired; retry/pemulihan tercatat. |
| 6. Pembayaran pada akhir bulan lalu pembelian ulang setelah expiry | Enam bulan kalender tanpa overflow; enrollment lama tetap sebagai riwayat. |
| 7. Buka order milik user lain dan periksa email/log | IDOR ditolak; pengiriman bersamaan dicegah, hasil SMTP tidak pasti ditangani dengan review; tidak ada server key/token di respons/log. |
| 8. Simulasikan refund/chargeback sandbox sesuai dukungan kanal | Penanda review admin, tidak otomatis mencabut enrollment. |

## Kriteria kelulusan, asumsi dan keterbatasan

F-508 dan rekonsiliasi kategori S direncanakan; penundaan harus disetujui dan dicatat. Staging sandbox lulus tidak berarti kredensial production sudah aktif. Ketidakpastian outcome gateway harus memiliki prosedur pemulihan sebelum lulus.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, UAT, asumsi/keterbatasan, dan kalimat: **“Sudah diimplementasikan, menunggu pengujian user. Tidak ada test yang dijalankan oleh agent.”** Gunakan kalimat implementasi itu hanya setelah kode benar-benar dibuat; dokumen ini masih rencana.
