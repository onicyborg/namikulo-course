(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-discussion-filters]');
        const tableElement = document.getElementById('discussion-table');
        if (!form || !tableElement) return;

        const size = form.querySelector('[name="per_page"]');
        const search = form.querySelector('[name="q"]');
        const status = form.querySelector('[name="status"]');
        const reset = form.querySelector('.nc-discussion-reset');
        const feedback = document.querySelector('[data-discussion-feedback]');
        let timer;
        let composing = false;
        const $ = window.jQuery;
        let table = null;

        function syncUrl() {
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            if (search.value.trim()) url.searchParams.set('q', search.value.trim());
            else url.searchParams.delete('q');
            if (status.value) url.searchParams.set('status', status.value);
            else url.searchParams.delete('status');
            if (size.value !== '10') url.searchParams.set('per_page', size.value);
            else url.searchParams.delete('per_page');
            window.history.replaceState(null, '', url);
        }

        function formatTimes() {
            tableElement.querySelectorAll('[data-local-datetime]').forEach(function (element) {
                const date = new Date(element.dateTime || element.dataset.utc);
                if (Number.isNaN(date.getTime())) return;
                element.textContent = new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short'
                }).format(date);
            });
        }

        function applyFilters() {
            clearTimeout(timer);
            if (!table) {
                form.requestSubmit();
                return;
            }
            syncUrl();
            table.page('first').draw();
        }

        if ($ && $.fn.DataTable) {
            document.querySelector('.nc-discussions').classList.add('nc-discussions-enhanced');
            $(tableElement).on('xhr.dt', function (event, settings, json) {
                feedback.hidden = Boolean(json);
                if (!json) feedback.textContent = 'Daftar diskusi gagal dimuat. Periksa koneksi, lalu ubah filter untuk mencoba lagi.';
            });
            table = $(tableElement).DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: Number(size.value),
                lengthMenu: [10, 25, 50, 100],
                dom: 'tip',
                order: [[1, 'desc']],
                columns: [
                    { data: 'lesson', orderable: false },
                    { data: 'author', orderable: true },
                    { data: 'body', orderable: false },
                    { data: 'status', orderable: false },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                ajax: {
                    url: tableElement.dataset.sourceUrl,
                    data: function (data) {
                        data.status = status.value;
                        data.q = search.value.trim();
                    }
                },
                drawCallback: formatTimes,
                language: {
                    processing: 'Memuat diskusi…',
                    emptyTable: 'Belum ada kiriman diskusi.',
                    zeroRecords: 'Tidak ada kiriman yang sesuai filter.',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ kiriman',
                    infoEmpty: 'Tidak ada kiriman',
                    infoFiltered: '(difilter dari _MAX_ kiriman)',
                    paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                }
            });
        }

        size.addEventListener('change', function () {
            if (table) {
                syncUrl();
                table.page.len(Number(size.value)).draw();
            } else applyFilters();
        });
        status.addEventListener('change', applyFilters);
        search.addEventListener('compositionstart', function () { composing = true; clearTimeout(timer); });
        search.addEventListener('compositionend', function () { composing = false; timer = setTimeout(applyFilters, 450); });
        search.addEventListener('input', function () {
            clearTimeout(timer);
            if (!composing) timer = setTimeout(applyFilters, 450);
        });
        form.addEventListener('submit', function (event) {
            if (!table) return;
            event.preventDefault();
            applyFilters();
        });
        reset.addEventListener('click', function (event) {
            if (!table) return;
            event.preventDefault();
            clearTimeout(timer);
            search.value = '';
            status.value = '';
            size.value = '10';
            syncUrl();
            table.page.len(10).draw();
        });
    });
})();
