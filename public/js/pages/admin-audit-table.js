(function () {
    'use strict';
    window.ncAdaptiveAudit = function (api, table, viewport) {
        let compact;
        function controls() {
            api.rows({ page: 'current' }).every(function () {
                const cell = api.cell(this.index(), 0).node();
                cell.querySelector('[data-audit-expand]')?.remove();
                if (!compact) return;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'nc-audit-expand';
                button.dataset.auditExpand = '';
                button.setAttribute('aria-label', 'Tampilkan sumber dan keterangan aktivitas');
                button.setAttribute('aria-expanded', 'false');
                button.textContent = '+';
                cell.prepend(button);
            });
        }
        function adapt() {
            // Five readable columns need 850px inside the content area, excluding the sidebar.
            const next = viewport.clientWidth < 850;
            if (next === compact) return;
            compact = next;
            table.classList.toggle('nc-audit-compact', compact);
            api.rows().every(function () { this.child.remove(); });
            api.columns([3, 4]).visible(!compact, false);
            api.columns.adjust();
            controls();
        }
        table.addEventListener('click', function (event) {
            const button = event.target.closest('[data-audit-expand]');
            if (!button) return;
            const row = api.row(button.closest('tr'));
            const opening = !row.child.isShown();
            if (opening) {
                const panel = document.createElement('div');
                panel.className = 'nc-audit-details';
                [3, 4].forEach(function (index) {
                    const group = document.createElement('div');
                    const label = document.createElement('strong');
                    label.textContent = index === 3 ? 'Sumber / ID record' : 'Keterangan';
                    const content = document.createElement('div');
                    // Clone server-rendered cells to retain escaping and existing detail markup.
                    const cell = api.cell(row.index(), index).node();
                    Array.from(cell.childNodes).forEach(node => content.appendChild(node.cloneNode(true)));
                    group.append(label, content);
                    panel.appendChild(group);
                });
                row.child(panel, 'nc-audit-child').show();
                if (window.ncEnhanceDetails) window.ncEnhanceDetails(panel);
            } else row.child.remove();
            button.textContent = opening ? '−' : '+';
            button.setAttribute('aria-expanded', String(opening));
            button.setAttribute('aria-label', opening ? 'Tutup detail aktivitas' : 'Tampilkan sumber dan keterangan aktivitas');
        });
        window.jQuery(table).on('draw.dt', function () { api.rows().every(function () { this.child.remove(); }); controls(); });
        new ResizeObserver(adapt).observe(viewport);
        adapt();
    };
})();
