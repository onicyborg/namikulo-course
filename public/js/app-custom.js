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

    function initializeConfirmDialog() {
        var dialog = document.querySelector('[data-confirm-dialog]');
        if (!dialog) return;

        var title = dialog.querySelector('[data-confirm-dialog-title]');
        var message = dialog.querySelector('[data-confirm-dialog-message]');
        var confirmButton = dialog.querySelector('[data-confirm-dialog-submit]');
        var cancelButton = dialog.querySelector('[data-confirm-dialog-cancel]');
        var approved = new WeakSet();
        var pending = null;

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm') || approved.has(form) || event.defaultPrevented) return;

            event.preventDefault();
            if (typeof dialog.showModal !== 'function') return;

            pending = { form: form, submitter: event.submitter };
            title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
            message.textContent = form.dataset.confirm || '';
            confirmButton.textContent = form.dataset.confirmLabel || 'Konfirmasi';
            dialog.classList.toggle('is-warning', form.dataset.confirmTone === 'warning');
            if (!dialog.open) dialog.showModal();
            cancelButton.focus();
        }, true);

        cancelButton.addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('close', function () { pending = null; });
        confirmButton.addEventListener('click', function () {
            if (!pending) return;
            var form = pending.form;
            var submitter = pending.submitter;
            dialog.close();
            if (!form.isConnected) return;
            approved.add(form);
            try {
                if (submitter && submitter.form === form) form.requestSubmit(submitter);
                else form.requestSubmit();
            } finally {
                approved.delete(form);
            }
        });
    }

    function normalizeSidebarTabOrder() {
        var sidebar = document.querySelector('.main-sidebar');
        if (!sidebar) return;

        var normalize = function () {
            if (Number(sidebar.getAttribute('tabindex')) > 0) sidebar.setAttribute('tabindex', '0');
        };
        normalize();
        new MutationObserver(normalize).observe(sidebar, { attributes: true, attributeFilter: ['tabindex'] });
    }

    function initializeTimezone() {
        var zone = '';
        try {
            zone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        } catch (error) {
            return;
        }

        document.querySelectorAll('[data-browser-timezone]').forEach(function (input) {
            input.value = zone;
        });

        document.querySelectorAll('[data-local-datetime]').forEach(function (element) {
            var date = new Date(element.dateTime || element.dataset.utc);
            if (isNaN(date.getTime())) return;
            element.textContent = new Intl.DateTimeFormat('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short'
            }).format(date);
        });

        document.querySelectorAll('[data-local-datetime-input]').forEach(function (input) {
            if (input.value || !input.dataset.utc) return;
            var date = new Date(input.dataset.utc);
            if (isNaN(date.getTime())) return;
            var pad = function (number) { return String(number).padStart(2, '0'); };
            input.value = date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        });

        document.querySelectorAll('[data-local-time-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                form.querySelectorAll('[data-local-datetime-input]').forEach(function (input) {
                    var utcInput = form.querySelector('[name="' + input.name + '_utc"]');
                    if (!utcInput) return;
                    utcInput.value = '';
                    input.setCustomValidity('');
                    if (!input.value) return;
                    var date = new Date(input.value);
                    var pad = function (number) { return String(number).padStart(2, '0'); };
                    var roundTrip = date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
                    if (isNaN(date.getTime()) || roundTrip !== input.value) {
                        input.setCustomValidity('Waktu ini tidak berlaku di zona waktu perangkat Anda.');
                        event.preventDefault();
                        input.reportValidity();
                        return;
                    }
                    utcInput.value = date.toISOString();
                });
            }, true);
        });

        var body = document.body;
        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (!zone || !body.dataset.timezoneUrl || body.dataset.savedTimezone === zone || !csrf) return;
        fetch(body.dataset.timezoneUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content },
            body: JSON.stringify({ timezone: zone })
        }).then(function (response) {
            if (response.ok) body.dataset.savedTimezone = zone;
        }).catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeLoadingForms();
        initializePasswordToggles();
        initializeDeleteModal();
        initializeConfirmDialog();
        normalizeSidebarTabOrder();
        initializeTimezone();

        var summary = document.querySelector('[data-validation-summary]');
        if (summary) {
            summary.focus();
        }
    });
})();
