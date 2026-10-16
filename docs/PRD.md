# PRD — Platform Course Berbayar

Versi 1.2 · Status: Draf; MVP cPanel dengan pemrosesan browser sesuai arahan user · Tanggal: 16 Oktober 2026

Dokumen ini menjelaskan **apa** yang dibangun dan **mengapa**. Detail teknis ada di [SRS.md](SRS.md). Urutan pengerjaan ada di [phases/README.md](phases/README.md).

---

## 1. Ringkasan

Platform untuk menjual dan menyelenggarakan course berbasis video. Admin membuat course yang tersusun dari beberapa bab, tiap bab berisi beberapa video. Pengunjung melihat course unggulan di landing page, membeli course lewat pembayaran online (Midtrans, tampilan custom), lalu belajar selama 6 bulan sejak pembayaran. Materi dilindungi agar tidak mudah diunduh atau disebarkan.

Referensi tampilan: portal course dengan hero, langkah belajar, statistik, dan grid course (gambar yang diberikan user). Target: tampilan lebih modern dan lebih mudah dipakai.

## 2. Tujuan

| ID | Tujuan |
|---|---|
| G1 | Admin dapat membuat dan mengelola course, bab, dan video tanpa bantuan developer. |
| G2 | Calon peserta dapat menemukan, memahami, dan membeli course dengan alur singkat. |
| G3 | Peserta hanya dapat mengakses course yang dibeli dan hanya selama masa aktif 6 bulan. |
| G4 | Konten video terlindungi semaksimal yang realistis untuk platform web (lihat bagian 7). |
| G5 | Pembayaran berjalan di UI milik sendiri, status bayar andal dan tanpa pemberian akses ganda atau salah. |

Ukuran keberhasilan (diukur setelah rilis, bukan syarat tiap phase): konversi dari halaman detail ke pesanan, rasio pesanan yang berstatus lunas, jumlah laporan akses salah (target nol), dan jumlah tiket "sudah bayar tapi tidak bisa akses" (target nol).

## 3. Pengguna dan role

| Role | Deskripsi | Kemampuan utama |
|---|---|---|
| **Pengunjung** (belum login) | Calon peserta | Melihat landing, katalog, detail course; mendaftar dan masuk. |
| **Member** | Peserta/anggota course | Membeli course, belajar pada course dengan akses aktif, melihat pesanan, mengelola profil. |
| **Admin** | Pengelola platform | Mengelola course, kurikulum, video, user, anggota course, dan memantau pesanan. |

Hanya dua role yang disimpan di database: `admin` dan `member`. Pengunjung adalah kondisi belum login.

## 4. Scope

### 4.1 Dalam scope (MVP)

1. Autentikasi: daftar, masuk, keluar, lupa/reset password.
2. Admin: CRUD kategori, course, bab, video; urutan; status publikasi; course unggulan.
3. Admin: kelola user (daftar, tambah, ubah, nonaktifkan, reset password).
4. Admin: kelola anggota di tiap course (tambah manual, perpanjang, cabut) dan lihat pesanan.
5. Landing page dengan course unggulan; katalog; detail course dengan kurikulum.
6. Pembelian course: 1 pesanan = 1 course, pembayaran Midtrans dengan UI custom (Virtual Account, QRIS, e-wallet).
7. Akses 6 bulan sejak pembayaran; pembatasan akses otomatis setelah berakhir.
8. Pemutar video terlindungi: tanpa unduhan langsung, tanpa URL file publik, watermark dinamis, deterrent screenshot.
9. Area member: course saya, progres menonton, pesanan, profil.
10. Dashboard admin dengan ringkasan angka utama.

### 4.2 Di luar scope (belum dikerjakan)

Keranjang belanja multi-course, kupon/voucher, afiliasi, sertifikat, kuis/ujian, forum/komentar, live class, aplikasi mobile native, pembayaran kartu kredit, langganan berulang otomatis, refund otomatis dari panel, multi-bahasa selain Indonesia, instruktur sebagai role terpisah, lampiran dokumen (PDF/slide) pada materi.

