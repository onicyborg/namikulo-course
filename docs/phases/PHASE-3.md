# Phase 3: Landing, katalog dan detail course

Status: **Direncanakan, belum diimplementasikan atau lulus UAT**.

Acuan: PRD F-401–F-404; SRS 5.4; design 19.2–19.5. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

Phase 2 lulus dan user menginstruksikan lanjut. Tinjau identitas visual user; gunakan UIUX Promax bila tersedia dan antislop sesuai AGENTS. Tidak mengarang statistik/testimoni.

## Scope dan urutan implementasi

1. Buat layout Public sesuai token, navbar responsif, hero, statistik DB, unggulan maksimum 8, langkah belajar, info akses enam bulan, CTA/footer dengan tautan nyata.
2. Buat x-course-card reusable: thumbnail 16:9/placeholder jujur, kategori, judul, jumlah bab/video/durasi, harga/diskon dan label akses.
3. Katalog query q/category/sort (newest/price_asc/price_desc), pagination 12. Pencarian LOWER LIKE portabel, eager loading dan agregat; filter dapat dibagikan lewat URL.
4. Detail course: deskripsi tersanitasi, kurikulum judul/durasi tanpa URL/kunci video, harga sticky responsif, state guest/can_buy/pending_order/active/expired dari service sesuai data yang sudah ada.
5. Publik hanya published; admin preview draft dengan banner. UI yang bergantung Phase 4/5 diberi state belum tersedia tanpa link rusak. Jangan membuat payment atau sesi playback pada phase ini.
6. Title/meta description/Open Graph, empty/loading/error/permission, keyboard/kontras dan reduced motion. Urutan populer yang hanya disebut design tidak ditambahkan karena SRS membatasi sort.

## Artefak yang direncanakan dan batas scope

Site controllers, route publik, views/components, site.css dan JS halaman; perluasan stateFor service bila dibutuhkan; metadata SEO. Tidak menambah skema produk atau dependency frontend.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

Tidak ada variabel wajib baru. APP_NAME/APP_URL dan identitas visual harus sesuai lingkungan. Font memakai fallback dan helper asset yang benar.

## Perintah untuk user

Perintah berikut **baru dijalankan sesudah implementasi tersedia**, dengan urutan final diperiksa kembali pada serah-terima. Agent tidak menjalankan migrate/seed atau pengujian.

1. `php artisan optimize:clear`
2. Buka halaman publik dan preview admin untuk UAT. Tidak ada migrate/seeder wajib bila skema Phase 1/2 sudah lengkap.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Buka landing/katalog saat belum ada course terbit | Empty state jelas, statistik dari DB, tidak ada konten buatan. |
| 2. Buka preview course draft sebagai admin dan sebagai pengunjung | Admin melihat banner; publik mendapatkan 404. |
| 3. Gunakan pencarian/filter/sort/pagination dan bagikan URL | Parameter bertahan; hasil sesuai filter, termasuk perbedaan huruf besar/kecil. |
| 4. Periksa HTML/kurikulum, CTA dan tampilan mobile | Tidak ada URL privat; CTA tidak menuju route yang belum dibuat; harga tidak menutupi kontrol. |
| 5. Setelah Phase 4 publish course, ulangi listing/detail/unggulan; Phase 5 ulangi pending order | Course nyata tampil sesuai query; state akses/pembelian terhubung tanpa dummy data. |

## Kriteria kelulusan, asumsi dan keterbatasan

Karena publikasi membutuhkan video siap, UAT konten terbit nyata menjadi gate integrasi Phase 4/5. User boleh meluluskan layout/preview/empty state Phase 3 dengan dependensi ini tercatat.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, UAT, asumsi/keterbatasan, dan kalimat: **“Sudah diimplementasikan, menunggu pengujian user. Tidak ada test yang dijalankan oleh agent.”** Gunakan kalimat implementasi itu hanya setelah kode benar-benar dibuat; dokumen ini masih rencana.
