# AGENTS.md — Pedoman Pengembangan Platform Course

Pedoman ini wajib diikuti oleh setiap agent AI. Urutan sumber kebenaran:

| Topik | Sumber |
|---|---|
| UI / layout / komponen | [design.md](design.md) |
| Kebutuhan produk & aturan bisnis | [docs/PRD.md](docs/PRD.md) |
| Spesifikasi teknis (skema DB, rute, alur, keamanan) | [docs/SRS.md](docs/SRS.md) |
| Urutan kerja & scope tiap phase | [docs/phases/README.md](docs/phases/README.md) dan `docs/phases/PHASE-N.md` |

Jika dokumen saling bertentangan, urutan prioritasnya: **AGENTS.md > SRS.md > PRD.md > design.md**. Jika ada hal yang tidak tercakup atau ambigu, ambil asumsi paling konservatif, tulis asumsinya di laporan serah-terima, dan jangan memperluas scope.

---

## 0. Aturan kerja agent (prioritas tertinggi)

### 0.1 Agent TIDAK melakukan testing

Seluruh pengujian dilakukan oleh user secara langsung. Agent fokus mengembangkan fitur.

Dilarang:

- Menjalankan `php artisan test`, `phpunit`, `pest`, `dusk`, Playwright, Cypress, atau test runner apa pun.
- Menulis file test (`tests/**`) kecuali user memintanya secara eksplisit.
- Menjalankan aplikasi untuk menguji (`php artisan serve`, `curl` ke endpoint, membuka browser headless, screenshot UI).
- Memanggil API Midtrans sandbox/production dari terminal untuk mencoba alur pembayaran.
- Mengklaim "sudah diuji" di laporan. Gunakan frasa "sudah diimplementasikan, menunggu pengujian user".

Diperbolehkan (bukan testing, hanya perkakas pengembangan):

- `php artisan make:*`, `php artisan route:list`, `php artisan optimize:clear`, `composer dump-autoload`.
- `composer require/install` dan `npm`/`npx` hanya bila benar-benar dibutuhkan phase aktif dan dependensinya diizinkan (lihat bagian 4).
- `git status`, `git diff`, `git diff --check`, `git add`, `git commit` bila user memintanya.
- Membaca file, mencari kode, dan membaca log yang sudah ada.

Sebagai pengganti testing, setiap phase WAJIB menyertakan **Panduan Uji Manual (UAT)** di laporan serah-terima: daftar langkah yang dapat diikuti user beserta hasil yang diharapkan.

### 0.2 Migrate & seed hanya dijalankan user

Agent tidak boleh menjalankan perintah yang mengubah isi database:

- `php artisan migrate`, `migrate:fresh`, `migrate:refresh`, `migrate:rollback`, `migrate:reset`
- `php artisan db:seed`, `db:wipe`
- `php artisan tinker` yang menulis data, SQL manual, atau perintah `psql`/`mysql`.

