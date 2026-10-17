# Otika Admin Template — Design & Usage Guide

Panduan lengkap penggunaan template Otika sebagai dasar aplikasi admin/dashboard. Template ini berupa HTML statis berbasis Bootstrap 4, jQuery, CSS Otika, Feather Icons, Font Awesome, dan plugin JavaScript per halaman.

> Asset sudah di-deploy. Semua contoh memakai `ASSET_URL`; jangan menyalin folder asset lokal apabila asset publik sudah tersedia.
>
> **Catatan proyek Platform Course:** bagian 1–18 adalah panduan template Otika. Bagian **19** menambahkan layout, token desain, dan spesifikasi halaman khusus proyek ini (landing, katalog, checkout custom, player). Bila ada konflik, bagian 19 dan `AGENTS.md` yang berlaku. Seluruh frontend memakai Blade murni (tanpa Vue/React/Alpine/Livewire).
>
> Font Awesome pada CSS Otika memuat berkas font dari host aset. Host tersebut belum mengirim header CORS untuk origin pengembangan lokal. Pada halaman proyek, gunakan ikon Feather atau kontrol teks sampai CORS di host aset dikonfigurasi; jangan menambahkan kelas `fas`/`far` yang memicu permintaan font lintas origin.
>
> Font Nunito untuk shell admin/member disimpan di `public/fonts/nunito` dan dipanggil lewat `public/css/app-custom.css` dari origin aplikasi. CSS Otika tetap dimuat dari `ASSET_URL`; deklarasi font lokal mencegah permintaan font Nunito lintas origin.

> Font ikon Summernote yang dirujuk oleh `bundles/summernote/summernote-bs4.css` juga harus berasal dari origin aplikasi. Berkas WOFF/TTF ada di `public/fonts/summernote`, dan `public/css/content-admin.css` mendefinisikan ulang `@font-face` setelah CSS Summernote. Untuk plugin Otika lain yang memakai font relatif, periksa permintaan font di browser sebelum dipakai; host aset eksternal belum menyediakan CORS font untuk origin lokal.

## 1. Arsitektur template

Jenis halaman:

- **Application page**: navbar, sidebar, konten, setting panel, footer.
- **Authentication page**: login, register, forgot password, reset password tanpa sidebar.
- **Utility page**: blank, empty state, dan error 403/404/500/503.

Struktur application page:

```text
#app
└── .main-wrapper.main-wrapper-1
    ├── .navbar-bg
    ├── nav.main-navbar
    ├── .main-sidebar > #sidebar-wrapper
    ├── .main-content
    │   └── section.section > .section-body
    ├── .settingSidebar (opsional)
    └── footer.main-footer
```

Gunakan `blank.html` sebagai starting point halaman baru.

## 2. Asset URL

Definisikan satu base URL:

```js
const ASSET_URL = 'https://otika.namikulo.com/assets';
```

Jika memakai framework, bungkus dengan helper asset agar URL dapat diganti dari konfigurasi deployment.

| Kebutuhan | Path |
|---|---|
| CSS vendor/global | `css/app.min.css` |
| CSS layout | `css/style.css` |
| CSS komponen | `css/components.css` |
| CSS override | `css/custom.css` |
| JS vendor/global | `js/app.min.js` |
| Perilaku template | `js/scripts.js` |
| JS override aplikasi | `js/custom.js` |
| Page-specific JS | `js/page/*.js` |
| Logo | `img/logo.png` |
| Avatar default | `img/user.png`, `img/users/user-*.png` |
| Favicon | `img/favicon.ico` |

Contoh `head` minimum:

```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
<title>Nama Halaman</title>
<link rel="stylesheet" href="ASSET_URL/css/app.min.css">
<link rel="stylesheet" href="ASSET_URL/css/style.css">
<link rel="stylesheet" href="ASSET_URL/css/components.css">
<link rel="stylesheet" href="ASSET_URL/css/custom.css">
<link rel="shortcut icon" href="ASSET_URL/img/favicon.ico">
```

Pada Blade, ganti `ASSET_URL/...` dengan `{{ asset('...') }}` (mengikuti `ASSET_URL`). Ini hanya untuk asset **Otika**. File milik aplikasi (`public/css/app-custom.css`, `public/css/site.css`, `public/js/app-custom.js`, upload) memakai `{{ url('...') }}` agar tidak ikut berpindah ke host Otika.

## 3. Layout application

### Wrapper dan loader

```html
<body>
  <div class="loader"></div>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <!-- navbar, sidebar, main-content, footer -->
    </div>
  </div>
</body>
```

`scripts.js` menyembunyikan `.loader` setelah halaman selesai dimuat.

### Navbar

