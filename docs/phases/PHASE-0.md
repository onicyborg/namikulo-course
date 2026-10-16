# Phase 0: Fondasi, layout dan autentikasi

Status: **Direncanakan, belum diimplementasikan atau lulus UAT**.

Acuan: PRD F-101–F-104, BR-12; SRS 1–4, 5.1, 6–7; design 19.1–19.2. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

User menginstruksikan mulai Phase 0. Periksa skeleton Laravel, composer scripts, migration dan konfigurasi aktual. Pastikan apakah database awal sudah pernah dimigrasikan; jangan mengasumsikan izin reset.

## Scope dan urutan implementasi

1. Konfigurasikan Laravel 12, Asia/Jakarta, locale id, PostgreSQL lokal dan cache/session database; QUEUE_CONNECTION=sync tanpa worker server. Pisahkan asset Otika dan aplikasi.
2. Selaraskan skeleton: hilangkan kebutuhan Vite/build frontend dan skrip otomatis migrate yang tidak sesuai. Jangan menjalankan skrip setup bawaan. Tidak memasang starter kit.
3. Buat users UUID v7, role enum, is_active, profil dasar; sesuaikan sessions.user_id. Sediakan tabel framework dan system_logs sesuai SRS (system_logs hanya created_at). Jika migration sudah dipakai, buat migration baru serta rencana konversi data yang disetujui user.
4. Implementasikan ActivityLogger dengan sanitasi rekursif data sensitif, model/relasi/fillable, middleware role/active dan header keamanan dasar di bootstrap/app.php.
5. Implementasikan register/login/logout/forgot/reset password, throttle, session regeneration, penolakan akun nonaktif, redirect sesuai role/intended yang sah, serta log login/logout/gagal tanpa password.
6. Buat lima layout dan komponen dasar (header, alert, empty state, konfirmasi hapus), halaman auth, error 403/404/419/429/500/503, serta halaman masuk admin/member minimal. Navigasi hanya ke route tersedia; dashboard KPI belum dibuat.
7. Buat AdminUserSeeder idempoten dengan SEED_ADMIN_* melalui config; tidak menimpa password admin existing tanpa tindakan eksplisit. Data demo hanya local. Dokumentasikan setup lokal tanpa npm.

## Artefak yang direncanakan dan batas scope

Migration users/framework/system_logs; UserRole/User; middleware/policy dasar; ActivityLogger; controller/view auth; lima layout; komponen/error; CSS/JS aplikasi; AdminUserSeeder; .env.example/config terkait. Tidak membuat course, checkout, atau pemicu cron-job.org sebelum Phase 2.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

APP_NAME, APP_URL, APP_TIMEZONE=Asia/Jakarta, APP_LOCALE=id, ASSET_URL; DB_*; SESSION_DRIVER=database, QUEUE_CONNECTION=sync, CACHE_STORE=database; MAIL_*; SEED_ADMIN_NAME/EMAIL/PASSWORD. Rahasia hanya di lingkungan user.

## Perintah untuk user

Perintah berikut **baru dijalankan sesudah implementasi tersedia**, dengan urutan final diperiksa kembali pada serah-terima. Agent tidak menjalankan migrate/seed atau pengujian.

1. Isi .env dan koneksi database/SMTP; jangan menimpa .env existing.
2. `composer install` jika dependency belum tersedia, setelah skrip diaudit.
3. `php artisan key:generate` hanya instalasi baru tanpa APP_KEY; jangan mengganti kunci existing.
4. `php artisan migrate`
5. `php artisan db:seed --class=AdminUserSeeder`
6. `php artisan storage:link`
7. `php artisan optimize:clear`
8. Jalankan aplikasi lokal dengan cara user sendiri untuk UAT. Email dikirim sinkron dengan timeout, tanpa ShouldQueue atau dispatch antrean.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Daftar dengan data valid lalu email duplikat/password tidak cocok | Akun selalu member; input tidak valid ditolak dengan pesan field. |
| 2. Login admin/member, logout, lalu ulangi akses route terlindungi | Redirect sesuai role; session diperbarui; setelah logout akses ditolak. |
| 3. Coba akun nonaktif, login gagal berulang, dan lintas role | Akun nonaktif ditolak, throttle bekerja, role salah 403. |
| 4. Kirim forgot password dan pakai tautan reset sah/tidak sah | SMTP diterima user; password berubah hanya dengan token sah, tidak bocor dalam log. |
| 5. Periksa log dan lima layout/error pada ukuran wajib | Log bersih dari rahasia; aset benar dan navigasi hanya ke route yang tersedia. |

## Kriteria kelulusan, asumsi dan keterbatasan

Lulus setelah UAT auth/otorisasi/layout/log dinyatakan lulus user. Halaman dashboard minimal adalah batas scope, bukan dashboard produk lengkap. Tidak ada aktivasi cron-job.org pada phase ini.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, UAT, asumsi/keterbatasan, dan kalimat: **“Sudah diimplementasikan, menunggu pengujian user. Tidak ada test yang dijalankan oleh agent.”** Gunakan kalimat implementasi itu hanya setelah kode benar-benar dibuat; dokumen ini masih rencana.
