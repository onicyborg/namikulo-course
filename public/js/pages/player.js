(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('learn-player');
        if (!root) return;
        var video = document.getElementById('course-video');
        var stage = root.querySelector('[data-video-stage]');
        var screenMessage = root.querySelector('[data-screen-message]');
        var watermark = root.querySelector('[data-watermark]');
        var play = root.querySelector('[data-play]');
        var seek = root.querySelector('[data-seek]');
        var volume = root.querySelector('[data-volume]');
        var speed = root.querySelector('[data-speed]');
        var fullscreen = root.querySelector('[data-fullscreen]');
        var time = root.querySelector('[data-time]');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var hls = null;
        var heartbeatTimer = null;
        var watermarkTimer = null;
        var watermarkText = '';
        var stopped = false;

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
            play.disabled = true;
            seek.disabled = true;
            showMessage(text);
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
                if (!response.ok) { showMessage('Akses video tidak tersedia atau sesi Anda berakhir. Muat ulang halaman.'); return; }
                var data = await response.json();
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
                play.disabled = false;
                seek.disabled = false;
                showMessage('Video siap. Tekan Putar untuk mulai belajar.');
            } catch (error) { stop('Pemutar belum dapat terhubung. Periksa koneksi lalu muat ulang halaman.'); }
        }

        play.addEventListener('click', function () {
            if (video.paused) video.play().catch(function () { showMessage('Browser menolak pemutaran. Tekan Putar lagi.'); });
            else video.pause();
        });
        video.addEventListener('play', function () { play.textContent = 'Jeda'; play.setAttribute('aria-label', 'Jeda video'); hideMessage(); });
        video.addEventListener('pause', function () { play.textContent = 'Putar'; play.setAttribute('aria-label', 'Putar video'); });
        video.addEventListener('error', function () { if (!stopped) stop('Video tidak dapat diputar. Periksa koneksi lalu muat ulang.'); });
        video.addEventListener('timeupdate', function () {
            time.textContent = duration(video.currentTime) + ' / ' + duration(video.duration);
            if (Number.isFinite(video.duration) && video.duration > 0) seek.value = String(Math.round(video.currentTime / video.duration * 100));
        });
        seek.addEventListener('input', function () { if (Number.isFinite(video.duration)) video.currentTime = Number(seek.value) / 100 * video.duration; });
        volume.addEventListener('input', function () { video.volume = Number(volume.value) / 100; });
        speed.addEventListener('change', function () { video.playbackRate = Number(speed.value); });
        fullscreen.addEventListener('click', function () {
            if (document.fullscreenElement) document.exitFullscreen();
            else if (stage.requestFullscreen) stage.requestFullscreen();
        });
        stage.addEventListener('contextmenu', function (event) { event.preventDefault(); });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) obscure();
            else { stage.classList.remove('is-obscured'); if (!stopped) showMessage('Video dijeda. Tekan Putar untuk melanjutkan.'); }
        });
        window.addEventListener('blur', obscure);
        window.addEventListener('focus', function () { stage.classList.remove('is-obscured'); });
        window.addEventListener('beforeprint', obscure);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'PrintScreen') { obscure(); setTimeout(function () { if (!document.hidden) stage.classList.remove('is-obscured'); }, 3000); }
        });
        start();
    });
})();
