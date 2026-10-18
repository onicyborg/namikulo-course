<div class="nl-video-frame" data-player-frame>
    <div class="learn-stage" data-video-stage>
        <video id="course-video" preload="none" playsinline controlsList="nodownload noremoteplayback" disablepictureinpicture disableremoteplayback aria-label="Video {{ $lesson->title }}"></video>
        <div class="learn-watermark" data-watermark aria-hidden="true"></div>
        <div class="learn-screen-message" data-screen-message role="status" aria-live="polite">Menyiapkan pemutar video...</div>
    </div>
    <div class="learn-controls" aria-label="Kontrol video">
        <div class="nl-video-timeline">
            <label class="sr-only" for="learn-seek">Posisi video</label>
            <input id="learn-seek" data-seek type="range" min="0" max="100" value="0" disabled>
        </div>
        <div class="nl-video-toolbar">
            <div class="nl-video-playback">
                <button type="button" data-play aria-label="Putar video" disabled>Putar</button>
                <span data-time>0:00 / 0:00</span>
            </div>
            <div class="nl-video-options">
                <div class="nl-video-volume"><label for="learn-volume">Volume</label><input id="learn-volume" data-volume type="range" min="0" max="100" value="100"></div>
                <div class="nl-video-speed"><label class="sr-only" for="learn-speed">Kecepatan video</label><select id="learn-speed" data-speed aria-label="Kecepatan video"><option value="0.75">0,75×</option><option value="1" selected>1×</option><option value="1.25">1,25×</option><option value="1.5">1,5×</option><option value="2">2×</option></select></div>
                <button type="button" data-fullscreen aria-label="Aktifkan layar penuh">Layar penuh</button>
            </div>
        </div>
    </div>
</div>
