# SRS — Platform Course Berbayar

Versi 1.0 · Status: Draf untuk persetujuan user · Tanggal: 16 Oktober 2026

Dokumen ini adalah spesifikasi teknis yang menjadi acuan agent. Kebutuhan produk ada di [PRD.md](PRD.md); aturan kerja agent ada di [../AGENTS.md](../AGENTS.md). Bila ada konflik, urutan prioritas: AGENTS.md > SRS.md > PRD.md > design.md.

Kata "harus" = wajib; "sebaiknya" = dianjurkan; "boleh" = opsional.

---

## 1. Ikhtisar arsitektur

- Aplikasi monolit Laravel 12 dengan Blade. Tanpa SPA, tanpa build frontend.
- Database: PostgreSQL (development), MySQL 8 (production). Migration portabel.
- Queue `database`, scheduler Laravel dijalankan cron setiap menit.
- Penyimpanan:
  - Disk `public` (`storage/app/public`): thumbnail course, avatar.
  - Disk `video_private` (`storage/app/private/videos`): video asli, hasil HLS, kunci. **Tidak pernah** dapat diakses langsung lewat web server.
- Integrasi eksternal: Midtrans Core API (HTTP), SMTP, `ffmpeg`/`ffprobe` (proses lokal).
- Pemisahan lapisan: Controller (tipis) → Service (logika bisnis) → Model. Kode Blade tidak berisi logika bisnis.

```text
Browser ──► Laravel (Blade) ──► DB (PostgreSQL / MySQL)
              │   ├─► Disk video_private (HLS terenkripsi)
              │   ├─► Queue worker ─► ffmpeg
              │   └─► Midtrans Core API  ◄── Webhook Midtrans
              └─► SMTP
```

## 2. Struktur direktori

```text
app/
  Enums/            UserRole, CourseStatus, EnrollmentStatus, EnrollmentSource,
                    OrderStatus, PaymentStatus, VideoStatus
  Http/Controllers/
    Auth/  Admin/  Member/  Site/  Checkout/  Learn/  Stream/  Webhook/
  Http/Middleware/  EnsureRole, SecurityHeaders, EnsureActiveUser
  Models/           User, Category, Course, Section, Lesson, VideoAsset, Order,
                    Payment, PaymentNotification, Enrollment, LessonProgress,
                    PlaybackSession, SystemLog
  Policies/
  Services/
    ActivityLogger.php
    EnrollmentService.php
    Checkout/OrderService.php  Checkout/MidtransClient.php  Checkout/PaymentService.php
    Video/VideoProcessor.php  Video/PlaybackService.php  Video/VideoDelivery.php (interface)
    Video/HlsVideoDelivery.php
  Jobs/             ProcessVideoToHls
  Console/Commands/ ExpireEnrollments, ExpirePendingOrders, ReconcilePayments,
                    PrunePlaybackSessions, CleanupVideoUploads
config/              course.php  midtrans.php  video.php
resources/views/
  layouts/ (admin, member, auth, public, player)
  partials/ components/ errors/
  admin/ member/ site/ checkout/ learn/ auth/
public/css/ (app-custom.css, site.css)  public/js/ (app-custom.js, pages/*)
docs/ (PRD.md, SRS.md, phases/)
```

## 3. Konvensi database

Seluruh aturan di AGENTS.md bagian 2 berlaku. Ringkasan:

- PK UUID v7 (`HasUuids`), FK `foreignUuid`.
- Status/tipe: `string` + PHP Backed Enum; tanpa `enum()` native.
- Uang: `unsignedBigInteger` Rupiah. Waktu: `timestamp` nullable eksplisit.
- Hanya Schema Builder portabel; indeks dengan nama eksplisit bila panjang.
- Soft delete (`softDeletes`) untuk `courses`, `sections`, `lessons`.
- "Satu hal aktif per kombinasi" (mis. satu pesanan tertunda per user+course) ditegakkan di service dengan transaksi dan `lockForUpdate`, **bukan** partial index (tidak portabel).

### 3.1 Skema tabel

Kolom `id` (UUID v7, PK), `created_at`, `updated_at` ada di semua tabel kecuali dinyatakan lain.

**users**

| Kolom | Tipe | Catatan |
|---|---|---|
| name | string(120) | |
| email | string | unique |
| email_verified_at | timestamp null | |
| password | string | hash |
| role | string(20) | `admin`/`member`, default `member`, index |
| phone | string(30) null | |
| avatar_path | string null | |
| is_active | boolean | default true |
| last_login_at | timestamp null | |
| remember_token | string(100) null | |

**categories** — `name` string, `slug` string unique, `sort_order` unsignedInteger default 0.

**courses**

