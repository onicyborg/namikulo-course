# Tabel server-side — analisis dan UAT

## Cakupan dan dampak

Seluruh tabel daftar interaktif memakai Yajra DataTables Oracle 12.x (Laravel 12). Tabel tata letak email, invoice PDF, dan tabel di dalam konten rich text bukan daftar interaktif sehingga tetap statis.

| Halaman | Endpoint data JSON | Aksi yang perlu diperiksa setelah pindah halaman |
|---|---|---|
| `/admin` | `/admin/dashboard/orders/data` | Detail pesanan |
| `/admin/categories` | `/admin/categories/data` | Ubah dan hapus |
| `/admin/courses` | `/admin/courses/data` | Kurikulum, preview tab baru, anggota, ubah, hapus |
| `/admin/courses/{course}/members` | `/admin/courses/{course}/members/data` | Ubah masa berlaku dan cabut |
| `/admin/users` | `/admin/users/data` | Ubah, aktif/nonaktif, kirim reset password |
| `/admin/discussions` | `/admin/discussions/data` | Buka materi, sembunyikan/pulihkan |
| `/admin/orders` | `/admin/orders/data` | Detail |
| `/admin/notifications` | `/admin/notifications/data` | Buka tujuan, tandai dibaca |
| `/admin/operations` | `/admin/operations/data` | Detail perangkat pada log |
| `/my/orders` | `/my/orders/data` | Lanjut bayar, detail, invoice lunas |

Dampak implementasi:
- Controller halaman mengembalikan HTML, sedangkan endpoint `/data` khusus mengembalikan JSON Yajra. Query SQL melakukan filter, sort, offset, dan limit; HTML awal memuat satu halaman sebelum permintaan DataTables pertama.
- `App\Services\ServerTable` memakai Yajra Eloquent. Kolom urutan berasal dari allow-list server; field JSON dibatasi ke sel Blade yang di-escape. Jumlah total selalu mengikuti scope izin akses, jumlah terfilter mengikuti filter aktif.
- Ukuran halaman hanya 10/25/50/100; tidak ada opsi mengambil seluruh data. Urutan ditambah ID sebagai pemecah nilai seri agar paging stabil.
- `x-server-table` menyatukan pencarian, status/peran, ukuran halaman, indikator proses/error, dan pagination. Pencarian berjalan langsung setelah jeda 250 ms tiap input; filter mengembalikan posisi ke halaman pertama. Kontrol memakai tombol, bukan tautan/form GET yang dapat memuat ulang halaman.
- Pagination menampilkan paling banyak lima nomor: halaman pertama, terakhir, dan tiga nomor di sekitar halaman aktif. Jarak diberi elipsis; tombol sebelumnya/berikutnya tetap ada.
- Modal edit kategori/course/user/enrollment dimuat saat dibutuhkan melalui GET `/{resource}/{id}/modal` di grup admin. Endpoint memeriksa policy. Form tetap submit biasa dengan CSRF, validasi, dan flash; modal untuk data yang gagal validasi disiapkan dari `old()`.
- Handler delete/loading memakai delegasi event, tooltip menggunakan delegasi yang sudah tersedia. Modal baru menginisialisasi waktu lokal dan Lexical melalui event modal. Tidak ada migration atau perubahan data user oleh agent.
- Perubahan menyentuh controller daftar, partial sel/modal, komponen tabel, JS/CSS bersama, layout admin (asset tabel pada dashboard), routes, dan composer.

## Perintah untuk user

Dependency sudah dipasang di workspace ini. Pada checkout atau server lain:

```bash
composer install --no-interaction
```

Jika deployment memakai cache route/view, bangun ulang cache sesuai prosedur deployment. Tidak perlu migration, seed, perubahan `.env`, atau Node.js.

## UAT manual

Pengujian otomatis, lint, dan Chrome DevTools tidak dijalankan sesuai permintaan user. Berikut skenario untuk dijalankan pada data uji non-production:

1. Buka setiap halaman pada tabel di atas. Daftar berhasil dimuat, console/network tidak error, satu permintaan DataTables per perubahan, tanpa duplikasi kontrol.
2. Gunakan dataset >10 baris: pindah halaman 2, terakhir, sebelumnya, lalu ubah ukuran 25/50/100. Baris dan total sesuai; nomor halaman tetap ringkas. Dengan data kosong/satu halaman, kontrol tidak menyesatkan.
3. Cari teks dengan kapital berbeda, gabungkan filter yang tersedia, urutkan header yang dapat diurutkan, lalu reset. Filter/search/sort berlaku pada seluruh dataset, bukan halaman sekarang. Harga course diurutkan berdasarkan harga setelah diskon.
4. Pada halaman 2+, jalankan aksi di matriks. Modal menampilkan record yang diklik; tooltip/icon berfungsi setelah redraw. Tutup dan buka modal lain, pastikan tidak ada backdrop tertinggal.
5. Ubah course lewat modal halaman 2, gunakan Lexical, kirim field invalid: modal yang benar terbuka kembali dan pesan validasi muncul. Ulangi pada kategori/user/enrollment.
6. Pada masa berlaku anggota, jam mengikuti browser; simpan dengan alasan valid dan periksa hasilnya. Pembukaan modal baru tidak mengulang konversi input.
7. Hapus baris terakhir pada halaman terakhir memakai data uji: daftar kembali ke halaman yang tersedia. Setelah refresh, filter dan posisi halaman tetap masuk akal.
8. Cek modal konfirmasi moderasi/delete, aksi notifikasi dan invoice dari halaman 2. Pengiriman reset password hanya gunakan akun/mail pengujian.
9. Login member berbeda: transaksi dan hitungan total tidak boleh mencakup akun lain. Member tidak boleh mengakses endpoint JSON/modal admin; request tanpa sesi harus ditolak.
10. Putuskan koneksi sementara, ubah halaman: tampil pesan gagal dan tombol coba lagi. Sambungkan ulang dan coba lagi. Cek light/dark, lebar 375/768/1024/desktop, tab keyboard, overflow tabel dan pagination.