Agent boleh membuat file migration, seeder, dan factory. Agent **wajib** menuliskan instruksi perintah yang harus dijalankan user, berurutan dan lengkap, pada bagian "Perintah untuk user" di laporan serah-terima. Contoh:

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
php artisan storage:link
```

Seeder tidak boleh berisi kredensial hardcode. Ambil dari `.env` (mis. `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`) dan batasi data demo hanya untuk `APP_ENV=local`.

### 0.3 Pengembangan berbasis phase

- Pekerjaan dibagi menjadi phase (Phase 0, 1, 2, ...) sesuai `docs/phases/README.md`.
- Kerjakan **hanya phase aktif**. Jangan mengerjakan fitur phase berikutnya, bahkan bila "sekalian".
- Sebuah phase dianggap **selesai hanya setelah user memberi feedback hasil test langsung** dan menyatakan lulus. Agent tidak boleh menandai phase selesai atas inisiatif sendiri.
- Jika user melaporkan bug pada phase aktif, perbaiki di phase tersebut sampai user menyatakan lulus.
- Jangan berpindah phase tanpa instruksi eksplisit user, misalnya "Phase 1 lulus, lanjut Phase 2".
- Detail phase berikutnya (`PHASE-N.md`) dibuat setelah phase sebelumnya lulus. Jika file belum ada, jangan mengarang scope; gunakan ringkasan di `docs/phases/README.md` dan minta konfirmasi user.

### 0.4 Skill yang tersedia

Agent memiliki akses ke skill **UIUX Promax** dan **antislop**. Gunakan keduanya secara aktif:

- **UIUX Promax**: dipakai saat merancang dan menyusun tampilan, terutama landing page, katalog, detail course, halaman checkout/pembayaran custom, player, dan area member. Gunakan untuk keputusan hierarki visual, spacing, state, microcopy, dan alur pengguna.
- **antislop**: dipakai sebagai pemeriksaan akhir untuk menghilangkan pola generik buatan AI pada UI, copywriting, dan kode (teks pengisi, kalimat klise, komentar tak berguna, komponen berulang tanpa fungsi, abstraksi berlebihan).
- Batasan: output skill hanya menjadi **arahan desain**. Implementasi tetap harus mematuhi stack di dokumen ini (Blade + Bootstrap 4 + Otika). Skill tidak boleh dijadikan alasan untuk memakai framework/library baru.

### 0.5 Format laporan serah-terima tiap phase

Akhiri setiap sesi kerja dengan laporan ringkas berisi:

1. Ringkasan perubahan dan daftar file yang dibuat/diubah.
2. **Perintah untuk user** (composer, migrate, seed, storage:link, queue, dll.) berurutan.
3. Variabel `.env` baru atau berubah.
4. Panduan Uji Manual (UAT) per fitur dengan hasil yang diharapkan.
5. Asumsi yang diambil dan keterbatasan yang diketahui.
6. Pernyataan bahwa tidak ada test yang dijalankan.

---

## 1. Stack

- Backend: Laravel 12 dan PHP sesuai `composer.json`.
- Frontend: **Blade templating murni**. Lihat bagian 4.
- UI: Otika Admin Template (Bootstrap 4, jQuery, CSS Otika, Feather Icons, Font Awesome, plugin halaman) sesuai `design.md`.
- Database development: **PostgreSQL**. Database production: **MySQL**. Migration harus berjalan di keduanya (bagian 2).
- Queue/cache/session: driver `database`, kecuali SRS menentukan lain.
- Payment gateway: Midtrans Core API dengan UI pembayaran custom (bukan Snap bawaan Midtrans).
- Jangan memakai starter kit (Breeze, Jetstream, Fortify UI, Livewire, Inertia) dan jangan memakai Vite atau bundler frontend.
- Jangan mengasumsikan Metronic atau Bootstrap 5. Periksa implementasi aktual sebelum mengubah layout global.

---

## 2. Database: UUID v7 dan kompatibel PostgreSQL + MySQL

### 2.1 Primary key dan foreign key

- Semua tabel aplikasi wajib memakai **UUID v7** sebagai primary key.
- Pada model gunakan trait `Illuminate\Database\Eloquent\Concerns\HasUuids` (Laravel 12 menghasilkan UUID v7). Jangan memakai `HasVersion4Uuids`.
- Migration: `$table->uuid('id')->primary();`
- Foreign key: `$table->foreignUuid('course_id')->constrained()->cascadeOnDelete();` (atau `restrictOnDelete()` / `nullOnDelete()` sesuai SRS). Jangan memakai `foreignId`, `id()`, `bigIncrements`, atau `increments` untuk tabel aplikasi.
- **Pengecualian tabel internal framework** yang tetap memakai skema bawaan Laravel: `migrations`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks`, `password_reset_tokens`. Tabel `sessions` tetap memakai `id` string bawaan, tetapi kolom `user_id`-nya wajib `foreignUuid('user_id')->nullable()->index()` karena `users.id` adalah UUID.

### 2.2 Aturan kompatibilitas MySQL (production) tanpa mengorbankan PostgreSQL (development)

Migration hanya boleh memakai Schema Builder yang portabel. Wajib:

