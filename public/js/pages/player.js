(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('learn-player');
        if (!root) return;
        var video = document.getElementById('course-video');
        var stage = root.querySelector('[data-video-stage]');
        var playerFrame = root.querySelector('[data-player-frame]') || stage;
        var screenMessage = root.querySelector('[data-screen-message]');
        var watermark = root.querySelector('[data-watermark]');
        var play = root.querySelector('[data-play]');
        var seek = root.querySelector('[data-seek]');
        var volume = root.querySelector('[data-volume]');
        var speed = root.querySelector('[data-speed]');
        var fullscreen = root.querySelector('[data-fullscreen]');
        var time = root.querySelector('[data-time]');
        var progressFeedback = root.querySelector('[data-progress-feedback]');
        var currentLessonState = root.querySelector('.is-current [data-lesson-state]');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var hls = null;
        var heartbeatTimer = null;
        var watermarkTimer = null;
        var watermarkText = '';
        var stopped = false;
        var progressUrl = null;
        var progressToken = null;
        var progressTimer = null;
        var hasPlayed = false;
        var resumed = false;
        var lastPlaybackPosition = null;
        var pendingWatchedSeconds = 0;

        function showMessage(text) { screenMessage.textContent = text; screenMessage.hidden = false; }
        function hideMessage() { screenMessage.hidden = true; }
        function duration(seconds) {
            if (!Number.isFinite(seconds)) return '0:00';
            return Math.floor(seconds / 60) + ':' + String(Math.floor(seconds % 60)).padStart(2, '0');
        }
        function moveWatermark() {
            watermark.style.left = (8 + Math.floor(Math.random() * 46)) + '%';
            watermark.style.top = (10 + Math.floor(Math.random() * 64)) + '%';
        }
        function obscure() { video.pause(); stage.classList.add('is-obscured'); showMessage('Video dijeda saat halaman tidak aktif.'); }
        function stop(text) {
            if (stopped) return;
            stopped = true;
            video.pause();
            video.removeAttribute('src');
            if (hls) { hls.destroy(); hls = null; }
            if (heartbeatTimer) clearInterval(heartbeatTimer);
            if (watermarkTimer) clearInterval(watermarkTimer);
            if (progressTimer) clearInterval(progressTimer);
            play.disabled = true;
            seek.disabled = true;
            showMessage(text);
        }

        function saveProgress(eventName, keepalive) {
            if (!progressUrl || !progressToken || !hasPlayed || !Number.isFinite(video.currentTime)) return;
            var position = Math.max(0, Math.floor(video.currentTime));
            var watched = Math.floor(pendingWatchedSeconds);
            pendingWatchedSeconds -= watched;
            fetch(progressUrl, { method: 'POST', credentials: 'same-origin', keepalive: !!keepalive,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf.content },
                body: JSON.stringify({ token: progressToken, position_seconds: position, watched_seconds: watched, event: eventName })
            }).then(function (response) {
                if (response.status === 403 || response.status === 401) {
                    stop('Sesi video atau akses course telah berakhir. Muat ulang halaman.');
                    return;
                }
                if (!response.ok) throw new Error('progress');
                return response.json();
            }).then(function (data) {
                if (!data || !progressFeedback) return;
                progressFeedback.textContent = data.completed ? 'Materi selesai.' : 'Posisi belajar tersimpan.';
                if (currentLessonState) currentLessonState.textContent = data.completed ? 'Selesai' : 'Sedang dipelajari';
                if (data.next_unlocked_id) {
                    document.querySelectorAll('[data-locked-lesson]').forEach(function (locked) {
                        if (locked.dataset.lockedLesson !== data.next_unlocked_id) return;
                        var link = document.createElement('a');
                        link.href = locked.dataset.lessonUrl;
                        link.className = locked.className.replace(/\bnl-unavailable\b|\bnl-next-locked\b/g, '').trim();
                        while (locked.firstChild) link.appendChild(locked.firstChild);
                        locked.replaceWith(link);
                        var state = link.querySelector('[data-lesson-state]');
                        if (state) state.textContent = 'Belum dimulai';
                        if (link.classList.contains('nl-next')) {
                            var hints = link.querySelectorAll('small');
                            if (hints[0]) hints[0].textContent = 'Materi berikutnya';
                            if (hints[1]) hints[1].remove();
                        }
                    });
                }
            }).catch(function () {
                if (!stopped) pendingWatchedSeconds += watched;
                if (progressFeedback && !stopped) progressFeedback.textContent = 'Progres belum tersimpan. Periksa koneksi sebelum menutup halaman.';
            });
        }

        async function heartbeat(url) {
            try {
                var response = await fetch(url, { method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content } });
                if (!response.ok) stop('Sesi video atau akses course telah berakhir. Muat ulang halaman untuk memeriksa akses.');
            } catch (error) { stop('Koneksi terputus. Muat ulang halaman saat koneksi kembali.'); }
        }

        async function start() {
            if (!csrf) { showMessage('Sesi halaman tidak tersedia. Muat ulang halaman.'); return; }
            try {
                var response = await fetch(root.dataset.startUrl, { method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content } });
                if (response.status === 429) {
                    var retrySeconds = Number(response.headers.get('Retry-After'));
                    showMessage('Terlalu banyak permintaan pemutaran. Tunggu ' + (retrySeconds > 0 ? Math.ceil(retrySeconds) : 60) + ' detik, lalu muat ulang halaman.');
                    return;
                }
                if (!response.ok) { showMessage('Akses video tidak tersedia atau sesi Anda berakhir. Muat ulang halaman.'); return; }
                var data = await response.json();
                progressUrl = data.progress_url || null;
                progressToken = data.progress_token || null;
                watermarkText = data.watermark || '';
                watermark.textContent = watermarkText;
                moveWatermark();
                watermarkTimer = setInterval(moveWatermark, 25000);
                new MutationObserver(function () {
                    if (!stage.contains(watermark)) stage.appendChild(watermark);
                    if (watermark.textContent !== watermarkText) watermark.textContent = watermarkText;
                }).observe(stage, { childList: true, subtree: true, characterData: true });

                if (window.Hls && window.Hls.isSupported()) {
                    hls = new window.Hls({ enableWorker: true, maxBufferLength: 30 });
                    hls.on(window.Hls.Events.ERROR, function (event, info) {
                        if (info.fatal) stop('Video tidak dapat dilanjutkan. Periksa koneksi atau akses course lalu muat ulang.');
                    });
                    hls.loadSource(data.manifest_url);
                    hls.attachMedia(video);
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = data.manifest_url;
                } else {
                    stop('Browser ini belum mendukung pemutaran HLS. Gunakan browser terbaru.');
                    return;
                }
                heartbeatTimer = setInterval(function () { heartbeat(data.heartbeat_url); }, Number(data.heartbeat_seconds || 60) * 1000);
                if (progressUrl && progressToken) {
                    progressTimer = setInterval(function () { if (!video.paused && !video.ended) saveProgress('tick', false); }, 15000);
                }
                play.disabled = false;
                seek.disabled = false;
                showMessage('Video siap. Tekan Putar untuk mulai belajar.');
            } catch (error) { stop('Pemutar belum dapat terhubung. Periksa koneksi lalu muat ulang halaman.'); }
        }

        play.addEventListener('click', function () {
            if (video.paused) video.play().catch(function () { showMessage('Browser menolak pemutaran. Tekan Putar lagi.'); });
            else video.pause();
        });
        video.addEventListener('loadedmetadata', function () {
            if (resumed || !Number.isFinite(video.duration)) return;
            resumed = true;
            var lastPosition = Number(root.dataset.resumeSeconds || 0);
            if (lastPosition > 0 && lastPosition < video.duration - 2) video.currentTime = lastPosition;
        });
        video.addEventListener('play', function () { hasPlayed = true; play.textContent = 'Jeda'; play.setAttribute('aria-label', 'Jeda video'); hideMessage(); });
        video.addEventListener('pause', function () {
            lastPlaybackPosition = null;
            play.textContent = 'Putar'; play.setAttribute('aria-label', 'Putar video');
            if (!video.ended && !stopped) saveProgress('pause', false);
        });
        video.addEventListener('ended', function () { saveProgress('ended', false); });
        video.addEventListener('seeking', function () { lastPlaybackPosition = null; });
        video.addEventListener('seeked', function () { lastPlaybackPosition = video.currentTime; });
        video.addEventListener('error', function () { if (!stopped) stop('Video tidak dapat diputar. Periksa koneksi lalu muat ulang.'); });
        video.addEventListener('timeupdate', function () {
            if (!video.paused && !video.seeking && Number.isFinite(video.currentTime)) {
                if (lastPlaybackPosition !== null) {
                    var advanced = video.currentTime - lastPlaybackPosition;
                    if (advanced > 0 && advanced <= Math.max(2, video.playbackRate * 2)) pendingWatchedSeconds += advanced;
                }
                lastPlaybackPosition = video.currentTime;
            }
            time.textContent = duration(video.currentTime) + ' / ' + duration(video.duration);
            if (Number.isFinite(video.duration) && video.duration > 0) seek.value = String(Math.round(video.currentTime / video.duration * 100));
        });
        seek.addEventListener('input', function () { if (Number.isFinite(video.duration)) video.currentTime = Number(seek.value) / 100 * video.duration; });
        volume.addEventListener('input', function () { video.volume = Number(volume.value) / 100; });
        speed.addEventListener('change', function () { video.playbackRate = Number(speed.value); });
        fullscreen.hidden = !playerFrame.requestFullscreen;
        fullscreen.addEventListener('click', function () {
            var request = document.fullscreenElement ? document.exitFullscreen() : playerFrame.requestFullscreen();
            if (request && request.catch) request.catch(function () { showMessage('Layar penuh belum tersedia. Anda tetap dapat menonton di halaman ini.'); });
        });
        document.addEventListener('fullscreenchange', function () {
            var isFullscreen = document.fullscreenElement === playerFrame;
            fullscreen.textContent = isFullscreen ? 'Keluar layar penuh' : 'Layar penuh';
            fullscreen.setAttribute('aria-label', isFullscreen ? 'Keluar dari layar penuh' : 'Aktifkan layar penuh');
        });
        stage.addEventListener('contextmenu', function (event) { event.preventDefault(); });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) obscure();
            else { stage.classList.remove('is-obscured'); if (!stopped) showMessage('Video dijeda. Tekan Putar untuk melanjutkan.'); }
        });
        window.addEventListener('blur', obscure);
        window.addEventListener('focus', function () { stage.classList.remove('is-obscured'); });
        window.addEventListener('beforeprint', obscure);
        window.addEventListener('pagehide', function () { if (!stopped && !video.ended) saveProgress('pause', true); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'PrintScreen') { obscure(); setTimeout(function () { if (!document.hidden) stage.classList.remove('is-obscured'); }, 3000); }
        });
        start();
    });
})();
