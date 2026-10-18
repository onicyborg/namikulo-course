(function () {
    'use strict';

    document.querySelectorAll('[data-member-auto-filter]').forEach(function (form) {
        var submit = form.querySelector('[data-filter-submit]');
        if (!form.requestSubmit) return;
        if (submit) submit.hidden = true;
        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () { form.requestSubmit(); });
        });
    });
}());
