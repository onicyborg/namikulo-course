# Roadmap pengembangan

Status: Phase 0 **menunggu UAT user**; Phase 1–6 **lulus UAT user**; Phase 7 **diimplementasikan, menunggu UAT user dan staging MySQL**. Proyek belum siap rilis.

Dokumen Phase 0–7 disiapkan sekaligus atas permintaan user. Persiapan dokumen tidak mengaktifkan phase atau menyatakan implementasi selesai. Prioritas: [AGENTS.md](../../AGENTS.md) > [SRS](../SRS.md) > [PRD](../PRD.md) > [design](../../design.md).

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
| [7](PHASE-7.md) | Dashboard admin, penguatan, operasi dan kesiapan rilis | F-509 (opsional), NFR, dashboard MVP | Menunggu UAT |

Prioritas M wajib rilis; S direncanakan pada phase terkait dan setiap penundaan membutuhkan keputusan user yang dicatat. F-509 berkategori C: default ditunda, implementasi hanya jika user memilih. Fitur di luar MVP PRD 4.2 tidak masuk roadmap ini.

## Penyelesaian dependensi

- Phase 1 membangun guard publikasi yang menolak course tanpa video siap. Jangan memalsukan `ready` untuk meluluskan UAT. Publikasi positif diuji setelah Phase 4.
- Phase 2 membuat `orders` sebagai skema prasyarat FK `enrollments.order_id` sesuai SRS, tanpa checkout atau integrasi gateway. Enrollment manual memakai order null. Phase 5 memakai skema itu dan membuat payments/notifications; jangan membuat tabel orders dua kali.
- Phase 2 membuat inti `EnrollmentService`, expiry, dan infrastruktur pemicu. Integrasi pencabutan playback ditambahkan pada Phase 4 setelah tabel playback tersedia.
- Phase 3 menguji empty state dan katalog tanpa memaksa publikasi. Konten terbit nyata, akses aktif dan pending order diuji kembali pada Phase 4–6. CTA checkout tidak boleh menuju route yang belum tersedia; sementara tampilkan informasi pembelian belum tersedia, lalu aktifkan di Phase 5.
- Phase 4 menguji player dengan enrollment manual Phase 2. Guard akses wajib sudah berfungsi; pembelian belum diperlukan.
- Phase 4 menyiapkan kontrol/proses playback. Penyimpanan progres dan perhitungan persentase dibuat Phase 6. Jangan menampilkan progres fiktif atau memanggil endpoint yang belum ada.
- Phase 5 menyediakan halaman pembayaran, akses langsung ke player, daftar transaksi member, dan invoice PDF sesuai permintaan user. Dashboard member lengkap dan progres diselesaikan Phase 6.
- SRS awal menempatkan F-306/F-307 sebagian dan F-105 di Phase 2. Roadmap ini memusatkan pesanan/sinkron pada Phase 5 dan profil member pada Phase 6 agar tidak mendahului dependensi; Phase 2 fokus administrasi user/anggota. Tabel keterlacakan SRS diselaraskan.

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

## Gerbang siap rilis

Seluruh kebutuhan M dan UAT integrasi lulus oleh user; keputusan S/C dicatat; staging MySQL berhasil; batch cron-job.org, SMTP dan Midtrans terkonfigurasi; file privat dan akses terverifikasi; backup/pemulihan serta rollback tersedia; tidak ada blocker terbuka. User menyetujui rilis secara eksplisit. Penyusunan roadmap atau selesainya coding bukan tanda proyek siap production.

## Perintah untuk user sekarang

Phase 7 sudah diimplementasikan dan menunggu UAT user. Jalankan migration indeks baru pada staging MySQL dan ikuti [panduan UAT Phase 7](PHASE-7.md#panduan-uji-manual-uat). UAT Phase 0 masih menunggu konfirmasi. Production dan status siap rilis mengikuti [checklist rilis](../RELEASE-CHECKLIST.md), bukan penyelesaian coding. Agent tidak menjalankan migration atau seed pada database user.
