# Brief pengujian end to end untuk Claude

## Tujuan dan sumber kebenaran

Periksa alur yang benar-benar dapat diamati melalui Chrome DevTools MCP dari Phase 0 sampai Phase 9. Temukan bug, tulis langkah reproduksi dan bukti, lalu hasilkan `docs/QA-REPORT-E2E.md`. Laporan ini adalah bahan perbaikan, bukan keputusan bahwa suatu phase lulus atau aplikasi siap rilis.

Sebelum mulai, baca `AGENTS.md`, `design.md`, `docs/PRD.md`, `docs/SRS.md`, `docs/phases/README.md`, `docs/phases/PHASE-0.md` sampai `PHASE-9.md`, `docs/BROWSER-TESTING.md`, dan `docs/RELEASE-CHECKLIST.md`. Cocokkan perilaku aktual dengan dokumen dan route yang ada. Jika dokumen berbeda, ikuti prioritas dalam `AGENTS.md`; catat selisihnya di laporan. Jangan menggunakan isi laporan UAT lama sebagai bukti hasil saat ini.

## Siapkan Chrome DevTools MCP pada Claude Code

Jika alat `chrome-devtools` belum muncul di sesi Claude Code, pasang sendiri mengikuti [panduan resmi Chrome DevTools MCP untuk Claude Code](https://github.com/ChromeDevTools/chrome-devtools-mcp/blob/main/docs/client-configurations.md#claude-code) dan [panduan MCP Claude Code](https://code.claude.com/docs/en/mcp). Pastikan Node.js LTS, npm, dan Google Chrome stable tersedia. Jangan menganggap konfigurasi `.codex/config.toml` berlaku untuk Claude.

1. Periksa `claude mcp list` dari root proyek. Jika server `chrome-devtools` sudah terhubung, gunakan yang ada; jangan pasang entri duplikat.
2. Jika belum terpasang, jalankan dari root proyek:

   ```bash
   claude mcp add --scope local chrome-devtools -- npx -y chrome-devtools-mcp@1.11.0 --headless --isolated --no-usage-statistics --no-performance-crux
   ```

   Versi `1.11.0` mengikuti pin proyek di `.codex/config.toml`. Scope `local` menyimpan konfigurasi pribadi Claude untuk proyek ini, bukan file aplikasi dalam repository. Pemasangan MCP ini adalah pengecualian yang diizinkan dari larangan mengubah konfigurasi aplikasi pada brief ini.
3. Jalankan `claude mcp get chrome-devtools` atau `claude mcp list`, lalu cek statusnya di `/mcp` pada Claude Code. Jika tool belum muncul di sesi yang sedang berjalan, mulai ulang sesi Claude dan lanjutkan dari brief ini.
4. Buktikan koneksi dengan **memanggil tool Chrome DevTools MCP** untuk membuka URL aplikasi lokal/staging test dan mengambil snapshot DOM. Catat nama tool yang berhasil serta browser yang digunakan di laporan. Status konfigurasi `Connected` saja belum membuktikan browser dapat dikendalikan.
5. Jika instalasi, koneksi, atau pemanggilan tool gagal, laporkan penyebab dan langkah yang sudah dicoba kepada user, lalu hentikan pengujian E2E. Jangan mengganti MCP dengan `curl`, `urllib`, screenshot dari alat lain, atau pembacaan source HTML.

## Batas lingkungan dan izin tindakan

1. Gunakan **Chrome DevTools MCP** di profil browser terisolasi setelah langkah pemasangan dan pemeriksaan koneksi di atas berhasil.
2. Gunakan **hanya** aplikasi lokal yang sudah berjalan di `http://127.0.0.1:8000`. Jangan beralih diam-diam ke `localhost`, port lain, staging, atau production; perbedaan origin dapat mengubah sesi dan hasil. Jika URL tidak dapat dibuka lewat MCP, laporkan hambatannya.
3. User menyediakan akun admin uji melalui prompt dan mengizinkan agent mendaftarkan member uji baru di aplikasi lokal ini sebagai bagian dari P0-01. Gunakan email uji unik yang dapat diakses bila alur email hendak diperiksa. Akun/data yang dibuat harus dapat dikenali sebagai data uji. Untuk mutasi lain, pastikan objeknya milik pengujian ini dan aman diubah; jangan menyentuh data lama pengguna. Jangan menjalankan `migrate:fresh`, `db:wipe`, seed, SQL tulis, rollback, atau migration pada database user. Bila skema kurang, minta user menjalankan migration.
4. Gunakan data dummy yang jelas dikenali. Jangan menghapus atau mengubah data nyata. Aksi destruktif (hapus course/user, revoke akses, refund, simulasi kegagalan gateway, backup/restore) hanya pada fixture uji yang aman; bila tidak ada, tandai `BLOCKED` atau `MANUAL`.
5. Midtrans harus sandbox. Jangan memicu transaksi production atau mengirim webhook palsu ke sistem nyata. Jangan membuka rahasia `.env`, cookie, token, server key, kunci HLS, atau data pribadi di prompt, screenshot, log, atau laporan. Redaksi order ID/email bila perlu. Simpan screenshot sementara di luar repository.
6. Jangan mengubah kode aplikasi, dokumen requirement, konfigurasi, atau status phase. Satu-satunya output repository yang boleh ditulis selama tugas ini adalah `docs/QA-REPORT-E2E.md`. Temuan diserahkan untuk diperbaiki kemudian.

## Cara menguji dan mencatat bukti

- Mulai dari preflight: keterjangkauan `http://127.0.0.1:8000`, commit/branch, mode `APP_DEBUG`, kesiapan migration, fixture course terbit dan draf, akun admin uji, member baru yang didaftarkan, ketersediaan member kedua untuk diskusi, video siap, Midtrans sandbox, email uji, serta cron-job.org test. Catat hanya status tersedia/tidak tersedia, bukan nilainya yang sensitif. Jangan menulis email akun uji atau password di laporan.
- Jalankan perjalanan pengguna secara berurutan: guest → akun member → katalog/course → checkout/transaksi → akses belajar → notifikasi/diskusi; selingi aksi admin untuk menyiapkan dan memeriksa data. Pakai URL dan kontrol UI yang benar-benar ada. Jangan menganggap status HTTP 200 berarti alur berhasil: pastikan state UI dan data yang terlihat berubah sesuai harapan.
- Untuk setiap skenario, periksa DOM/snapshot, interaksi nyata, Console, dan Network. Catat request gagal 4xx/5xx yang tidak diharapkan, error JavaScript, CSP/CORS, font/aset 404, loading macet, respons 429 pada pemakaian normal, dan state setelah refresh/kembali/login ulang. Jangan salin payload sensitif.
- Periksa tampilan utama di lebar 375, 768, 1024, dan desktop. Cek scroll horizontal, modal di atas backdrop, fokus keyboard, label form, pesan validasi, tabel, sidebar, player, serta diskusi.
- Uji hasil negatif dengan akun dan objek uji yang sah: role salah, resource milik member lain, course tanpa akses, input invalid, dan refresh/ulang aksi. Jangan melakukan brute force atau mengirim banyak request hanya untuk mencapai rate limit.
- Status per kasus hanya `PASS` (diamati dan sesuai), `FAIL` (diamati menyimpang), `BLOCKED` (prasyarat kurang), `MANUAL` (butuh keputusan/perangkat/layanan operator), atau `NOT_RUN` (belum dikerjakan). Berikan alasan konkret untuk tiga status terakhir. Jangan mengubahnya menjadi `PASS` berdasarkan pembacaan kode saja.

## Matriks skenario browser

Gunakan kode kasus berikut dalam laporan. Rincian ekspektasi mengikuti UAT pada dokumen phase dan PRD/SRS; skenario di bawah menetapkan apa yang masuk akal diverifikasi langsung oleh agent.

| ID | Phase | Langkah yang diamati dengan Chrome DevTools MCP | Hasil yang perlu dibuktikan |
|---|---|---|---|
| P0-01 | 0 | Buka landing, login, register, lupa/reset password; kirim form valid/invalid pada akun uji | Validasi per field, role member saat daftar, reset hanya dengan tautan uji yang sah; email reset bersyarat inbox uji. |
| P0-02 | 0 | Login admin/member, logout, buka URL terlindungi dan akun nonaktif yang disiapkan | Tujuan sesuai role, sesi logout tidak dapat dipakai lagi, akun nonaktif mendapat pesan tepat, lintas role ditolak. |
| P0-03 | 0 | Buka dashboard admin/member dan halaman error | Shell/layout benar; tidak ada error console, font lintas origin/CORS, atau aset gagal. |
| P1-01 | 1 | Tambah/ubah kategori dan course uji melalui modal; input invalid/slug duplikat | Modal operasional di atas backdrop; pesan validasi jelas; tabel dan detail merefleksikan data. |
| P1-02 | 1 | Tambah bab dan materi, ubah urutan, buka pratinjau; coba publikasi tanpa video siap | Urutan dan scope benar; draf tanpa video siap tetap tidak terbit; tombol arsip tidak tampil pada draf. |
| P1-03 | 1 | Periksa thumbnail serta editor deskripsi memakai file/gambar uji aman | Preview/hasil tampil, toolbar Summernote dan font lokal tidak error; input yang tidak sah ditolak. |
| P2-01 | 2 | Cari user aktif pada modal anggota, beri akses manual ke member uji, lihat status lalu ubah/cabut bila fixture aman | Pencarian berfungsi, akses muncul sesuai course, duplikasi ditolak, member tanpa akses ditolak server. |
| P2-02 | 2 | Buka form waktu/akses dari browser dengan zona waktu berbeda bila alat mendukung emulasi | Waktu layar mengikuti zona browser; momen akses sama. Jika emulasi zona tidak tersedia, `MANUAL`. |
| P3-01 | 3 | Buka landing, katalog, filter/pencarian/sort/paginasi, detail course dan tautan CTA | Hasil sesuai filter dan URL; course draf tidak terlihat publik; state kosong serta harga benar. |
| P4-01 | 4 | Pada admin, buka halaman persiapan video dan proses file MP4 kecil yang disediakan khusus uji | Pemeriksaan kemampuan browser, progres, upload/finalisasi, status siap dan pratinjau. Jika file/perangkat tidak ada, `BLOCKED`. |
| P4-02 | 4 | Sebagai member berakses, buka player, putar/jeda/seek, kembali ke detail lalu buka lagi | Playlist/segmen dimuat tanpa 401/429 pada penggunaan normal; kontrol, watermark, dan navigasi bekerja. |
| P4-03 | 4 | Coba player dengan member tanpa akses; refresh/logout saat sesi uji berjalan | Video/key tidak tersedia bagi sesi tanpa hak; pesan akses jelas. Jangan tulis nilai kunci atau URL bertoken ke laporan. |
| P5-01 | 5 | Checkout course terbit sebagai member uji, pilih metode sandbox, buka lagi dari Transaksi, coba ganti metode/batalkan | Satu tagihan aktif sesuai aturan, instruksi VA/QR bila tersedia, perubahan metode tidak menimbulkan tagihan ganda. Bersyarat sandbox. |
| P5-02 | 5 | Selesaikan pembayaran melalui UI sandbox yang disediakan, lalu cek status, akses course dan email uji | Akses baru muncul setelah status server terverifikasi; email instruksi/konfirmasi sesuai momen. Tanpa sandbox/inbox uji, `BLOCKED`. |
| P5-03 | 5 | Buka transaksi dan invoice PDF milik akun uji; coba URL order/invoice akun uji lain | Pemilik dapat melihat invoice order lunas; pending dan lintas pemilik ditolak; isi PDF/nominal terbaca. |
| P6-01 | 6 | Buka `/my/dashboard`, Course Saya, detail, riwayat, profil | Course berakses muncul, data akun benar, tidak ada ruang kosong/layout rusak, tautan berfungsi. |
| P6-02 | 6 | Tonton sebagian, keluar, masuk lagi, selesaikan video uji | Resume dan progres berubah; pembukaan ulang normal tidak terhambat throttle. |
| P6-03 | 6 | Ubah profil/password/avatar memakai data uji dan input salah | Perubahan sah tersimpan, invalid ditolak tanpa bocor data, foto tampil bila disimpan. |
| P7-01 | 7 | Buka dashboard admin, pesanan, Tugas & Log; cek filter/paginasi/waktu lokal | KPI dan daftar masuk akal terhadap fixture; hanya admin melihatnya; tidak ada snapshot rahasia di layar. |
| P7-02 | 7 | Ulangi alur utama pada empat lebar layar, keyboard, Console dan Network | Tidak ada blocker UI, modal tertutup backdrop, aset CORS/CSP, error 5xx, atau tombol mati. |
| P8-01 | 8 | Buat peristiwa uji yang aman (mis. pesanan/akses), buka pusat notifikasi admin/member | Item dan badge muncul sekali pada penerima tepat; tautan menuju tujuan yang masih boleh diakses. |
| P8-02 | 8 | Tandai satu/semua dibaca, refresh dan login sebagai akun lain | Hitungan menetap, kepemilikan terjaga, akun lain tidak melihat item tersebut. |
| P8-03 | 8 | Jika cron test dan inbox uji tersedia, amati rekap setelah pemicu resmi | Rekap hanya item belum dibaca dan tidak berganda pada tanggal WIB yang sama. Tanpa fasilitas itu, `MANUAL/BLOCKED`. |
| P9-01 | 9 | Dua member berakses aktif bertanya dan membalas pada satu lesson; admin menjawab | Post tampil pada lesson tepat, label Admin jelas, waktu lokal, notifikasi penerima benar dan tidak mengirim ke diri sendiri. |
| P9-02 | 9 | Uji balasan ke komentar, kiriman kosong/terlalu panjang/HTML sebagai teks, pagination | Validasi dan escape benar; thread/reply tidak tertukar atau menutupi player. |
| P9-03 | 9 | Penulis hapus post uji; admin sembunyikan/pulihkan root dan reply | Konten tersembunyi bagi member, moderasi tercatat, pemulihan konsisten. Hanya pada fixture uji. |
| P9-04 | 9 | Akses thread/post melalui akun tanpa enrollment atau resource milik lesson lain | Server menolak baca/tulis lintas akses; tautan notifikasi lama tidak membuka materi setelah akses dicabut. |

## Yang tidak dapat dibuktikan hanya dari browser

Catat sebagai `MANUAL` atau `BLOCKED`, bukan `PASS`, kecuali ada lingkungan/alat uji tambahan yang sah dan bukti spesifik:

- Portabilitas migration PostgreSQL → MySQL, integritas data lama, backup/restore, serta data UTC mentah dalam database.
- Validitas kriptografi HLS AES-128, kerahasiaan kunci di storage/log, ketahanan terhadap perekaman layar, dan performa semua OS/perangkat. Browser dapat membuktikan playback serta penolakan akses yang terlihat saja.
- Verifikasi signature webhook Midtrans, urutan webhook terbalik/idempotensi internal, refund/chargeback, timeout gateway, dan kejadian webhook sesungguhnya tanpa sandbox yang dikendalikan.
- Pengiriman SMTP nyata, deliverability, jadwal cron-job.org pukul 07.00 WIB, checkpoint batch/lock, dan retensi jangka panjang tanpa fasilitas test khusus. Browser hanya melihat akibat yang muncul di UI.
- Keputusan release, kelulusan phase, audit keamanan menyeluruh, dan kesiapan production cPanel. Itu tetap keputusan user.

## Bentuk laporan `docs/QA-REPORT-E2E.md`

Tulis laporan baru dengan susunan berikut:

1. **Ringkasan eksekusi:** tanggal/waktu, commit, URL/jenis lingkungan, browser/MCP, peran fixture, kondisi sandbox/email/cron/migration, tanpa kredensial.
2. **Cakupan:** tabel seluruh ID P0-01 sampai P9-04 berisi `PASS/FAIL/BLOCKED/MANUAL/NOT_RUN`, hasil ringkas, dan ID temuan bila ada. Jangan hilangkan skenario yang belum berjalan.
3. **Temuan terurut berdasarkan dampak:** ID `BUG-001` dst.; severity `Blocker/High/Medium/Low`; phase; URL/role; prasyarat; langkah reproduksi bernomor; hasil aktual; hasil yang diharapkan berdasarkan sumber; bukti Console/Network/DOM/screenshot yang disanitasi; dugaan penyebab bila ada, diberi label dugaan. Pisahkan bug produk dari lingkungan atau fixture yang belum siap.
4. **Alur lintas phase:** catat sampai langkah mana perjalanan daftar → course → checkout → bayar → belajar → notifikasi → diskusi berhasil. Sebutkan titik pemutusnya.
5. **Batas dan tindak lanjut:** daftar prasyarat untuk `BLOCKED/MANUAL`, risiko yang belum diverifikasi, serta urutan perbaikan yang disarankan. Jangan menyatakan phase lulus atau aplikasi siap rilis.

Jika menemukan blocker awal, teruskan skenario independen lain yang masih aman. Jangan mengisi hasil hipotetis. Setelah laporan selesai, beri user ringkasan jumlah kasus tiap status, temuan utama, dan lokasi file laporan.
