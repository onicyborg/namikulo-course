(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-member-filters]');
        if (!form) return;
        const size = form.querySelector('[name="per_page"]');
        const search = form.querySelector('[name="q"]');
        let timer;
        let composing = false;
        const submit = function () { clearTimeout(timer); form.requestSubmit(); };
        const $ = window.jQuery;
        let table;
        if ($ && $.fn.DataTable && document.getElementById('course-members-table')) {
            table = $('#course-members-table').DataTable({
                pageLength: Number(size.value), responsive: true, dom: 'tip', order: [],
                columnDefs: [{ targets: 5, orderable: false, searchable: false }],
                language: { emptyTable: 'Tidak ada anggota yang sesuai', info: 'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty: 'Tidak ada data', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } }
            });
        }
        size.addEventListener('change', function () {
            if (table) {
                table.page.len(Number(size.value)).draw();
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', size.value);
                history.replaceState(null, '', url);
            } else submit();
        });
        form.querySelector('[name="status"]').addEventListener('change', submit);
        search.addEventListener('compositionstart', function () { composing = true; clearTimeout(timer); });
        search.addEventListener('compositionend', function () { composing = false; timer = setTimeout(submit, 650); });
        search.addEventListener('input', function () { clearTimeout(timer); if (!composing) timer = setTimeout(submit, 650); });
        form.addEventListener('submit', function () { clearTimeout(timer); form.setAttribute('aria-busy', 'true'); });
    });
})();