Item ini dapat ditambahkan setelah MVP dengan PRD revisi.

## 5. Kebutuhan fungsional

Prioritas: **M** = wajib MVP, **S** = sebaiknya ada, **C** = bisa ditunda.

### 5.1 Autentikasi dan akun

| ID | Kebutuhan | P |
|---|---|---|
| F-101 | Pengunjung dapat mendaftar (nama, email, password) dan otomatis menjadi member. | M |
| F-102 | User dapat masuk dan keluar; setelah masuk diarahkan sesuai role. | M |
| F-103 | Lupa dan reset password lewat email. | M |
| F-104 | User nonaktif tidak dapat masuk. | M |
| F-105 | Member dapat mengubah profil dan password. | S |

### 5.2 Manajemen course (admin)

| ID | Kebutuhan | P |
|---|---|---|
| F-201 | CRUD kategori course. | S |
| F-202 | CRUD course: judul, slug, ringkasan, deskripsi, thumbnail, harga, harga diskon (opsional), kategori, nama pengajar, status (draft/terbit/arsip). | M |
| F-203 | Tandai course sebagai unggulan dan atur urutan tampil di landing. | M |
| F-204 | CRUD **bab** dalam sebuah course (mis. Bab I, Bab II) dengan urutan. | M |
| F-205 | CRUD **video** dalam sebuah bab, jumlah tidak dibatasi (mis. Bab I berisi 4 video, Bab II berisi 5 video). Atribut: judul, deskripsi, urutan, durasi, file video. | M |
| F-206 | Atur ulang urutan bab dan video. | M |
| F-207 | Pilih video; browser admin memprosesnya dengan ffmpeg.wasm menjadi HLS terenkripsi satu kualitas, lalu mengunggah hasilnya. Tampilkan progres persiapan/upload/validasi serta state siap/gagal dan coba lagi. Tidak membutuhkan worker server. | M |
| F-208 | Course hanya dapat terbit jika memiliki minimal satu video berstatus siap. | S |
| F-209 | Hapus course/bab/video yang sudah punya anggota atau pesanan dilakukan sebagai arsip (soft delete), bukan penghapusan permanen. | M |

### 5.3 Manajemen user dan anggota (admin)

| ID | Kebutuhan | P |
|---|---|---|
| F-301 | Daftar user dengan pencarian dan filter role/status. | M |
| F-302 | Tambah, ubah, nonaktifkan/aktifkan user; reset password. | M |
| F-303 | Per course: daftar anggota beserta sumber (pembelian/manual), tanggal mulai, tanggal berakhir, status. | M |
| F-304 | Tambah anggota secara manual ke course (akses 6 bulan sejak tanggal diberikan, dapat diubah admin), tanpa pembayaran. | M |
| F-305 | Perpanjang atau ubah tanggal berakhir akses; cabut akses. | M |
| F-306 | Daftar pesanan dengan filter status dan pencarian, serta detail pembayaran. | M |
| F-307 | Tombol sinkron status pesanan ke Midtrans untuk kasus webhook terlambat. | S |

### 5.4 Situs publik

| ID | Kebutuhan | P |
|---|---|---|
| F-401 | Landing page: hero, bar angka, course unggulan, cara belajar, informasi akses 6 bulan, CTA. | M |
| F-402 | Katalog course dengan pencarian, filter kategori, dan urutan. | M |
| F-403 | Detail course dengan kurikulum (bab → video → durasi) dan harga. Isi video tidak dapat diputar tanpa akses. | M |
| F-404 | Tampilan responsif, cepat, dan konsisten dengan identitas visual. | M |

### 5.5 Pembelian dan pembayaran