## Batasan

Performa aktual, kompatibilitas plugin vendor, dan seluruh interaksi runtime masih perlu dikonfirmasi lewat UAT. Tidak ada klaim bebas bug sebelum hasil pengujian. Dataset besar tetap dapat memerlukan optimasi index/pencarian berdasarkan profil query nyata. Halaman dengan daftar kartu (misalnya notifikasi member/katalog) tetap menggunakan pagination yang sudah ada.

## Tambahan: log aktivitas operasi admin

`/admin/operations` kini juga memakai Yajra server-side untuk log aktivitas (total cakupan menjadi 10 daftar). Daftar log sebelumnya berupa kartu sehingga tidak termasuk audit elemen tabel awal. Filter `action`, `table`, dan `q` diproses di SQL, ukuran halaman 10/25/50/100, urutan default waktu terbaru dengan ID sebagai pemecah seri. Respons hanya memuat sel waktu/aksi/pelaku/sumber/keterangan; snapshot old/new tidak disertakan. Detail perangkat pemutaran tetap dapat dibuka melalui elemen `details`, termasuk setelah redraw. Permintaan JSON log dikembalikan sebelum query ringkasan tugas/digest dijalankan.

Pencarian `q` mencakup kode dan label aksi, nama atau email pelaku, kode dan label sumber, ID record, serta keterangan. Kata “Sistem” juga menemukan log tanpa pelaku. Waktu tidak dicari sebagai teks karena tampilannya mengikuti zona waktu browser.

UAT tambahan: buka Tugas & log, pindah halaman 2 dan terakhir, pastikan nomor halaman memakai elipsis; gabungkan filter aksi dan sumber dengan pencarian ID/keterangan, ubah ukuran halaman, reset, dan buka detail perangkat pada baris halaman 2. Periksa jam lokal dan tema terang/gelap. Tidak ada perubahan `.env` atau migration; pengujian diserahkan kepada user sesuai permintaan sebelumnya.

## Pembaruan area tabel — 19 Oktober 2026

Kesepuluh daftar di atas memakai `x-server-table` yang sama. Pagination, pengurutan, ukuran halaman, pencarian, dan filter mengirim permintaan DataTables ke endpoint JSON khusus. Respons mengganti isi tabel dan pagination saja; ringkasan dashboard, status tugas, header, sidebar, dan formulir di sekitarnya tidak dimuat ulang. Interaksi tabel tidak mengubah URL halaman di address bar.

Saat permintaan berjalan, area tabel diberi status sibuk dan aksi pada baris lama dihentikan sementara. Loader memakai ikon buku Namikulo dengan animasi halaman bergerak, tampil setelah jeda singkat agar permintaan cepat tidak berkedip, dan memenuhi `prefers-reduced-motion`. Kegagalan memuat menampilkan tombol coba lagi pada area tabel. Respons yang datang terlambat ditangani oleh nomor `draw` DataTables. Tiap endpoint JSON tetap memeriksa scope/otorisasi masing-masing.

Asset DataTables CSS/JS disimpan lokal di `public/vendor/datatables/`, dengan versi bundle Bootstrap 4 yang sama seperti aset Otika sebelumnya. Layout admin/member memuatnya secara eksplisit hanya pada route tabel. Jika plugin gagal diinisialisasi, halaman menampilkan pesan kesalahan. Kontrol tabel tidak mempunyai aksi navigasi HTML, sehingga tidak memuat ulang halaman ketika JavaScript bermasalah.

Atribut konfigurasi aplikasi pada elemen `<table>` memakai awalan `data-nc-*`. DataTables membaca `data-*` tabel sebagai opsi inisialisasi; nama umum seperti `data-columns` dapat menimpa opsi `columns` dan menggagalkan inisialisasi sebelum permintaan JSON terkirim.

UAT manual lanjutan: pada setiap daftar, ubah halaman, urutkan, ketik pencarian, pilih filter, reset, dan ubah ukuran halaman sambil memperhatikan Network. Setiap interaksi hanya boleh memanggil GET JSON ke endpoint `/data` pada matriks di atas; tidak boleh ada permintaan dokumen HTML atau perubahan address bar. Pastikan loader berada di atas tabel, berhenti saat berhasil/gagal, baris lama tidak dapat diklik selama memuat, aksi pada halaman berikut tetap bekerja, dan halaman lain di luar tabel tidak berkedip. Ulangi pada koneksi lambat, saat permintaan beruntun, mode gelap/terang, serta pengaturan reduced motion. Agent tidak menjalankan pengujian browser sesuai permintaan user.
