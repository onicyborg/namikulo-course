(function () {
    'use strict';

    function initializeLoadingForms() {
        document.querySelectorAll('[data-loading-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');

                if (!button || button.disabled) {
                    return;
                }

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.dataset.originalText = button.textContent;
                button.textContent = button.dataset.loadingText || 'Memproses...';
            });
        });
    }

    function initializePasswordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            var input = document.getElementById(button.dataset.passwordToggle);

            if (!input) {
                return;
            }

            button.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', show ? 'true' : 'false');
                button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                button.textContent = show ? 'Sembunyikan' : 'Tampilkan';
            });
        });
    }

    function initializeDeleteModal() {
        var modal = document.querySelector('[data-delete-form]');

        if (!modal) {
            return;
        }

        document.querySelectorAll('[data-delete-url]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                modal.action = trigger.dataset.deleteUrl;
                var name = document.querySelector('[data-delete-name]');
                if (name) {
                    name.textContent = trigger.dataset.name || 'ini';
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeLoadingForms();
        initializePasswordToggles();
        initializeDeleteModal();

        var summary = document.querySelector('[data-validation-summary]');
        if (summary) {
            summary.focus();
        }
    });
})();
