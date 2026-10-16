# Phase 2: User, anggota course dan pemicu berkala

Status: **Direncanakan, belum diimplementasikan atau lulus UAT**.

Acuan: PRD F-301–F-305, BR-09/BR-12; SRS 3–4, 5.3, 5.5, 5.10. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

Phase 1 lulus dan user menginstruksikan lanjut. Sediakan HTTPS staging/tunnel milik user untuk UAT cron-job.org.

## Scope dan urutan implementasi

1. CRUD user modal, pencarian/filter role/status, aktif/nonaktif, reset via tautan email atau password sementara sekali tampil. Lindungi admin terakhir dan larang menonaktifkan/menurunkan role diri sendiri; perubahan serentak harus aman secara transaksi.
2. Buat skema orders sebagai prasyarat FK nullable enrollment.order_id, lengkap sesuai SRS; belum ada UI order/gateway. Buat enrollments, enum source/status, relasi dan policy.
3. Implementasikan EnrollmentService::activeFor dan pemberian manual, ubah/perpanjang tanggal, cabut akses. Default enam bulan kalender tanpa overflow; tolak duplikat akses aktif. Perubahan tanggal/cabut dicatat dengan pelaku dan alasan. Pembelian otomatis belum tersedia.
4. Buat halaman anggota per course, modal pilih user, tanggal mulai/akhir dan alasan. DataTables client-side dengan batas SRS; bila >2.000 baris implementasikan server-side dan dokumentasikan endpoint.
5. Implementasikan kontrak cron-job.org SRS 5.10: config/operations.php, middleware token, endpoint POST allow-list, CSRF terbatas, throttle, batch langsung/lock/checkpoint/log. Aktifkan hanya task expire-enrollments yang sudah tersedia.
6. Buat service ExpireEnrollments bertahap dan idempoten; operation_states menyimpan checkpoint/cursor sesuai SRS 5.10. Integrasi pencabutan sesi playback ditambahkan Phase 4; jangan query tabel playback yang belum ada. Pemeriksaan expires_at langsung tetap wajib.
7. Dokumentasikan token, batas batch dan setup pemicu. Tidak menggunakan Schedule atau command scheduler.

## Artefak yang direncanakan dan batas scope

Migration orders/enrollments/operation_states; enum/model/policy; admin users/members; EnrollmentService; Operations controller/middleware/config/service; routes dan panduan operasi. F-306/F-307 dipenuhi Phase 5; profil member Phase 6.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

OPERATIONS_CRON_TOKEN baru (rahasia acak minimal 32 byte); COURSE_ACCESS_MONTHS tetap 6. OPERATIONS_BATCH_SIZE=20, OPERATIONS_MAX_SECONDS=10; cache database, QUEUE_CONNECTION=sync. Jangan memasukkan token aktual ke contoh .env.

## Perintah untuk user

Perintah berikut **baru dijalankan sesudah implementasi tersedia**, dengan urutan final diperiksa kembali pada serah-terima. Agent tidak menjalankan migrate/seed atau pengujian.

1. Isi OPERATIONS_CRON_TOKEN dan konfigurasi terkait.
2. `php artisan migrate`
3. `php artisan optimize:clear`
4. Pastikan batas request hosting memadai untuk batch maksimal 10 detik; tidak menjalankan worker.
5. Pada cron-job.org, buat POST /operations/jobs/expire-enrollments tiap jam, Authorization Bearer token, zona waktu Asia/Jakarta. Gunakan domain HTTPS aktual, bukan localhost.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Tambah/edit/nonaktifkan user dan reset password | Data berubah sesuai izin; akun nonaktif ditolak dan password tidak tercatat di log. |
| 2. Coba menonaktifkan diri/menurunkan admin terakhir | Ditolak; minimal satu admin aktif tetap tersedia. |
| 3. Beri akses manual, ulangi pemberian, lalu ubah akhir/cabut | Akses enam bulan tanpa overflow; duplikat aktif ditolak; alasan/pelaku tersimpan. |
| 4. Kirim pemicu sah dua kali; coba token salah/task asing/GET | Hanya task sah dieksekusi dalam batch, overlap terlindungi; request tidak sah tidak menjalankan job. |
| 5. Siapkan data lebih dari satu batch, putus request lalu picu kembali | Respons partial jelas; checkpoint melanjutkan pekerjaan tanpa mutasi ganda atau melewatkan data gagal. |
| 6. Siapkan akses melewati tanggal akhir melalui UI yang diizinkan lalu jalankan expiry | Status berubah expired; pembacaan akses tetap menolak yang kadaluwarsa walau job belum berjalan. |

## Kriteria kelulusan, asumsi dan keterbatasan

User memvalidasi token dan hasil batch, bukan sekadar status HTTP. Pencabutan playback dan akses belajar diuji ulang Phase 4. Jadwal layanan dibuat user; agent tidak mengakses akun eksternal untuk membuat job.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, UAT, asumsi/keterbatasan, dan kalimat: **“Sudah diimplementasikan, menunggu pengujian user. Tidak ada test yang dijalankan oleh agent.”** Gunakan kalimat implementasi itu hanya setelah kode benar-benar dibuat; dokumen ini masih rencana.