| ID | Kebutuhan | P |
|---|---|---|
| F-501 | Member membuat pesanan dari halaman detail; harus sudah login. | M |
| F-502 | Pilih metode pembayaran di UI custom: Virtual Account (BCA, BNI, BRI, Permata, CIMB), QRIS, e-wallet (GoPay/ShopeePay) sesuai yang aktif di akun merchant. | M |
| F-503 | Halaman instruksi bayar: nomor VA/QR, nominal, hitung mundur, petunjuk langkah, tombol salin. | M |
| F-504 | Status pesanan diperbarui otomatis (polling UI) dan benar-benar ditentukan oleh webhook Midtrans. | M |
| F-505 | Setelah lunas, akses aktif otomatis selama 6 bulan sejak waktu pembayaran. | M |
| F-506 | Pesanan kedaluwarsa otomatis bila lewat batas bayar; pesanan dapat dibatalkan atau metode diganti. | M |
| F-507 | Member dapat melihat riwayat pesanan dan membuka kembali instruksi bayar yang masih berlaku. | M |
| F-508 | Notifikasi email saat pembayaran berhasil. | S |
| F-509 | Pengingat email saat akses tersisa 7 hari. | C |

### 5.6 Belajar

| ID | Kebutuhan | P |
|---|---|---|
| F-601 | Member melihat daftar course aktif dan yang kedaluwarsa, beserta sisa masa akses. | M |
| F-602 | Halaman belajar: pemutar video, kurikulum, navigasi sebelumnya/berikutnya. | M |
| F-603 | Akses ditolak otomatis bila enrollment tidak aktif atau sudah lewat 6 bulan, termasuk saat video sedang diputar. | M |
| F-604 | Simpan posisi terakhir dan tandai video selesai; tampilkan persentase progres. | S |
| F-605 | Course yang sudah berakhir dapat dibeli lagi untuk periode 6 bulan berikutnya. | M |

### 5.7 Perlindungan konten

| ID | Kebutuhan | P |
|---|---|---|
| F-701 | Tidak ada URL file video yang dapat dibuka langsung atau disimpan lewat menu browser. | M |
| F-702 | Video disajikan terenkripsi dan hanya melalui token yang terikat user dan sesi. | M |
| F-703 | Watermark dinamis (nama + email/ID) di atas video. | M |
| F-704 | Deterrent sisi klien: blokir klik kanan, tanpa tombol unduh, pause dan blur saat tab tidak aktif, sembunyikan saat print. | M |
| F-705 | Satu sesi pemutaran aktif per user. | M |
| F-706 | Log sesi pemutaran untuk penelusuran penyalahgunaan. | S |

## 6. Aturan bisnis

| ID | Aturan |
|---|---|
| BR-01 | Setiap course harus dibeli untuk diakses. Tidak ada akses gratis kecuali diberikan admin secara manual. |
| BR-02 | Satu pembelian memberi akses **6 bulan** sejak `paid_at` (waktu pembayaran terkonfirmasi), dihitung dengan penambahan bulan kalender. Contoh: bayar 31 Agustus → berakhir 28 Februari. |
| BR-03 | Setelah `expires_at` lewat, user tidak dapat membuka video, kurikulum terkunci, dan halaman course menampilkan tombol beli lagi. Riwayat pesanan tetap terlihat. |
| BR-04 | Satu user tidak dapat membeli course yang masih aktif. Setelah kedaluwarsa, pembelian baru memulai periode 6 bulan baru dari waktu bayar. |
| BR-05 | Satu pesanan hanya untuk satu course. Harga yang berlaku dibekukan di pesanan saat dibuat. |
| BR-06 | Akses hanya diberikan setelah webhook Midtrans tervalidasi dan status `settlement` (atau `capture` yang diterima). Status dari browser tidak dipakai. |
| BR-07 | Pesanan belum dibayar kedaluwarsa dalam 24 jam (dapat diatur). Satu user maksimal satu pesanan tertunda per course. |
| BR-08 | Admin selalu dapat melihat materi untuk keperluan pratinjau. |
| BR-09 | Admin dapat memberi akses manual; tanggal berakhir default 6 bulan dan dapat diubah dengan alasan tercatat. |
| BR-10 | Course berstatus draft atau arsip tidak tampil di publik dan tidak dapat dibeli. Pengarsipan hanya dapat dilakukan pada course yang sudah terbit. Anggota lama pada course yang diarsipkan tetap dapat belajar sampai akses berakhir. |
| BR-11 | Harga disimpan dalam Rupiah bulat. Harga diskon, bila ada, harus lebih kecil dari harga normal. |
| BR-12 | Perubahan akses (beri, perpanjang, cabut) dan perubahan status pesanan dicatat di log sistem. |