- Gunakan `json()`, bukan `jsonb()`. Jangan beri default pada kolom `json`/`text`.
- Jangan memakai fitur khusus PostgreSQL: `jsonb`, array, `ilike`, partial/expression index, `DB::statement` dengan sintaks PostgreSQL, sequence, `uuid_generate_*`, extension, `enum` native PostgreSQL.
- Jangan memakai fitur khusus MySQL: `->after()`, `->first()`, `->charset()`/`->collation()` per kolom, `fullText`, `unsigned` mengandalkan perilaku MySQL.
- Status/tipe memakai `string(30)` + PHP Backed Enum di `app/Enums` dan cast di model. Hindari `$table->enum()`.
- Uang dalam Rupiah memakai `unsignedBigInteger` (tanpa desimal).
- Waktu memakai `timestamp()->nullable()` atau `dateTime()->nullable()` secara eksplisit untuk kolom selain `timestamps()`.
- Pencarian teks tanpa membedakan huruf besar/kecil memakai `whereRaw('LOWER(kolom) LIKE ?', [...])`, karena collation default MySQL case-insensitive sedangkan PostgreSQL tidak.
- Beri nama eksplisit pada index/unique/foreign key yang namanya panjang (batas 64 karakter di MySQL), mis. `->unique(['user_id','course_id','status'], 'enr_user_course_status_uq')`.
- `string()` panjang default 255 sudah aman untuk unique index di MySQL 8 (utf8mb4). Jangan menaikkan panjang kolom yang di-index melebihi 255.
- Hindari query mentah. Jika terpaksa, gunakan fungsi SQL yang ada di kedua engine dan jelaskan di komentar.
- Setiap migration wajib memiliki `down()` yang benar.
- Satu migration satu tujuan; jangan mengedit migration yang sudah pernah dijalankan user. Perubahan skema dibuat sebagai migration baru, kecuali user menyatakan database boleh di-reset.
- Periksa migration yang ada sebelum menambah kolom/relasi.

### 2.3 Model

- `$fillable` eksplisit. Jangan memakai `$guarded = []`.
- Gunakan cast enum, `datetime`, `array`/`json` seperlunya.
- Gunakan relasi + eager loading (`with`, `withCount`) untuk mencegah N+1.
- Operasi multi-tabel wajib memakai `DB::transaction`.

---

## 3. Laravel 12

- Route di `routes/web.php` (dan `routes/api.php` bila perlu). Controller di `app/Http/Controllers` dengan subfolder `Admin`, `Member`, `Site`, `Auth`, `Webhook`, `Stream`. Service di `app/Services`, enum di `app/Enums`.
- Middleware dikonfigurasi di `bootstrap/app.php`; jangan membuat/mengubah `app/Http/Kernel.php`.
- Gunakan route model binding, migration, seeder, factory, policy, dan command Artisan standar.
- Jangan memakai `Route::resource` sebagai default. Bila dipakai untuk master data, wajib `->except(['show', 'create', 'edit'])`; form create/edit master selalu berupa **modal** di halaman index.
- Halaman detail/builder yang bukan CRUD master (mis. kurikulum course, anggota course) dibuat sebagai **route kustom bernama jelas** (`admin.courses.curriculum`, `admin.courses.members`), bukan `show` dari resource.
- Validasi default `$request->validate([...])`; Form Request hanya untuk aturan kompleks atau reusable.
- Form normal: redirect + flash message. AJAX: JSON minimal `{ message, errors: { field: [message] } }`.
- Jangan mencatat password, token, secret, server key, dan data sensitif ke log.
- Logika bisnis (pembayaran, enrollment, playback) ditaruh di `app/Services`, bukan di controller atau Blade.
- Zona waktu aplikasi `Asia/Jakarta`, locale `id`.

---

## 4. Frontend: Blade murni

- Hanya **Blade templating** (termasuk Blade component/anonymous component, `@include`, `@extends`, `@push`).
- **Dilarang**: Vue, React, Angular, Svelte, Alpine.js, Livewire, Inertia, htmx, Turbo, dan framework/SPA sejenis. Dilarang pula Vite, Webpack, atau build step frontend.
- JavaScript yang diizinkan: JavaScript vanilla dan jQuery yang sudah ada di bundle Otika, plus plugin Otika yang tersedia.
- Library tambahan hanya jika kebutuhannya nyata dan tercantum di daftar izin berikut:
  - `hls.js` — pemutar HLS pada halaman player (Phase 4).
  - Midtrans JS tokenisasi kartu — hanya jika pembayaran kartu diaktifkan (di luar MVP).
