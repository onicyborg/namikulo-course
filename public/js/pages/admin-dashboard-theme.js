(function () {
    'use strict';

    var storageKey = 'namikulo.admin.dashboard.theme';
    var root = document.documentElement;
    root.classList.add('nd-js');
    var storedTheme = null;

    try {
        storedTheme = window.localStorage.getItem(storageKey);
    } catch (error) {
        storedTheme = null;
    }

    var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var defaultTheme = root.getAttribute('data-nc-default-theme');
    var theme = storedTheme === 'light' || storedTheme === 'dark'
        ? storedTheme
        : (defaultTheme === 'dark' || systemDark ? 'dark' : 'light');

    root.setAttribute('data-nc-theme', theme);

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-nc-theme-toggle]');

        if (!toggle) {
            return;
        }

        function render() {
            var isDark = root.getAttribute('data-nc-theme') === 'dark';
            toggle.title = isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap';
            toggle.setAttribute('aria-label', isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
            toggle.setAttribute('aria-pressed', String(isDark));
        }

        toggle.addEventListener('click', function () {
            var nextTheme = root.getAttribute('data-nc-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-nc-theme', nextTheme);

            try {
                window.localStorage.setItem(storageKey, nextTheme);
            } catch (error) {
                // The choice still applies for this page when storage is unavailable.
            }

            render();
        });

        render();
    });
}());
