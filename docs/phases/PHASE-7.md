# Phase 7: Penguatan, operasi dan kesiapan rilis

Status: **Diimplementasikan, menunggu UAT user dan bukti staging MySQL**. Belum siap rilis.

Acuan: PRD dashboard MVP, F-509 (C); SRS 5.9, 5.10, seluruh NFR dan bagian 8. Lihat [roadmap](README.md), [PRD](../PRD.md), [SRS](../SRS.md), [design](../../design.md), dan [AGENTS](../../AGENTS.md). Jika ada konflik, prioritas AGENTS > SRS > PRD > design.

## Prasyarat

Phase 6 lulus dan user menginstruksikan lanjut. Kumpulkan hasil UAT semua phase serta keputusan penundaan S/C. Sediakan staging MySQL dan konfigurasi operasi production.

## Scope dan urutan implementasi

1. Dashboard admin: total user, course terbit, enrollment aktif, pendapatan bulan berjalan dari paid, pending, pesanan terbaru dan video gagal. Query agregat/eager loading, state kosong tanpa angka buatan.
2. Review seluruh route/policy/ownership/CSRF/rate limit, sanitasi HTML/upload, secret/log, keamanan file/stream, idempotensi pembayaran, lock task, checkpoint dan retry batch. Perbaiki temuan dalam scope MVP.
3. Lengkapi halaman log admin bila F-706/observabilitas memerlukannya (S); akses admin, filter/pagination, sanitasi. Pastikan operator dapat memantau trigger diterima, job selesai/gagal dan backlog tanpa membuat dashboard operasional baru yang tidak diminta.
4. F-509 hanya jika dipilih user: email akses tersisa tujuh hari, service batch berkala dan penanda persisten per enrollment/tanggal akhir agar retry tidak menggandakan email. Pada implementasi ini F-509 ditunda sesuai default roadmap; endpoint/jadwal tersebut tidak diaktifkan.
5. Tulis docs/DEPLOYMENT.md, docs/OPERATIONS.md, docs/RELEASE-CHECKLIST.md pada phase ini: setup MySQL, PHP/webserver/OpenSSL, asset WASM/worker browser, disk, timeout dan checkpoint batch, SMTP, Midtrans, cron-job.org, backup/restore APP_KEY/DB/video, rotasi token, pemulihan impor terputus dan batch gagal dan gangguan gateway.
6. Dokumentasikan deploy/rollback yang menjaga DB dan file: backup sebelum migrate, migration baru non-destruktif, rollback kode hanya bila skema kompatibel, pemulihan backup dilakukan user jika dibutuhkan. Jangan menggunakan migrate:fresh di production.
7. User melakukan UAT staging MySQL, browser/responsif, gangguan browser/pemicu/gateway, dan restore di lingkungan terisolasi. Catat bukti dan blocker. Tetapkan siap rilis hanya setelah persetujuan eksplisit user.

## Artefak yang direncanakan dan batas scope

Dashboard admin dan pemantauan task/log; pemisahan kuota rate limit, dukungan HTTP Range stream privat, redaksi payload notifikasi historis, perbaikan status kegagalan batch, dan migration indeks query; tiga dokumen operasi/rilis; README diperbarui. Reminder F-509 tidak diaktifkan. Tidak menambah fitur di luar MVP atau menandai lulus otomatis.

Nama file teknis mengikuti struktur SRS dan kode aktual; daftar file final wajib disertakan pada serah-terima. Migration yang sudah dipakai user tidak diedit. Tidak mengerjakan phase berikutnya tanpa instruksi user.

## Variabel lingkungan

APP_ENV=production, APP_DEBUG=false, APP_URL HTTPS; DB_CONNECTION=mysql dan DB_*; MAIL_* production; MIDTRANS_IS_PRODUCTION=true dan kredensial production setelah sandbox lulus; OPERATIONS_CRON_TOKEN berbeda lingkungan. APP_KEY dipertahankan aman. Variabel tambahan reminder hanya jika benar-benar diperlukan dan didokumentasikan.

## Perintah untuk user

Perintah berikut dijalankan user setelah meninjau implementasi dan, untuk production, setelah staging MySQL lulus. Agent tidak menjalankan migrate/seed pada database user. Pengujian browser agent mengikuti [panduan terpisah](../BROWSER-TESTING.md).

Untuk lingkungan development/staging yang sudah berisi data, buat backup database lebih dulu, lalu jalankan berurutan:

```bash
php artisan migrate
php artisan optimize:clear
php artisan payments:redact-notifications
php artisan payments:redact-notifications --apply
```

