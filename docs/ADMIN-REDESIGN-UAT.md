# UAT — Redesign panel admin

Scope: tampilan seluruh `/admin/*`, tanpa perubahan controller, endpoint, database, atau aturan bisnis. Pengujian browser dan test runner tidak dijalankan agent sesuai permintaan user. Pemeriksaan statis `git diff --check` dilakukan; tampilan akhir memerlukan pemeriksaan manual berikut.

## Persiapan

Tidak ada perintah instalasi, build, migration, atau perubahan `.env`. Gunakan aplikasi yang sedang berjalan dan akun admin. Jika aset lama masih tersimpan browser, lakukan hard refresh.

## Skenario manual

| Area | Langkah | Hasil yang diharapkan |
|---|---|---|
| Shell | Buka setiap menu admin, lalu ganti light/dark | Active menu sesuai halaman; breadcrumb dan deskripsi tepat; semua surface, teks, border, dan kontrol terbaca |
| Navigasi | Buka popover notifikasi, profil, dan navigasikan dengan Tab | Popover tidak tertutup konten; fokus terlihat; tautan dan logout tetap berfungsi |
| Dashboard | Periksa metrik, pesanan, status video, dan pintasan | Angka asli terbaca; tidak ada data ilustratif; pintasan menuju halaman terkait |
| Daftar data | Buka kategori, course, anggota, diskusi, notifikasi, pesanan, user, log | Filter dan tabel konsisten; teks panjang tidak merusak layout; badge berbeda sesuai status |
| AJAX tabel | Ketik pencarian, ubah filter/show entries, reset, pindah halaman | Hanya tabel dimuat dengan loader; halaman tidak berpindah; pagination tetap ringkas |
| Aksi tabel | Hover/fokus ikon dan buka aksi pada halaman kedua | Tooltip sesuai fungsi; tombol/modal tetap mengarah ke record yang benar |
| Form dan modal | Buka tambah/ubah kategori, course, user, anggota; coba validasi kosong | Label, input, error, tombol, dan backdrop terbaca dalam dua tema; modal bisa ditutup |
| Rich text | Buka editor course/bab/materi, format teks dan tautan | Toolbar dan area tulis terbaca di kedua tema; format tersimpan tetap sesuai input |
| Kurikulum | Expand bab, buka menu, tambah materi, ubah urutan | Hierarki bab/materi jelas; semua aksi dan autosave urutan tetap bekerja |
| Video | Buka halaman persiapan video | Tahapan, informasi file, progres, dan pesan status terbaca; tombol tetap bisa dioperasikan |
| Pesanan | Buka detail pesanan | Label/nilai, status, metadata pembayaran, serta tombol tindak lanjut tidak bertabrakan |
| Operasi | Buka tugas/log, expand detail perangkat, cari/filter log | Status task mudah dikenali; metadata membungkus; expand tetap beranimasi dan tabel tetap AJAX |
| Responsif | Periksa lebar 375, 768, 1024, dan 1440px | Sidebar menjadi drawer pada layar kecil; filter membungkus; tabel scroll di dalam container; tidak ada overflow halaman |
| Aksesibilitas | Aktifkan reduced motion, gunakan keyboard | Fokus terlihat; efek gerak berkurang; kontrol dapat dicapai dengan keyboard |
| Isolasi | Buka area member, landing, dan login | Stylesheet admin baru tidak dimuat pada area tersebut |

## File implementasi

- `public/css/admin-workspace.css`
- `public/css/admin-workspace-data.css`
- `resources/views/layouts/admin.blade.php`
- `resources/views/components/page-header.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/dashboard/sidebar.blade.php`
- `resources/views/admin/dashboard/workspace-links.blade.php`
- `resources/views/admin/operations/_task-card.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `design.md`

Asumsi: scope admin mencakup halaman turunan course dan detail pesanan; pratinjau publik/player tetap memakai layout asalnya. Kelulusan visual dan perilaku interaksi menunggu UAT user.

## Refinement tepi tabel

- Periksa sudut kiri/kanan header dan baris terakhir dalam kedua tema: tidak ada gutter putih, border ganda, atau radius sel di dalam container.
- Periksa tabel kosong, satu baris, dan banyak baris: zebra/hover mengisi lebar penuh dengan separator tunggal.
- Pada layar kecil, scroll horizontal sampai ujung kiri/kanan; seluruh kolom tetap dapat dijangkau.
- Hover/fokus tooltip aksi di baris pertama/terakhir, buka modal pada halaman kedua, dan expand detail perangkat log: tooltip tidak terpotong dan detail tetap memperbesar tinggi baris.
- Cari, filter, sort, reset, dan pindah pagination: hanya data tabel dimuat ulang, dengan frame yang tetap konsisten.

## Activity Log adaptif — hasil pengujian agent

Chrome DevTools MCP pada aplikasi lokal `http://127.0.0.1:8000/admin/operations`:

- 1440px, light dan dark: lima kolom terlihat; lebar tabel 1080px; tidak ada overflow halaman.
- 1024px, light dan dark: area tabel 664px, tiga kolom utama terlihat; sumber/keterangan dapat diekspansi.
- 768px dan 375px, kedua tema: area tabel masing-masing 668px dan 303px; tabel pas dengan container tanpa horizontal overflow; ekspansi berfungsi.
- Pindah pagination lalu expand/collapse: berhasil. Filter `playback_started` dan buka detail perangkat di expanded row: berhasil. URL halaman tidak berubah. Reset filter dijalankan setelah pemeriksaan.
- Console error/warning: tidak ditemukan saat pemeriksaan.
- Pengujian terbatas ke layout DOM/computed styles dan interaksi tabel Activity Log; tidak menguji seluruh aplikasi atau memodifikasi record bisnis.

Konfigurasi khusus: `adaptive="audit"` pada `x-server-table`. Breakpoint menggunakan lebar container 850px, bukan lebar layar. AutoWidth dan responsive extension bawaan hanya dinonaktifkan untuk konfigurasi ini; ResizeObserver mengatur kolom 3/4 dan tombol detail keyboard-accessible. Tabel lain mempertahankan konfigurasi sebelumnya. Keterangan panjang dibungkus tanpa memangkas data.

UAT lanjutan: periksa log dengan keterangan sangat panjang, keyboard Tab/Enter pada tombol expand, dan perubahan ukuran layar ketika detail terbuka. Semua informasi harus tetap dapat dibaca, dan detail perangkat terpisah dari tombol expand sumber/keterangan.
