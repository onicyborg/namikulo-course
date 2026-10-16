# Phase 6: Area member dan progres belajar

Status: **Direncanakan, belum diimplementasikan atau lulus UAT**.

Acuan: PRD F-105, F-601, F-604, F-507; SRS 5.8; design 19.8. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

Phase 5 lulus dan user menginstruksikan lanjut. Gunakan order/enrollment/video nyata hasil UAT sebelumnya.

## Scope dan urutan implementasi

1. Buat lesson_progress sesuai SRS, unique user_id+lesson_id, endpoint progress dengan akses/ownership, validasi posisi/durasi dan konsistensi parent course/lesson.
2. Simpan posisi terakhir setiap 15 detik dan saat pause/selesai; tandai selesai tanpa menganggap seek sebagai waktu menonton. Default konservatif: event ended yang tervalidasi akses/posisi menandai selesai; jangan membuat ambang 80/90% tanpa keputusan produk. Ini progres belajar, bukan bukti menonton yang tidak dapat dimanipulasi.
3. Resume playback, indikator belum/sedang/selesai, persentase dari lesson selesai dibagi lesson tersedia, aman jika total nol. Preview admin tidak menyimpan progres.
4. Dashboard member, course aktif/kedaluwarsa, sisa akses dan badge <14/<3 hari, pesanan pending, rekomendasi course terbit bila tersedia. Course saya/detail/kurikulum dan tombol belajar/beli lagi sesuai server state.
5. Riwayat pesanan dengan instruksi bayar pending. Profil nama/telepon/avatar/password dengan verifikasi password lama, validasi upload dan ownership.
6. Integrasikan state arsip/expired/revoked, loading/empty/error, sidebar dan navigasi. Pencarian/agregasi eager loading; tidak membocorkan course/order user lain.

## Artefak yang direncanakan dan batas scope

Migration lesson_progress; model/service progres, member controllers/views, route progres/profil, integrasi player.js. Tidak ada sertifikat/kuis/fitur di luar PRD.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

Tidak ada variabel wajib baru. MAIL, disk public avatar dan driver existing tetap digunakan.

## Perintah untuk user

Perintah berikut **baru dijalankan sesudah implementasi tersedia**, dengan urutan final diperiksa kembali pada serah-terima. Agent tidak menjalankan migrate/seed atau pengujian.

1. `php artisan migrate`
2. `php artisan storage:link` jika belum tersedia.
3. `php artisan optimize:clear`
4. Pastikan konfigurasi sinkron dan batas request tetap sesuai; tidak ada restart worker.
5. User melakukan UAT dengan setidaknya dua member dan admin.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Tonton, pause, tutup lalu buka lagi; selesaikan video | Posisi tersimpan/resume benar; indikator dan persentase diperbarui tanpa duplikat. |
| 2. Kirim progress untuk lesson/course lain, posisi negatif/melebihi durasi dan admin preview | Input tidak valid/scope salah ditolak; preview admin tidak mengubah progres. |
| 3. Buka course aktif/expired/revoked/arsip dan course tanpa lesson | Status/tombol benar; akses tetap dicek server; tidak ada pembagian nol. |
| 4. Buka riwayat dan order milik member lain | Pemilik dapat melanjutkan pending; user lain ditolak. |
| 5. Ubah profil/avatar/password benar dan password lama salah | Perubahan sah tersimpan; input gagal dijelaskan; upload berbahaya ditolak. |
| 6. Jalankan alur daftar → beli → bayar → belajar → resume melalui mobile | Navigasi lengkap tanpa route sementara atau tombol mati. |

## Kriteria kelulusan, asumsi dan keterbatasan

Asumsi penanda selesai via ended perlu dicatat pada serah-terima; jika user meminta ambang lain, revisi spesifikasi dahulu. UAT integrasi menutup state/progres yang ditunda Phase 3–5.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, UAT, asumsi/keterbatasan, dan kalimat: **“Sudah diimplementasikan, menunggu pengujian user. Tidak ada test yang dijalankan oleh agent.”** Gunakan kalimat implementasi itu hanya setelah kode benar-benar dibuat; dokumen ini masih rencana.
