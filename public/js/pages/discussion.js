(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        var prompt = form.getAttribute('data-confirm');
        if (prompt && !window.confirm(prompt)) event.preventDefault();
    });
})();