```html
<nav class="navbar navbar-expand-lg main-navbar sticky">
  <div class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg collapse-btn">
        <i data-feather="align-justify"></i></a></li>
      <li><a href="#" class="nav-link nav-link-lg fullscreen-btn">
        <i data-feather="maximize"></i></a></li>
      <li>
        <form class="form-inline mr-auto">
          <div class="search-element">
            <input class="form-control" type="search" placeholder="Search" aria-label="Search">
            <button class="btn" type="submit"><i class="fas fa-search"></i></button>
          </div>
        </form>
      </li>
    </ul>
  </div>
  <!-- message-toggle, notification-toggle, user dropdown -->
</nav>
```

Pertahankan selector `data-toggle="sidebar"` dan `.fullscreen-btn` karena event handler global menggunakannya. Dropdown menggunakan atribut Bootstrap `data-toggle="dropdown"`.

### Sidebar

```html
<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="/dashboard">
        <img src="ASSET_URL/img/logo.png" alt="Nama aplikasi" class="header-logo">
        <span class="logo-name">Nama Aplikasi</span>
      </a>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-header">MAIN</li>
      <li class="dropdown active">
        <a href="/dashboard" class="nav-link">
          <i data-feather="monitor"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="dropdown">
        <a href="#" class="menu-toggle nav-link has-dropdown">
          <i data-feather="users"></i><span>Users</span>
        </a>
        <ul class="dropdown-menu">
          <li><a class="nav-link" href="/admin/users">Semua Pengguna</a></li>
          {{-- Tidak ada menu "Tambah": create/edit memakai modal di halaman index. --}}
        </ul>
      </li>
    </ul>
  </aside>
</div>
```

Aturan:

- Tambahkan `.active` pada `li` halaman saat ini.
- Menu anak memakai `.menu-toggle.nav-link.has-dropdown` dan child `.dropdown-menu`.
- Menu aktif otomatis dibuka oleh `scripts.js`.
- Ikon Feather memakai `data-feather="nama-icon"`.
- Struktur dapat diulang untuk multilevel menu.

### Konten dan footer

```html
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Judul Halaman</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="/dashboard">Dashboard</a></div>
        <div class="breadcrumb-item active">Judul Halaman</div>
      </div>
    </div>
    <div class="section-body">
      <!-- grid dan card -->
    </div>
  </section>
</div>

<footer class="main-footer">
  <div class="footer-left">Copyright &copy; 2026 Nama Aplikasi</div>
  <div class="footer-right">v1.0.0</div>
</footer>
```

Gunakan `.row` dan `.col-12 col-md-* col-lg-*` di dalam `.section-body`.

## 4. Card dan dashboard

```html
<div class="card">
  <div class="card-header">
    <h4>Judul Card</h4>
    <div class="card-header-action">
      <a href="#" class="btn btn-primary">Action</a>
    </div>
  </div>
  <div class="card-body">Isi card.</div>
  <div class="card-footer text-right">
    <button class="btn btn-light">Batal</button>
    <button class="btn btn-primary">Simpan</button>
  </div>
</div>
```

Variasi:

- `.card-primary`, `.card-secondary`, `.card-success`, `.card-danger`, `.card-warning`, `.card-info`.
- `.card-statistic-1` sampai `.card-statistic-4` untuk KPI.
- `.card-header-action` untuk action di header.
- `.sortable-card` untuk card sortable; handle default adalah `.card-header`.
- `widget-chart.html` dan `widget-data.html` untuk contoh widget siap pakai.

Contoh KPI:

```html
<div class="col-lg-3 col-md-6 col-12">
  <div class="card card-statistic-1">
    <div class="card-icon bg-primary"><i class="fas fa-shopping-cart"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Total Order</h4></div>
      <div class="card-body">1,287</div>
    </div>
  </div>
</div>
```

## 5. Komponen UI

### Typography, badge, button

```html
<h1>Heading</h1>
<p class="text-muted">Keterangan tambahan</p>
<span class="badge badge-success">Active</span>
<button class="btn btn-primary">Primary</button>
<button class="btn btn-outline-primary">Outline</button>
<button class="btn btn-success btn-icon icon-left">
  <i class="fas fa-check"></i> Simpan
</button>
```

Button mendukung `.btn-sm`, `.btn-lg`, `.btn-block`, `.btn-icon`, `.icon-left`, dan `.icon-right`.

### Alert, dropdown, tooltip, popover

```html
<div class="alert alert-success alert-has-icon">
  <div class="alert-icon"><i class="far fa-lightbulb"></i></div>
  <div class="alert-body">Data berhasil disimpan.</div>
</div>

<div class="dropdown">
  <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown">Actions</button>
  <div class="dropdown-menu">
    <a class="dropdown-item" href="/edit">Edit</a>
    <a class="dropdown-item text-danger" href="/delete">Delete</a>
  </div>
</div>

<button class="btn btn-light" data-toggle="tooltip" title="Informasi">?</button>
<button class="btn btn-light" data-toggle="popover" data-content="Detail tambahan">Info</button>
```