- Library lain (SweetAlert, Toastr, chart baru, dll.) dilarang tanpa persetujuan user.
- Data dari server ke JS dikirim lewat atribut `data-*`, `@json` di tag `<script>`, atau endpoint JSON. Jangan membangun HTML dari input user tanpa escape.

---

## 5. Struktur UI Otika dan layout

Aplikasi memiliki lima layout (detail di `design.md` bagian 19):

| Layout | File | Dipakai untuk |
|---|---|---|
| Admin | `layouts/admin.blade.php` | Seluruh area `/admin/*` (shell Otika penuh) |
| Member | `layouts/member.blade.php` | Dashboard, course saya, pesanan, profil (shell Otika) |
| Auth | `layouts/auth.blade.php` | Login, register, lupa/reset password |
| Public | `layouts/public.blade.php` | Landing, katalog, detail course, checkout |
| Player | `layouts/player.blade.php` | Halaman belajar/video, tanpa gangguan |

Struktur application page (Admin dan Member):

    #app
    └── .main-wrapper.main-wrapper-1
        ├── .navbar-bg
        ├── nav.main-navbar
        ├── .main-sidebar > #sidebar-wrapper
        ├── .main-content
        │   └── section.section > .section-body
        ├── .settingSidebar (opsional)
        └── footer.main-footer

- Gunakan `section-header`, `section-header-breadcrumb`, `section-body`, `row`, `col-12 col-md-* col-lg-*`, `card`, `card-header`, `card-body`, `card-footer`, `card-header-action`. KPI memakai `card-statistic-*`.
- Bootstrap 4: `mr-*`, `ml-*`, `data-toggle`, `data-target`, `data-dismiss`. Dilarang `me-*`, `ms-*`, `data-bs-*`, `btn-close`, `data-kt-*`, wrapper Metronic.
- Pertahankan `data-toggle="sidebar"`, `collapse-btn`, `fullscreen-btn`, `main-sidebar`, `#sidebar-wrapper`, dan `menu-toggle.nav-link.has-dropdown`.
- Menu aktif memakai class `active` berdasarkan `request()->routeIs(...)`.
- Layout Public dan Player **tidak** memakai shell `.main-wrapper`. Keduanya memuat `css/app.min.css` (Bootstrap 4) dan CSS milik aplikasi, serta **tidak** memuat `scripts.js` karena skrip itu mengasumsikan struktur admin.
- Jangan mengubah layout global untuk masalah satu halaman; gunakan override khusus halaman.

---

## 6. Asset dan dependency

Ada dua jenis asset dengan helper berbeda. Jangan tertukar.

**A. Asset Otika (di host eksternal)** — CSS/JS/DataTables/ikon/logo/favicon bawaan template:

- Wajib dipanggil dengan `asset()`; mengikuti `ASSET_URL`, default `https://otika.namikulo.com/assets`.
- Path: `css/app.min.css`, `css/style.css`, `css/components.css`, `css/custom.css`, `js/app.min.js`, `js/scripts.js`, `js/custom.js`, `js/page/*`, `bundles/datatables/*`, `img/*`.
- Jangan mengedit vendor, `app.min.js`, `scripts.js`, atau `custom.js` milik Otika (file itu berada di host eksternal).

**B. Asset milik aplikasi (di folder `public/` proyek ini)** — wajib dipanggil dengan `url('...')` atau `Storage::url()`, **bukan** `asset()`:

- `public/css/app-custom.css` — override Otika untuk admin/member.
- `public/css/site.css` — gaya Public, Auth custom, Player.
- `public/js/app-custom.js` — helper umum (CSRF, error 422, modal hapus).
- `public/js/pages/*.js` — skrip khusus halaman.
- `public/fonts`, `public/img` milik aplikasi.
- Upload (thumbnail course, avatar) memakai `url('storage/...')` setelah `storage:link`.
- **Video dan materi privat tidak boleh berada di `public/` atau `storage/app/public`.** Lihat bagian 11.

Urutan JS: vendor/global (`app.min.js`) → plugin halaman → konfigurasi page → `scripts.js` → `app-custom.js` → skrip halaman. Jangan memuat atau menginisialisasi plugin dua kali.

---

## 7. Blade, form, dan CRUD