| Kolom | Tipe | Catatan |
|---|---|---|
| category_id | foreignUuid null | nullOnDelete |
| title | string | |
| slug | string | unique |
| summary | string(500) null | |
| description | longText null | HTML dari Summernote, disanitasi |
| thumbnail_path | string null | disk public |
| instructor_name | string null | |
| price | unsignedBigInteger | Rupiah |
| discount_price | unsignedBigInteger null | harus < price |
| access_months | unsignedTinyInteger | default dari `config('course.access_months')` = 6 |
| status | string(20) | `draft`/`published`/`archived`, index |
| is_featured | boolean | default false |
| featured_order | unsignedInteger null | |
| published_at | timestamp null | |
| deleted_at | timestamp null | soft delete |

Index: `(status, is_featured, featured_order)` bernama eksplisit.

**sections** — `course_id` foreignUuid (cascade), `title`, `description` text null, `sort_order` unsignedInteger, soft delete. Index `(course_id, sort_order)`.

**lessons** — `course_id`, `section_id` (foreignUuid, cascade), `title`, `description` text null, `sort_order`, `duration_seconds` unsignedInteger default 0, soft delete. Index `(section_id, sort_order)`.

**video_assets** (1 lesson : 1 video)

| Kolom | Tipe | Catatan |
|---|---|---|
| lesson_id | foreignUuid | unique, cascade |
| status | string(20) | `pending`/`processing`/`ready`/`failed` |
| original_path | string null | relatif disk `video_private` |
| hls_path | string null | direktori HLS relatif |
| encryption_key | text null | kunci 16 byte terenkripsi `Crypt::encryptString` (base64) |
| renditions | json null | mis. `["720p","480p"]` |
| duration_seconds | unsignedInteger null | dari ffprobe |
| size_bytes | unsignedBigInteger null | |
| error_message | text null | ringkas, tanpa path server |
| processed_at | timestamp null | |

**orders**

| Kolom | Tipe | Catatan |
|---|---|---|
| order_number | string(40) | unique, mis. `ORD-20261016-8F3KQ2` |
| user_id | foreignUuid | restrict |
| course_id | foreignUuid | restrict |
| course_title | string | snapshot |
| amount | unsignedBigInteger | harga final dibekukan |
| status | string(20) | `pending`/`paid`/`expired`/`cancelled`/`failed`/`refunded`, index |
| expires_at | timestamp null | batas bayar |
| paid_at | timestamp null | |
| cancelled_at | timestamp null | |

**payments** (satu pesanan dapat memiliki beberapa percobaan pembayaran)

| Kolom | Tipe | Catatan |
|---|---|---|
| order_id | foreignUuid | cascade |
| gateway | string(20) | default `midtrans` |
| midtrans_order_id | string(50) | unique, dikirim ke Midtrans, mis. `{order_number}-1` |
| transaction_id | string null | dari Midtrans, index |
| payment_type | string(30) | `bank_transfer`/`qris`/`gopay`/`shopeepay` |
| channel | string(30) null | `bca`/`bni`/`bri`/`permata`/`cimb`/... |
| status | string(20) | `pending`/`settlement`/`capture`/`deny`/`cancel`/`expire`/`failure`/`refund` |
| fraud_status | string(20) null | |
| gross_amount | unsignedBigInteger | |
| va_number | string null | |
| qr_url | text null | URL gambar QR |
| deeplink_url | text null | |
| expires_at | timestamp null | |
| paid_at | timestamp null | |
| raw_response | json null | respons charge, dibersihkan |

**payment_notifications** — `payment_id` foreignUuid null (nullOnDelete), `midtrans_order_id` string index, `transaction_status` string(30), `signature_valid` boolean, `payload` json, `processed_at` timestamp null, `process_result` string(50) null.

**enrollments**

| Kolom | Tipe | Catatan |
|---|---|---|
| user_id | foreignUuid | restrict |
| course_id | foreignUuid | restrict |
| order_id | foreignUuid null | **unique** (idempotensi: satu order → maksimal satu enrollment); null untuk pemberian manual |
| source | string(20) | `purchase`/`admin` |
| status | string(20) | `active`/`expired`/`revoked` |
| starts_at | timestamp | |
| expires_at | timestamp | index |
| granted_by | foreignUuid null | users, nullOnDelete |
| revoked_at | timestamp null | |
| revoked_by | foreignUuid null | users, nullOnDelete |
| note | string null | alasan perubahan |

Index `(user_id, course_id, status)` bernama eksplisit.

**lesson_progress** — `user_id`, `course_id`, `lesson_id`, `last_position_seconds` unsignedInteger default 0, `watched_seconds` unsignedInteger default 0, `completed_at` timestamp null. Unique `(user_id, lesson_id)`.

**playback_sessions** — `user_id`, `lesson_id`, `enrollment_id` null, `token_hash` string(64) unique, `ip_address` string(45) null, `user_agent` string(255) null, `started_at`, `last_heartbeat_at`, `expires_at`, `revoked_at` null.

**system_logs** — `user_id` foreignUuid null (nullOnDelete), `action` string(40), `table_name` string(80) null, `record_id` string(36) null, `method` string(10) null, `url` string(500) null, `ip_address` string(45) null, `user_agent` string(255) null, `old_values` json null, `new_values` json null, `description` string null. Hanya `created_at`. Index `(table_name, record_id)` dan `action`.