Tooltip dan popover diinisialisasi oleh `scripts.js`.

### Tabs, collapse, modal

Gunakan markup Bootstrap standar:

```html
<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#overview">Overview</a></li>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#activity">Activity</a></li>
</ul>
<div class="tab-content card-body">
  <div class="tab-pane fade show active" id="overview">...</div>
  <div class="tab-pane fade" id="activity">...</div>
</div>
```

Modal menggunakan `.modal`, `.modal-dialog`, `.modal-content`, `.modal-header`, `.modal-body`, dan `.modal-footer`. Collapse memakai `data-toggle="collapse"`, `href/data-target`, dan `aria-expanded`.

Untuk layout Admin, render semua modal melalui `@push('modals')` pada view. `layouts/admin.blade.php` merender `@stack('modals')` sebagai anak langsung `<body>`, di luar `#app`, `.main-wrapper`, dan `.main-content`. Bootstrap menaruh backdrop sebagai anak `<body>`; modal yang berada di dalam shell Otika dapat tertahan oleh stacking context shell sehingga backdrop menggelapkan modal dan memblokir klik. Jangan mengatasi kasus ini dengan menaikkan `z-index` modal atau backdrop secara global. Saat membuat modal baru, periksa bahwa dialog tetap terang, bisa difokuskan dan diklik, serta backdrop hanya menggelapkan halaman di belakangnya.

### Avatar, media, progress, list, empty state

```html
<img src="ASSET_URL/img/users/user-1.png" alt="Nama pengguna"
     class="rounded-circle" width="45">

<div class="media">
  <img class="mr-3 rounded-circle" src="ASSET_URL/img/users/user-2.png" width="45" alt="">
  <div class="media-body"><h6>Nama</h6><p>Deskripsi.</p></div>
</div>

<div class="progress">
  <div class="progress-bar bg-primary" style="width:72%" role="progressbar"
       aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">72%</div>
</div>
```

Komponen khusus tersedia melalui `.profile-widget`, `.author-box`, `.avatar-item`, `.list-group`, dan pola dari `empty-state.html`.

## 6. Form dan input plugin

### Form dasar

```html
<form method="POST" action="/users" class="needs-validation" novalidate>
  <div class="form-group">
    <label for="name">Nama</label>
    <input id="name" name="name" class="form-control" required>
    <div class="invalid-feedback">Nama wajib diisi.</div>
  </div>
  <div class="form-group">
    <label for="role">Role</label>
    <select id="role" name="role" class="form-control">
      <option value="admin">Admin</option>
      <option value="staff">Staff</option>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">Simpan</button>
</form>
```

Setiap control harus punya label, `id`, name, dan pesan error. Validasi server tetap wajib.

### Class input yang sudah dihubungkan ke scripts.js

| Class | Fungsi | Konfigurasi bawaan |
|---|---|---|
| `.select2` | searchable select | Select2 otomatis |
| `.selectric` | styled select | Selectric otomatis |
| `.datepicker` | single date | `YYYY-MM-DD` |
| `.datetimepicker` | date + time | `YYYY-MM-DD hh:mm`, 24 jam |
| `.daterange` | rentang tanggal | `YYYY-MM-DD` |
| `.timepicker` | pemilih waktu | ikon chevron |
| `.summernote` | rich editor | min-height 250px |
| `.summernote-simple` | editor ringkas | min-height 150px |
| `.dropzone` | multiple upload | lihat `multiple-upload.html` |
| `.image-preview` | preview gambar | lihat `forms-advanced-form.html` |

Contoh:

```html
<input class="form-control datepicker" name="published_at" autocomplete="off">
<input class="form-control datetimepicker" name="scheduled_at" autocomplete="off">
<input class="form-control daterange" name="period" autocomplete="off">
```

Select-all pada table memakai:

```html
<input type="checkbox" data-checkboxes="items" data-checkbox-role="dad">
<input type="checkbox" data-checkboxes="items">
<input type="checkbox" data-checkboxes="items">
```

## 7. Table

Table dasar:

```html
<div class="table-responsive">
  <table class="table table-striped" id="users-table">
    <thead><tr><th>#</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      <tr><td>1</td><td>Sarah</td><td><span class="badge badge-success">Active</span></td>
        <td><a href="/users/1" class="btn btn-sm btn-primary">Detail</a></td></tr>
    </tbody>
  </table>
</div>
```

DataTables:

```html
<link rel="stylesheet" href="ASSET_URL/bundles/datatables/datatables.min.css">
<script src="ASSET_URL/bundles/datatables/datatables.min.js"></script>
<script src="ASSET_URL/js/page/datatables.js"></script>
<script>
  $('#users-table').DataTable({
    pageLength: 10,
    order: [[1, 'asc']],
    responsive: true
  });
</script>
```