Migration Phase 7 hanya menambah indeks untuk query operasional dan memiliki `down()`; data lama dipertahankan. Perintah redaksi menghapus `signature_key` dan field di luar daftar aman dari snapshot notifikasi lama. Jalankan pratinjau sebelum `--apply`; output hanya jumlah record, tidak isi payload.

Urutan rencana deployment oleh user setelah kode dan UAT disetujui:

1. Backup DB, storage privat/public, konfigurasi rahasia dan APP_KEY; siapkan pemulihan.
2. Deploy kode/dependency: `composer install --no-dev --optimize-autoloader` setelah skrip composer diaudit.
3. Isi/verifikasi .env production, webroot public, izin storage dan HTTPS; jangan mengganti APP_KEY existing.
4. `php artisan migrate --force` setelah migration ditinjau dan staging MySQL lulus.
5. `php artisan db:seed --class=AdminUserSeeder --force` hanya instalasi pertama dengan SEED_ADMIN_* aman, bukan setiap deploy.
6. `php artisan storage:link` bila belum ada.
7. `php artisan config:cache`
8. `php artisan route:cache`
9. `php artisan view:cache`
10. `php artisan payments:redact-notifications` lalu `php artisan payments:redact-notifications --apply` untuk instalasi yang sudah memiliki notifikasi lama, setelah backup.
11. Verifikasi QUEUE_CONNECTION=sync, asset ffmpeg.wasm lokal dan konfigurasi batch; tidak memasang worker server.
12. Konfigurasi webhook merchant dan seluruh job cron-job.org yang tersedia sesuai SRS 5.10; aktifkan notifikasi kegagalan.
13. User melakukan pemeriksaan pascadeploy dan menyetujui rilis. Tidak memasang schedule:run/schedule:work.

## Panduan Uji Manual (UAT)

Dilakukan user. Tambahkan pemeriksaan umum [roadmap](README.md#prosedur-tiap-phase) untuk setiap halaman baru.

| Langkah | Hasil yang diharapkan |
|---|---|
| 1. Bandingkan KPI dengan data order/enrollment yang diketahui | Hitungan akurat, timezone benar, tanpa N+1 yang nyata pada review/query user. |
| 2. Jalankan semua migration pada staging MySQL dan ulangi alur utama | Portabel; register hingga pembayaran/player berjalan sama seperti PostgreSQL. |
| 3. Ulangi role/IDOR/upload/XSS/CSRF/rate limit dan akses stream kedaluwarsa | Batas akses konsisten; tidak ada rahasia atau file asli terbuka. |
| 4. Tutup tab persiapan atau gagalkan cron-job.org, lalu pulihkan; ulangi pemicu/webhook | Impor parsial tidak dipublikasikan; batch pulih dari checkpoint tanpa mutasi bisnis ganda. Hasil email tidak pasti ditangani secara eksplisit. |
| 5. Pulihkan backup pada lingkungan terisolasi dengan APP_KEY yang benar | DB dan video dapat dipakai; kunci video tetap dapat didekripsi. |
| 6. Periksa browser target, ukuran layar, keyboard, asset/console dan alur lengkap | Tidak ada blocker UX atau fitur M yang tertunda. |
| 7. Buka Admin → Tugas & log, filter aksi/sumber, dan bandingkan backlog dengan data diketahui | Hanya admin bisa melihat; pagination/filter benar, snapshot sensitif tidak ditampilkan, waktu mengikuti zona browser. |
| 8. Minta satu segmen video dengan `Range` valid/tidak valid dan buka materi berulang saat unggahan/status dipolling | Respons 206/416 sesuai rentang; tidak ada throttle lintas fitur yang memutus playback atau unggahan normal. |
| 9. Lengkapi checklist rilis dan keputusan user | Semua bukti/penundaan tercatat; user menyatakan lulus dan siap rilis. |

## Kriteria kelulusan, asumsi dan keterbatasan

Rilis ditahan bila kebutuhan M, keamanan akses/pembayaran, backup, pemrosesan browser, atau integrasi wajib belum lulus. User memberi persetujuan production; agent tidak melakukan deploy atau transaksi production. F-509 boleh ditunda secara eksplisit karena kategori C.

Phase hanya berubah menjadi lulus setelah user mengonfirmasi hasil UAT. Perbaiki temuan pada phase ini sebelum meminta lanjut. Laporan akhir memuat ringkasan/file, perintah user, .env, hasil pengujian agent, UAT, dan asumsi/keterbatasan. Kesiapan rilis memerlukan keputusan terpisah pada [checklist rilis](../RELEASE-CHECKLIST.md).