## 7. Perlindungan konten: yang bisa dan tidak bisa dijanjikan

Permintaan "tidak bisa di-screenshot, tidak bisa di-download, dan tidak bisa diakali" perlu disikapi realistis agar keputusan produk tepat.

**Dapat dicapai dengan solid**

- Video tidak punya URL file publik dan tidak dapat diunduh lewat tombol atau menu browser.
- Video dienkripsi (HLS AES-128), kunci hanya diberikan kepada user dengan akses aktif dan token berumur pendek.
- Akses terputus otomatis saat enrollment berakhir.
- Satu sesi aktif per user menekan berbagi akun.
- Watermark dinamis menurunkan niat menyebarkan karena sumber kebocoran dapat dilacak.

**Hanya bisa dihambat, tidak dijamin**

- **Screenshot dan perekaman layar.** Browser tidak punya mekanisme untuk melarang screenshot sistem operasi, perekam layar, atau kamera ponsel. Skrip di halaman hanya dapat menyulitkan pemakai awam.
- **Pengunduhan oleh orang teknis.** Selama pengguna sah dapat memutar video, orang yang mahir masih bisa mengambil segmen terenkripsi plus kunci dalam masa tokennya, atau merekam keluaran layar.
- Pemblokiran DevTools di browser tidak dapat diandalkan.

**Jalur peningkatan bila risiko dianggap tinggi:** memakai layanan video **DRM** (Widevine/FairPlay/PlayReady) dari penyedia pihak ketiga. DRM adalah satu-satunya cara yang di banyak kombinasi perangkat dan browser membuat hasil screenshot/rekam layar menjadi hitam, tetapi tidak menjamin semua perangkat, menambah biaya bulanan, dan memerlukan integrasi pemutar penyedia. Sistem dirancang dengan lapisan abstraksi penyedia video (`VideoDelivery`) sehingga peralihan ke DRM kelak tidak mengubah model data kursus.

**Keputusan MVP:** HLS terenkripsi buatan sendiri + token + watermark + deterrent klien + satu sesi aktif. Teks di UI tidak boleh menjanjikan "tidak bisa direkam".

## 8. Asumsi dan pertanyaan terbuka

Agent memakai asumsi di kolom "Default" sampai user menyatakan lain.

| ID | Pertanyaan | Default yang dipakai |
|---|---|---|
| Q1 | Nama aplikasi dan identitas visual (logo, warna)? | Nama dari `APP_NAME`; warna primer sama dengan tema Otika; logo placeholder. |
| Q2 | Hosting cPanel mendukung HTTPS, PHP yang sesuai, OpenSSL, storage privat dan batas request yang memadai? | Tidak memerlukan FFmpeg/ffprobe, subprocess atau worker server. Video diproses di browser desktop admin; task cron-job.org dijalankan dalam batch singkat. Kecocokan hosting dan browser dikonfirmasi lewat UAT. |
| Q3 | Apakah akun Midtrans sudah aktif dan kanal pembayaran apa saja yang diaktifkan? | Sandbox dulu; VA BCA/BNI/BRI/Permata/CIMB, QRIS, GoPay/ShopeePay ditampilkan sesuai konfigurasi. |
| Q4 | Registrasi: perlu verifikasi email? | Tidak wajib di MVP. |
| Q5 | Perpanjangan sebelum akses berakhir? | Tidak diperbolehkan; beli lagi setelah kedaluwarsa (BR-04). |
| Q6 | Apakah perlu video pratinjau gratis pada halaman detail? | Tidak di MVP. |
| Q7 | Batas waktu bayar? | 24 jam. |
| Q8 | Pengiriman email (SMTP) tersedia untuk reset password dan notifikasi? | Diasumsikan SMTP diisi lewat `.env`. |
| Q9 | Perkiraan jumlah user, course, dan ukuran video? Menentukan ruang disk dan beban streaming. | Ratusan user, puluhan course. Batas awal usulan video sumber 200 MiB, durasi 15 menit, maksimum 720p, MP4 H.264/AAC; bukan jaminan kapasitas browser. Tetapkan ulang setelah UAT perangkat admin. Batas 2 GB sebelumnya dibatalkan. |
| Q10 | Materi selain video (PDF/slide)? | Di luar scope MVP. |