Untuk export gunakan `dom: 'Bfrtip'` dan button `copy/csv/excel/pdf/print` seperti `export-table.html`. Untuk tabel lebar gunakan `.table-responsive` atau `scrollX: true`. Hindari inisialisasi DataTables dua kali pada element yang sama.

Editable table memakai `bundles/editable-table/mindmup-editabletable.js`; lihat `editable-table.html`.

## 8. Chart

| Jenis | Halaman | Library | Page script |
|---|---|---|---|
| ApexCharts | `chart-apexchart.html` | `bundles/apexcharts/apexcharts.min.js` | `page/chart-apexcharts.js` |
| Chart.js | `chart-chartjs.html` | `bundles/chartjs/chart.min.js` | `page/chart-chartjs.js` |
| ECharts | `chart-echart.html` | `bundles/echart/echarts.js` | `page/chart-echarts.js` |
| Morris | `chart-morris.html` | `bundles/morris/*` | `page/chart-morris.js` |
| AmCharts 4 | `chart-amchart.html` | `bundles/amcharts4/*` | `page/chart-amchart.js` |
| Sparkline | `chart-sparkline.html` | `bundles/jquery.sparkline.min.js` | `page/sparkline.js` |

Pola ApexCharts:

```html
<div id="sales-chart"></div>
<script src="ASSET_URL/bundles/apexcharts/apexcharts.min.js"></script>
<script>
new ApexCharts(document.querySelector('#sales-chart'), {
  chart: { type: 'line', height: 320 },
  series: [{ name: 'Sales', data: [12, 18, 15, 24] }],
  xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr'] }
}).render();
</script>
```

Container chart harus memiliki `id` unik. Muat hanya satu library chart jika tidak diperlukan library lain.

## 9. Halaman aplikasi siap pakai

| Kebutuhan | Referensi |
|---|---|
| Auth | `auth-login.html`, `auth-register.html`, `auth-forgot-password.html`, `auth-reset-password.html` |
| Email | `email-inbox.html`, `email-compose.html`, `email-read.html` |
| Chat | `chat.html` |
| Calendar | `calendar.html` |
| Blog/posts | `blog.html`, `posts.html`, `create-post.html` |
| Portfolio/gallery | `portfolio.html`, `gallery1.html`, `light-gallery.html` |
| Profile/invoice | `profile.html`, `invoice.html` |
| Pricing/timeline | `pricing.html`, `timeline.html` |
| Maps | `gmaps-*.html`, `vector-map.html` |
| Error | `errors-403.html`, `errors-404.html`, `errors-500.html`, `errors-503.html` |

Gunakan halaman paling dekat sebagai referensi markup dan dependency.

## 10. Maps, calendar, carousel, gallery

### Google Maps

Gunakan `bundles/gmaps.js` dan page script yang sesuai: `gmaps-simple.js`, `gmaps-marker.js`, `gmaps-multiple-marker.js`, `gmaps-route.js`, `gmaps-advanced-route.js`, `gmaps-geolocation.js`, `gmaps-geocoding.js`, atau `gmaps-draggable-marker.js`. API key jangan di-hard-code pada repository publik.

### FullCalendar

```html
<link rel="stylesheet" href="ASSET_URL/bundles/fullcalendar/fullcalendar.min.css">
<script src="ASSET_URL/bundles/fullcalendar/fullcalendar.min.js"></script>
<script src="ASSET_URL/js/page/calendar.js"></script>
```

Ganti data contoh dengan endpoint aplikasi.

### Carousel dan lightbox

Owl Carousel memakai CSS `owl.carousel.min.css`, `owl.theme.default.min.css`, JS carousel, dan `page/owl-carousel.js`. LightGallery dan Chocolat adalah dua pilihan lightbox; pilih satu pada satu halaman. Semua gambar harus memakai URL deployment.

## 11. Icon system

- Feather: `<i data-feather="home"></i>`, diproses oleh `feather.replace()`.
- Font Awesome: `fas fa-home`, `far fa-user`, `fab fa-twitter`.
- Ionicons: lihat `icon-ionicons.html`.
- Material Icons: lihat `icon-material.html`.
- Weather Icons: lihat `icon-weather-icon.html`.

Pastikan bundle icon yang sesuai dimuat sebelum dipakai.

## 12. Theme dan global behavior

`scripts.js` menyediakan loader, Feather, sidebar dropdown, sidebar mini, mobile overlay, sticky header, fullscreen, tooltip, popover, Select2, Selectric, date picker, time picker, Summernote, sortable card, dan setting panel.

Class body:

```html
<body class="light light-sidebar theme-white">
```

