# Roadmap pengembangan

Status: Phase 0 **menunggu UAT user**; Phase 1–6 **lulus UAT user**; Phase 7 **diimplementasikan, menunggu UAT user dan staging MySQL**. Proyek belum siap rilis.

Dokumen Phase 0–7 disiapkan untuk MVP; Phase 8 telah diaktifkan atas instruksi user dan implementasinya menunggu UAT. Phase 9 tetap rencana. Prioritas: [AGENTS.md](../../AGENTS.md) > [SRS](../SRS.md) > [PRD](../PRD.md) > [design](../../design.md).

## Urutan kerja

| Phase | Hasil utama | Keterlacakan PRD | Status |
|---|---|---|---|
| [0](PHASE-0.md) | Fondasi, lima layout, autentikasi, role, audit log | F-101–104, BR-12 (fondasi) | Menunggu UAT user |
| [1](PHASE-1.md) | Kategori, course, bab, metadata video | F-201–206, F-208–209; prasyarat F-207 | Lulus UAT user |
| [2](PHASE-2.md) | User, enrollment manual, expiry, pemicu cron-job.org | F-301–305, BR-09/12 | Lulus UAT user |
| [3](PHASE-3.md) | Landing, katalog, detail | F-401–404 | Lulus UAT user |
| [4](PHASE-4.md) | Upload, HLS browser, impor, stream, player terlindungi | F-207–208, F-602–603, F-701–706 | Lulus UAT user |
| [5](PHASE-5.md) | Checkout, pembayaran, transaksi member, invoice PDF, pesanan admin, enrollment pembelian | F-306–307, F-501–508, F-510, F-605; BR-02/04–07 | Lulus UAT user |
| [6](PHASE-6.md) | Dashboard member, course saya, progres, profil | F-105, F-601, F-604 | Lulus UAT user |
| [7](PHASE-7.md) | Dashboard admin, penguatan, operasi dan kesiapan rilis MVP | F-509 ditunda saat Phase 7; NFR, dashboard MVP | Menunggu UAT |
| [8](PHASE-8.md) | Pusat notifikasi, pembayaran/akses, pengingat H-7, rekap 07.00 WIB | F-509, F-801–804 | Diimplementasikan; menunggu UAT user |
| [9](PHASE-9.md) | Diskusi per materi, moderasi admin, notifikasi pertanyaan/balasan | F-805–807 | Rencana; belum aktif |

Prioritas M pada Phase 0–7 adalah syarat rilis MVP; M pada Phase 8–9 adalah syarat kelulusan perluasan **setelah phase tersebut diaktifkan**, bukan syarat tambahan rilis MVP yang sedang berjalan. F-509 semula C dan ditunda di Phase 7; user kini memilihnya sebagai notifikasi aplikasi + rekap Phase 8. Forum umum tetap di luar scope.

## Penyelesaian dependensi

- Phase 1 membangun guard publikasi yang menolak course tanpa video siap. Jangan memalsukan `ready` untuk meluluskan UAT. Publikasi positif diuji setelah Phase 4.
- Phase 2 membuat `orders` sebagai skema prasyarat FK `enrollments.order_id` sesuai SRS, tanpa checkout atau integrasi gateway. Enrollment manual memakai order null. Phase 5 memakai skema itu dan membuat payments/notifications; jangan membuat tabel orders dua kali.
- Phase 2 membuat inti `EnrollmentService`, expiry, dan infrastruktur pemicu. Integrasi pencabutan playback ditambahkan pada Phase 4 setelah tabel playback tersedia.
- Phase 3 menguji empty state dan katalog tanpa memaksa publikasi. Konten terbit nyata, akses aktif dan pending order diuji kembali pada Phase 4–6. CTA checkout tidak boleh menuju route yang belum tersedia; sementara tampilkan informasi pembelian belum tersedia, lalu aktifkan di Phase 5.
- Phase 4 menguji player dengan enrollment manual Phase 2. Guard akses wajib sudah berfungsi; pembelian belum diperlukan.
- Phase 4 menyiapkan kontrol/proses playback. Penyimpanan progres dan perhitungan persentase dibuat Phase 6. Jangan menampilkan progres fiktif atau memanggil endpoint yang belum ada.
- Phase 5 menyediakan halaman pembayaran, akses langsung ke player, daftar transaksi member, dan invoice PDF sesuai permintaan user. Dashboard member lengkap dan progres diselesaikan Phase 6.
- SRS awal menempatkan F-306/F-307 sebagian dan F-105 di Phase 2. Roadmap ini memusatkan pesanan/sinkron pada Phase 5 dan profil member pada Phase 6 agar tidak mendahului dependensi; Phase 2 fokus administrasi user/anggota. Tabel keterlacakan SRS diselaraskan.
- Phase 8 memakai status pembayaran dan enrollment yang sudah otoritatif; notifikasi tidak memberi akses. Phase 9 memakai pusat notifikasi Phase 8 untuk pertanyaan dan balasan. Jangan mengaktifkan task digest atau tautan diskusi sebelum migration, route, dan kontrol aksesnya siap.

