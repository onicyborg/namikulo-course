(function () {
    'use strict';
    function init(element, options) {
        var $ = window.jQuery;
        if (!$ || !$.fn.select2 || !element) return null;
        var select = $(element);
        if (select.data('select2')) return select;
        var modal = element.closest('.modal');
        select.select2(Object.assign({
            theme: 'nc-premium',
            width: '100%',
            dropdownParent: $(modal || document.body),
            minimumResultsForSearch: 0,
            language: {
                searching: function () { return 'Mencari…'; },
                noResults: function () { return 'Tidak ada pilihan yang sesuai'; },
                errorLoading: function () { return 'Pilihan gagal dimuat. Coba lagi.'; },
                loadingMore: function () { return 'Memuat pilihan berikutnya…'; }
            }
        }, options || {}));
        var instance = select.data('select2');
        var selection = instance.$selection;
        var label = element.id && document.querySelector('label[for="' + CSS.escape(element.id) + '"]');
        if (label) selection.attr('aria-label', label.textContent.trim());
        if (element.classList.contains('is-invalid')) selection.attr('aria-invalid', 'true');
        if (element.getAttribute('aria-required')) selection.attr('aria-required', element.getAttribute('aria-required'));
        select.on('select2:open.ncSearchable', function () {
            var search = instance.$dropdown.find('.select2-search__field');
            search.attr({ 'aria-label': 'Cari pilihan', placeholder: 'Ketik untuk mencari…', autocomplete: 'off' });
            requestAnimationFrame(function () { if (search[0] && search[0].isConnected) search[0].focus(); });
        });
        return select;
    }
    function destroyWithin(root) {
        var $ = window.jQuery;
        if (!$ || !$.fn.select2) return;
        root.querySelectorAll('select.select2-hidden-accessible').forEach(function (element) {
            var select = $(element);
            if (select.data('select2')) {
                select.off('.ncSearchable').select2('destroy');
                delete element.dataset.searchInitialized;
            }
        });
    }
    window.ncSearchableSelect = { init: init, destroyWithin: destroyWithin };
    if (window.jQuery) {
        window.jQuery(document).on('hide.bs.modal.ncSearchable', '.modal', function () {
            var $ = window.jQuery;
            this.querySelectorAll('select.select2-hidden-accessible').forEach(function (element) {
                if ($(element).data('select2')) $(element).select2('close');
            });
        });
    }
    document.addEventListener('nc:content-dispose', function (event) {
        if (event.detail && event.detail.root) destroyWithin(event.detail.root);
    });
})();