Tema warna: `theme-white`, `theme-cyan`, `theme-black`, `theme-purple`, `theme-orange`, `theme-green`, `theme-red`. Mode dark memakai `dark`; sidebar memakai `light-sidebar` atau `dark-sidebar`.

Setting panel menggunakan `.settingSidebar`, `.settingPanelToggle`, `#mini_sidebar_setting`, `#sticky_header_setting`, `.select-layout`, `.select-sidebar`, `.choose-theme`, dan `.btn-restore-theme`. Jika pilihan tema harus permanen, simpan ke `localStorage` atau backend dan terapkan class sebelum render.

## 13. Urutan dependency

```html
<script src="ASSET_URL/js/app.min.js"></script>
<script src="ASSET_URL/bundles/datatables/datatables.min.js"></script>
<script src="ASSET_URL/js/page/datatables.js"></script>
<script src="ASSET_URL/js/scripts.js"></script>
<script src="ASSET_URL/js/custom.js"></script>
```

Urutan: vendor/global, library khusus halaman, konfigurasi page, perilaku template, lalu override aplikasi. `custom.js` milik Otika berada di host asset dan tidak diedit. Kode aplikasi diletakkan di `public/js/app-custom.js` dan `public/js/pages/*.js` (dimuat via `url()`) setelah `scripts.js`. Jangan mengedit vendor atau `scripts.js`.

## 14. Responsive behavior

- Di atas 1024px, toggle sidebar mengubah sidebar menjadi mini.
- Pada atau di bawah 1024px, sidebar menjadi overlay.
- Gunakan `.table-responsive` untuk tabel lebar.
- Gunakan grid Bootstrap dan hindari fixed width.
- Pastikan chart, modal, dan dropdown tidak terpotong oleh parent `overflow: hidden`.
- Uji minimal pada 375px, 768px, 1024px, dan desktop.

## 15. Accessibility dan state

Semua waktu bertanggal yang berasal dari database diberi nilai UTC eksplisit pada elemen `<time datetime="...">` dan ditampilkan sesuai zona browser dengan JavaScript aplikasi. Jika JavaScript tidak tersedia, tampilkan UTC beserta labelnya. Input `datetime-local` dalam modal admin mengikuti zona perangkat admin; tampilkan petunjuk zona dan konversi ke momen UTC sebelum disimpan. Zona browser adalah zona perangkat, bukan bukti lokasi fisik.

- Setiap gambar memiliki `alt`; dekoratif memakai `alt=""`.
- Setiap form control memiliki label atau `aria-label`.
- Tombol aksi memakai `button`, navigasi memakai `a`.
- Pertahankan focus state keyboard.
- Gunakan `aria-expanded`, `aria-controls`, `role`, dan `aria-valuenow`.
- Sediakan loading, empty, success, error, dan permission state.
- Jangan tampilkan credential, API key, atau data dummy pada production.

## 16. Workflow halaman baru

1. Duplikasi `blank.html` atau halaman referensi terdekat.
2. Ubah title, brand, menu, route, breadcrumb, dan item aktif.
3. Ganti isi `.section-body` dengan grid dan card.
4. Tambahkan dependency plugin hanya jika dipakai.
5. Ganti seluruh path `assets/...` dengan `ASSET_URL/...` atau helper asset.
6. Hubungkan data/action ke backend melalui endpoint aplikasi.
7. Tambahkan validasi, loading, empty, permission, dan error state.
8. Uji mobile, desktop, keyboard, dan browser console.

## 17. Checklist production

- [ ] Semua CSS, JS, gambar, font, dan favicon memakai asset deployment URL.
- [ ] Tidak ada path relatif asset yang tertinggal.
- [ ] Title, viewport, bahasa dokumen, favicon, dan brand sudah benar.
- [ ] Semua link placeholder dan data dummy sudah diganti.
- [ ] Form memiliki validasi client/server dan CSRF protection.
- [ ] Plugin tidak dimuat ganda dan tidak ada inisialisasi ganda.
- [ ] Tema, sidebar mini, sticky header, dan fullscreen telah diuji.
- [ ] Table, modal, chart, dan form usable di mobile.
- [ ] Browser console bersih dari 404 asset dan JavaScript error.

## 18. Struktur deployment asset

```text
ASSET_URL/
├── css/
│   ├── app.min.css
│   ├── style.css
│   ├── components.css
│   └── custom.css
├── js/
│   ├── app.min.js
│   ├── scripts.js
│   ├── custom.js
│   └── page/*.js
├── bundles/
│   ├── apexcharts/ chartjs/ datatables/ fullcalendar/
│   ├── gmaps.js lightgallery/ morris/ owlcarousel2/
│   └── summernote/ sweetalert/ select2/ ...
├── img/
│   ├── logo.png user.png users/ products/ posts/
│   ├── image-gallery/ browsers/ cards/
└── fonts/
```

