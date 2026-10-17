# SRS — Platform Course Berbayar

Versi 1.2 · Status: Draf; pemrosesan browser dan operasi tanpa worker · Tanggal: 16 Oktober 2026

Dokumen ini adalah spesifikasi teknis yang menjadi acuan agent. Kebutuhan produk ada di [PRD.md](PRD.md); aturan kerja agent ada di [../AGENTS.md](../AGENTS.md). Bila ada konflik, urutan prioritas: AGENTS.md > SRS.md > PRD.md > design.md.

Kata "harus" = wajib; "sebaiknya" = dianjurkan; "boleh" = opsional.

---

## 1. Ikhtisar arsitektur

- Aplikasi monolit Laravel 12 dengan Blade. Tanpa SPA, tanpa build frontend.
- Database: PostgreSQL (development), MySQL 8 (production). Migration portabel.
- Semua timestamp aplikasi dan koneksi DB memakai UTC. Browser membaca zona IANA perangkat untuk menampilkan jam lokal dan mengirim input waktu sebagai momen UTC; server tetap memvalidasi zona serta kecocokan jam lokal. `users.timezone` menyimpan zona terakhir yang terdeteksi untuk perhitungan kalender saat browser member tidak sedang terbuka. Jika belum ada, aturan kalender memakai UTC. Perubahan zona setelah akses dibuat tidak menggeser batas UTC yang sudah tersimpan.
- MVP cPanel tanpa queue worker: service sinkron, QUEUE_CONNECTION=sync; cache/session database. cron-job.org memicu batch langsung lewat HTTPS (5.10), tanpa Laravel Scheduler/crontab aplikasi.
- Penyimpanan:
  - Disk `public` (`storage/app/public`): thumbnail course, avatar.
- Disk `video_private` (`storage/app/video-private`): hasil HLS dan unggahan sementara; sumber asli tetap di komputer admin. Kunci persisten hanya DB terenkripsi. **Tidak pernah** dapat diakses langsung lewat web server.
- Integrasi eksternal: Midtrans Core API (HTTP), SMTP, ffmpeg.wasm di browser admin (asset lokal), tanpa FFmpeg/ffprobe di server.
- Pemisahan lapisan: Controller (tipis) → Service (logika bisnis) → Model. Kode Blade tidak berisi logika bisnis.

```text
Browser admin (ffmpeg.wasm) ──► Laravel (Blade) ──► DB (PostgreSQL / MySQL)
              │   ├─► Disk video_private (HLS terenkripsi)
              │   ├─► Batch HTTP cron-job.org (tanpa worker)
              │   └─► Midtrans Core API  ◄── Webhook Midtrans
              └─► SMTP
```

## 2. Struktur direktori

