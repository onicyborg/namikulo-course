(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('video-preparation');
        if (!root) return;

        var fileInput = document.getElementById('video-file');
        var startButton = root.querySelector('[data-video-start]');
        var cancelButton = root.querySelector('[data-video-cancel]');
        var retryButton = root.querySelector('[data-video-retry]');
        var replaceButton = root.querySelector('[data-video-replace]');
        var successActions = root.querySelector('[data-video-success-actions]');
        var currentStatus = root.querySelector('[data-video-current-status]');
        var currentDescription = root.querySelector('[data-video-current-description]');
        var readyActions = root.querySelector('[data-video-ready-actions]');
        var status = root.querySelector('[data-video-status]');
        var fileLabel = root.querySelector('[data-video-file]');
        var progressWrap = root.querySelector('[data-video-progress-wrap]');
        var progress = root.querySelector('[data-video-progress]');
        var progressLabel = root.querySelector('[data-video-progress-label]');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var ffmpeg = null;
        var uploadId = null;
        var abortController = null;
        var busy = false;
        var cancelled = false;
        var stage = 'browser';
        var processingStep = 'engine';

        var dropzone = root.querySelector('[data-video-dropzone]');
        var selected = root.querySelector('[data-video-selected]');
        var empty = root.querySelector('[data-video-empty]');
        var fileCheck = root.querySelector('[data-video-file-check]');
        var liveStatus = root.querySelector('[data-video-live-status]');
        var liveHint = root.querySelector('[data-video-live-hint]');
        var changeButton = root.querySelector('[data-video-change]');
        var clearButton = root.querySelector('[data-video-clear]');
        var lastError = false;
        var stageLabels = { browser: 'Periksa browser', processing: 'Sedang diproses', uploading: 'Sedang diunggah', validating: 'Menunggu validasi', ready: 'Video siap' };

        function refreshWorkspace() {
            var file = fileInput.files[0];
            var locked = busy || !!uploadId || fileInput.disabled;
            dropzone.setAttribute('aria-disabled', String(locked));
            dropzone.classList.toggle('is-locked', locked);
            dropzone.classList.toggle('has-error', lastError);
            // Keep the native input available for replacement even while its visual empty state is hidden.
            empty.classList.toggle('vp-empty-hidden', !!file);
            selected.hidden = !file;
            changeButton.disabled = locked;
            clearButton.disabled = locked;
            startButton.disabled = locked || !file || stage === 'ready';
            if (file) {
                root.querySelector('[data-video-file-name]').textContent = file.name;
                root.querySelector('[data-video-file-meta]').textContent = (file.size / 1024 / 1024).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' MiB · ' + (file.type || 'Format ditentukan dari ekstensi');
                fileCheck.textContent = lastError ? 'Perlu diperiksa kembali' : stage === 'ready' ? 'Hasil lolos validasi server' : busy ? stageLabels[stage] : 'File dipilih · profil belum diperiksa';
            }
            root.dataset.workspaceState = lastError ? 'error' : cancelled ? 'cancelled' : busy ? stage : stage === 'ready' ? 'ready' : file ? 'selected' : 'browser';
            liveStatus.textContent = lastError ? 'Terjadi kesalahan' : cancelled ? 'Persiapan dibatalkan' : busy ? stageLabels[stage] : stage === 'ready' ? 'Video siap' : file ? 'File siap diperiksa' : fileInput.disabled ? 'Browser belum siap' : 'Menunggu file video';
            liveHint.textContent = status.textContent;
            root.querySelectorAll('[data-stage]').forEach(function (item) {
                var current = item.dataset.stage === stage;
                item.classList.toggle('is-failed', current && lastError);
                item.querySelector('[data-step-state]').textContent = current && lastError ? 'Gagal' : item.classList.contains('is-done') || (current && stage === 'ready') ? 'Selesai' : current ? (busy ? 'Berlangsung' : 'Saat ini') : 'Menunggu';
                if (current) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
        }

        function message(text, error) {
            status.textContent = text;
            if (error) window.ncToast.error(text);
            else if (stage === 'ready') window.ncToast.success(text);
            status.classList.toggle('is-error', !!error);
            status.classList.toggle('is-success', stage === 'ready' && !error);
            lastError = !!error;
            refreshWorkspace();
        }

        function setStage(next) {
            stage = next;
            lastError = false;
            progressWrap.hidden = true;
            var order = ['browser', 'processing', 'uploading', 'validating', 'ready'];
            root.querySelectorAll('[data-stage]').forEach(function (item) {
                item.classList.toggle('is-current', item.dataset.stage === next);
                item.classList.toggle('is-done', order.indexOf(item.dataset.stage) < order.indexOf(next));
            });
            refreshWorkspace();
        }

        function setProgress(done, total, label) {
            var percent = total ? Math.min(100, Math.round(done / total * 100)) : 0;
            progressWrap.hidden = false;
            progress.style.width = percent + '%';
            progress.setAttribute('aria-valuenow', String(percent));
            progressLabel.textContent = label;
        }

        function supported() {
            return window.isSecureContext && typeof WebAssembly !== 'undefined'
                && typeof Worker !== 'undefined' && !!(window.crypto && crypto.subtle && crypto.getRandomValues)
                && !!(window.FFmpegWASM && window.FFmpegWASM.FFmpeg);
        }

        function sha256(bytes) {
            return crypto.subtle.digest('SHA-256', bytes).then(function (digest) {
                return Array.from(new Uint8Array(digest), function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
            });
        }

        function encodeBase64(bytes) {
            return btoa(String.fromCharCode.apply(null, bytes));
        }

        function hex(bytes) {
            return Array.from(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
        }

        function uploadUrl(suffix) {
            return root.dataset.uploadBase + '/' + encodeURIComponent(uploadId) + suffix;
        }

        async function requestJson(url, options) {
            var headers = Object.assign({ 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content }, options.headers || {});
            var response = await fetch(url, Object.assign({ credentials: 'same-origin', signal: abortController.signal }, options, { headers: headers }));
            var data = await response.json().catch(function () { return {}; });
            if (!response.ok) {
                var first = data.errors && Object.values(data.errors)[0];
                throw new Error(first ? first[0] : data.message || 'Permintaan gagal. Periksa koneksi lalu coba lagi.');
            }
            return data;
        }

        async function postJson(url, data) {
            return requestJson(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        }

        async function metadata(file) {
            var objectUrl = URL.createObjectURL(file);
            try {
                return await new Promise(function (resolve, reject) {
                    var video = document.createElement('video');
                    video.preload = 'metadata';
                    video.onloadedmetadata = function () { resolve({ width: video.videoWidth, height: video.videoHeight, duration: video.duration }); };
                    video.onerror = function () { reject(new Error('Browser tidak dapat membaca metadata MP4 ini. Ekspor ulang sebagai H.264/AAC.')); };
                    video.src = objectUrl;
                });
            } finally { URL.revokeObjectURL(objectUrl); }
        }

        async function loadEngine() {
            ffmpeg = new window.FFmpegWASM.FFmpeg();
            var base = root.dataset.ffmpegBase;
            await ffmpeg.load({
                coreURL: base + '/ffmpeg-core.js',
                wasmURL: base + '/ffmpeg-core.wasm'
            });
        }

        async function inspectCodec() {
            var code = await ffmpeg.ffprobe([
                '-v', 'error', '-show_entries', 'stream=codec_name,pix_fmt,width,height:format=duration',
                '-of', 'json', 'input.mp4', '-o', 'probe.json'
            ], 30000);
            var result;
            try { result = JSON.parse(await ffmpeg.readFile('probe.json', 'utf8')); }
            catch (error) { throw new Error('Profil video tidak dapat diperiksa. Coba ekspor ulang sebagai MP4 H.264 yuv420p/AAC.'); }
            if (code !== 0 && !result.streams) throw new Error('Profil video tidak dapat diperiksa.');
            var streams = result.streams || [];
            var videos = streams.filter(function (stream) { return !!stream.width && !!stream.height; });
            var audio = streams.filter(function (stream) { return !stream.width && stream.codec_name; });
            if (videos.length !== 1 || videos[0].codec_name !== 'h264' || videos[0].pix_fmt !== 'yuv420p'
                || audio.some(function (stream) { return stream.codec_name !== 'aac'; })) {
                throw new Error('Gunakan satu video H.264 yuv420p dengan audio AAC atau tanpa audio.');
            }
            var width = Number(videos[0].width);
            var height = Number(videos[0].height);
            var landscape = width <= Number(root.dataset.width) && height <= Number(root.dataset.height);
            var portrait = width <= Number(root.dataset.height) && height <= Number(root.dataset.width);
            if (!landscape && !portrait) throw new Error('Resolusi terlalu besar. Ekspor ulang maksimal ' + root.dataset.width + '×' + root.dataset.height + ' atau ' + root.dataset.height + '×' + root.dataset.width + '.');
            var duration = Number(result.format && result.format.duration);
            if (!Number.isFinite(duration) || duration <= 0 || duration > Number(root.dataset.durationSeconds)) {
                throw new Error('Durasi video tidak valid atau melewati batas ' + root.dataset.durationSeconds + ' detik.');
            }
            return Math.ceil(duration);
        }

        async function process(file) {
            setStage('processing');
            processingStep = 'engine';
            message('Memuat engine video di browser. Proses ini dapat memakai banyak memori; biarkan tab terbuka.');
            await loadEngine();
            if (cancelled) throw new Error('Persiapan dibatalkan.');
            processingStep = 'source';
            await ffmpeg.writeFile('input.mp4', new Uint8Array(await file.arrayBuffer()));
            processingStep = 'codec';
            var duration = await inspectCodec();
            var key = crypto.getRandomValues(new Uint8Array(16));
            var keyBase64 = encodeBase64(key);
            var iv = crypto.getRandomValues(new Uint8Array(16));
            var ivHex = hex(iv);
            await ffmpeg.createDir('out');
            // writeFile transfers the Uint8Array buffer to the worker.
            await ffmpeg.writeFile('key.bin', key);
            await ffmpeg.writeFile('keyinfo.txt', new TextEncoder().encode('key.bin\nkey.bin\n' + ivHex + '\n'));
            processingStep = 'hls';
            message('Mengemas video menjadi HLS terenkripsi. Lama proses bergantung pada perangkat Anda.');
            var code = await ffmpeg.exec([
                '-i', 'input.mp4', '-map', '0:v:0', '-map', '0:a:0?', '-c', 'copy',
                '-f', 'hls', '-hls_time', '6', '-hls_playlist_type', 'vod',
                '-hls_segment_type', 'mpegts', '-hls_key_info_file', 'keyinfo.txt',
                '-hls_segment_filename', 'out/seg_%05d.ts', 'out/index.m3u8'
            ], Number(root.dataset.processTimeoutMs));
            if (code !== 0) throw new Error('Engine gagal membuat HLS AES-128. Periksa profil MP4 dan jarak keyframe sumber.');
            await ffmpeg.deleteFile('input.mp4');
            processingStep = 'segments';
            var manifest = await ffmpeg.readFile('out/index.m3u8', 'utf8');
            var files = (await ffmpeg.listDir('out')).filter(function (entry) { return /^seg_\d{5}\.ts$/.test(entry.name); })
                .sort(function (a, b) { return a.name.localeCompare(b.name); });
            if (!files.length || files.length > Number(root.dataset.maxSegments)) throw new Error('Jumlah segmen hasil tidak valid.');
            var segments = [];
            var total = 0;
            for (var i = 0; i < files.length; i++) {
                var bytes = await ffmpeg.readFile('out/' + files[i].name);
                if (!bytes.length || bytes.length > Number(root.dataset.segmentBytes)) throw new Error('Ada segmen melebihi batas ' + Math.round(Number(root.dataset.segmentBytes) / 1024 / 1024) + ' MiB. Ekspor sumber dengan keyframe maksimal tiap 6 detik.');
                total += bytes.length;
                if (total > Number(root.dataset.uploadBytes)) throw new Error('Hasil HLS melewati batas ' + Math.round(Number(root.dataset.uploadBytes) / 1024 / 1024) + ' MiB. Gunakan bitrate sumber yang lebih rendah.');
                segments.push({ name: files[i].name, size: bytes.length, sha256: await sha256(bytes) });
                bytes = null;
            }
            return { key: keyBase64, iv_hex: ivHex, duration_seconds: duration, manifest: manifest, segments: segments, total: total };
        }

        async function sendChunk(name, index, bytes) {
            var hash = await sha256(bytes);
            for (var attempt = 0; attempt < 3; attempt++) {
                try {
                    return await requestJson(uploadUrl('/chunks/' + encodeURIComponent(name) + '/' + index), {
                        method: 'POST', headers: { 'Content-Type': 'application/octet-stream', 'X-Chunk-SHA256': hash }, body: bytes
                    });
                } catch (error) {
                    if (cancelled || attempt === 2) throw error;
                    await new Promise(function (resolve) { setTimeout(resolve, (attempt + 1) * 900); });
                }
            }
        }

        async function upload(result) {
            setStage('uploading');
            message('Mengunggah hasil HLS terenkripsi. Video sumber tetap di perangkat Anda.');
            if (typeof result.key !== 'string' || result.key.length !== 24) {
                throw new Error('Kunci video gagal disiapkan. Pilih ulang file sumber dan coba lagi.');
            }
            var created = await postJson(root.dataset.createUrl, { key: result.key, iv_hex: result.iv_hex, duration_seconds: result.duration_seconds });
            uploadId = created.upload_id;
            await postJson(uploadUrl('/manifest'), { manifest: result.manifest, segments: result.segments });
            var sent = 0;
            var chunkSize = Number(root.dataset.chunkBytes);
            for (var i = 0; i < result.segments.length; i++) {
                if (cancelled) throw new Error('Persiapan dibatalkan.');
                var segment = result.segments[i];
                var bytes = await ffmpeg.readFile('out/' + segment.name);
                for (var offset = 0, index = 0; offset < bytes.length; offset += chunkSize, index++) {
                    await sendChunk(segment.name, index, bytes.subarray(offset, Math.min(offset + chunkSize, bytes.length)));
                    sent += Math.min(chunkSize, bytes.length - offset);
                    setProgress(sent, result.total, 'Terunggah ' + Math.round(sent / 1024 / 1024) + ' dari ' + Math.round(result.total / 1024 / 1024) + ' MiB');
                }
                bytes = null;
            }
        }

        async function finalize() {
            setStage('validating');
            message('Server memeriksa setiap segmen. Biarkan tab tetap terbuka.');
            var result;
            do {
                if (cancelled) throw new Error('Persiapan dibatalkan.');
                result = await postJson(uploadUrl('/finalize'), {});
                setProgress(result.validated, result.total, result.validated + ' dari ' + result.total + ' segmen tervalidasi');
            } while (result.status === 'validating');
            if (result.status !== 'ready') throw new Error('Video tidak dapat diaktifkan. Periksa status impor.');
            setStage('ready');
            currentStatus.textContent = 'Siap';
            currentDescription.textContent = 'Video aktif tersedia untuk pratinjau. Penggantian berikutnya berlaku setelah hasil baru lolos validasi.';
            readyActions.hidden = false;
            message('Video berhasil disiapkan. Pratinjau hasilnya atau kembali ke kurikulum.');
        }

        function releaseEngine() {
            if (ffmpeg) {
                try { ffmpeg.terminate(); } catch (error) {}
                ffmpeg = null;
            }
        }

        function finish(error) {
            busy = false;
            if (stage === 'ready' && !error) uploadId = null;
            releaseEngine();
            cancelButton.hidden = true;
            fileInput.disabled = stage === 'ready';
            startButton.hidden = stage === 'ready';
            startButton.disabled = !fileInput.files.length || stage === 'ready';
            retryButton.hidden = !error;
            successActions.hidden = stage !== 'ready';
            if (error && !cancelled) {
                var fallback = {
                    engine: 'Engine video gagal dimuat. Periksa permintaan 814.ffmpeg.js dan ffmpeg-core.wasm pada tab Network.',
                    source: 'Browser gagal memuat video ke memori. Coba file yang lebih kecil atau perangkat dengan memori lebih besar.',
                    codec: 'Profil video gagal diperiksa. Ekspor ulang MP4 dengan H.264 yuv420p dan audio AAC.',
                    hls: 'Browser gagal menyiapkan HLS terenkripsi. Coba MP4 yang lebih pendek dan periksa ruang memori perangkat.',
                    segments: 'Browser gagal membaca hasil HLS. Coba kembali dari file sumber.'
                };
                message(error && error.message || (stage === 'processing' ? fallback[processingStep] : 'Persiapan video gagal. Coba lagi dari file lokal.'), true);
            }
            if (cancelled) message('Persiapan dibatalkan. File sumber tetap di perangkat Anda.');
            refreshWorkspace();
        }

        fileInput.addEventListener('change', function () {
            var file = fileInput.files[0];
            fileLabel.textContent = file ? file.name + ' · ' + Math.round(file.size / 1024 / 1024) + ' MiB' : 'Belum ada file dipilih.';
            startButton.disabled = !file || busy || !!uploadId;
            if (!busy && !uploadId) {
                cancelled = false;
                lastError = false;
                retryButton.hidden = true;
                setStage('browser');
                message(file ? 'File dipilih. Mulai persiapan untuk memeriksa profil video dan mengunggah hasilnya.' : 'Pilih satu file MP4 untuk memulai.');
            }
            refreshWorkspace();
        });

        changeButton.addEventListener('click', function () { if (!changeButton.disabled) fileInput.click(); });
        clearButton.addEventListener('click', function () {
            if (clearButton.disabled) return;
            fileInput.value = '';
            fileInput.dispatchEvent(new Event('change'));
            fileInput.focus();
        });
        var dragDepth = 0;
        dropzone.addEventListener('dragenter', function (event) {
            event.preventDefault();
            dragDepth++;
            if (!busy && !uploadId && !fileInput.disabled) dropzone.classList.add('is-dragging');
        });
        dropzone.addEventListener('dragover', function (event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = busy || uploadId || fileInput.disabled ? 'none' : 'copy';
        });
        dropzone.addEventListener('dragleave', function () {
            dragDepth = Math.max(0, dragDepth - 1);
            if (!dragDepth) dropzone.classList.remove('is-dragging');
        });
        dropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            dragDepth = 0;
            dropzone.classList.remove('is-dragging');
            if (busy || uploadId || fileInput.disabled) return;
            var files = event.dataTransfer.files;
            if (files.length !== 1) { message('Pilih satu file video untuk setiap persiapan.', true); return; }
            try {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            } catch (error) { message('Browser belum mendukung pilihan melalui drag-and-drop. Gunakan tombol Pilih file atau Ganti file.', true); }
        });

        startButton.addEventListener('click', async function () {
            if (busy || uploadId || !supported()) return;
            var file = fileInput.files[0];
            if (!file) return;
            busy = true;
            cancelled = false;
            uploadId = null;
            abortController = new AbortController();
            startButton.disabled = true;
            fileInput.disabled = true;
            retryButton.hidden = true;
            cancelButton.hidden = false;
            progressWrap.hidden = true;
            setStage('browser');
            message('Memeriksa format, ukuran, durasi, dan resolusi file sumber.');
            try {
                if (!/\.mp4$/i.test(file.name) || (file.type && file.type !== 'video/mp4')) throw new Error('Pilih file MP4 yang valid.');
                if (file.size > Number(root.dataset.sourceBytes)) throw new Error('File sumber melewati batas ' + Math.round(Number(root.dataset.sourceBytes) / 1024 / 1024) + ' MiB.');
                var info = await metadata(file);
                if (!Number.isFinite(info.duration) || info.duration <= 0 || info.duration > Number(root.dataset.durationSeconds)) throw new Error('Durasi sumber tidak sesuai batas.');
                var landscape = info.width <= Number(root.dataset.width) && info.height <= Number(root.dataset.height);
                var portrait = info.width <= Number(root.dataset.height) && info.height <= Number(root.dataset.width);
                if (!landscape && !portrait) throw new Error('Resolusi sumber melewati batas ' + root.dataset.width + '×' + root.dataset.height + ' atau ' + root.dataset.height + '×' + root.dataset.width + '.');
                var result = await process(file);
                if (cancelled) throw new Error('Persiapan dibatalkan.');
                await upload(result);
                await finalize();
                finish(null);
            } catch (error) {
                var abandoned = true;
                if (uploadId && !cancelled) {
                    try {
                        abortController = new AbortController();
                        await postJson(uploadUrl('/cancel'), {});
                        uploadId = null;
                    } catch (cancelError) {
                        try {
                            var current = await requestJson(uploadUrl('/status'), { method: 'GET' });
                            abandoned = ['failed', 'ready', 'cancelled', 'expired', 'replaced'].indexOf(current.status) !== -1;
                            if (abandoned) uploadId = null;
                        } catch (statusError) { abandoned = false; }
                    }
                }
                finish(error);
                if (!abandoned) {
                    retryButton.hidden = true;
                    cancelButton.hidden = false;
                    message('Sesi impor belum dapat dibatalkan. Pulihkan koneksi lalu tekan Batal sebelum mencoba lagi.', true);
                }
            }
        });

        cancelButton.addEventListener('click', async function () {
            cancelled = true;
            if (abortController) abortController.abort();
            releaseEngine();
            if (uploadId) {
                try {
                    abortController = new AbortController();
                    await postJson(uploadUrl('/cancel'), {});
                    uploadId = null;
                    cancelButton.hidden = true;
                    retryButton.hidden = false;
                    message('Sesi impor dibatalkan. Anda dapat mencoba lagi dari file lokal.');
                } catch (error) { message('Permintaan batal tidak terkonfirmasi. Sesi akan kedaluwarsa otomatis; periksa status sebelum mulai ulang.', true); }
            }
        });

        retryButton.addEventListener('click', function () { startButton.click(); });
        replaceButton.addEventListener('click', function () {
            if (busy || uploadId) return;
            cancelled = false;
            setStage('browser');
            fileInput.value = '';
            fileInput.disabled = false;
            fileLabel.textContent = 'Belum ada file dipilih.';
            progressWrap.hidden = true;
            startButton.textContent = 'Siapkan video pengganti';
            startButton.hidden = false;
            startButton.disabled = true;
            successActions.hidden = true;
            message('Pilih MP4 pengganti. Video yang aktif tetap tersedia sampai hasil baru siap.');
            fileInput.focus();
        });
        window.addEventListener('beforeunload', function (event) { if (busy) { event.preventDefault(); event.returnValue = ''; } });

        setStage('browser');
        if (supported() && csrf) {
            fileInput.disabled = false;
            message('Browser mendukung persiapan. Pilih MP4 untuk memulai.');
        } else {
            message('Browser atau koneksi ini belum mendukung WebAssembly, Worker, Web Crypto, atau HTTPS. Gunakan browser desktop terbaru melalui HTTPS.', true);
        }
    });
})();