File HTML yang tersedia berfungsi sebagai katalog implementasi. Ambil markup dari halaman referensi, panggil asset melalui URL deployment, lalu sambungkan data dan aksi ke sistem aplikasi sendiri.


---

## 19. Extension proyek Platform Course

Bagian ini khusus proyek course berbayar. Template Otika adalah template **admin**, jadi area publik dan player memakai layout sendiri yang tetap berbasis Bootstrap 4 dari `app.min.css`. Semua dibangun dengan Blade murni.

### 19.1 Peta layout

| Layout | Shell | CSS dimuat | JS dimuat |
|---|---|---|---|
| `layouts/admin` | Otika penuh (`.main-wrapper`) | `app.min.css`, `style.css`, `components.css`, `app-custom.css` | `app.min.js`, plugin halaman, `scripts.js`, `app-custom.js` |
| `layouts/member` | Otika penuh, sidebar khusus member | sama dengan admin | sama dengan admin |
| `layouts/auth` | Pola `auth-login.html` Otika, tanpa sidebar | `app.min.css`, `style.css`, `components.css`, `site.css` | `app.min.js`, `scripts.js` |
| `layouts/public` | Navbar atas + konten + footer, tanpa `.main-wrapper` | `app.min.css`, `site.css` | `app.min.js` (jQuery + Bootstrap 4), `app-custom.js`, skrip halaman. **Tanpa** `scripts.js` |
| `layouts/player` | Fokus belajar, tanpa sidebar admin | `app.min.css`, `site.css` | `app.min.js`, `hls.js`, `pages/player.js`. **Tanpa** `scripts.js` |

Aturan: `style.css`/`components.css` Otika mengasumsikan struktur admin, jadi tidak dimuat di Public dan Player kecuali sebuah komponen terbukti memerlukannya.

### 19.2 Token desain (Public, Auth, Player)

Gunakan CSS variable di `public/css/site.css`. Warna primer disamakan dengan tema Otika agar admin dan publik terasa satu produk.

```css
:root {
  --c-primary: #6777ef;
  --c-primary-600: #4e5fd6;
  --c-primary-50: #eef0fe;
  --c-ink: #1b2437;
  --c-muted: #6b7690;
  --c-line: #e6e9f2;
  --c-surface: #ffffff;
  --c-bg: #f6f7fb;
  --c-success: #2ea66f;
  --c-warning: #f0a30a;
  --c-danger: #e5484d;
  --radius-sm: 8px;
  --radius-md: 14px;
  --radius-lg: 22px;
  --shadow-card: 0 1px 2px rgba(27,36,55,.06), 0 8px 24px rgba(27,36,55,.06);
  --shadow-lift: 0 2px 4px rgba(27,36,55,.08), 0 16px 36px rgba(27,36,55,.12);
  --font-body: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --font-display: "Plus Jakarta Sans", var(--font-body);
}
```

- Tipografi: satu font display untuk judul, satu font body. Boleh memakai Google Fonts atau self-host di `public/fonts`; harus ada fallback sistem.
- Skala jarak 4/8/12/16/24/32/48/72 px. Judul bagian publik memakai `clamp()` agar responsif.
- Kontras teks minimal WCAG AA. Fokus keyboard harus terlihat.
- Animasi halus (150–250 ms) dan wajib menghormati `prefers-reduced-motion`.
- Keputusan visual detail (komposisi hero, ilustrasi, ritme spacing) dikerjakan dengan skill **UIUX Promax**, lalu diperiksa dengan **antislop**, selama tetap mengikuti token dan aturan di atas.

### 19.3 Landing page (`/`)

Tujuan: membuat pengunjung paham nilai platform dalam 5 detik, melihat course unggulan, lalu masuk ke detail course atau mendaftar. Referensi isi: portal course dengan hero, langkah belajar, statistik, dan grid course. Hasil akhir harus lebih modern dan lebih mudah dipakai.

Urutan bagian:

1. **Navbar**: logo, Katalog, tombol Masuk dan Daftar (atau nama user + menu bila sudah login). Sticky, kecil saat scroll, menjadi hamburger di mobile.
2. **Hero**: judul nilai utama, satu kalimat penjelas, CTA utama "Lihat Course" dan CTA sekunder "Cara kerja". Sisi visual berupa kolase thumbnail course nyata atau ilustrasi ringan. Jangan memakai gambar stok generik.
3. **Bar kepercayaan**: jumlah peserta, jumlah course, jumlah video. Angka berasal dari database, bukan hardcode.
4. **Course unggulan**: grid `x-course-card` dari course `is_featured` dan `published`, urut `featured_order`. Maksimal 6–8 kartu dan tombol "Lihat semua course".
5. **Cara belajar (3–4 langkah)**: Daftar → Pilih & bayar → Belajar 6 bulan → Selesai. Teks singkat, ikon konsisten.
6. **Informasi akses**: penjelasan jelas bahwa sekali beli akses berlaku 6 bulan.
7. **CTA penutup** dan **footer** (tautan, kontak, hak cipta).

