(function () {
    'use strict';
    const root = document.querySelector('[data-curriculum]');
    if (!root) return;
    const status = root.querySelector('[data-order-status]');
    let busy = false;
    let drag = null;
    const items = list => Array.from(list.children).filter(el => el.hasAttribute('data-curriculum-item'));
    const closeMenus = except => root.querySelectorAll('.nc-menu[open]').forEach(menu => { if (menu !== except) menu.open = false; });
    function refresh() {
        root.querySelectorAll('[data-curriculum-list]').forEach(list => {
            const rows = items(list);
            rows.forEach((row, index) => {
                const number = row.querySelector('[data-chapter-number]');
                if (number) number.textContent = index + 1;
                row.querySelectorAll('[data-order-move]').forEach(button => {
                    if (button.closest('[data-curriculum-item]') !== row) return;
                    button.disabled = busy || (button.dataset.orderMove === 'up' ? index === 0 : index === rows.length - 1);
                });
            });
        });
        root.querySelectorAll('[data-drag-handle]').forEach(button => { button.disabled = busy; });
        root.setAttribute('aria-busy', String(busy));
    }
    async function save(list, before, focus) {
        if (items(list).every((item, index) => item === before[index])) return;
        busy = true;
        closeMenus();
        refresh();
        status.removeAttribute('data-error');
        status.textContent = 'Menyimpan urutan…';
        let response;
        try {
            response = await fetch(list.dataset.reorderUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ order: items(list).map(item => item.dataset.curriculumItem) }),
                signal: AbortSignal.timeout(20000)
            });
            if (!response.ok || response.redirected) throw new Error('save');
            const result = await response.json();
            if (!result.message) throw new Error('response');
            status.textContent = result.message;
            busy = false;
        } catch (error) {
            before.forEach(item => list.appendChild(item));
            // A network failure can occur after the transaction commits; reload before another edit.
            busy = true;
            status.dataset.error = 'true';
            status.textContent = response?.status === 419 || response?.status === 401
                ? 'Sesi berakhir. Muat ulang halaman sebelum mengurutkan kembali.'
                : 'Penyimpanan urutan belum dapat dipastikan. Muat ulang halaman untuk mengambil urutan terbaru.';
            root.querySelector('[data-order-reload]').hidden = false;
        }
        refresh();
        if (!busy && focus?.isConnected) focus.focus({ preventScroll: true });
    }
    function move(row, direction, focus) {
        if (busy || drag) return;
        const list = row.parentElement;
        const before = items(list);
        const index = before.indexOf(row);
        const target = before[index + (direction === 'up' ? -1 : 1)];
        if (!target) return;
        list.insertBefore(row, direction === 'up' ? target : target.nextSibling);
        refresh();
        save(list, before, focus);
    }
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-order-move]');
        if (button) {
            const row = button.closest('[data-curriculum-item]');
            move(row, button.dataset.orderMove, row.querySelector('[data-drag-handle]'));
        }
        if (event.target.closest('.nc-menu-panel button')) closeMenus();
        const summary = event.target.closest('.nc-menu summary');
        if (summary) closeMenus(summary.parentElement);
    });
    document.addEventListener('click', event => { if (!event.target.closest('.nc-menu')) closeMenus(); });
    document.addEventListener('focusin', event => { if (!event.target.closest('.nc-menu')) closeMenus(); });
    root.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            root.querySelectorAll('.nc-menu[open]').forEach(menu => { menu.open = false; menu.querySelector('summary').focus(); });
            if (drag) finish(true);
        }
        const handle = event.target.closest('[data-drag-handle]');
        if (handle && ['ArrowUp', 'ArrowDown'].includes(event.key)) {
            event.preventDefault();
            move(handle.closest('[data-curriculum-item]'), event.key === 'ArrowUp' ? 'up' : 'down', handle);
        }
    });
    root.addEventListener('pointerdown', event => {
        const handle = event.target.closest('[data-drag-handle]');
        if (!handle || busy || drag || event.button !== 0) return;
        const row = handle.closest('[data-curriculum-item]');
        const list = row.parentElement;
        if (items(list).length < 2) return;
        closeMenus();
        drag = { handle, row, list, before: items(list), pointerId: event.pointerId, startY: event.clientY, moved: false };
        root.setPointerCapture(event.pointerId);
    });
    root.addEventListener('pointermove', event => {
        if (!drag || event.pointerId !== drag.pointerId) return;
        if (!drag.moved && Math.abs(event.clientY - drag.startY) < 6) return;
        drag.moved = true;
        drag.row.classList.add('nc-dragging');
        event.preventDefault();
        const siblings = items(drag.list).filter(row => row !== drag.row);
        const target = siblings.find(row => event.clientY < row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2);
        drag.list.insertBefore(drag.row, target || null);
        refresh();
        if (event.clientY < 100) window.scrollBy(0, -18);
        else if (event.clientY > window.innerHeight - 80) window.scrollBy(0, 18);
    });
    function finish(cancelled) {
        if (!drag) return;
        const current = drag;
        drag = null;
        current.row.classList.remove('nc-dragging');
        if (root.hasPointerCapture(current.pointerId)) root.releasePointerCapture(current.pointerId);
        if (cancelled) current.before.forEach(row => current.list.appendChild(row));
        refresh();
        if (!cancelled) save(current.list, current.before, current.handle);
    }
    root.addEventListener('pointerup', event => { if (drag?.pointerId === event.pointerId) finish(false); });
    root.addEventListener('pointercancel', () => finish(true));
    root.addEventListener('lostpointercapture', () => finish(true));
    window.addEventListener('beforeunload', event => {
        if (busy && !root.querySelector('[data-order-reload]').hidden) return;
        if (busy) { event.preventDefault(); event.returnValue = ''; }
    });
    refresh();
})();
