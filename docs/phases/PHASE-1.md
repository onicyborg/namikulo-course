# Phase 1: Kategori, course dan kurikulum metadata

Status: **Lulus UAT user**. User menyatakan Phase 1 sudah sesuai dan meminta commit, push, lalu lanjut Phase 2.

Acuan: PRD F-201–F-206, F-208–F-209; SRS 3–4, 5.2; design 19.9. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

User menginstruksikan lanjut Phase 1 pada 17 Oktober 2026 setelah menerima tampilan Phase 0, tanpa menyatakan Phase 0 lulus. Video siap belum dapat dibuat hingga Phase 4.

## Scope dan urutan implementasi

1. Buat categories, courses, sections, lessons dan video_assets menurut SRS. video_assets masih skema/relasi status; belum menerima upload. Tambahkan enum CourseStatus/VideoStatus, model, policy, eager loading serta transaksi.
2. Buat CRUD kategori dan course via modal index; slug unik, harga Rupiah, diskon < harga normal, kategori opsional, nama pengajar, thumbnail acak JPG/PNG/WebP <=2 MB, deskripsi HTML disanitasi allow-list di server.
3. Tambahkan unggulan/urutan, publish/unpublish/arsip. Publish wajib menolak jika field wajib atau lesson ready belum ada. Jangan membypass guard karena pipeline belum tersedia.
4. Buat route kustom kurikulum; modal bab/video metadata; urutan naik/turun atau sortable Otika dengan validasi seluruh ID berasal dari parent yang sama. Simpan reorder atomik.
5. Soft delete konten sesuai SRS, log perubahan, validasi scope lesson-section-course. Pisahkan pengarsipan course dan penghapusan lesson; implementasi pembersihan berkas mengikuti Phase 4.
6. Listing Otika/DataTables satu inisialisasi, empty/validasi/flash/loading, menu aktif. Jangan menambah library sanitizer/frontend tanpa persetujuan jika dependency baru diperlukan.

## Artefak implementasi dan batas scope

Migration lima tabel konten; enum/model/policy; Admin Category/Course/Section/Lesson controllers; route master/kustom; Blade modal/index/kurikulum; JS dan CSS halaman; config/course.php. Belum upload, playback, enrollment, atau pembayaran.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

COURSE_ACCESS_MONTHS=6. Disk public untuk thumbnail; ASSET_URL tetap hanya Otika. Tidak ada kredensial baru.

## Perintah untuk user

Jalankan perintah berikut setelah perubahan Phase 1 tersedia di lingkungan Anda. Agent tidak menjalankan migrate/seed atau pengujian. Pastikan extension PHP DOM tersedia untuk sanitasi HTML.

1. `php artisan migrate`
2. `php artisan storage:link` jika belum dibuat.
3. `php artisan optimize:clear`
4. Buat data kategori/course/bab/video metadata melalui UI admin untuk UAT. Tidak ada seeder wajib.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Tambah/edit kategori dan course, slug duplikat, diskon tidak valid | Modal mempertahankan input/error; server menolak data tidak sah. |
| Buka modal tambah, ubah, dan hapus pada kategori, course, serta kurikulum | Modal berada di depan backdrop, isian dan tombol dapat diklik, fokus keyboard masuk ke dialog, dan backdrop hanya menggelapkan halaman belakang. |
| Buka modal tambah/ubah course dan periksa toolbar deskripsi serta Console/Network browser | Ikon Summernote tampil; font WOFF/TTF dimuat dari origin aplikasi tanpa permintaan font Summernote lintas origin atau error CORS. |
| 2. Upload thumbnail valid dan berkas salah format/lebih dari batas | Hanya gambar valid tersimpan acak; URL berasal host aplikasi. |
| 3. Masukkan HTML/script berbahaya di deskripsi | Tag/atribut tidak diizinkan dibuang; konten aman saat dirender. |
| 4. Tambah bab/video dan reorder; kirim ID dari course lain | Urutan tersimpan; scope salah ditolak tanpa perubahan parsial. |
| 5. Publish course tanpa video siap | Ditolak dengan alasan jelas; tidak ada status ready palsu. |
| Pada course draf, periksa tombol status dan coba kirim POST arsip langsung dengan sesi admin | Tombol Arsipkan tidak tampil; server menolak permintaan arsip. Pada course terbit, tombol Arsipkan tampil. |
| 6. Arsip/hapus metadata lalu lihat listing dan log | Soft delete/arsip konsisten; log tersanitasi; member tidak bisa CRUD. |

## Kriteria kelulusan, asumsi dan keterbatasan

UAT positif publish ditunda secara eksplisit ke Phase 4. Phase ini bisa lulus untuk metadata dan penolakan publikasi saja setelah user menyetujuinya; bukan kelulusan penuh F-208.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, UAT, asumsi/keterbatasan, dan kalimat: **“Sudah diimplementasikan, menunggu pengujian user. Tidak ada test yang dijalankan oleh agent.”**
