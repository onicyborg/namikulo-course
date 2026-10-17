# Pengujian browser dengan Chrome DevTools MCP

Proyek ini memakai [Chrome DevTools MCP](https://github.com/ChromeDevTools/chrome-devtools-mcp) sebagai perkakas agent. Konfigurasi Codex berada di [`.codex/config.toml`](../.codex/config.toml) dan memin versi `1.11.0`. Paket dijalankan oleh `npx` di lingkungan agent; paket ini **bukan** dependency Laravel, frontend, atau server cPanel.

## Persiapan lokal

1. Pastikan Node.js versi yang didukung paket, npm, dan Google Chrome stable tersedia. Lingkungan pengembangan ini memiliki Node.js 24 dan Chrome stable.
2. Buka repository ini sebagai proyek tepercaya di Codex. Project `.codex/config.toml` akan mengesampingkan konfigurasi global yang mungkin menunjuk proyek lain. Argumen `--workspace` pada file itu memakai path absolut komputer saat ini; sesuaikan jika proyek dipindah ke lokasi lain.
3. Dari root repository, jalankan `codex mcp get chrome-devtools` untuk memastikan versi dan workspace sesuai. Mulai sesi Codex baru bila alat MCP belum muncul di sesi yang sudah berjalan.
4. Siapkan aplikasi lokal melalui prosedur biasa. Agent boleh menjalankan `php artisan serve` untuk inspeksi browser, tetapi **tidak** menjalankan migration atau seed pada database development milik user. Jika skema belum siap, user menjalankan perintah database pada panduan phase terkait.

Server MCP akan meluncurkan Chrome saat alat browser pertama kali digunakan. `--headless` menjaga pengujian tanpa jendela, dan `--isolated` memakai profil sementara sehingga sesi login Chrome pribadi tidak terbaca. Telemetri penggunaan dan CrUX dinonaktifkan dalam konfigurasi proyek.

## Alur pemeriksaan agent

1. Buka URL lokal atau staging test yang disediakan user. Periksa snapshot DOM dan status halaman, bukan hanya screenshot.
2. Periksa console dan network untuk error JavaScript, CSP, font/aset 404, respons 4xx/5xx, serta request yang tidak selesai.
3. Uji interaksi utama sesuai scope phase: keyboard/fokus, validasi form, loading, sukses, error, empty state, role, dan navigasi ulang. Gunakan data dummy pada lingkungan test terpisah bila alur perlu mengubah database.
4. Periksa ukuran 375, 768, 1024, dan desktop, termasuk modal, tabel, sidebar, dan player. Catat halaman, langkah, hasil aktual, dan bukti yang relevan.
5. Setelah perbaikan, ulangi skenario yang gagal dan alur yang berdekatan. Hapus artefak sementara; jangan commit screenshot, trace, cookie, atau payload pribadi.

MCP dapat melihat isi halaman dan DevTools dari browser yang dikendalikannya. Jangan menghubungkannya ke profil Chrome pribadi atau membuka production berisi data sensitif. Pengujian browser tidak membuktikan pembayaran end-to-end, kompatibilitas MySQL production, atau keberhasilan restore; bagian itu tetap memerlukan lingkungan dan UAT yang sesuai.

## Database dan pembayaran

Test runner yang memutasi data hanya boleh memakai database test khusus dengan kredensial terpisah. Verifikasi konfigurasi koneksi sebelum test dijalankan. Jangan gunakan `migrate:fresh` atau seed pada database development, staging non-test, maupun production. Uji alur Midtrans memakai sandbox dan akun/data uji yang disediakan user; jangan memicu pembayaran production.

Hasil agent dicatat pada laporan serah-terima: lingkungan, skenario, pass/fail, temuan, dan batas yang belum diperiksa. User tetap memutuskan kelulusan phase serta kesiapan rilis.
