# Phase 4: Persiapan video di browser, impor HLS dan player

Status: **Direncanakan, belum diimplementasikan atau lulus UAT**.

Acuan: [PRD](../PRD.md) F-207–208, F-602–603, F-701–706 dan keputusan MVP cPanel; [SRS](../SRS.md) 5.7/5.10; [design](../../design.md) 19.7/19.9; [AGENTS](../../AGENTS.md). Prioritas AGENTS > SRS > PRD > design. Lihat [roadmap](README.md).

## Prasyarat dan gate kelayakan

Phase 3 lulus dan user menginstruksikan lanjut. Hosting HTTPS menyediakan PHP/OpenSSL, database, disk privat dan penyajian asset WASM/JS. Tidak membutuhkan FFmpeg, ffprobe, subprocess, queue worker, Supervisor atau systemd.

Sebelum fitur penuh, buat halaman minimum persiapan yang memakai build ffmpeg.wasm single-thread dipin; user menguji satu video representatif untuk stream copy, HLS MPEG-TS AES-128, impor dan playback/dekripsi. UAT harus mencakup batas perangkat browser; dukungan enkripsi build belum dianggap terbukti. Jika gagal, perbaiki atau minta keputusan alternatif; jangan diam-diam memakai file MP4 publik. Tidak ada test oleh agent.

## Scope dan urutan implementasi

1. Sediakan asset distribusi ffmpeg.wasm wrapper/core/Web Worker lokal lewat url(), tanpa Vite atau build frontend. Muat hanya halaman persiapan admin. Browser memeriksa HTTPS/WebAssembly/Web Worker/Web Crypto, tanpa pilihan OS wajib. Target awal Chrome/Edge desktop; Firefox/Safari mengikuti hasil UAT. Tidak menjanjikan semua perangkat/HP.
2. Buat halaman kustom persiapan video sesuai design. Input MP4 H.264 yuv420p/AAC, maksimum 720p; batas usulan sumber 200 MiB/900 detik, satu kualitas mengikuti sumber. Validasi metadata; input tidak sesuai ditolak. Tidak ada encoding ulang otomatis atau dua resolusi. Admin menjaga tab terbuka.
3. Browser mengemas HLS VOD stream copy dan AES-128 memakai kunci acak lokal, lalu mengunggah hanya manifest/segmen. Jangan upload video sumber; jangan menyimpan key di localStorage/URL/log. Server menyimpan key terenkripsi dengan APP_KEY. Implementasi mengikuti SRS-V1–V8, termasuk keterbatasan verifikasi metadata server tanpa decoder.
4. Buat migration video_uploads dan UploadStatus, VideoImportService, policy/rute sesi/manifest/chunk/finalize/status/batal. Chunk <=5 MiB, per-segmen <=10 MiB, total output <=250 MiB, maksimum 2.000 segmen, manifest <=1 MiB. Semua batas konfigurabel, bukan jaminan kapasitas. Tidak menerima ZIP/path bebas. Validasi struktur manifest, hash/ukuran, AES/TS bertahap, allow-list nama, scope dan expiry.
5. Satu impor aktif per admin; chunk idempoten, lock ber-owner dan checkpoint validasi. Finalisasi maksimal 10 detik/request; browser meneruskan request hingga selesai. Transaksi singkat mengaktifkan aset baru hanya setelah seluruh hasil valid. Penggantian menjaga aset lama. Batal/refresh/gagal tidak boleh membuat konten parsial ready.
6. Buat playback_sessions dan PlaybackService/VideoDelivery/HlsVideoDelivery: token hash, satu sesi aktif, TTL 10 menit, heartbeat 60 detik, batas 12 jam; periksa akses tiap manifest/playlist/key/segmen. Admin preview tanpa progres.
7. Route streaming privat dengan playlist rewrite, anti traversal, no-store/private, auth/ownership dan Sec-Fetch-Site. Tidak ada URL file asli. Key hanya pada endpoint playback sah. Tambahkan CSP sesuai sumber aktual dan worker-src halaman admin; jangan memaksa isolasi cross-origin global untuk single-thread.
8. Player: kontrol custom play/seek/volume/speed/fullscreen, satu kualitas tanpa selector semu, kurikulum dan navigasi, watermark bergerak, blur/pause saat tab tidak aktif, deterrent print/contextmenu/PiP. Player mobile tetap bagian scope walaupun persiapan ditargetkan desktop.
9. Integrasikan revoke/expiry dengan sesi pemutaran. Arsip course menjaga akses anggota lama; penghapusan lesson/file eksplisit mengikuti SRS. Maintenance cleanup/prune berupa service batch langsung sesuai SRS 5.10, melindungi impor aktif dan aset yang dirujuk.
10. Tutup UAT publikasi positif serta katalog Phase 1/3 menggunakan hasil impor nyata. Progres belajar tetap Phase 6; checkout Phase 5.

## Artefak yang direncanakan