**Tabel framework** (`sessions`, `cache`, `jobs`, `failed_jobs`, `password_reset_tokens`): bawaan Laravel, dengan `sessions.user_id` diubah menjadi `foreignUuid` nullable index.

### 3.2 Relasi utama

`Category 1—N Course`, `Course 1—N Section 1—N Lesson 1—1 VideoAsset`, `User 1—N Order N—1 Course`, `Order 1—N Payment`, `Order 1—0..1 Enrollment`, `User N—N Course` melalui `Enrollment`, `User 1—N LessonProgress`, `User 1—N PlaybackSession`.

## 4. Peta rute

Prefix, nama, dan middleware. Controller mengikuti folder di bagian 2. `{course}` memakai binding `slug` pada rute publik/member dan `id` pada rute admin.

### 4.1 Publik dan auth

| Method | URL | Nama | Middleware |
|---|---|---|---|
| GET | `/` | `site.home` | — |
| GET | `/courses` | `site.courses.index` | — |
| GET | `/courses/{course:slug}` | `site.courses.show` | — |
| GET/POST | `/login`, `/register` | `login`, `register` | guest, throttle |
| POST | `/logout` | `logout` | auth |
| GET/POST | `/forgot-password`, `/reset-password/{token}` | `password.*` | guest, throttle |

### 4.2 Member (`auth`, `role:member`, `active`)

| Method | URL | Nama |
|---|---|---|
| GET | `/my/dashboard` | `member.dashboard` |
| GET | `/my/courses` | `member.courses.index` |
| GET | `/my/courses/{course:slug}` | `member.courses.show` |
| GET | `/my/orders` | `member.orders.index` |
| GET/PUT | `/my/profile` | `member.profile.edit` / `.update` |
| GET | `/checkout/{course:slug}` | `checkout.show` |
| POST | `/checkout/{course:slug}` | `checkout.store` |
| GET | `/orders/{order}/pay` | `orders.pay` |
| GET | `/orders/{order}/status` | `orders.status` (JSON) |
| POST | `/orders/{order}/cancel` | `orders.cancel` |

### 4.3 Belajar dan stream (`auth`, dapat diakses member dan admin, akses diperiksa di service)

| Method | URL | Nama |
|---|---|---|
| GET | `/learn/{course:slug}/{lesson}` | `learn.show` |
| POST | `/learn/lessons/{lesson}/playback` | `learn.playback` |
| POST | `/learn/playback/{session}/heartbeat` | `learn.heartbeat` |
| POST | `/learn/lessons/{lesson}/progress` | `learn.progress` |
| GET | `/stream/{token}/master.m3u8` | `stream.master` |
| GET | `/stream/{token}/{rendition}/index.m3u8` | `stream.playlist` |
| GET | `/stream/{token}/{rendition}/key` | `stream.key` |
| GET | `/stream/{token}/{rendition}/{segment}` | `stream.segment` (segment: `seg_\d{5}\.ts`) |

### 4.4 Webhook

| Method | URL | Nama | Catatan |
|---|---|---|---|
| POST | `/webhooks/midtrans` | `webhooks.midtrans` | Dikecualikan dari CSRF (`validateCsrfTokens(except:)` di `bootstrap/app.php`), throttle longgar, tanpa auth |

### 4.5 Admin (`auth`, `role:admin`; prefix `/admin`, nama `admin.`)

| Area | Rute |
|---|---|
| Dashboard | `GET /admin` → `admin.dashboard` |
| Kategori | resource `categories` `->except(['show','create','edit'])` |
| Course | resource `courses` `->except(['show','create','edit'])`; `POST courses/{course}/featured` toggle unggulan; `POST courses/{course}/publish` dan `/unpublish` |
| Kurikulum | `GET courses/{course}/curriculum` → `admin.courses.curriculum`; `POST/PUT/DELETE courses/{course}/sections[/{section}]`; `POST courses/{course}/sections/reorder`; `POST/PUT/DELETE sections/{section}/lessons[/{lesson}]`; `POST sections/{section}/lessons/reorder` |
| Video | `POST lessons/{lesson}/video/chunk` (unggah per potongan), `POST lessons/{lesson}/video/retry`, `GET lessons/{lesson}/video/status` (JSON), `DELETE lessons/{lesson}/video` |
| User | resource `users` `->except(['show','create','edit'])`; `POST users/{user}/reset-password`; `POST users/{user}/toggle-active` |
| Anggota course | `GET courses/{course}/members` → `admin.courses.members`; `POST courses/{course}/members`; `PUT members/{enrollment}` (ubah tanggal/perpanjang); `POST members/{enrollment}/revoke` |
| Pesanan | `GET orders` → `admin.orders.index`; `GET orders/{order}` → `admin.orders.show`; `POST orders/{order}/sync` |
| Log | `GET logs` → `admin.logs.index` (S) |

## 5. Spesifikasi fungsional

### 5.1 Autentikasi dan otorisasi