State: landing tetap bagus bila belum ada course unggulan (empty state ramah, bukan area kosong).

### 19.4 Kartu course (`x-course-card`)

- Thumbnail rasio 16:9, `object-fit: cover`, `loading="lazy"`, `alt` berisi judul course.
- Badge kategori, judul maksimal 2 baris (`line-clamp`), meta: jumlah bab, jumlah video, total durasi.
- Harga (dengan harga coret jika ada diskon) dan label "Akses 6 bulan".
- Seluruh kartu dapat diklik; hover menaikkan bayangan sedikit. Jika user sudah memiliki akses aktif, tampilkan label "Sedang diikuti" dan tautan ke area belajar.
- Skeleton/placeholder bila thumbnail kosong (gradien dari token, bukan gambar rusak).

### 19.5 Katalog dan detail course

- **Katalog** (`/courses`): pencarian judul, filter kategori, urutan (terbaru/populer/harga), pagination. Filter lewat query string agar dapat dibagikan.
- **Detail course** (`/courses/{slug}`): header (thumbnail, judul, ringkasan, harga, tombol Beli/Lanjutkan), tab atau bagian Deskripsi dan Kurikulum. Kurikulum berupa accordion Bootstrap 4: bab → daftar video + durasi. Video terkunci menampilkan ikon gembok bagi non-anggota. Kartu harga sticky di desktop dan bar harga sticky di bawah pada mobile.
- Status yang harus ditangani: belum login, login belum beli, akses aktif, akses kedaluwarsa (tampilkan tombol "Perpanjang/Beli lagi"), pembayaran tertunda (tautan ke instruksi bayar).

### 19.6 Checkout dan pembayaran custom (tanpa tampilan Midtrans)

Seluruh UI dibuat sendiri dengan layout Public. Midtrans hanya dipanggil dari server (Core API).

**Langkah 1 — Ringkasan & pilih metode** (`/checkout/{slug}`)
- Kolom kiri: ringkasan course (thumbnail, judul, "Akses 6 bulan sejak pembayaran", harga).
- Kolom kanan: pilihan metode sebagai kartu radio yang besar: Virtual Account (BCA, BNI, BRI, Permata, CIMB), QRIS, e-wallet (GoPay/ShopeePay) sesuai konfigurasi merchant. Logo bank dari `public/img/payment/`.
- Tombol "Bayar sekarang" memiliki loading state dan mencegah klik ganda.

**Langkah 2 — Instruksi bayar** (`/orders/{order}/pay`)
- Kepala: status (badge), nomor pesanan, nominal dengan tombol salin.
- Virtual Account: nama bank, nomor VA besar dengan tombol "Salin", nominal, batas waktu bayar, dan langkah bayar per kanal (accordion: ATM, mobile banking, internet banking).
- QRIS/e-wallet: gambar QR dari URL aksi Midtrans, tombol "Buka aplikasi" (deeplink) di mobile, petunjuk singkat.
- **Hitung mundur** batas waktu bayar (JS vanilla). Setelah habis, tampilkan state "Kedaluwarsa" dan tombol "Buat pesanan baru".
- **Polling status** ke `/orders/{order}/status` setiap 5 detik (hentikan saat tab tidak aktif). Polling hanya memperbarui tampilan; pemberian akses tetap oleh webhook di server.
- Saat berhasil: layar sukses dengan tanggal akses berakhir dan tombol "Mulai belajar".
- State lain: gagal, dibatalkan, kedaluwarsa, menunggu. Tombol "Ganti metode" (membatalkan percobaan pembayaran lama dan membuat percobaan baru pada pesanan yang sama sesuai SRS 5.6) dan "Batalkan pesanan".

Ketentuan UI: nominal selalu dalam format Rupiah (`Rp 1.250.000`), tidak ada tulisan "Midtrans" yang mengganggu selain info kepercayaan kecil di footer langkah pembayaran, dan seluruh teks berbahasa Indonesia.

### 19.7 Halaman belajar / Player (`layouts/player`)

Tata letak desktop: area video lebar (kiri) + panel kurikulum (kanan, dapat dilipat). Mobile: video di atas, kurikulum di bawah.

Komponen:
- Header ramping: tombol kembali ke course, judul video, progres.
- Pemutar: elemen `<video>` tanpa kontrol bawaan; kontrol custom (play/pause, seek, volume, kecepatan, layar penuh; pilihan kualitas hanya bila ada lebih dari satu rendition).
- Panel kurikulum: bab → video, penanda sedang diputar, penanda selesai, gembok bila tidak boleh.
- Tombol "Sebelumnya" dan "Berikutnya".
- Bagian deskripsi di bawah video.