Asset ffmpeg.wasm lokal; JS browser preparation; halaman admin persiapan; migration/model video_uploads, playback_sessions; VideoImportService, policy dan controller impor; config video/operations; Learn/Stream controllers, player Blade/JS, service cleanup/prune; integrasi revoke/expiry. Daftar file final disertakan serah-terima. Jangan mengubah migration yang sudah dijalankan user.

## Variabel lingkungan

QUEUE_CONNECTION=sync; VIDEO_DISK=video_private; VIDEO_SOURCE_MAX_SIZE_MB=200; VIDEO_SOURCE_MAX_DURATION_SECONDS=900; VIDEO_UPLOAD_MAX_SIZE_MB=250; VIDEO_SEGMENT_MAX_SIZE_MB=10; VIDEO_MAX_SEGMENTS=2000; VIDEO_SESSION_TTL_MINUTES=10; VIDEO_HEARTBEAT_SECONDS=60; VIDEO_ACCEL_REDIRECT=false. OPERATIONS_PLAYBACK_RETENTION_DAYS=30; OPERATIONS_UPLOAD_STALE_HOURS=24; OPERATIONS_BATCH_SIZE=20; OPERATIONS_MAX_SECONDS=10. APP_KEY dipertahankan.

VIDEO_FFMPEG_PATH, VIDEO_FFPROBE_PATH, VIDEO_MAX_SIZE_MB lama dan VIDEO_KEEP_ORIGINAL tidak dipakai pada rancangan MVP baru. Versi asset, batas dimensi, ukuran manifest/chunk dan anggaran finalisasi ditetapkan pada config/video.php; jangan menambah env tanpa kebutuhan.

## Perintah untuk user

Hanya setelah implementasi tersedia:

1. Isi .env sesuai konfigurasi; pastikan HTTPS, OpenSSL, storage privat, MIME application/wasm dan batas POST/upload >=5 MiB + overhead.
2. `php artisan migrate`
3. `php artisan optimize:clear`
4. Tidak menjalankan instalasi FFmpeg atau queue worker di cPanel.
5. Tambahkan pemicu cleanup-video-uploads dan prune-playback-sessions setiap 15 menit melalui cron-job.org dengan token sesuai SRS 5.10.
6. User menjalankan UAT browser dan streaming; tetapkan batas input aktual berdasarkan hasilnya.

## Panduan Uji Manual (UAT)

| Langkah oleh user | Hasil diharapkan |
|---|---|
| Jalankan gate video kecil pada build yang dipin | HLS terenkripsi benar-benar bisa diimpor dan diputar; tidak sekadar manifest terbentuk |
| Buka halaman di Windows/macOS/Linux dengan browser target yang tersedia | Kemampuan diperiksa; browser tidak didukung mendapat pesan jelas tanpa harus memilih OS |
| Coba format/codec/dimensi/durasi/ukuran tidak sah | Ditolak sebelum upload dengan petunjuk konkret |
| Proses video mendekati batas usulan, amati memori dan waktu | Batas realistis tercatat; kegagalan tidak membekukan halaman tanpa pesan; tidak mengklaim semua perangkat berhasil |
| Putus koneksi, retry chunk, refresh/tutup tab, batal dan ganti video aktif | Status konsisten, retry tidak menggandakan data; aset lama tetap tersedia; proses ulang dijelaskan |
| Ubah manifest menjadi traversal/URL asing, key salah, segmen kurang/rusak | Server menolak; konten tidak menjadi ready; tidak ada key/path internal di log |
| Finalisasi lebih dari satu batch, ulangi request | Cursor melanjutkan; publikasi atomik sekali dan data parsial tetap privat |
| Publish hasil impor, buka landing/detail | Gate publikasi dan tampilan course nyata Phase 1/3 berfungsi |
| Putar sebagai anggota aktif/admin lalu user tanpa akses/token user lain | Hanya sesi sah dapat stream/key; admin preview tidak mengubah progres |
| Buka sesi kedua, cabut akses, tunggu expiry | Sesi lama/akses invalid ditolak termasuk saat video berjalan |
| Uji kontrol, mobile player, watermark, tab blur/print | Perilaku sesuai design tanpa klaim anti-rekam absolut |
| Jalankan cleanup pada sesi stale dan impor aktif | Hanya data memenuhi retensi dihapus; aset yang dirujuk terlindungi |

Tambahkan UAT umum role/CSRF/keyboard/asset/console/responsif roadmap. Cakupan OS yang belum dicoba dicatat belum didukung untuk persiapan, bukan diasumsikan lulus.

## Kelulusan dan keterbatasan

User harus meluluskan gate HLS AES-128, batas input perangkat nyata, validasi impor serta playback/otorisasi. ffmpeg.wasm membutuhkan RAM/CPU browser; refresh dapat menghilangkan hasil sementara. Metadata codec/durasi tidak dapat dibuktikan penuh server tanpa decoder; otorisasi tidak bergantung metadata. Tidak ada jaminan screenshot/rekam layar terblokir.

Migrasi ke VPS/queue/encoding multikualitas ditunda setelah MVP. Phase tetap direncanakan sampai implementasi dan UAT user. Serah-terima wajib mencakup file, perintah, env, UAT, asumsi dan pernyataan tidak ada test oleh agent.