- SRS-A1: Registrasi membuat user `role = member`, `is_active = true`. Validasi: nama wajib, email unik, password minimal 8 karakter dan terkonfirmasi.
- SRS-A2: Login memeriksa `is_active`; user nonaktif ditolak dengan pesan umum. Setelah login: admin → `admin.dashboard`, member → `member.dashboard`, atau URL tujuan semula (`intended`) bila sah.
- SRS-A3: Login dibatasi throttle (mis. 5 percobaan/menit per email+IP). Sesi di-regenerate setelah login.
- SRS-A4: Middleware `role:admin|member` didaftarkan sebagai alias di `bootstrap/app.php`. User yang salah role menerima 403.
- SRS-A5: `ActivityLogger` mencatat `login`, `logout`, dan percobaan login gagal (tanpa password).
- SRS-A6: Reset password lewat tautan email (tabel `password_reset_tokens` bawaan).

### 5.2 Manajemen course (Admin)

- SRS-C1: Slug dibuat otomatis dari judul, unik; ubah slug hanya lewat edit eksplisit. Jika slug berubah pada course terbit, tampilkan peringatan.
- SRS-C2: Thumbnail: JPG/PNG/WebP, maksimal 2 MB, rasio disarankan 16:9, disimpan dengan nama acak di disk `public/courses/thumbnails`.
- SRS-C3: `description` HTML dari Summernote **wajib disanitasi** di server (allow-list tag) sebelum disimpan atau saat dirender.
- SRS-C4: Aksi terbit hanya bila: judul, harga, thumbnail, dan minimal satu lesson dengan `video_assets.status = ready`. Jika tidak, tolak dengan daftar alasan.
- SRS-C5: Unggulan: `is_featured` + `featured_order`. Landing menampilkan maksimal 8 course unggulan terbit.
- SRS-C6: Hapus course/bab/video = soft delete. Course yang memiliki enrollment atau order tidak boleh dihapus permanen. Hapus video juga menghapus berkas video di disk privat setelah soft delete dikonfirmasi.
- SRS-C7: Reorder menerima daftar id berurutan, memvalidasi bahwa semua id milik parent yang sama, dan menyimpan dalam satu transaksi.
- SRS-C8: Total durasi dan jumlah video course dihitung dari relasi (`withCount`, `withSum`), tidak disimpan ganda.

### 5.3 User dan anggota course (Admin)

- SRS-U1: Tambah/ubah user lewat modal. Admin tidak boleh menonaktifkan atau menurunkan role dirinya sendiri; harus selalu tersisa minimal satu admin aktif.
- SRS-U2: Reset password oleh admin menghasilkan password sementara yang ditampilkan sekali, atau mengirim tautan reset. Tidak disimpan di log.
- SRS-U3: Tambah anggota manual: pilih user (select2), `starts_at` default sekarang, `expires_at = starts_at + access_months` (dapat diubah), `source = admin`, `granted_by`, `note` opsional. Jika user sudah memiliki enrollment aktif di course tersebut, tolak dan sarankan perpanjang.
- SRS-U4: Perpanjang/ubah tanggal berakhir wajib mencatat `note` dan masuk `system_logs`. Cabut = `status = revoked`, `revoked_at`, `revoked_by`; sesi pemutaran user pada course itu dicabut.
- SRS-U5: Daftar user dan anggota memakai DataTables client-side; bila data > 2.000 baris, beralih ke server-side dan dokumentasikan endpoint-nya.

### 5.4 Situs publik

- SRS-S1: Landing mengambil course unggulan terbit dengan eager loading kategori dan `withCount('lessons')`, `withSum('lessons','duration_seconds')`. Angka statistik (peserta aktif, course, video) dihitung dengan query efisien dan boleh di-cache 10 menit.
- SRS-S2: Katalog: query string `q`, `category`, `sort` (`newest`/`price_asc`/`price_desc`), pagination 12 per halaman. Pencarian memakai `LOWER(col) LIKE`.
- SRS-S3: Detail course menampilkan kurikulum tanpa membocorkan data video (hanya judul dan durasi). Tombol aksi ditentukan oleh `EnrollmentService::stateFor(user, course)` dengan kemungkinan: `guest`, `can_buy`, `pending_order`, `active`, `expired`.
- SRS-S4: Course non-terbit mengembalikan 404 untuk publik; admin boleh melihat dengan banner "Pratinjau".
- SRS-S5: Setiap halaman memiliki `<title>`, meta description, dan Open Graph dasar.

### 5.5 Akses dan enrollment

- SRS-E1: `EnrollmentService::activeFor(User $user, Course $course): ?Enrollment` mengembalikan enrollment dengan `status = active` **dan** `expires_at > now()`. Seluruh pemeriksaan akses (halaman belajar, playback, key, segmen, heartbeat) memakai method ini; kolom `status` saja tidak dipercaya.
- SRS-E2: Pembuatan enrollment dari pembelian: `starts_at = payment.paid_at`, `expires_at = starts_at->copy()->addMonthsNoOverflow($course->access_months)`.
- SRS-E3: Command `enrollments:expire` (terjadwal tiap jam) mengubah `active` → `expired` bila `expires_at <= now()` dan mencabut sesi pemutarannya.
- SRS-E4: Pembelian dilarang bila `activeFor` ada (BR-04). Setelah kedaluwarsa, enrollment baru dibuat (baris baru), baris lama tetap sebagai riwayat.
- SRS-E5: Admin (`role = admin`) dianggap memiliki akses pratinjau ke semua course tanpa enrollment, dan sesi pratinjau tidak mengubah progres.