```text
app/
  Enums/            UserRole, CourseStatus, EnrollmentStatus, EnrollmentSource,
                    OrderStatus, PaymentStatus, VideoStatus, UploadStatus
  Http/Controllers/
    Auth/  Admin/  Member/  Site/  Checkout/  Learn/  Stream/  Webhook/  Operations/
  Http/Middleware/  EnsureRole, SecurityHeaders, EnsureActiveUser
  Models/           User, Category, Course, Section, Lesson, VideoAsset, Order,
                    Payment, PaymentNotification, Enrollment, LessonProgress,
                    PlaybackSession, SystemLog, VideoUpload, OperationState
  Policies/
  Services/
    ActivityLogger.php
    EnrollmentService.php
    Checkout/OrderService.php  Checkout/MidtransClient.php  Checkout/PaymentService.php
    Video/VideoImportService.php  Video/PlaybackService.php  Video/VideoDelivery.php (interface)
    Video/HlsVideoDelivery.php
  Services/Operations/ ExpireEnrollments, ExpirePendingOrders, ReconcilePayments,
                       PrunePlaybackSessions, CleanupVideoUploads
                       SendAccessExpiryReminders (opsional F-509)
config/              course.php  midtrans.php  video.php  operations.php
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
| timezone | string(64) null | zona IANA terakhir yang tervalidasi dari browser; null berarti belum diketahui |
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
| original_path | string null | null pada MVP browser; sumber tidak diunggah |
| hls_path | string null | direktori HLS relatif |
| encryption_key | text null | kunci 16 byte terenkripsi `Crypt::encryptString` (base64) |
| renditions | json null | MVP `["source"]`, satu kualitas maksimum 720p |
| duration_seconds | unsignedInteger null | metadata browser, diverifikasi konsistensinya dengan manifest saat impor |
| size_bytes | unsignedBigInteger null | |
| error_message | text null | ringkas, tanpa path server |
| processed_at | timestamp null | |

**video_uploads** (staging impor, dibuat Phase 4)

Kolom UUID v7 `id`, `user_id`/`lesson_id` foreignUuid, `status` string(20) (preparing/uploading/validating/ready/replaced/failed/cancelled/expired), `encryption_key` text (cast encrypted), `iv_hex` string(32), `duration_seconds` unsignedInteger, `manifest` json null, `expected_bytes` unsignedBigInteger null, `received_bytes` unsignedBigInteger default 0, `validation_cursor` unsignedInteger default 0, `storage_path` string, `last_activity_at`/`expires_at` timestamp, `cleaned_at` timestamp null, `error_message` string null, timestamps. Index `(user_id,status,expires_at)` bernama eksplisit. Manifest menyimpan playlist dan daftar segmen beserta ukuran/hash; jangan menyimpan payload biner. FK restrict pada lesson/user selama sesi berlangsung. Record terminal dibersihkan sesuai retensi; jangan menghapus aset aktif melalui cascade sesi. Model fillable eksplisit dan enum UploadStatus. Publikasi atomik pada finalisasi memperbarui video_assets; aset lama tetap valid sampai penggantinya siap.

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
| status | string(20) | `initiating`/`unknown` untuk charge yang belum pasti; lalu `pending`/`settlement`/`capture`/`deny`/`cancel`/`expire`/`failure`/`refund` |
| fraud_status | string(20) null | |
| gross_amount | unsignedBigInteger | |
| va_number | string null | |
| qr_url | text null | URL gambar QR |
| deeplink_url | text null | |
| expires_at | timestamp null | |
| paid_at | timestamp null | |
| raw_response | json null | respons charge, dibersihkan |
| instruction_email_sent_at | timestamp null | email instruksi tercatat terkirim |
| instruction_email_last_attempt_at | timestamp null | percobaan pengiriman terakhir |

**payment_notifications** — `payment_id` foreignUuid null (nullOnDelete), `midtrans_order_id` string index, `transaction_status` string(30), `signature_valid` boolean, `payload` json, `processed_at` timestamp null, `process_result` string(50) null.

**order_invoices** — UUID v7 primary key; `order_id` foreignUuid unique (restrictOnDelete), `invoice_number` string unique, snapshot `order_number`, identitas penjual (nama wajib, alamat/email/NPWP opsional), nama/email pembeli, judul course, metode pembayaran, nominal Rupiah `unsignedBigInteger`, `display_timezone`, dan `issued_at` timestamp UTC. Nomor invoice deterministik `INV-{order_number}`. Tidak menyimpan HTML/PDF; dokumen dibuat dari snapshot saat diminta.

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
| GET | `/my/orders/{order}/invoice` | `member.orders.invoice` |
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
| Video | `GET lessons/{lesson}/video/prepare` → `admin.lessons.video.prepare`; `POST lessons/{lesson}/video/uploads` (inisiasi); `POST video/uploads/{upload}/manifest`; `POST video/uploads/{upload}/chunks/{name}/{index}`; `POST video/uploads/{upload}/finalize`; `GET video/uploads/{upload}/status`; `POST video/uploads/{upload}/cancel`; `DELETE lessons/{lesson}/video` (hapus aset). Prefix admin, nama `admin.video-uploads.*`, policy admin + ownership/lesson scope, CSRF dan throttle. Retry potongan pada sesi yang sama bila valid atau sesi baru setelah gagal; tidak menjalankan encoding server. |
| User | resource `users` `->except(['show','create','edit'])`; `POST users/{user}/reset-password`; `POST users/{user}/toggle-active` |
| Anggota course | `GET courses/{course}/members` → `admin.courses.members`; `POST courses/{course}/members`; `PUT members/{enrollment}` (ubah tanggal/perpanjang); `POST members/{enrollment}/revoke` |
| Pesanan | `GET orders` → `admin.orders.index`; `GET orders/{order}` → `admin.orders.show`; `POST orders/{order}/sync` |
| Log | `GET logs` → `admin.logs.index` (S) |

### 4.6 Pemicu operasi

| Method | URL | Nama | Middleware |
|---|---|---|---|
| POST | `/operations/jobs/{task}` | `operations.jobs.store` | `cron.token`, throttle; tanpa auth sesi, pengecualian CSRF terbatas sesuai 5.10 |

## 5. Spesifikasi fungsional

### 5.1 Autentikasi dan otorisasi

- SRS-A1: Registrasi membuat user `role = member`, `is_active = true`. Validasi: nama wajib, email unik, password minimal 8 karakter dan terkonfirmasi.
- SRS-A2: Login memeriksa `is_active` setelah email dan password terverifikasi. Kredensial yang salah menerima pesan umum; akun nonaktif dengan kredensial yang benar ditolak dengan pesan bahwa akun dinonaktifkan dan arahan menghubungi admin. Setelah login: admin → `admin.dashboard`, member → `member.dashboard`, atau URL tujuan semula (`intended`) bila sah.
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
- SRS-C9: Aksi arsip hanya tersedia untuk course berstatus `published`; server menolak permintaan arsip untuk course `draft` atau `archived`.

### 5.3 User dan anggota course (Admin)

- SRS-U1: Tambah/ubah user lewat modal. Admin tidak boleh menonaktifkan atau menurunkan role dirinya sendiri; harus selalu tersisa minimal satu admin aktif.
- SRS-U2: Reset password oleh admin menghasilkan password sementara yang ditampilkan sekali, atau mengirim tautan reset. Tidak disimpan di log.
- SRS-U3: Tambah anggota manual: pilih user (select2), `starts_at` default sekarang, `expires_at = starts_at + access_months` (dapat diubah), `source = admin`, `granted_by`, `note` opsional. Input admin mengikuti zona IANA browser dan dikonversi ke UTC sebelum disimpan. Durasi kalender enam bulan dihitung di zona member terakhir yang tersimpan, lalu hasilnya dikonversi ke UTC; jika belum diketahui, gunakan UTC. Jika user sudah memiliki enrollment aktif di course tersebut, tolak dan sarankan perpanjang.
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
- SRS-E2: Pembuatan enrollment dari pembelian: `starts_at = payment.paid_at` dalam UTC. Untuk `expires_at`, ubah start ke zona member terakhir yang tersimpan (atau UTC bila belum ada), tambahkan `access_months` kalender tanpa overflow, lalu simpan kembali sebagai UTC.
- SRS-E3: Service `ExpireEnrollments` (dipicu cron-job.org tiap jam; bagian 5.10) mengubah `active` → `expired` bila `expires_at <= now()` dan mencabut sesi pemutarannya.
- SRS-E4: Pembelian dilarang bila `activeFor` ada (BR-04). Setelah kedaluwarsa, enrollment baru dibuat (baris baru), baris lama tetap sebagai riwayat.
- SRS-E5: Admin (`role = admin`) dianggap memiliki akses pratinjau ke semua course tanpa enrollment, dan sesi pratinjau tidak mengubah progres.

### 5.6 Pembelian dan pembayaran

Aturan umum: seluruh logika di `OrderService` dan `PaymentService`; `MidtransClient` hanya membungkus HTTP. Kunci dan URL dari `config/midtrans.php` (sandbox/production lewat `MIDTRANS_IS_PRODUCTION`). **Agent harus membaca dokumentasi Core API Midtrans yang berlaku untuk nama field dan tidak boleh mengarang field.** Agent tidak boleh memanggil API Midtrans untuk mencoba; user yang menguji di sandbox.

**Membuat pesanan dan pembayaran (`checkout.store`)**

1. Validasi: user login, course terbit, `activeFor` kosong, metode ada di `config('midtrans.enabled_channels')`.
2. Dalam transaksi dengan `lockForUpdate` pada pesanan tertunda milik user+course:
   - Jika ada order `pending` dengan payment `pending` yang belum kedaluwarsa dan metode sama → arahkan ke instruksi bayar yang ada.
   - Jika metode berbeda → coba batalkan payment lama melalui Midtrans Cancel API. Bila hasilnya belum terkonfirmasi, periksa Get Status; jika masih `pending`, coba Expire API sebagai jalur pemulihan. Tandai lokal `cancel` atau `expire` hanya setelah respons gateway yang cocok atau Get Status terverifikasi menyatakan status final. Buat payment baru dengan `midtrans_order_id` baru (`{order_number}-{n}`) hanya setelah payment lama tidak dapat dibayar lagi.
   - Jika belum ada order → buat `orders` (`amount` = harga efektif course saat itu, `expires_at = now + pending_order_ttl_hours`).
3. Panggil `POST /v2/charge` sesuai metode (Virtual Account bank, QRIS, GoPay, ShopeePay), dengan `transaction_details.order_id` = `midtrans_order_id`, `gross_amount` integer Rupiah, `customer_details`, `item_details`, dan masa berlaku (`custom_expiry`) sesuai sisa waktu order.
4. Simpan respons yang diperlukan ke `payments` (`va_number`, `qr_url`, `deeplink_url`, `expires_at`, `transaction_id`, `raw_response` yang sudah dibersihkan dari data sensitif).
5. Setelah charge tersimpan sebagai `pending`, kirim email instruksi ke member secara sinkron dan tepat satu percobaan otomatis per payment attempt. Bila charge awal berstatus belum pasti, kirim ketika rekonsiliasi memverifikasi status `pending`. Email berisi course, nomor order, nominal, metode, batas bayar dalam zona member, VA atau QRIS, serta tautan `orders.pay`. Untuk QRIS/GoPay dengan kode QR, coba ambil PNG dari URL aksi Midtrans yang telah divalidasi dan tanam sebagai inline image; bila gagal, tampilkan URL gambar di HTML email dan tautan halaman bayar. Catat `payments.instruction_email_sent_at` dan `instruction_email_last_attempt_at` lewat migration baru. Kegagalan SMTP atau pengambilan QR tidak membatalkan tagihan. Admin dapat mengirim ulang secara manual hanya untuk payment pending yang masih aktif dan belum tercatat terkirim; hasil timeout SMTP dapat tidak pasti.
6. Redirect ke `orders.pay`. Jika Midtrans gagal, simpan error ringkas ke log (tanpa kunci), tampilkan pesan ramah, dan jangan meninggalkan payment menggantung `pending` tanpa transaction_id.

**Halaman instruksi bayar dan status**

- `orders.pay` hanya untuk pemilik order. Menampilkan UI sesuai `design.md` 19.6.
- `orders.status` mengembalikan `{ status, payment_status, expires_at, paid_at, redirect }` untuk polling UI. Endpoint ini hanya **membaca** database, tidak memberi akses.
- Throttle polling (mis. 30 request/menit/user).

**Webhook (`webhooks.midtrans`)**

1. Simpan payload mentah ke `payment_notifications` (dengan `signature_valid`).
2. Verifikasi signature: `hash('sha512', order_id . status_code . gross_amount . server_key)` dibandingkan dengan `signature_key` memakai `hash_equals`. Gunakan `gross_amount` **persis seperti string yang diterima** (mis. `"250000.00"`). Jika tidak valid → respons 403 dan hentikan.
3. Notifikasi uji dari dashboard Midtrans dengan `order_id` berawalan `payment_notif_test_{merchant_id}_`, `merchant_id` sesuai konfigurasi, dan signature valid dicatat lalu dibalas HTTP 200 tanpa mengubah pesanan, pembayaran, atau enrollment. Untuk notifikasi transaksi, temukan `payments` berdasarkan `midtrans_order_id`. Jika tidak ada → respons 404.
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
8. Catat hasil di `payment_notifications.process_result` dan `system_logs`. Email sukses (S) dikirim sinkron setelah commit dengan timeout SMTP; kegagalan email tidak membatalkan pembayaran/enrollment atau mengubah respons webhook menjadi gagal. Simpan `orders.payment_email_sent_at` dan `payment_email_last_attempt_at` (timestamp nullable, migration Phase 5) sebagai penanda hasil/percobaan, dan sediakan retry manual admin berotorisasi. Lock per order mencegah pengiriman bersamaan; timeout SMTP setelah pengiriman dapat menghasilkan hasil tidak pasti, jadi jangan menjanjikan exactly-once email atau retry otomatis tanpa batas. Email instruksi dan email sukses memakai layout HTML responsif yang sama dengan alternatif teks polos; tidak memuat aset eksternal untuk tata letak.

**Penjadwalan**

- Service `ExpirePendingOrders`, dipicu cron-job.org tiap 10 menit: untuk order `pending` yang lewat `expires_at`, cek dulu status ke Midtrans (hindari menandai kedaluwarsa pesanan yang sebenarnya sudah dibayar), lalu tandai `expired`.
- Service `ReconcilePayments`, dipicu cron-job.org tiap 15 menit (S): cek status payment `pending` berumur > 10 menit sebagai jaring pengaman webhook terlambat.
- Admin dapat menekan "Sinkron" pada order (`admin.orders.sync`), memanggil logika yang sama.

**Metode pembayaran MVP:** Virtual Account (BCA, BNI, BRI, Permata, CIMB), QRIS, GoPay, ShopeePay. Daftar yang tampil diambil dari `config('midtrans.enabled_channels')` sehingga mengikuti kanal yang aktif di akun merchant. Kartu kredit tidak termasuk MVP.

### 5.7 Pemutar dan pengiriman video terlindungi

**Persiapan di browser dan impor tanpa worker (menggantikan encoding server)**

- SRS-V1: Halaman admin memeriksa HTTPS, WebAssembly, Web Worker dan Web Crypto sebelum memilih file. Tidak memakai OS sebagai penentu; Windows/macOS/Linux lewat browser desktop yang lulus UAT. Mulai Chrome/Edge desktop; Firefox/Safari dievaluasi terpisah. HP/tablet bukan target persiapan MVP, tetapi tetap target player sesuai NFR-C1.
- SRS-V2: ffmpeg.wasm single-thread, wrapper/core/worker dipin versi, di-host di `public/vendor/ffmpeg/` via `url()`, dimuat hanya pada halaman admin terkait. Gunakan distribusi siap pakai tanpa Vite/npm build/frontend framework. Periksa dukungan module worker dan MIME WASM di hosting. Multi-thread/SharedArrayBuffer tidak menjadi persyaratan MVP; jangan memasang COOP/COEP global tanpa kebutuhan.
- SRS-V3: Batas usulan awal: sumber MP4 H.264 8-bit yuv420p, audio AAC bila ada, dimensi landscape <=1280x720 (portrait <=720x1280), durasi <=900 detik dan ukuran <=200 MiB. Nilai ini batas konfigurasi awal untuk UAT, bukan kapasitas terjamin. Periksa codec/dimensi/durasi melalui metadata engine browser; MIME/ekstensi saja tidak cukup. Input tidak sesuai ditolak dengan petunjuk ekspor ulang. Tidak mengonversi video bebas atau membuat dua resolusi pada MVP.
- SRS-V4: Browser menjalankan stream copy satu rendition `source`, HLS VOD MPEG-TS dengan target segmen 6 detik. Batas segmen mengikuti keyframe sumber, tidak selalu persis 6 detik; tolak profil yang menghasilkan segmen melebihi batas upload yang diizinkan. Target GOP sumber disarankan <=6 detik. Verifikasi kemampuan HLS muxer dan AES-128 dari build yang dipin melalui UAT user; jangan mengasumsikan semua build mendukungnya.
- SRS-V5: Buat kunci 16 byte dengan `crypto.getRandomValues` per percobaan impor. Kunci hanya sementara di memori/virtual FS browser untuk HLS key info. Kirim via HTTPS body autentikasi admin+CSRF ke sesi impor, simpan dengan Crypt di DB; redaksi payload key pada seluruh logger. Jangan menggunakan server secret di browser, localStorage, URL atau file publik untuk kunci. Playback key tetap hanya lewat endpoint sesi sah. Hapus virtual key/files setelah proses, batal atau gagal sebisa mungkin.
- SRS-V6: Upload sumber asli tidak dilakukan. Browser mengirim hasil manifest dan segmen melalui fetch/FormData bertahap, satu video aktif per admin dengan lock server. Chunk maksimal 5 MiB, jalur/nama ditentukan server, potongan bernomor dengan hash/ukuran serta retry idempoten (isi berbeda untuk nomor sama ditolak). Database video_uploads melacak owner, lesson, key terenkripsi, status/manifest/cursor/expiry. Putus koneksi dapat melanjutkan upload bila hasil masih ada di browser dan sesi sah; refresh/tutup tab bisa membutuhkan pemilihan sumber dan proses ulang. Tidak menjanjikan resume lintas restart browser.
- SRS-V7: Server tidak menjalankan executable atau menerima ZIP bebas. Allow-list `source`, `index.m3u8`, `seg_\d{5}\.ts`; batas jumlah 2.000 segmen, total hasil awal <=250 MiB, ukuran segmen <=10 MiB, manifest <=1 MiB. Semua batas di config. Tolak traversal, symlink, URI eksternal, tag HLS tidak diizinkan dan ukuran/hash berbeda. Client dianggap tidak tepercaya: hash bukan bukti konten aman. Server memvalidasi struktur manifest (VOD/endlist, jumlah/durasi/sequence/IV), kelengkapan file, panjang ciphertext AES, dan struktur TS setelah dekripsi bertahap dengan OpenSSL sebelum ready. Validasi ini tidak membuktikan codec/durasi frame secara penuh tanpa decoder server; metadata bukan dasar otorisasi. Simpan hanya privat, jangan mengeksekusi hasil upload, selalu sajikan tipe konten yang ditetapkan server.
- SRS-V8: `finalize` memvalidasi sejumlah segmen per request dengan cursor tersimpan, batas waktu maksimal 10 detik; browser mengulang hingga selesai. Respons `200` mencakup status validating/ready, progress dan next cursor; tidak ada antrean. Gunakan lock sesi, verifikasi expected bytes/semua segmen, lalu transaksi singkat mengganti pointer video_assets + durasi lesson hanya setelah lengkap. Aset lama tetap dapat diputar hingga aset baru siap; folder versi baru memakai upload UUID. Kegagalan/timeout tidak boleh menerbitkan hasil parsial. Retry stale dengan lease/token pemilik lock; cleanup tidak boleh menghapus finalisasi aktif. Simpan status gagal dan sumber lokal tetap diperlukan untuk proses ulang. Penyimpanan: `{lesson_id}/{upload_id}/hls/source/`; manifest penyajian dibuat/ditulis ulang server, key tidak menjadi file publik.

UI membedakan Memeriksa browser, Memproses di perangkat, Mengunggah, Memvalidasi, Siap, Gagal/Dibatalkan. video_assets hanya mencerminkan aset yang sudah aktif atau kegagalan pertama; progres staging ada di video_uploads agar penggantian tidak mematikan aset lama. Jangan memakai `dispatch`, ProcessVideoToHls, `proc_open`, atau worker server. Enkripsi browser tidak membuktikan keaslian pengunggah; seluruh endpoint wajib policy admin dan scope sesi.

Referensi: [ffmpeg.wasm overview](https://ffmpegwasm.netlify.app/docs/overview/) dan [FAQ](https://ffmpegwasm.netlify.app/docs/faq/). Web Worker di sini milik browser, berbeda dari queue worker Laravel. Batas dan keberhasilan HLS AES-128 harus ditetapkan berdasarkan UAT user, bukan klaim sudah diverifikasi.

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
- SRS-V18: `<video>` tanpa atribut `controls`; kontrol custom; selector kualitas disembunyikan untuk satu rendition. Atribut `controlsList="nodownload noremoteplayback"`, `disablePictureInPicture`, `playsinline`. Blokir `contextmenu` pada area player.
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
- SRS-M4a: Member dapat mengunduh invoice PDF hanya untuk order miliknya dengan status `paid`; request lain mendapat 404. Invoice pertama membuat satu snapshot `order_invoices` (UUID v7, `order_id` unique) dengan nomor deterministik `INV-{order_number}`, waktu pembayaran UTC dan zona tampilan member, nama/email pembeli, identitas penjual dari `config('invoice.*')`, course, nominal, dan metode. Unduhan berikutnya memakai snapshot yang sama. PDF dibuat di server dari HTML lokal dengan Dompdf, tanpa aset remote atau subprocess, lalu dikirim sebagai attachment dengan cache privat. Jangan menyebutnya faktur pajak atau menghitung pajak tanpa data dan ketentuan yang telah ditetapkan.
- SRS-M5: Profil: ubah nama, telepon, avatar, password (verifikasi password lama).

### 5.9 Dashboard admin

KPI (`card-statistic-*`): total user, course terbit, enrollment aktif, pendapatan bulan ini (pesanan `paid`), pesanan tertunda. Tabel pesanan terbaru dan video gagal diproses. Query harus efisien (agregasi, tanpa N+1).

### 5.10 Pemicu berkala melalui cron-job.org

cron-job.org memanggil endpoint HTTPS yang menjalankan batch service sinkron. Tidak ada queue worker, dispatch, atau Laravel Scheduler. [FAQ resmi cron-job.org](https://cron-job.org/en/faq/) mendokumentasikan POST/header dan batas respons; target request maksimal 10 detik dengan margin terhadap batas hosting/layanan.

**Kontrak endpoint**

- `POST /operations/jobs/{task}`, nama `operations.jobs.store`, controller `Operations/ScheduledJobController`. Nama URL dipertahankan; jobs berarti task berkala, bukan Laravel queued job. Task harus berasal allow-list di bawah, bukan class/command/shell dari request.
- HTTPS production, header `Authorization: Bearer <OPERATIONS_CRON_TOKEN>` dibandingkan via hash_equals, token acak minimal 32 byte; config kosong menonaktifkan endpoint. Token tidak berada di URL/log/respons/repository.
- Middleware cron.token, tanpa auth sesi/redirect; CSRF dikecualikan hanya operations/jobs/*, throttle 30 request/menit/IP. GET tidak menjalankan pekerjaan.
- Respons `200` berisi status completed/partial/busy, processed dan remaining (jumlah atau boolean sesuai query). completed berarti batch eligible saat itu selesai; partial berarti masih ada pekerjaan. `401` token salah, `404` task tidak dikenal, `429` throttle, `503` konfigurasi nonaktif atau dependency gagal. Jangan menggunakan queued/202 sebagai klaim hasil.
- Setiap request dibatasi jumlah record (default 20), waktu (default 10 detik), serta timeout koneksi/HTTP/SMTP lebih pendek daripada sisa anggaran. Hindari retry menunggu di dalam request. Sisa data dilanjutkan pada pemicu berikutnya menggunakan state persisten; cron-job.org tidak otomatis mengikuti cursor respons.
- Lock atomik database cache per task, TTL lebih panjang daripada batas request; pelepasan hanya oleh owner. Mutasi bisnis idempoten, transaksi singkat/lock row. Tulis checkpoint setelah tiap unit berhasil, jangan melewati item gagal selamanya: simpan next eligible/retry/backoff dan pastikan siklus scan kembali mencakupnya.
- Tabel `operation_states` dibuat Phase 2: UUID v7, task string(80) unique, cursor JSON nullable (tanpa default), last_started_at/last_succeeded_at/next_attempt_at timestamp nullable, last_error string nullable, timestamps. Detail cursor/retry hanya ID/status/waktu, tidak rahasia. Cache lock tidak menggantikan checkpoint persisten. Pantau lag/last_success, status partial, serta jumlah gagal di system_logs; jangan mengandalkan HTTP 200 saja.

| Task | Interval pemicu cron-job.org | Service | Phase |
|---|---|---|---|
| expire-enrollments | Tiap jam | ExpireEnrollments | 2 |
| prune-playback-sessions | Tiap 15 menit | PrunePlaybackSessions | 4 |
| cleanup-video-uploads | Tiap 15 menit | CleanupVideoUploads | 4 |
| expire-pending-orders | Tiap 10 menit | ExpirePendingOrders | 5 |
| reconcile-payments | Tiap 15 menit | ReconcilePayments | 5 (S) |
| send-access-expiry-reminders | Tiap 15 menit, mulai setelah 08:00 | SendAccessExpiryReminders | 7, hanya jika F-509 dipilih |

Pemicu cleanup lebih sering dari rancangan lama agar batch kecil dapat mengejar backlog. Retensi sesi berakhir/dicabut 30 hari; unggahan tidak aktif >24 jam. Jangan menghapus sesi impor aktif, folder aset yang dirujuk, atau hasil penggantian sebelum swap atomik. Reminder hanya eligible satu kali per enrollment/tanggal akhir pada hari H-7 menurut zona member yang tersimpan (UTC bila tidak diketahui); timestamp/penanda persisten dan lock diperlukan, termasuk keterbatasan hasil SMTP tidak pasti. Aktifkan jadwal hanya saat fiturnya tersedia.

User mengatur URL aktual, POST, header rahasia, zona waktu dan notifikasi gagal melalui dashboard cron-job.org; tidak memerlukan API key layanan di aplikasi. Staging HTTPS/tunnel disediakan user untuk UAT. User memeriksa pemicu berulang, partial continuation, timeout, recovery checkpoint, rotasi token dan backlog. Jika batch tertinggal, sesuaikan frekuensi dalam batas hosting atau rencanakan VPS; jangan diam-diam menambah worker.

Get Status Midtrans diizinkan dalam batch dengan timeout; kegagalan API tidak berarti lunas/kedaluwarsa. Rekonsiliasi/admin sync menggunakan verifikasi server dan service idempoten yang sama dengan webhook. Polling browser tetap hanya membaca DB. Akses expired tetap ditolak langsung meskipun maintenance terlambat. Backup dikelola user/provider, bukan endpoint perintah bebas.

## 6. Kebutuhan non-fungsional

**Keamanan**

- NFR-S1: Seluruh akses data di-scope di server (cegah IDOR): member hanya dapat melihat order/enrollment/progres miliknya.
- NFR-S2: Header keamanan lewat `SecurityHeaders`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal. CSP halaman video mengizinkan origin asset Otika yang dihitung dari `asset()` pada `script-src`, `style-src`, `font-src`, dan `connect-src` (termasuk permintaan source map CSS oleh DevTools); `worker-src 'self' blob:` dan `media-src 'self' blob:`. Origin lain tidak diizinkan tanpa kebutuhan yang tercatat.
- NFR-S3: Rate limiting: login, register, forgot/reset, checkout, polling status, stream, webhook.
- NFR-S4: Kunci Midtrans dan kunci konten tidak muncul di log/Blade atau respons umum. Pengecualian kunci konten hanya input HTTPS impor admin dan respons endpoint playback key terotorisasi; key browser dibuat lokal sesuai V5. `APP_KEY` dijaga; mengganti `APP_KEY` membuat `encryption_key` video tidak terbaca, jadi dokumentasikan dan sediakan prosedur backup.
- NFR-S5: Upload divalidasi MIME, ekstensi, ukuran, dan diberi nama acak. HTML dari editor disanitasi.
- NFR-S6: Password di-hash bawaan Laravel (bcrypt/argon). Sesi di-regenerate saat login.

**Kinerja**

- NFR-P1: Halaman publik < 2 detik pada koneksi umum; gunakan `loading="lazy"`, ukuran thumbnail wajar, eager loading, dan indeks sesuai bagian 3.
- NFR-P2: Streaming segmen harus mendukung `Range`/`stream` tanpa memuat seluruh file ke memori.
- NFR-P3: Query daftar admin memakai pagination atau DataTables dengan batas data wajar.

**Keandalan dan operasional**

- NFR-R1: Operasi pembayaran dan enrollment atomik (transaksi) dan idempoten.
- NFR-R2: Impor video dan batch operasi memiliki lock, batas waktu, checkpoint, retry terbatas serta status kegagalan persisten; tidak bergantung failed_jobs atau worker. Email sinkron dengan timeout, gagal kirim tidak membatalkan transaksi bisnis.
- NFR-R3: Cadangan database dan folder `storage/app/private/videos` harus dijadwalkan oleh user/server (di luar kode); dokumentasikan.
- NFR-R4: Pemicu cron-job.org sesuai 5.10 aktif di production; pantau hasil batch dan backlog. Tidak memakai Laravel Scheduler/crontab aplikasi atau worker server.

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
APP_TIMEZONE=UTC
APP_LOCALE=id
ASSET_URL=https://otika.namikulo.com/assets

DB_CONNECTION=pgsql          # production: mysql
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER= MAIL_HOST= MAIL_PORT= MAIL_USERNAME= MAIL_PASSWORD= MAIL_FROM_ADDRESS= MAIL_TIMEOUT=5

COURSE_ACCESS_MONTHS=6
COURSE_PENDING_ORDER_TTL_HOURS=24

MIDTRANS_IS_PRODUCTION=false
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_ENABLED_CHANNELS=bca,bni,bri,permata,cimb,qris,gopay,shopeepay
INVOICE_SELLER_NAME= INVOICE_SELLER_ADDRESS= INVOICE_SELLER_EMAIL= INVOICE_SELLER_TAX_ID=

VIDEO_DISK=video_private
VIDEO_SOURCE_MAX_SIZE_MB=200
VIDEO_SOURCE_MAX_DURATION_SECONDS=900
VIDEO_UPLOAD_MAX_SIZE_MB=250
VIDEO_SEGMENT_MAX_SIZE_MB=10
VIDEO_MAX_SEGMENTS=2000
VIDEO_SESSION_TTL_MINUTES=10
VIDEO_HEARTBEAT_SECONDS=60
VIDEO_ACCEL_REDIRECT=false

OPERATIONS_CRON_TOKEN=
OPERATIONS_PLAYBACK_RETENTION_DAYS=30
OPERATIONS_UPLOAD_STALE_HOURS=24
OPERATIONS_BATCH_SIZE=20
OPERATIONS_MAX_SECONDS=10

SEED_ADMIN_NAME=
SEED_ADMIN_EMAIL=
SEED_ADMIN_PASSWORD=
```