- `@extends` layout yang sesuai; gunakan `@push('styles')` / `@push('scripts')`.
- Setiap control memiliki label, `id`, `name`, pesan error, dan mempertahankan `old()`. Setiap gambar memiliki `alt`.
- Sediakan state loading, empty, success, error, dan permission.
- CRUD master memakai modal create/edit dan modal delete. Modal memakai Bootstrap 4 (`data-toggle="modal"`, `data-dismiss="modal"`).
- Jangan membuat `create.blade.php`/`edit.blade.php` untuk master yang memakai modal.
- Selalu `@csrf`; edit memakai `@method('PUT'|'PATCH')`.
- Tombol delete menyimpan `data-id` dan `data-name`; form delete memakai `@method('DELETE')`.
- Plugin Otika yang tersedia: select2, selectric, datepicker, datetimepicker, daterange, timepicker, summernote, dropzone, image-preview. Pastikan plugin berfungsi di dalam modal.
- Komponen berulang dijadikan Blade component di `resources/views/components` (mis. `x-page-header`, `x-empty-state`, `x-course-card`, `x-confirm-delete-modal`).
- Teks UI berbahasa Indonesia, formal-ramah, singkat, tanpa teks pengisi.

---

## 8. JavaScript dan AJAX

- CRUD default boleh submit normal + redirect. AJAX boleh memakai `fetch()` atau jQuery AJAX; sertakan token CSRF dari Blade (`<meta name="csrf-token">`).
- PUT/PATCH/DELETE via `FormData` memakai `_method`.
- Tangani status 422 dengan `is-invalid` dan `invalid-feedback` per field.
- Sediakan loading state, escape konten user sebelum `innerHTML`, dan pastikan skrip aman bila elemen target tidak ada.
- Flash memakai mekanisme alert Otika yang ada. Jangan menambah library notifikasi baru.

---

## 9. Tabel dan DataTables

- Gunakan `table-responsive` dan `table table-striped`.
- Client-side default: `pageLength: 10`, `responsive: true`. Server-side hanya untuk dataset besar, dan endpoint/query harus didokumentasikan.
- Export mengikuti Buttons Otika: copy, csv, excel, pdf, print. Export hanya untuk data admin, tidak untuk konten materi.
- Jangan menginisialisasi DataTables dua kali pada elemen yang sama.

---

## 10. Routing, authorization, dan keamanan

- Nama route: `admin.courses.index`, `member.courses.show`, `site.courses.show`, `checkout.show`, dst. (lihat SRS bagian 6).
- Role: `admin` dan `member`. Proteksi route dengan `auth` + middleware `role:admin` / `role:member`, atau Policy. Alias middleware didaftarkan di `bootstrap/app.php`.
- Gunakan Policy, `can`, atau `$this->authorize(...)`. `@can` di Blade hanya mengatur visibilitas.
- Validasi ownership/scope di server. Cegah IDOR, CSRF, XSS, upload berbahaya, mass assignment, dan kebocoran credential.
- Upload: validasi MIME + ekstensi + ukuran, simpan dengan nama acak, jangan percaya nama file asli.
- Rate limiting pada login, register, forgot password, checkout, webhook, dan endpoint stream.
- Akses course dihitung dari tabel `enrollments` (`status = active` dan `expires_at > now()`), selalu diverifikasi di server, tidak pernah hanya di Blade/JS.
- Admin dapat melihat semua materi untuk keperluan preview tanpa enrollment.
- Secret (Midtrans server key, kunci enkripsi video) hanya di `.env`/DB terenkripsi; tidak pernah dikirim ke browser atau dicatat di log.

---

## 11. Perlindungan konten dan pembayaran (ringkasan; rincian di SRS bagian 8–9)

### Perlindungan video/materi

- Tidak ada URL file video langsung. Video disimpan di disk **privat** dan disajikan sebagai HLS terenkripsi lewat endpoint ber-token yang divalidasi enrollment aktif.
- Pemutar menampilkan watermark dinamis (nama + email/ID user) dan menerapkan deterrent sisi klien (blokir klik kanan, tanpa tombol download, pause saat tab tidak aktif, sembunyikan saat print).
- Batasi satu sesi pemutaran aktif per user.
- Agent harus jujur: pemblokiran screenshot/rekam layar tidak dapat dijamin 100% oleh web biasa. Jangan mengklaim sebaliknya di UI, dokumentasi, atau laporan. Lihat PRD bagian 7.