### 5.6 Pembelian dan pembayaran

Aturan umum: seluruh logika di `OrderService` dan `PaymentService`; `MidtransClient` hanya membungkus HTTP. Kunci dan URL dari `config/midtrans.php` (sandbox/production lewat `MIDTRANS_IS_PRODUCTION`). **Agent harus membaca dokumentasi Core API Midtrans yang berlaku untuk nama field dan tidak boleh mengarang field.** Agent tidak boleh memanggil API Midtrans untuk mencoba; user yang menguji di sandbox.

**Membuat pesanan dan pembayaran (`checkout.store`)**

1. Validasi: user login, course terbit, `activeFor` kosong, metode ada di `config('midtrans.enabled_channels')`.
2. Dalam transaksi dengan `lockForUpdate` pada pesanan tertunda milik user+course:
   - Jika ada order `pending` dengan payment `pending` yang belum kedaluwarsa dan metode sama → arahkan ke instruksi bayar yang ada.
   - Jika metode berbeda → batalkan payment lama (Midtrans Cancel API, lalu tandai lokal `cancel`) dan buat payment baru dengan `midtrans_order_id` baru (`{order_number}-{n}`).
   - Jika belum ada order → buat `orders` (`amount` = harga efektif course saat itu, `expires_at = now + pending_order_ttl_hours`).
3. Panggil `POST /v2/charge` sesuai metode (Virtual Account bank, QRIS, GoPay, ShopeePay), dengan `transaction_details.order_id` = `midtrans_order_id`, `gross_amount` integer Rupiah, `customer_details`, `item_details`, dan masa berlaku (`custom_expiry`) sesuai sisa waktu order.
4. Simpan respons yang diperlukan ke `payments` (`va_number`, `qr_url`, `deeplink_url`, `expires_at`, `transaction_id`, `raw_response` yang sudah dibersihkan dari data sensitif).
5. Redirect ke `orders.pay`. Jika Midtrans gagal, simpan error ringkas ke log (tanpa kunci), tampilkan pesan ramah, dan jangan meninggalkan payment menggantung `pending` tanpa transaction_id.

**Halaman instruksi bayar dan status**

- `orders.pay` hanya untuk pemilik order. Menampilkan UI sesuai `design.md` 19.6.
- `orders.status` mengembalikan `{ status, payment_status, expires_at, paid_at, redirect }` untuk polling UI. Endpoint ini hanya **membaca** database, tidak memberi akses.
- Throttle polling (mis. 30 request/menit/user).

**Webhook (`webhooks.midtrans`)**

1. Simpan payload mentah ke `payment_notifications` (dengan `signature_valid`).
2. Verifikasi signature: `hash('sha512', order_id . status_code . gross_amount . server_key)` dibandingkan dengan `signature_key` memakai `hash_equals`. Gunakan `gross_amount` **persis seperti string yang diterima** (mis. `"250000.00"`). Jika tidak valid → respons 403 dan hentikan.
3. Temukan `payments` berdasarkan `midtrans_order_id`. Jika tidak ada → respons 404.
4. Konfirmasi ke Midtrans dengan **Get Status API** dan gunakan hasilnya sebagai kebenaran. Cocokkan `gross_amount` dengan `orders.amount`; jika berbeda, tandai anomali di log dan jangan beri akses.
5. Dalam satu transaksi dengan `lockForUpdate` pada payment dan order:
   - `settlement`, atau `capture` dengan `fraud_status = accept` → payment `settlement`/`capture`, order `paid`, `paid_at` = waktu transaksi dari Midtrans (atau `now()` bila tidak ada), lalu `EnrollmentService::createFromOrder($order)`.
   - `pending` → perbarui tanpa mengubah order.
   - `deny`, `failure` → payment gagal; order `failed` bila tidak ada payment lain yang masih tertunda.
   - `cancel` → payment `cancel`.
   - `expire` → payment `expire`; order `expired` bila order juga lewat batas atau tidak ada payment lain yang tertunda.
   - `refund`/`partial_refund`/`chargeback` → catat, tandai order `refunded`, **jangan** mencabut enrollment otomatis; beri penanda untuk ditinjau admin.
   - Status tidak boleh turun dari `paid` ke status non-lunas oleh notifikasi yang datang terlambat/urutan terbalik.