Konfigurasi aplikasi dibaca lewat `config('course.*')`, `config('midtrans.*')`, `config('video.*')`, `config('operations.*')`. Jangan memanggil `env()` di luar file config.

## 8. Catatan deployment (production MySQL)

1. Jalankan migration di MySQL staging sebelum rilis untuk memastikan kompatibilitas.
2. Pastikan OpenSSL PHP, storage privat, HTTPS, MIME application/wasm, asset worker/core lokal dan browser admin kompatibel. Tidak membutuhkan ffmpeg/ffprobe di hosting.
3. Atur `upload_max_filesize`, `post_max_size`, `client_max_body_size`, `max_execution_time` untuk potongan unggahan.
4. Daftarkan URL notifikasi di dashboard Midtrans: `https://<domain>/webhooks/midtrans`.
5. Pastikan folder `storage/app/private` tidak dapat diakses langsung oleh web server.
6. Konfigurasikan cron-job.org sesuai 5.10; pantau hasil batch, checkpoint dan backlog. Tidak menjalankan queue:work/Supervisor.
7. Jalankan `php artisan storage:link`, `config:cache`, `route:cache`, `view:cache` (user).
8. Untuk invoice PDF, jalankan `composer install` pada deployment dan pastikan PHP memiliki ekstensi `dom` dan `mbstring`; folder `storage/framework/cache` harus dapat ditulis proses PHP.

