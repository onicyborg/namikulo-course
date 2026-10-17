# Browser video engine

Local distribution files copied unchanged from npm packages on 2026-10-18:

- `@ffmpeg/ffmpeg@0.12.15`: `dist/umd/ffmpeg.js`, `dist/umd/814.ffmpeg.js` (MIT; [source](https://github.com/ffmpegwasm/ffmpeg.wasm/tree/main/packages/ffmpeg)).
- `@ffmpeg/core@0.12.10`: `dist/umd/ffmpeg-core.js`, `dist/umd/ffmpeg-core.wasm` (GPL-2.0-or-later; [source](https://github.com/ffmpegwasm/ffmpeg.wasm/tree/main/packages/core)).

These files run in the admin's browser. The core is single-threaded. The application does not use server FFmpeg or a JavaScript build step. The upstream source and licensing terms must accompany redistributed production artifacts.