6. `createFromOrder` idempoten: bila sudah ada enrollment untuk `order_id` tersebut, tidak membuat baru. Unique index pada `enrollments.order_id` adalah pengaman terakhir.
7. Selalu balas HTTP 200 setelah notifikasi sah diproses (termasuk bila sudah pernah diproses) agar Midtrans tidak mengirim ulang tanpa henti.
8. Catat hasil di `payment_notifications.process_result` dan `system_logs`. Kirim email sukses secara queued (S).

**Penjadwalan**

- `orders:expire-pending` tiap 10 menit: untuk order `pending` yang lewat `expires_at`, cek dulu status ke Midtrans (hindari menandai kedaluwarsa pesanan yang sebenarnya sudah dibayar), lalu tandai `expired`.
- `payments:reconcile` tiap 15 menit (S): cek status payment `pending` berumur > 10 menit sebagai jaring pengaman webhook terlambat.
- Admin dapat menekan "Sinkron" pada order (`admin.orders.sync`), memanggil logika yang sama.

**Metode pembayaran MVP:** Virtual Account (BCA, BNI, BRI, Permata, CIMB), QRIS, GoPay, ShopeePay. Daftar yang tampil diambil dari `config('midtrans.enabled_channels')` sehingga mengikuti kanal yang aktif di akun merchant. Kartu kredit tidak termasuk MVP.

### 5.7 Pemutar dan pengiriman video terlindungi

**Unggah**

- SRS-V1: Unggah dari modal memakai Dropzone dengan `chunking`, ukuran potongan 5 MB, ke `admin/lessons/{lesson}/video/chunk`. Server menyimpan potongan di `videos/tmp/{upload_id}`, menyusunnya setelah potongan terakhir, memvalidasi ekstensi (`mp4`, `mov`, `mkv`, `webm`) dan isi lewat `ffprobe` (harus memiliki stream video). Tolak jika ukuran melebihi `config('video.max_size_mb')`.
- SRS-V2: Membuat/menimpa `video_assets` dengan `status = pending`, lalu dispatch job `ProcessVideoToHls` ke queue.
- SRS-V3: Server harus dikonfigurasi (`upload_max_filesize`, `post_max_size`, `client_max_body_size`) minimal sebesar ukuran potongan + overhead. Hal ini dicatat di panduan deployment.

**Pemrosesan (`ProcessVideoToHls`)**

- SRS-V4: Status `processing`. `ffprobe` mengambil durasi, simpan ke `video_assets` dan `lessons.duration_seconds`.
- SRS-V5: Buat kunci acak 16 byte per video, simpan terenkripsi (`Crypt`) di `video_assets.encryption_key`, dan tulis file kunci sementara untuk `-hls_key_info_file` (file dihapus setelah selesai). Untuk setiap rendition (`config('video.renditions')`, default 720p dan 480p), jalankan ffmpeg dengan pola:

```text
ffmpeg -y -i <input> \
  -vf scale=-2:<height> -c:v libx264 -preset veryfast -profile:v main -crf 23 \
  -c:a aac -b:a 128k \
  -hls_time 6 -hls_playlist_type vod \
  -hls_key_info_file <keyinfo> \
  -hls_segment_filename <dir>/<rendition>/seg_%05d.ts \
  <dir>/<rendition>/index.m3u8
```

- SRS-V6: Jalankan ffmpeg lewat `Symfony\Component\Process\Process` dengan argumen array (bukan string shell), timeout besar, dan tanpa memasukkan nama file dari user ke perintah. Nama file selalu dari ID/UUID internal.
- SRS-V7: Berhasil → `status = ready`, `processed_at`, simpan daftar rendition, hapus file temporer; file asli boleh dipertahankan di disk privat (`config('video.keep_original')`). Gagal → `status = failed`, `error_message` ringkas (tanpa path), log detail di log aplikasi, job dapat dicoba ulang dari admin.
- SRS-V8: Struktur penyimpanan: `videos/{lesson_id}/original.<ext>`, `videos/{lesson_id}/hls/{rendition}/seg_*.ts`, `index.m3u8`. Master playlist dibuat saat penyajian, bukan disimpan.

**Memulai pemutaran**

- SRS-V9: `learn.show` memeriksa akses (`activeFor` atau admin). Tidak ada URL video di HTML.
- SRS-V10: `learn.playback` (POST, JSON): periksa akses dan `video_assets.status = ready`; buat `playback_sessions` dengan token acak 40 karakter (hanya `sha256`-nya yang disimpan), `expires_at = now + video.session_ttl_minutes` (default 10); **cabut sesi aktif lain milik user yang sama** (satu sesi aktif); kembalikan `{ manifest_url, heartbeat_url, ttl_seconds, watermark }`.
- SRS-V11: `learn.heartbeat` (POST) tiap `video.heartbeat_seconds` (default 60): periksa sesi belum dicabut, enrollment masih aktif, lalu perpanjang `expires_at` (batas mutlak 12 jam). Gagal → 403 dan pemutar berhenti dengan pesan sesuai alasan.

**Penyajian (`stream.*`)**

Semua rute stream: middleware `auth`, throttle, dan `SecurityHeaders`. Token di URL harus cocok dengan sesi milik user yang sedang login, belum dicabut, belum kedaluwarsa, dan enrollment masih aktif (atau admin). Jika header `Sec-Fetch-Site` ada dan bukan `same-origin`, tolak.