## 9. Keterlacakan phase

Detail urutan dan dependensi ada di [roadmap](phases/README.md). Publikasi positif menunggu Phase 4, checkout Phase 5, dan progres Phase 6. F-306/F-307 serta F-507/F-510 dipusatkan di Phase 5 sesuai permintaan user; F-105 di Phase 6. Phase 2 membuat skema orders hanya sebagai prasyarat FK enrollment, tanpa integrasi pembayaran.

| Phase | Cakupan PRD | Bagian SRS utama |
|---|---|---|
| 0 | F-101–F-104 (dasar), BR-12 (log) | 3, 5.1, 6, 7 |
| 1 | F-201–F-209 (metadata; belum video) | 5.2 |
| 2 | F-301–F-305, enrollment manual dan expiry | 5.3, 5.5 (manual), 5.10 |
| 3 | F-401–F-404 | 5.4 |
| 4 | F-207–F-208, F-602, F-603, F-701–F-706 | 5.7, 5.10 |
| 5 | F-306–F-307, F-501–F-508, F-605, BR-02, BR-04–BR-07 | 5.5, 5.6, 5.10 |
| 6 | F-601, F-604, F-507, F-105 | 5.8 |
| 7 | F-509 (opsional), dashboard, audit dan kesiapan rilis | 5.9, 5.10, 6, 8 |

## 10. Jalur peningkatan setelah MVP

Saat pindah ke VPS, service impor dan VideoDelivery dapat dipertahankan sambil menambah pemrosesan sumber di server lewat queue. Encoding multikualitas dan retry background memerlukan revisi phase/scope tersendiri; tidak diimplementasikan pada MVP cPanel.