**Elemen perlindungan di UI** (deterrent; lihat PRD bagian 7 untuk batasannya):
- Watermark dinamis: teks nama + email/ID user, sangat samar (sekitar 16% putih, ketebalan normal, tanpa bayangan), berpindah posisi tiap 20–30 detik di atas video. Jaga agar materi tetap mudah dibaca; identitas watermark dapat lebih sulit dilihat pada bagian video yang terang.
- `controlsList="nodownload noremoteplayback"`, `disablePictureInPicture`, blokir `contextmenu` pada area player, `user-select: none` di area materi.
- `@media print { body { display: none } }`.
- Saat tab tidak aktif atau jendela kehilangan fokus: video di-pause dan area di-blur, tampilkan pesan netral.
- Tombol `PrintScreen`/pintasan perekam yang umum: sembunyikan sementara area video (best effort).
- Pesan kesalahan ramah: sesi dipakai di perangkat lain, akses telah berakhir, video belum siap. Jangan menampilkan URL internal atau stack trace.

Jangan menjanjikan "tidak bisa direkam" di antarmuka. Tampilkan catatan netral: "Konten ini dilindungi. Penggunaan ulang tanpa izin dilarang."

### 19.8 Area member (`layouts/member`)

Sidebar: Dashboard, Course Saya, Pesanan, Profil. Dashboard menampilkan course aktif dengan progres, sisa waktu akses (badge, kuning bila <14 hari, merah bila <3 hari), dan rekomendasi course. Empty state mengarahkan ke katalog. Halaman Pesanan memakai tabel Otika dengan badge status dan tautan ke instruksi bayar untuk pesanan tertunda.

### 19.9 Admin: builder kurikulum

- Halaman listing kategori dan course memakai seluruh lebar `.section-body` yang tersedia. Hindari `max-width` tetap pada wrapper tabel; batasi lebar hanya pada halaman dengan konten bacaan atau builder yang membutuhkan fokus.
- Halaman daftar course memakai DataTables + modal create/edit. Field kaya (deskripsi) memakai Summernote di dalam modal besar (`modal-lg`) dan thumbnail memakai `image-preview`.
- Panel status course menampilkan **Terbitkan course** pada draf; tombol **Arsipkan course** hanya tampil saat status terbit. Aksi arsip juga divalidasi di server.
- Halaman **Kurikulum** (`admin.courses.curriculum`) berupa halaman kustom: daftar bab sebagai card; tiap card berisi daftar video (judul, durasi, status pemrosesan video) dan tombol Tambah Video. Tambah/ubah bab dan video memakai modal. Urutan dapat diubah (drag `sortable` Otika atau tombol naik/turun) dan disimpan via AJAX.
- Persiapan video menggunakan halaman kustom `admin.lessons.video.prepare` (GET `/admin/lessons/{lesson}/video/prepare`) dengan konteks course/bab/video; form CRUD metadata tetap modal. Halaman memeriksa browser, tanpa pilihan OS wajib. Tampilkan batas format/ukuran/durasi dari config sebelum memilih file.
- Tahap: Memeriksa browser → Memproses di perangkat → Mengunggah → Memvalidasi → Siap. Pisahkan progres tiap tahap, jangan membuat persen palsu jika engine tidak menyediakannya. Tampilkan pesan menjaga tab terbuka, tombol Batal/Coba lagi dan alasan browser/input tidak didukung.
- ffmpeg.wasm single-thread dimuat hanya di halaman ini, sumber tetap lokal, HLS terenkripsi satu kualitas diunggah bertahap. Refresh/tutup tab dapat mengharuskan proses ulang; jangan menjanjikan proses background atau resume otomatis setelah browser ditutup. Aset lama tetap aktif saat penggantinya disiapkan.
- State wajib: dependency gagal dimuat, browser tidak kompatibel, memori tidak cukup, format/ukuran tidak sesuai, koneksi terputus, sesi kedaluwarsa, validasi gagal, selesai/dibatalkan. Tidak ada tombol pemilih kualitas semu pada player satu kualitas.
- Halaman **Anggota Course** (`admin.courses.members`): tabel anggota dengan sumber (pembelian/manual), tanggal mulai, tanggal berakhir, status; aksi Tambah anggota (modal, cari user via select2), Perpanjang, Cabut akses.

### 19.10 State wajib di seluruh halaman baru

Loading, empty, success, error, permission (403), tidak ditemukan (404), kedaluwarsa (akses habis), dan batas laju (429). Halaman error `403/404/419/429/500/503` tersedia di `resources/views/errors`.