## Prosedur tiap phase

1. User mengaktifkan phase setelah phase sebelumnya lulus (Phase 0 cukup instruksi mulai).
2. Agent membaca dokumen terbaru dan implementasi/migration aktual; jangan mengubah migration yang sudah dipakai user.
3. Implementasikan hanya scope aktif. Agent boleh menguji melalui [Chrome DevTools MCP](../BROWSER-TESTING.md) atau test runner pada lingkungan test terpisah sesuai AGENTS.md; database user tetap tidak dimutasi oleh agent.
4. Review kode/dokumen terhadap SRS, pedoman UI, authorization, validasi dan kompatibilitas database. Catat hasil pemeriksaan statis dan pengujian yang benar-benar dijalankan.
5. Serahkan daftar file, perintah user berurutan, `.env`, hasil pengujian agent, UAT dengan hasil diharapkan, serta asumsi/keterbatasan.
6. User meninjau dan menguji sesuai kebutuhan, lalu memberi hasil. Status dapat berubah menjadi “Menunggu UAT”, “Perlu perbaikan”, atau “Lulus oleh user”. Jangan mengisi kelulusan sebelum ada feedback eksplisit.

UAT semua halaman: role benar/salah, validasi dan CSRF, `old()`, flash/loading/empty/error, aset tanpa 404, console, keyboard, serta lebar 375/768/1024 px dan desktop. Agent boleh memeriksa skenario tersebut di lingkungan test; user tetap memberi keputusan kelulusan. Tabel pada tiap phase menambah skenario khusus.

## Keputusan yang perlu dijaga

- Nama/logo dari user/APP_NAME; placeholder harus jujur, tanpa testimoni/statistik buatan.
- Server cPanel mendukung HTTPS, OpenSSL PHP, disk privat dan batas request; browser admin mendukung ffmpeg.wasm. Gate UAT awal Phase 4 membuktikan HLS AES-128 dan batas memori tanpa FFmpeg/worker server.
- SMTP dan kanal Midtrans mengikuti konfigurasi merchant; sandbox oleh user sebelum production.
- Pembayaran UI polling hanya baca DB. Webhook tervalidasi serta verifikasi Get Status menjadi jalur utama. Admin sync/rekonsiliasi adalah pemulihan server melalui service verifikasi yang sama.
- Akses enam bulan kalender tanpa overflow; default dari config, disimpan pada course menurut SRS. Jangan menambah UI masa akses course yang dapat diubah tanpa keputusan produk.
- Pengarsipan course menjaga akses anggota lama sesuai BR-10. Dampak penghapusan lesson/file pada anggota dibahas di Phase 4; jangan menghapus berkas saat sekadar mengarsipkan course.
- Hari/jam pembersihan dan retensi baru di SRS 5.10 adalah default operasional, bukan aturan produk baru.
- Rekap notifikasi Phase 8 dimulai pukul 07.00 WIB untuk seluruh akun; tampilan tanggal tetap mengikuti zona browser/member sesuai aturan lama. Sesuai keputusan Q11 di PRD, email instruksi pembayaran dan konfirmasi lunas tetap dikirim segera; batas satu email harian hanya untuk rekap notifikasi.

## Gerbang siap rilis

Untuk rilis **MVP Phase 0–7**, seluruh kebutuhan M pada phase tersebut dan UAT integrasi lulus oleh user; keputusan S/C dicatat; staging MySQL berhasil; batch cron-job.org, SMTP dan Midtrans terkonfigurasi; file privat dan akses terverifikasi; backup/pemulihan serta rollback tersedia; tidak ada blocker terbuka. Phase 8–9 memiliki gerbang UAT tersendiri setelah diaktifkan. User menyetujui setiap rilis secara eksplisit. Penyusunan roadmap atau selesainya coding bukan tanda proyek siap production.

## Perintah untuk user sekarang

Phase 7 dan Phase 8 sudah diimplementasikan dan menunggu UAT user. Untuk Phase 8, user perlu backup DB lalu menjalankan migration sebelum memakai aplikasi; dua jadwal cron baru diaktifkan setelah UAT. Phase 9 masih rencana. Ikuti [panduan UAT Phase 8](PHASE-8.md#panduan-uji-manual-uat) dan [checklist rilis](../RELEASE-CHECKLIST.md). Agent tidak menjalankan migration atau seed pada database user.
