(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const tableElement = document.querySelector('[data-admin-data-table]');
        const filters = document.querySelector('[data-admin-table-filters]');
        if (!tableElement || !filters) return;

        const size = filters.querySelector('[name="per_page"]');
        const search = filters.querySelector('[name="q"]');
        const status = filters.querySelector('[name="status"]');
        const reset = filters.querySelector('[data-admin-table-reset]');
        const feedback = document.querySelector('[data-admin-table-feedback]');
        const area = filters.closest('[data-admin-table-area]');
        const pagination = area.querySelector('[data-admin-table-pagination]');
        const pageInfo = pagination.querySelector('[data-admin-table-page-info]');
        const pageNumbers = pagination.querySelector('[data-admin-table-page-numbers]');
        const previous = pagination.querySelector('[data-admin-table-previous]');
        const next = pagination.querySelector('[data-admin-table-next]');
        const $ = window.jQuery;
        let table;
        let timer;
        let composing = false;

        function syncUrl() {
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            for (const [key, value] of [['q', search.value.trim()], ['status', status.value]]) {
                if (value) url.searchParams.set(key, value);
                else url.searchParams.delete(key);
            }
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

        function updatePagination(api) {
            const info = api.page.info();
            const pageUrl = function (index) {
                const url = new URL(window.location.href);
                if (index > 0) url.searchParams.set('page', String(index + 1));
                else url.searchParams.delete('page');
                return url.toString();
            };
            const setNavigation = function (link, disabled, index) {
                link.href = disabled ? '#' : pageUrl(index);
                if (disabled) {
                    link.setAttribute('aria-disabled', 'true');
                    link.tabIndex = -1;
                } else {
                    link.removeAttribute('aria-disabled');
                    link.removeAttribute('tabindex');
                }
            };
            pageInfo.textContent = info.recordsDisplay
                ? 'Menampilkan ' + (info.start + 1) + '–' + info.end + ' dari ' + info.recordsDisplay + ' data'
                : 'Tidak ada data';
            setNavigation(previous, info.page <= 0, info.page - 1);
            setNavigation(next, info.pages === 0 || info.page >= info.pages - 1, info.page + 1);
            pageNumbers.replaceChildren();

            function addPage(index) {
                const link = document.createElement('a');
                link.href = pageUrl(index);
                link.textContent = String(index + 1);
                link.dataset.adminTablePage = String(index);
                link.setAttribute('aria-label', 'Halaman ' + (index + 1));
                if (index === info.page) link.setAttribute('aria-current', 'page');
                pageNumbers.appendChild(link);
            }

            function addEllipsis() {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '…';
                ellipsis.setAttribute('aria-hidden', 'true');
                pageNumbers.appendChild(ellipsis);
            }

            const first = Math.max(0, Math.min(info.page - 2, info.pages - 5));
            const last = Math.min(info.pages, first + 5);
            if (first > 0) {
                addPage(0);
                if (first > 1) addEllipsis();
            }
            for (let index = first; index < last; index++) addPage(index);
            if (last < info.pages) {
                if (last < info.pages - 1) addEllipsis();
                addPage(info.pages - 1);
            }

            window.history.replaceState(null, '', pageUrl(info.page));
            area.classList.add('nc-data-table-enhanced');
        }

        function applyFilters() {
            clearTimeout(timer);
            if (!table) {
                filters.requestSubmit();
                return;
            }
            syncUrl();
            table.page('first').draw();
        }

        if ($ && $.fn.DataTable) {
            const columns = tableElement.dataset.columns.split(',');
            const sortable = tableElement.dataset.sortable.split(',').map(Number);
            const orderColumn = Number(tableElement.dataset.orderColumn);
            $(tableElement).on('xhr.dt', function (event, settings, json) {
                feedback.hidden = Boolean(json);
                if (!json) feedback.textContent = 'Daftar gagal dimuat. Periksa koneksi, lalu ubah filter untuk mencoba lagi.';
            });
            table = $(tableElement).DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: Number(size.value),
                displayStart: Number(tableElement.dataset.displayStart) || 0,
                lengthMenu: [10, 25, 50, 100],
                dom: 't',
                order: [[orderColumn, tableElement.dataset.orderDirection || 'desc']],
                columns: columns.map(function (name, index) {
                    return { data: name, orderable: sortable.includes(index), searchable: false };
                }),
                ajax: {
                    url: tableElement.dataset.sourceUrl,
                    data: function (data) {
                        data.q = search.value.trim();
                        data.status = status.value;
                    }
                },
                drawCallback: function () {
                    formatTimes();
                    updatePagination(this.api());
                },
                language: {
                    processing: 'Memuat data…',
                    emptyTable: tableElement.dataset.emptyMessage || 'Belum ada data.',
                    zeroRecords: 'Tidak ada data yang sesuai filter.',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(difilter dari _MAX_ data)',
                    paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                }
            });
        }

        pagination.addEventListener('click', function (event) {
            if (!table) return;
            const link = event.target.closest('a');
            if (!link || link.getAttribute('aria-disabled') === 'true') return;
            event.preventDefault();
            if (link === previous) table.page('previous').draw('page');
            else if (link === next) table.page('next').draw('page');
            else if (link.dataset.adminTablePage !== undefined) table.page(Number(link.dataset.adminTablePage)).draw('page');
        });

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
        filters.addEventListener('submit', function (event) {
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
