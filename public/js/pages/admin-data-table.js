(function () {
    'use strict';
    function initializeTable() {
        const tableElement = document.querySelector('[data-admin-data-table]');
        const filters = document.querySelector('[data-admin-table-filters]');
        if (!tableElement || !filters) return;

        const size = filters.querySelector('[name="per_page"]');
        const search = filters.querySelector('[name="q"]');
        const selectFilters = Array.from(filters.querySelectorAll('select[name]')).filter(element => element !== size);
        const reset = filters.querySelector('[data-admin-table-reset]');
        const area = filters.closest('[data-admin-table-area]');
        const feedback = area.querySelector('[data-admin-table-feedback]');
        const stage = area.querySelector('[data-server-table-stage]');
        const scroll = area.querySelector('[data-server-table-scroll]');
        const loader = area.querySelector('[data-server-table-loader]');
        const pagination = area.querySelector('[data-admin-table-pagination]');
        const pageInfo = pagination.querySelector('[data-admin-table-page-info]');
        const pageNumbers = pagination.querySelector('[data-admin-table-page-numbers]');
        const previous = pagination.querySelector('[data-admin-table-previous]');
        const next = pagination.querySelector('[data-admin-table-next]');
        const $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.DataTable) {
            feedback.hidden = false;
            feedback.textContent = 'Tabel interaktif gagal dimuat. Muat ulang halaman untuk mencoba lagi.';
            return;
        }
        let table;
        let timer;
        let composing = false;
        let loaderTimer;
        let loadingSince = 0;
        let loadVersion = 0;

        function beginLoad() {
            const version = ++loadVersion;
            clearTimeout(loaderTimer);
            stage.setAttribute('aria-busy', 'true');
            scroll.inert = true;
            loaderTimer = setTimeout(function () {
                if (version !== loadVersion) return;
                loadingSince = Date.now();
                loader.hidden = false;
            }, 120);
        }

        function finishLoad() {
            const version = loadVersion;
            clearTimeout(loaderTimer);
            const visibleFor = loadingSince ? Date.now() - loadingSince : 0;
            const remaining = loader.hidden ? 0 : Math.max(0, 180 - visibleFor);
            loaderTimer = setTimeout(function () {
                if (version !== loadVersion) return;
                loader.hidden = true;
                loadingSince = 0;
                stage.setAttribute('aria-busy', 'false');
                scroll.inert = false;
            }, remaining);
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
            const setNavigation = function (button, disabled) { button.disabled = disabled; };
            pageInfo.textContent = info.recordsDisplay
                ? 'Menampilkan ' + (info.start + 1) + '–' + info.end + ' dari ' + info.recordsDisplay + ' data'
                : 'Tidak ada data';
            setNavigation(previous, info.page <= 0);
            setNavigation(next, info.pages === 0 || info.page >= info.pages - 1);
            pageNumbers.replaceChildren();

            function addPage(index) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = String(index + 1);
                button.dataset.adminTablePage = String(index);
                button.setAttribute('aria-label', 'Halaman ' + (index + 1));
                if (index === info.page) button.setAttribute('aria-current', 'page');
                pageNumbers.appendChild(button);
            }

            function addEllipsis() {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '…';
                ellipsis.setAttribute('aria-hidden', 'true');
                pageNumbers.appendChild(ellipsis);
            }

            const first = Math.max(0, Math.min(info.page - 1, info.pages - 3));
            const last = Math.min(info.pages, first + 3);
            if (first > 0) {
                addPage(0);
                if (first > 1) addEllipsis();
            }
            for (let index = first; index < last; index++) addPage(index);
            if (last < info.pages) {
                if (last < info.pages - 1) addEllipsis();
                addPage(info.pages - 1);
            }

            area.classList.add('nc-data-table-enhanced');
        }

        function applyFilters() {
            clearTimeout(timer);
            if (!table) return;
            table.page('first').draw();
        }

        {
            const columns = tableElement.dataset.ncColumns.split(',');
            const sortable = tableElement.dataset.ncSortable.split(',').map(Number);
            const orderColumn = Number(tableElement.dataset.ncOrderColumn);
            $(tableElement).on('preXhr.dt', beginLoad);
            $(tableElement).on('xhr.dt', function (event, settings, json, xhr) {
                if (json && Number(json.draw) < settings.iDraw) return;
                if (!json && xhr !== settings.jqXHR) return;
                feedback.hidden = Boolean(json && !json.error);
                if (!json || json.error) {
                    finishLoad();
                    feedback.textContent = 'Daftar gagal dimuat. Periksa koneksi atau sesi Anda. ';
                    const retry = document.createElement('button');
                    retry.type = 'button';
                    retry.className = 'btn btn-outline-primary btn-sm';
                    retry.textContent = 'Coba lagi';
                    retry.addEventListener('click', function () { table.ajax.reload(null, false); });
                    feedback.appendChild(retry);
                    return true;
                }
            });
            $.fn.dataTable.ext.errMode = 'none';
            table = $(tableElement).DataTable({
                orderMulti: false,
                processing: true,
                serverSide: true,
                responsive: tableElement.dataset.ncAdaptive !== 'audit',
                autoWidth: tableElement.dataset.ncAdaptive !== 'audit',
                pageLength: Number(size.value),
                displayStart: Number(tableElement.dataset.ncDisplayStart) || 0,
                lengthMenu: [10, 25, 50, 100],
                dom: 't',
                order: tableElement.dataset.ncOrderColumn === '' ? [] : [[orderColumn, tableElement.dataset.ncOrderDirection || 'desc']],
                columns: columns.map(function (name, index) {
                    return { data: name, orderable: sortable.includes(index), searchable: false };
                }),
                ajax: {
                    url: tableElement.dataset.ncSourceUrl,
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    data: function (data) {
                        data.q = search.value.trim();
                        selectFilters.forEach(element => { data[element.name] = element.value; });
                    }
                },
                drawCallback: function () {
                    formatTimes();
                    if (window.ncEnhanceDetails) window.ncEnhanceDetails(tableElement);
                    const api = this.api();
                    const info = api.page.info();
                    if (info.recordsDisplay > 0 && info.start >= info.recordsDisplay) {
                        api.page('last').draw('page');
                        return;
                    }
                    updatePagination(api);
                    finishLoad();
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

        if (tableElement.dataset.ncAdaptive === 'audit' && window.ncAdaptiveAudit) window.ncAdaptiveAudit(table, tableElement, scroll);

        pagination.addEventListener('click', function (event) {
            if (!table) return;
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            if (button === previous) table.page('previous').draw('page');
            else if (button === next) table.page('next').draw('page');
            else if (button.dataset.adminTablePage !== undefined) table.page(Number(button.dataset.adminTablePage)).draw('page');
        });

        size.addEventListener('change', function () {
            if (table) {
                table.page.len(Number(size.value));
                table.page('first').draw();
            } else applyFilters();
        });
        selectFilters.forEach(element => element.addEventListener('change', applyFilters));
        search.addEventListener('compositionstart', function () { composing = true; clearTimeout(timer); });
        search.addEventListener('compositionend', function () { composing = false; timer = setTimeout(applyFilters, 250); });
        search.addEventListener('input', function () {
            clearTimeout(timer);
            if (!composing) timer = setTimeout(applyFilters, 250);
        });
        reset.addEventListener('click', function () {
            if (!table) return;
            clearTimeout(timer);
            search.value = '';
            selectFilters.forEach(element => { element.value = ''; });
            size.value = '10';
            table.page.len(10);
            table.page('first').draw();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTable, { once: true });
    } else {
        initializeTable();
    }
})();