- SRS-V12: `stream.master` membangun master playlist dari rendition yang ada, dengan URI relatif ke `stream.playlist`.
- SRS-V13: `stream.playlist` membaca `index.m3u8` rendition dan **menulis ulang** baris `#EXT-X-KEY` agar `URI` menunjuk ke `stream.key` yang bertoken, dan semua baris segmen menjadi URL `stream.segment` bertoken.
- SRS-V14: `stream.key` mengembalikan 16 byte kunci (`Content-Type: application/octet-stream`) dengan `Cache-Control: no-store, private`.
- SRS-V15: `stream.segment` memvalidasi nama berkas dengan regex (`seg_\d{5}\.ts`) untuk mencegah path traversal, lalu mengalirkan berkas dari disk privat (`Storage::readStream` atau `X-Accel-Redirect` ke location internal bila `video.accel_redirect` aktif). Header: `Content-Type: video/mp2t`, `Cache-Control: no-store, private`.
- SRS-V16: Playlist dan kunci tidak boleh di-cache. Tidak ada rute yang mengembalikan berkas asli.

**Sisi klien (`pages/player.js`, vanilla JS + hls.js)**

- SRS-V17: Gunakan `hls.js` bila `Hls.isSupported()`; jika tidak dan `canPlayType('application/vnd.apple.mpegurl')` benar (Safari/iOS), gunakan HLS native.
- SRS-V18: `<video>` tanpa atribut `controls`; kontrol custom. Atribut `controlsList="nodownload noremoteplayback"`, `disablePictureInPicture`, `playsinline`. Blokir `contextmenu` pada area player.
- SRS-V19: Watermark: elemen overlay di atas video dengan teks `nama · email` (email disamarkan sebagian bila diinginkan), opasitas rendah, `pointer-events: none`, berpindah posisi tiap 20–30 detik, dan dibangun ulang bila dihapus (MutationObserver). Teks diambil dari respons playback, bukan hardcode.
- SRS-V20: `visibilitychange`/`blur` → pause + blur area video. CSS `@media print` menyembunyikan konten. Deteksi tombol `PrintScreen` → sembunyikan sementara (best effort).
- SRS-V21: Kirim heartbeat; hentikan pemutaran saat server menolak. Simpan progres (`learn.progress`) tiap 15 detik dan saat jeda/selesai.
- SRS-V22: Seluruh deterrent adalah penghambat, bukan jaminan (PRD bagian 7). Jangan menulis klaim sebaliknya di UI.

**Abstraksi penyedia**

- SRS-V23: Definisikan interface `VideoDelivery` (mis. `startSession`, `manifestFor`, `assertAccess`) dengan implementasi `HlsVideoDelivery`. Controller dan Blade memakai interface agar kelak dapat diganti penyedia DRM tanpa mengubah model course.

### 5.8 Area member

- SRS-M1: Dashboard menampilkan course aktif (progres, sisa hari akses), course kedaluwarsa (tombol beli lagi), dan pesanan tertunda.
- SRS-M2: `member.courses.show` menampilkan kurikulum dengan status per video (belum/sedang/selesai) dan tautan ke `learn.show`.
- SRS-M3: Progres course = jumlah lesson selesai / total lesson (lesson yang dihapus tidak dihitung).
- SRS-M4: Riwayat pesanan menampilkan nomor, course, nominal, status, tanggal; pesanan `pending` memiliki tombol "Lanjutkan pembayaran".
- SRS-M5: Profil: ubah nama, telepon, avatar, password (verifikasi password lama).

### 5.9 Dashboard admin

KPI (`card-statistic-*`): total user, course terbit, enrollment aktif, pendapatan bulan ini (pesanan `paid`), pesanan tertunda. Tabel pesanan terbaru dan video gagal diproses. Query harus efisien (agregasi, tanpa N+1).

## 6. Kebutuhan non-fungsional

**Keamanan**

- NFR-S1: Seluruh akses data di-scope di server (cegah IDOR): member hanya dapat melihat order/enrollment/progres miliknya.
- NFR-S2: Header keamanan lewat `SecurityHeaders`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal. `Content-Security-Policy` didefinisikan di Phase 4 setelah daftar sumber script (host asset Otika, cdnjs/hls.js, Google Fonts bila dipakai) diketahui, dengan `media-src 'self' blob:`.
- NFR-S3: Rate limiting: login, register, forgot/reset, checkout, polling status, stream, webhook.
- NFR-S4: Kunci Midtrans dan kunci enkripsi video tidak muncul di log, respons, atau Blade. `APP_KEY` dijaga; mengganti `APP_KEY` membuat `encryption_key` video tidak terbaca, jadi dokumentasikan dan sediakan prosedur backup.
- NFR-S5: Upload divalidasi MIME, ekstensi, ukuran, dan diberi nama acak. HTML dari editor disanitasi.
- NFR-S6: Password di-hash bawaan Laravel (bcrypt/argon). Sesi di-regenerate saat login.

