(function () {
    'use strict';
    var modal = document.getElementById('member-create');
    var select = document.querySelector('[data-member-user-select]');
    if (!modal || !select || !window.jQuery) return;
    var hint = modal.querySelector('[data-member-timezone-hint]');
    var startsInput = modal.querySelector('#member-starts');
    if (startsInput) startsInput.addEventListener('input', function () { startsInput.dataset.defaultNow = 'false'; });
    function showMemberTimezone(timezone) {
        if (!hint) return;
        hint.textContent = timezone
            ? 'Durasi enam bulan dihitung menurut zona ' + timezone + '.'
            : 'Zona member belum terdeteksi; durasi enam bulan dihitung menurut UTC.';
    }
    window.jQuery(modal).on('shown.bs.modal', function () {
        if (startsInput && startsInput.dataset.defaultNow === 'true') {
            var now = new Date();
            var pad = function (number) { return String(number).padStart(2, '0'); };
            startsInput.value = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        }
        if (!window.jQuery.fn.select2 || select.dataset.searchInitialized === 'true') return;
        var $select = window.jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
        $select.select2({
            width: '100%',
            dropdownParent: window.jQuery(modal),
            minimumInputLength: 0,
            placeholder: 'Pilih atau cari member',
            language: {
                searching: function () { return 'Mencari user...'; },
                noResults: function () { return 'Member aktif tidak ditemukan'; },
                errorLoading: function () { return 'Pencarian gagal. Coba lagi.'; }
            },
            ajax: {
                url: select.dataset.searchUrl,
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) { return { results: data.results || [] }; }
            }
        });
        $select.on('select2:select', function (event) {
            showMemberTimezone(event.params.data.timezone || '');
        });
        var selected = select.options[select.selectedIndex];
        if (selected && selected.value) showMemberTimezone(selected.dataset.timezone || '');
        select.dataset.searchInitialized = 'true';
    });
})();
