(function () {
    'use strict';

    var modal = document.getElementById('discussion-thread-modal');
    var modalBody = modal && modal.querySelector('[data-discussion-modal-body]');
    var dialogEnabled = Boolean(modal && window.jQuery && window.jQuery.fn && window.jQuery.fn.modal);
    if (dialogEnabled) document.documentElement.classList.add('has-discussion-dialog');
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var currentRequest = null;
    var lastTrigger = null;

    function highlight(id, context) {
        if (!/^((dialog-)?discussion-post-)[a-f0-9-]+$/i.test(id)) return false;
        var post = (context || document).querySelector('[id="' + id + '"]');
        if (!post || !post.classList.contains('discussion-post')) return false;
        post.scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth', block: 'center' });
        post.focus({ preventScroll: true });
        post.classList.remove('is-reply-target');
        requestAnimationFrame(function () { post.classList.add('is-reply-target'); });
        window.setTimeout(function () { post.classList.remove('is-reply-target'); }, 3500);
        return true;
    }

    function dialogUrl(href) {
        var url = new URL(href, window.location.href);
        url.searchParams.set('dialog', '1');
        url.hash = '';
        return url;
    }

    function errorContent(href) {
        modalBody.replaceChildren();
        var message = document.createElement('p');
        message.className = 'discussion-modal-error';
        message.textContent = 'Percakapan belum dapat dimuat. Coba lagi atau buka materi.';
        var link = document.createElement('a');
        link.href = href;
        link.textContent = 'Buka materi';
        modalBody.append(message, link);
    }

    async function loadThread(href, focusId, successMessage) {
        if (!dialogEnabled) return;
        if (currentRequest) currentRequest.abort();
        currentRequest = new AbortController();
        modalBody.setAttribute('aria-busy', 'true');
        if (!modal.classList.contains('show')) {
            modalBody.innerHTML = '<p class="discussion-modal-loading" role="status">Memuat percakapan…</p>';
            window.jQuery(modal).modal('show');
        }
        try {
            var response = await fetch(dialogUrl(href), {
                credentials: 'same-origin', headers: { 'Accept': 'text/html' }, signal: currentRequest.signal
            });
            if (!response.ok) throw new Error('thread');
            modalBody.innerHTML = await response.text();
            modalBody.scrollTop = 0;
            modalBody.removeAttribute('aria-busy');
            if (successMessage) {
                var message = document.createElement('p');
                message.className = 'discussion-modal-success';
                message.setAttribute('role', 'status');
                message.textContent = successMessage;
                modalBody.prepend(message);
            }
            window.jQuery(modal).trigger('shown.bs.modal');
            var targetId = focusId || new URL(href, location.href).searchParams.get('focus');
            if (targetId) window.setTimeout(function () { highlight('dialog-discussion-post-' + targetId, modalBody); }, 60);
            else if (new URL(href, location.href).searchParams.has('reply_to')) {
                modalBody.querySelector('[data-discussion-reply-form] .nc-lexical-content, [data-discussion-reply-form] textarea')?.focus();
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                modalBody.removeAttribute('aria-busy');
                errorContent(href);
            }
        }
    }

    document.addEventListener('click', function (event) {
        var quote = event.target.closest('[data-discussion-quote]');
        if (quote && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            if (highlight(quote.dataset.discussionQuote, quote.closest('.modal') || document)) {
                event.preventDefault();
                if (!quote.closest('.modal')) history.replaceState(null, '', '#' + quote.dataset.discussionQuote);
                return;
            }
            if (dialogEnabled) {
                event.preventDefault();
                loadThread(quote.href);
                return;
            }
        }

        var link = event.target.closest('a[data-discussion-dialog], .nc-discussion-modal .discussion-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0 || !dialogEnabled) return;
        event.preventDefault();
        if (!modal.contains(link)) lastTrigger = link;
        loadThread(link.href);
    });

    document.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!modal || !modal.contains(form) || !form.matches('[data-discussion-reply-form]') || event.defaultPrevented) return;
        event.preventDefault();
        var button = form.querySelector('[type="submit"]');
        var feedback = form.querySelector('[data-discussion-feedback]');
        button.disabled = true;
        button.textContent = 'Mengirim…';
        feedback.hidden = true;
        try {
            var response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }, body: new FormData(form)
            });
            if ([401, 403, 419].includes(response.status)) {
                feedback.textContent = 'Sesi atau akses Anda telah berakhir. Muat ulang halaman untuk melanjutkan.';
                feedback.hidden = false;
                button.disabled = false;
                button.textContent = 'Kirim balasan';
                return;
            }
            if (response.status === 429) {
                feedback.textContent = 'Terlalu banyak kiriman. Tunggu sebentar sebelum mencoba lagi.';
                feedback.hidden = false;
                button.disabled = false;
                button.textContent = 'Kirim balasan';
                return;
            }
            var data = await response.json();
            if (!response.ok) {
                var errors = data.errors || {};
                feedback.textContent = Object.values(errors).flat()[0] || data.message || 'Balasan belum dapat dikirim.';
                feedback.hidden = false;
                button.disabled = false;
                button.textContent = 'Kirim balasan';
                return;
            }
            await loadThread(data.url, data.post_id, 'Balasan berhasil dikirim.');
        } catch (error) {
            feedback.textContent = 'Balasan belum dapat dikirim. Periksa koneksi lalu coba lagi.';
            feedback.hidden = false;
            button.disabled = false;
            button.textContent = 'Kirim balasan';
        }
    });

    if (dialogEnabled) {
        window.jQuery(modal).on('hidden.bs.modal', function () {
            if (currentRequest) currentRequest.abort();
            modalBody.replaceChildren();
            if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var focusId = new URLSearchParams(location.search).get('focus');
        var hashId = /^#discussion-post-[a-f0-9-]+$/i.test(location.hash) ? location.hash.slice(1) : null;
        if (focusId && /^[a-f0-9-]+$/i.test(focusId)) highlight('discussion-post-' + focusId);
        else if (hashId) highlight(hashId);
    });
})();