Penjadwalan operasional memakai **cron-job.org** untuk memicu endpoint HTTPS yang diamankan, lalu pekerjaan dieksekusi langsung dalam batch kecil dengan batas waktu dan checkpoint. Keterlambatan pemicu tidak boleh membuka akses yang sudah kedaluwarsa. Detail: SRS bagian 5.10.

## 9. Roadmap phase

Ringkasan; rincian ada di `phases/README.md`.

| Phase | Fokus |
|---|---|
| 0 | Fondasi: konfigurasi, layout, autentikasi, role, log |
| 1 | Admin: kategori, course, kurikulum (bab & video metadata) |
| 2 | Admin: user dan anggota course |
| 3 | Situs publik: landing, katalog, detail course |
| 4 | Video terlindungi: unggah, enkripsi HLS, pemutar, watermark |
| 5 | Pembayaran Midtrans dengan UI custom dan enrollment otomatis |
| 6 | Area member: course saya, progres, pesanan, profil |
| 7 | Penguatan: dashboard admin, pengingat, audit keamanan, panduan deployment MySQL |

Aturan: satu phase selesai setelah feedback uji langsung dari user menyatakan lulus. Agent tidak melakukan testing.

## 10. Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Ekspektasi proteksi screenshot 100% | Kekecewaan, klaim keliru | Jelaskan batas (bagian 7); siapkan jalur DRM. |
| Browser kehabisan memori/halaman ditutup saat pemrosesan | Persiapan atau upload gagal | Batasi input, satu video sekali proses, cek kompatibilitas, retry dan UAT browser desktop. Tidak menjanjikan dukungan semua perangkat. |
| Webhook Midtrans terlambat/gagal | User sudah bayar tetapi belum dapat akses | Sinkron status manual oleh admin, pengecekan status terjadwal, log notifikasi. |
| Perbedaan PostgreSQL (dev) vs MySQL (prod) | Migration gagal di production | Aturan migration portabel di AGENTS.md; user mencoba migrate di MySQL staging sebelum rilis. |
| Streaming melalui PHP membebani server | Lambat saat banyak penonton | Dukungan `X-Accel-Redirect`/Nginx internal location; cache segmen privat. |
| Tanpa testing oleh agent | Bug lolos antar phase | UAT tertulis tiap phase, user menguji sebelum phase berikutnya. |

## 11. Keputusan MVP cPanel (16 Oktober 2026)

Admin memakai halaman persiapan video di browser Windows/macOS/Linux yang kompatibel; tidak perlu memilih OS atau memasang alat lokal. Deteksi kemampuan WebAssembly/Web Worker dilakukan sebelum proses; deteksi OS hanya bantuan, bukan gate. Target awal Chrome/Edge desktop, Firefox/Safari hanya setelah UAT; pembatasan ini khusus persiapan video, bukan player member. HP/tablet belum menjadi target persiapan.

Satu kualitas mengikuti sumber yang memenuhi profil (maksimum 720p), tanpa transcoding otomatis atau pilihan kualitas ganda. Input tidak sesuai ditolak dengan petunjuk ekspor ulang. Pengepakan HLS memakai stream copy untuk mengurangi beban perangkat. Browser tetap perlu terbuka; dukungan AES-128 build ffmpeg.wasm terpilih harus dibuktikan sebelum implementasi penuh Phase 4 diluluskan.

Seluruh MVP tidak membutuhkan worker server: email sinkron dengan timeout, pekerjaan berkala melalui cron-job.org dalam batch. Migrasi ke VPS/encoding server dan queue adalah peningkatan setelah MVP, bukan implementasi dua sistem sekaligus.
