(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.querySelector('[data-site-menu-toggle]');
        var menu = document.querySelector('[data-site-menu]');
        var header = document.querySelector('.public-header');
        if (header) {
            var updateHeader = function () { header.classList.toggle('is-scrolled', window.scrollY > 24); };
            updateHeader();
            window.addEventListener('scroll', updateHeader, { passive: true });
        }
        if (!button || !menu) return;

        button.addEventListener('click', function () {
            var isOpen = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!isOpen));
            button.setAttribute('aria-label', isOpen ? 'Buka menu' : 'Tutup menu');
            menu.classList.toggle('is-open', !isOpen);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || button.getAttribute('aria-expanded') !== 'true') return;
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Buka menu');
            menu.classList.remove('is-open');
            button.focus();
        });
    });
})();