### Pembayaran Midtrans

- Gunakan Midtrans **Core API** lewat `Http` facade Laravel (tanpa SDK kecuali disetujui user); UI pembayaran dibuat sendiri dengan Blade.
- Sumber kebenaran status bayar adalah **notifikasi webhook yang tervalidasi** (verifikasi `signature_key`) dan pengecekan ulang ke Get Status API. Redirect/polling dari browser tidak boleh dipakai untuk memberi akses.
- Pembuatan enrollment harus idempoten (satu order → paling banyak satu enrollment).
- Satu pembelian = akses 6 bulan sejak `paid_at`. Nilai 6 diambil dari `config('course.access_months')`.

---

## 12. Logging

Tabel `system_logs` dibuat di Phase 0. Catat created, updated, deleted, assigned, unassigned, login, logout, dan kejadian penting pembayaran/enrollment dengan user, tabel, record, method, URL, IP, serta snapshot old/new yang relevan. Bersihkan password, token, secret, dan payload biner. Baca migration aktual sebelum mengasumsikan skema.

---

## 13. Serah-terima phase (pengganti quality gate)

Karena agent tidak melakukan testing, kualitas dijaga dengan:

1. Review mandiri kode terhadap checklist halaman baru (bagian 15) dan SRS.
2. `git diff --check` dan `php artisan route:list` boleh dijalankan sebagai pemeriksaan statis.
3. Pemeriksaan antislop pada UI dan teks.
4. Laporan serah-terima (bagian 0.5) dengan UAT lengkap.
5. Menunggu feedback user. Phase selesai hanya setelah user menyatakan lulus.

User menguji: render view, auth, otorisasi, CSRF, validasi, flash/AJAX, 404 asset, browser console, dan tampilan pada 375px, 768px, 1024px, dan desktop.

---

## 14. Anti-pattern

- Menjalankan test, `migrate`, atau `db:seed`; mengerjakan phase di luar phase aktif.
- Memakai Vue/React/Alpine/Livewire/Inertia/htmx, Vite, atau starter kit.
- Memakai Metronic, Bootstrap 5, `data-bs-*`, `btn-close`, `me-*`, `ms-*`, `data-kt-*`.
- Mengedit vendor Otika, `app.min.js`, `scripts.js`.
- Memuat plugin dua kali; menambah library tanpa kebutuhan nyata dan persetujuan.
- Memakai `asset()` untuk file milik aplikasi, atau `url()` untuk asset Otika.
- Menaruh video/materi di folder publik atau memberi URL file langsung.
- Memberi akses course berdasarkan redirect/polling browser, bukan webhook tervalidasi.
- Auto-increment integer pada tabel aplikasi; sintaks khusus PostgreSQL atau MySQL di migration.
- Kredensial, API key, atau data dummy di repository/view/seeder produksi.
- Mengubah layout global demi satu halaman.
- Teks pengisi, lorem ipsum, atau klaim keamanan yang berlebihan di UI.

---

## 15. Checklist halaman baru

- [ ] Layout yang tepat (Admin/Member/Auth/Public/Player); navbar, sidebar, breadcrumb, menu aktif, footer sesuai.
- [ ] Asset Otika via `asset()`, asset aplikasi via `url()`.
- [ ] Atribut/class Bootstrap 4 saja.
- [ ] Form: label, CSRF, validasi server, error state, `old()`.
- [ ] CRUD master memakai modal; tidak ada create/edit page.
- [ ] Listing memakai markup Otika dan DataTables bila perlu.
- [ ] Authorization route/controller konsisten dengan visibilitas tombol.
- [ ] Query memakai eager loading; transaksi untuk operasi multi-tabel.
- [ ] Migration UUID v7 dan kompatibel MySQL + PostgreSQL.
- [ ] Flash/AJAX response jelas; state loading, empty, success, error, permission tersedia.
- [ ] Responsif dan aksesibel (label, alt, fokus keyboard).
- [ ] Laporan serah-terima + UAT ditulis; tidak ada test dijalankan.
