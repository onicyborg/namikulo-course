(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && window.jQuery.fn.DataTable) {
            ['#category-table', '#course-table', '#user-table', '#member-table'].forEach(function (selector) {
                if (document.querySelector(selector) && !window.jQuery.fn.DataTable.isDataTable(selector)) {
                    window.jQuery(selector).DataTable({ pageLength: 10, responsive: true, language: { emptyTable: 'Belum ada data', search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ data', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } } });
                }
            });
        }

        if (window.contentModalToOpen && window.jQuery) {
            var modal = document.getElementById(window.contentModalToOpen);
            if (modal) window.jQuery(modal).modal('show');
        }

        document.querySelectorAll('[data-move]').forEach(function (button) {
            button.addEventListener('click', function () {
                var item = button.closest('[data-sort-id]');
                var list = item && item.parentElement;
                if (!list) return;
                if (button.dataset.move === 'up' && item.previousElementSibling) {
                    list.insertBefore(item, item.previousElementSibling);
                } else if (button.dataset.move === 'down' && item.nextElementSibling) {
                    list.insertBefore(item.nextElementSibling, item);
                }
            });
        });

        document.querySelectorAll('[data-order-source]').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('input[name="order[]"]').forEach(function (input) { input.remove(); });
                var list = form.dataset.orderSource === 'sections' ? document.querySelector('[data-sort-list="sections"]') : form.previousElementSibling;
                if (!list) return;
                Array.prototype.forEach.call(list.children, function (item) {
                    if (!item.dataset.sortId) return;
                    var input = document.createElement('input');
                    input.type = 'hidden'; input.name = 'order[]'; input.value = item.dataset.sortId;
                    form.appendChild(input);
                });
            });
        });
    });
})();
