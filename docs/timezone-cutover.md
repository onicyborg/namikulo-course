# Cutover waktu UTC — Phase 2

Mulai revisi ini, `APP_TIMEZONE=UTC` dan koneksi PostgreSQL/MySQL menggunakan UTC. Kolom timestamp lama dibuat ketika aplikasi memakai `Asia/Jakarta`; mengganti `.env` saja akan membuat jam lama dibaca sebagai UTC dan dapat menggeser batas akses. Perintah `timezones:normalize-existing` mengubah nilai lama satu kali, termasuk waktu dalam snapshot `system_logs` yang berbentuk timestamp polos.

## Sebelum menjalankan

1. Hentikan penulisan ke aplikasi (maintenance mode) dan jeda pemicu cron-job.org. Jangan memberi akses, menerbitkan course, atau login selama cutover. **Pastikan belum ada penulisan sejak `APP_TIMEZONE` diganti ke UTC.** Jika sudah ada atau tidak yakin, hentikan prosedur di sini: data lama dan data UTC baru harus dipisahkan dari riwayat perubahan sebelum konversi. Jangan menjalankan normalisasi menyeluruh pada data campuran.
2. Cadangkan database. Simpan contoh `created_at` user/course dan, bila ada, `starts_at`/`expires_at` enrollment beserta jam yang selama ini terlihat di UI.
3. Pada MySQL/MariaDB, identifikasi zona koneksi database **sebelum** cutover. Konfigurasi lama tidak menetapkan `timezone`, sehingga koneksi mengikuti zona server. Nilai `@@global.time_zone` atau `@@system_time_zone` dapat membantu; bila `SYSTEM`, gunakan zona sistem yang benar. Zona yang tidak diketahui tidak boleh ditebak untuk data produksi. PostgreSQL tidak memerlukan opsi ini karena kolom lama `timestamp without time zone` berisi jam dinding aplikasi.

## Urutan untuk database dengan data yang dipertahankan

Jalankan perintah ini sendiri setelah file aplikasi baru tersedia. Agent tidak menjalankan migrate atau perintah yang menulis DB.

```bash
php artisan down
# Cadangkan database dengan alat PostgreSQL/MySQL milik Anda.
# Pastikan APP_TIMEZONE=UTC di .env.
php artisan optimize:clear
php artisan migrate
php artisan timezones:normalize-existing --dry-run
php artisan timezones:normalize-existing
php artisan optimize:clear
php artisan up
```

Untuk **MySQL/MariaDB**, tambahkan `--legacy-db-zone=UTC` atau zona lama yang benar, misalnya `--legacy-db-zone=Asia/Jakarta`, pada kedua perintah `timezones:normalize-existing`. Opsi ini menyatakan zona **koneksi database lama**, bukan zona browser admin. Jalankan pratinjau dan bandingkan nilai lama/baru dengan waktu yang diketahui. Jika tidak cocok, jangan jalankan perintah yang menulis dan periksa kembali zona lama.

Perintah menyimpan penanda `legacy-utc-cutover` di `operation_states` dalam transaksi yang sama. Pengulangan sesudah berhasil tidak menggeser timestamp dua kali. Rollback aman dilakukan dengan mengembalikan cadangan database dan kode lama bersama-sama; jangan hanya membalik `APP_TIMEZONE`.

Kolom internal framework yang berupa Unix epoch (`sessions.last_activity`, cache, jobs) tidak dikonversi. `failed_jobs.failed_at` memakai nilai bawaan database, bukan jam aplikasi; bila tabel itu berisi data lama, tinjau terpisah menurut zona server lama sebelum menyebut seluruh histori framework sudah UTC.

Seluruh data lama dipertahankan. Jangan memakai `migrate:fresh`, `db:wipe`, atau perintah reset database pada jalur ini.

## Perilaku sesudah cutover

- Browser mendeteksi zona IANA dari pengaturan perangkat. Login/registrasi menyimpan zona itu; sesi yang sudah ada menyelaraskannya melalui permintaan terautentikasi. Jika perangkat melaporkan zona yang tidak didukung, akun tetap dapat dipakai dan zona tersimpan tidak diubah.
- Admin mengisi jam lokal perangkat. Browser mengirim momen UTC, dan server memeriksa kecocokannya dengan jam serta zona yang dikirim. Tabel menampilkan zona perangkat; jika JavaScript tidak tersedia, tampilkan UTC secara jelas.
- Akhir akses default dihitung sebagai enam bulan kalender di zona member terakhir yang tersimpan, kemudian dibekukan sebagai UTC. Jika member belum pernah mengakses situs, gunakan UTC. Perubahan zona sesudah itu hanya mengubah tampilan, bukan batas akses.
- Perbandingan `expires_at` dan cron tetap memakai momen UTC. Zona jadwal cron-job.org hanya menentukan kapan pemicu dikirim; interval setiap jam tidak mengubah hasil.

## UAT user

1. Bandingkan contoh timestamp lama sebelum/sesudah normalisasi. Momen akses harus sama, tetapi representasi UTC bergeser sesuai offset lama.
2. Login sebagai admin di browser berzona WIB, lalu buka daftar anggota dan isi waktu akses. Jam pada form harus WIB dan hasil di tabel harus menunjukkan WIB.
3. Ubah zona perangkat ke WITA/WIT, muat ulang halaman, dan periksa record yang sama. Jam tampil bergeser +1/+2 dari WIB, sementara status akses dan momen UTC tidak berubah.
4. Login sebagai member pada browser dengan zona berbeda, lalu periksa `users.timezone`. Pemberian akses baru dengan akhir otomatis harus mengikuti tanggal kalender zona member. Akun yang belum pernah login menggunakan UTC sampai zona diketahui.
5. Ubah jam akhir dari modal admin, lalu verifikasi timestamp UTC di database dan status expiry saat batas tersebut lewat. Browser dengan JavaScript nonaktif harus tetap menampilkan label UTC dan tidak mengirim waktu lokal yang ambigu.
