(function () {
    'use strict';
    const queue = [];
    let current = null;
    const labels = { success: 'Berhasil', error: 'Terjadi kesalahan', warning: 'Perhatian', info: 'Informasi' };
    const icons = { success: '✓', error: '!', warning: '!', info: 'i' };
    function show(message, type, options) {
        type = labels[type] ? type : 'info';
        message = String(message || '').trim();
        if (!message) return;
        const key = options?.key || type + ':' + message;
        if (current?.key === key || queue.some(item => item.key === key)) return;
        queue.push({ message, type, key });
        next();
    }
    function next() {
        const host = document.querySelector('[data-toast-host]');
        if (!host || current || !queue.length) return;
        const item = queue.shift();
        current = item;
        const toast = document.createElement('section');
        toast.className = 'nc-island nc-island--' + item.type;
        const icon = document.createElement('span');
        icon.className = 'nc-island-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = icons[item.type];
        const copy = document.createElement('div');
        copy.className = 'nc-island-copy';
        const title = document.createElement('strong');
        title.textContent = labels[item.type];
        const text = document.createElement('p');
        text.textContent = item.message;
        copy.append(title, text);
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'nc-island-close';
        close.setAttribute('aria-label', 'Tutup notifikasi');
        close.textContent = '×';
        toast.append(icon, copy, close);
        host.append(toast);
        const live = document.querySelector(item.type === 'error' ? '[data-toast-assertive]' : '[data-toast-polite]');
        if (live) { live.textContent = ''; setTimeout(() => { live.textContent = labels[item.type] + '. ' + item.message; }, 50); }
        let remaining = ['warning', 'error'].includes(item.type) ? 6000 : 4000;
        let timer, started, closing = false;
        function pause() { if (timer) { clearTimeout(timer); timer = null; remaining -= Date.now() - started; } }
        function resume() {
            if (closing || timer || document.hidden || toast.matches(':hover') || toast.contains(document.activeElement)) return;
            started = Date.now(); timer = setTimeout(dismiss, Math.max(0, remaining));
        }
        function visibility() { document.hidden ? pause() : resume(); }
        function dismiss() {
            if (closing) return;
            closing = true;
            pause();
            document.removeEventListener('visibilitychange', visibility);
            toast.classList.add('is-leaving');
            setTimeout(() => { toast.remove(); current = null; next(); }, matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 200);
        }
        close.addEventListener('click', dismiss);
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', () => setTimeout(resume, 0));
        document.addEventListener('visibilitychange', visibility);
        resume();
    }
    window.ncToast = { show };
    ['success', 'error', 'warning', 'info'].forEach(type => { window.ncToast[type] = (message, options) => show(message, type, options); });
    function boot() {
        document.querySelectorAll('[data-toast-flash]').forEach(element => {
            show(element.textContent, element.dataset.toastFlash);
            element.remove();
        });
        if (document.querySelector('[data-validation-summary], .invalid-feedback')) {
            const summary = document.querySelector('[data-validation-summary]');
            if (summary && summary.textContent.trim()) show('Periksa kembali isian yang ditandai pada formulir.', 'error');
        }
        next();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
