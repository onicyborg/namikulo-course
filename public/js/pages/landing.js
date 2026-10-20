(function () {
    'use strict';
    var menu = document.querySelector('.nm-public [data-site-menu]');
    var toggle = document.querySelector('.nm-public [data-site-menu-toggle]');
    if (!menu || !toggle) return;
    menu.addEventListener('click', function (event) {
        var link = event.target.closest('a[href^="#"]');
        if (!link) return;
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Buka menu');
    });
})();
