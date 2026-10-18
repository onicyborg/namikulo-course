(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-user-filters]');
        if (!form) return;
        const size = form.querySelector('[name="per_page"]');
        const search = form.querySelector('[name="q"]');
        const tableElement = document.getElementById('users-table');
        const footer = document.querySelector('[data-user-table-footer]');
        const $ = window.jQuery;
        let timer;
        let composing = false;
        let table;

        if (tableElement && $ && $.fn.DataTable) {
            table = $(tableElement).DataTable({
                pageLength: Number(size.value),
                responsive: true,
                dom: 'tip',
                order: [[0, 'asc']],
                columnDefs: [{ targets: 5, orderable: false, searchable: false }],
                language: {
                    emptyTable: 'Tidak ada user yang sesuai filter.',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ user',
                    infoEmpty: 'Tidak ada user',
                    paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                },
                initComplete: function () {
                    const wrapper = this.api().table().container();
                    const info = wrapper.querySelector('.dataTables_info');
                    const pager = wrapper.querySelector('.dataTables_paginate');
                    if (info) footer.appendChild(info);
                    if (pager) footer.appendChild(pager);
                }
            });
        }

        function submitFilters() {
            clearTimeout(timer);
            form.requestSubmit();
        }

        size.addEventListener('change', function () {
            if (!table) return submitFilters();
            table.page.len(Number(size.value)).draw();
            const url = new URL(window.location.href);
            if (size.value === '10') url.searchParams.delete('per_page');
            else url.searchParams.set('per_page', size.value);
            window.history.replaceState(null, '', url);
        });
        form.querySelector('[name="role"]').addEventListener('change', submitFilters);
        form.querySelector('[name="status"]').addEventListener('change', submitFilters);
        search.addEventListener('compositionstart', function () { composing = true; clearTimeout(timer); });
        search.addEventListener('compositionend', function () { composing = false; timer = setTimeout(submitFilters, 450); });
        search.addEventListener('input', function () {
            clearTimeout(timer);
            if (!composing) timer = setTimeout(submitFilters, 450);
        });
        form.addEventListener('submit', function () { clearTimeout(timer); form.setAttribute('aria-busy', 'true'); });
    });
})();
