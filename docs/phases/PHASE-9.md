# Phase 9: Diskusi pada setiap materi

Status: **Aktif; implementasi menunggu UAT user.** User menginstruksikan pengembangan Phase 9 sambil menunda pengujian end to end. Phase ini tidak mengubah role: penjawab resmi sementara adalah admin. Kelulusan tetap menunggu feedback UAT user.

Acuan: [PRD F-805–F-807 dan BR-13–BR-15](../PRD.md), [SRS 3.1 dan 5.12](../SRS.md), [design.md 19.12](../../design.md), [AGENTS.md](../../AGENTS.md).

## Tujuan dan dependensi

Di bawah setiap video, member yang masih mempunyai akses dapat mengajukan pertanyaan, menanggapi pertanyaan atau komentar, dan membaca jawaban member/admin lain. Balasan memanfaatkan notifikasi Phase 8; pertanyaan baru mengundang admin untuk menjawab. Diskusi diikat pada `lesson`, bukan forum umum/course global.

Prasyarat implementasi: pusat notifikasi dan digest Phase 8 tersedia di kode; UAT keduanya dapat dilakukan bersama sesuai keputusan user. `EnrollmentService::activeFor` tetap menjadi sumber kebenaran akses; lesson/course soft delete dan mode admin preview mengikuti implementasi aktual.

## Urutan implementasi

1. Tambahkan migration portabel `lesson_discussion_posts` dengan UUID v7, FK dan indeks pendek, soft delete, serta migration terpisah yang menambah referensi post nullable pada `user_notifications`. Migration yang pernah dijalankan tidak diedit.
2. Buat policy/service yang memeriksa akses course aktif atau admin pada setiap baca/tulis/hapus. Validasi `lesson_id`, root, `reply_to_id`, dan post berada pada lesson/thread sama; root tersembunyi menutup thread bagi member. Revisi editor: HTML disanitasi, teks 10–2000 karakter, tanpa lampiran; batasi laju dan duplikasi cepat. Migration terpisah `body_format` menjaga komentar lama tetap plain. Kutipan balasan dapat diklik untuk menuju dan menyorot komentar asal.
3. Tambahkan bagian diskusi di bawah video pada layout Player: daftar pertanyaan/jawaban berhalaman, form berlabel, balasan ke komentar dengan konteks penulis, penanda Admin, waktu lokal, dan state kosong/error/akses kedaluwarsa. Submit normal memakai CSRF, `old()`, flash dan anchor; pemutar tidak auto-play ulang setelah kirim.
4. Kiriman baru dan notifikasi dibuat dalam satu transaksi: pertanyaan ke admin aktif; balasan ke penulis pertanyaan dan komentar yang ditanggapi, penerima unik dan bukan pengirim. Tautan tetap memeriksa enrollment saat dibuka. Item belum dibaca masuk digest Phase 8 berikutnya sesuai cutoff.
5. Tambahkan halaman moderasi admin berhalaman dan aksi sembunyikan/pulihkan. Penulis dapat soft delete miliknya; admin dapat melihat log moderasi tanpa mengekspos body ke `system_logs`. Konten hilang tetap dapat diaudit sesuai kebijakan data yang berlaku.
6. Perbarui dokumentasi UI dan operasi dengan batas moderasi, pemulihan, dan dampak course/lesson arsip. Jangan menambah role trainer, live chat, lampiran, WebSocket, atau forum umum.

## Perintah untuk user

Setelah backup database, user menjalankan `php artisan migrate` lalu `php artisan optimize:clear` saat siap melakukan UAT. Tidak ada worker atau cron tambahan; notifikasi balasan memakai digest Phase 8 setelah task itu diaktifkan.

## Variabel lingkungan terencana

Tidak ada variabel `.env` baru. `APP_TIMEZONE=UTC`, `QUEUE_CONNECTION=sync`, dan konfigurasi cron/email Phase 8 tetap digunakan.

## Panduan Uji Manual (UAT)

| Langkah | Hasil yang diharapkan |
|---|---|
| Dua member berakses aktif membuka lesson yang sama; satu bertanya, yang lain menjawab | Pertanyaan dan jawaban muncul pada lesson itu; waktu lokal benar; penulis pertanyaan mendapat satu notifikasi. |
| Balas komentar tertentu; admin menjawab pertanyaan | Penulis komentar dan root yang berbeda mendapat notifikasi masing-masing sekali; tidak ada notifikasi untuk diri sendiri; jawaban admin jelas ditandai. |
| Buat pertanyaan baru dan buka sebagai admin | Admin aktif mendapat notifikasi dan dapat membuka thread/moderasi. |
| Uji akun tanpa enrollment, enrollment expired/revoked, lesson lain, dan ID post yang dipalsukan | Tidak dapat membaca/menulis lintas akses atau lesson; tautan notifikasi lama tidak membuka materi setelah akses habis. |
| Arsipkan course dengan enrollment masih aktif; soft delete lesson | Diskusi course arsip tetap tersedia bagi member aktif; lesson yang dihapus tidak menerima kiriman baru. |
| Kirim teks berformat/script, pesan kosong/panjang, dan melebihi batas laju | Format yang diizinkan tampil; script/atribut berbahaya dibuang. Komentar lama plain tetap tampil sebagai teks aman. Validasi serta respons 429 jelas dan tidak membuat item ganda. |
| Penulis menghapus komentar; admin menyembunyikan/memulihkan reply dan root | Konten yang dibatasi tidak terlihat member; thread root tersembunyi menutup semua balasan; audit admin tetap tersedia. |
| Buat banyak thread/reply dan cek 375/768/1024/desktop, keyboard, playback | Pagination dan form dapat dipakai; diskusi tidak menutupi video/kontrol; pemutar tetap bekerja. |
| Migrasi dan alur diskusi pada MySQL staging | FK/index/migration portabel dan query berhalaman berjalan benar. |

## Kriteria kelulusan dan risiko

User menilai moderasi, keamanan akses, escape teks, batas kirim, notifikasi penerima, dan beban query. Phase 9 lulus hanya setelah feedback eksplisit user. Asumsi: diskusi teks saja, tanpa edit kiriman atau lampiran; apabila kebutuhan moderasi meningkat, peran trainer/reporting/pre-moderation direncanakan sebagai scope baru.
