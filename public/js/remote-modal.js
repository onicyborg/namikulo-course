(function () {
    'use strict';
    let loading = false;
    document.addEventListener('click', async function (event) {
        const trigger = event.target.closest('[data-remote-modal-url]');
        if (!trigger || !window.jQuery) return;
        event.preventDefault();
        if (loading) return;
        const $ = window.jQuery;
        const existing = document.getElementById(trigger.dataset.modalId);
        if (existing) { $(existing).modal('show'); return; }
        const area = trigger.closest('[data-admin-table-area]');
        const feedback = area && area.querySelector('[data-admin-table-feedback]');
        loading = true;
        trigger.setAttribute('aria-busy', 'true');
        trigger.disabled = true;
        let container;
        try {
            const response = await fetch(trigger.dataset.remoteModalUrl, { credentials: 'same-origin', headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok || response.redirected) throw new Error('modal');
            container = document.createElement('div');
            container.innerHTML = await response.text();
            const modal = Array.from(container.querySelectorAll('.modal')).find(element => element.id === trigger.dataset.modalId);
            if (!modal) throw new Error('modal');
            document.body.appendChild(container);
            document.dispatchEvent(new CustomEvent('nc:content-ready', { detail: { root: container } }));
            $(modal).one('hidden.bs.modal', function () {
                document.dispatchEvent(new CustomEvent('nc:content-dispose', { detail: { root: container } }));
                $(modal).modal('dispose');
                container.remove();
                if (trigger.isConnected) trigger.focus();
            }).modal('show');
            if (feedback) feedback.hidden = true;
        } catch (error) {
            if (container) container.remove();
            window.ncToast.error('Form gagal dimuat. Coba klik kembali, atau muat ulang halaman jika sesi berakhir atau data telah berubah.');
        } finally {
            loading = false;
            trigger.disabled = false;
            trigger.removeAttribute('aria-busy');
        }
    });
})();