**Kinerja**

- NFR-P1: Halaman publik < 2 detik pada koneksi umum; gunakan `loading="lazy"`, ukuran thumbnail wajar, eager loading, dan indeks sesuai bagian 3.
- NFR-P2: Streaming segmen harus mendukung `Range`/`stream` tanpa memuat seluruh file ke memori.
- NFR-P3: Query daftar admin memakai pagination atau DataTables dengan batas data wajar.

**Keandalan dan operasional**

- NFR-R1: Operasi pembayaran dan enrollment atomik (transaksi) dan idempoten.
- NFR-R2: Job queue memiliki `tries` dan `timeout`; job gagal masuk `failed_jobs` dan dapat dicoba ulang dari admin untuk video.
- NFR-R3: Cadangan database dan folder `storage/app/private/videos` harus dijadwalkan oleh user/server (di luar kode); dokumentasikan.
- NFR-R4: Cron `* * * * * php artisan schedule:run` dan worker queue (supervisor atau cron `queue:work --stop-when-empty`) harus aktif di production.

**Kompatibilitas**

- NFR-C1: Browser: dua versi terakhir Chrome, Edge, Firefox, Safari; iOS Safari 15+; Android Chrome. Responsif pada 375, 768, 1024 px, dan desktop.
- NFR-C2: Migration berjalan di PostgreSQL dan MySQL 8 tanpa perubahan.

**Aksesibilitas dan bahasa**

- NFR-A1: Label form, `alt` gambar, fokus keyboard, kontras AA. Teks UI Bahasa Indonesia. Format Rupiah `Rp 1.250.000`, tanggal `16 Okt 2026`.

**Logging dan audit**

- NFR-L1: `system_logs` mencatat aksi admin (CRUD, beri/ubah/cabut akses), login/logout, perubahan status order, dan hasil notifikasi pembayaran. Data sensitif dibersihkan.

## 7. Konfigurasi

`.env` (nama variabel; nilai rahasia tidak boleh di-commit):

```text
APP_NAME=
APP_ENV=local
APP_URL=
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
ASSET_URL=https://otika.namikulo.com/assets

DB_CONNECTION=pgsql          # production: mysql
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER= MAIL_HOST= MAIL_PORT= MAIL_USERNAME= MAIL_PASSWORD= MAIL_FROM_ADDRESS=

COURSE_ACCESS_MONTHS=6
COURSE_PENDING_ORDER_TTL_HOURS=24

MIDTRANS_IS_PRODUCTION=false
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_ENABLED_CHANNELS=bca,bni,bri,permata,cimb,qris,gopay,shopeepay

VIDEO_DISK=video_private
VIDEO_FFMPEG_PATH=ffmpeg
VIDEO_FFPROBE_PATH=ffprobe
VIDEO_MAX_SIZE_MB=2048
VIDEO_SESSION_TTL_MINUTES=10
VIDEO_HEARTBEAT_SECONDS=60
VIDEO_KEEP_ORIGINAL=true
VIDEO_ACCEL_REDIRECT=false

SEED_ADMIN_NAME=
SEED_ADMIN_EMAIL=
SEED_ADMIN_PASSWORD=
```

Konfigurasi aplikasi dibaca lewat `config('course.*')`, `config('midtrans.*')`, `config('video.*')`. Jangan memanggil `env()` di luar file config.

## 8. Catatan deployment (production MySQL)

1. Jalankan migration di MySQL staging sebelum rilis untuk memastikan kompatibilitas.
2. Pastikan `ffmpeg` dan `ffprobe` terpasang, atau pindahkan pemrosesan video ke luar server.
3. Atur `upload_max_filesize`, `post_max_size`, `client_max_body_size`, `max_execution_time` untuk potongan unggahan.
4. Daftarkan URL notifikasi di dashboard Midtrans: `https://<domain>/webhooks/midtrans`.
5. Pastikan folder `storage/app/private` tidak dapat diakses langsung oleh web server.
6. Pasang cron scheduler dan worker queue; pantau `failed_jobs`.
7. Jalankan `php artisan storage:link`, `config:cache`, `route:cache`, `view:cache` (user).

## 9. Keterlacakan phase

| Phase | Cakupan PRD | Bagian SRS utama |
|---|---|---|
| 0 | F-101–F-104 (dasar), BR-12 (log) | 3, 5.1, 6, 7 |
| 1 | F-201–F-209 (metadata; belum video) | 5.2 |
| 2 | F-301–F-307 (F-306/F-307 sebagian), F-105 | 5.3 |
| 3 | F-401–F-404 | 5.4 |
| 4 | F-207, F-602, F-603, F-701–F-706 | 5.7 |
| 5 | F-501–F-508, F-605, BR-02, BR-04–BR-07 | 5.5, 5.6 |
| 6 | F-601, F-604, F-507, F-105 | 5.8 |
| 7 | F-509, dashboard, audit | 5.9, 6, 8 |
