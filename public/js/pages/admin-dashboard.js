(function () {
    'use strict';

    var sidebar = document.getElementById('dashboard-sidebar');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('.nd-backdrop');
    var workspace = document.querySelector('.nd-workspace');
    var profile = document.querySelector('[data-profile-menu]');
    var notifications = document.querySelector('[data-notification-menu]');
    var mobile = window.matchMedia('(max-width: 1023px)');

    if (!sidebar || !toggle || !backdrop || !workspace) return;

    function setSidebar(open, restoreFocus) {
        open = Boolean(open && mobile.matches);
        document.body.toggleAttribute('data-sidebar-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
        backdrop.hidden = !open;
        workspace.inert = open;
        sidebar.inert = mobile.matches && !open;

        if (open) {
            if (profile) profile.open = false;
            if (notifications) notifications.open = false;
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            sidebar.querySelector('[data-sidebar-close]').focus();
        } else {
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
            if (restoreFocus) toggle.focus();
        }
    }

    toggle.addEventListener('click', function () {
        setSidebar(!document.body.hasAttribute('data-sidebar-open'), true);
    });

    document.querySelectorAll('[data-sidebar-close]').forEach(function (button) {
        button.addEventListener('click', function () { setSidebar(false, true); });
    });

    mobile.addEventListener('change', function () {
        var focusWasInSidebar = sidebar.contains(document.activeElement);
        setSidebar(false, mobile.matches && focusWasInSidebar);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (document.body.hasAttribute('data-sidebar-open')) setSidebar(false, true);
            if (profile && profile.open) {
                profile.open = false;
                profile.querySelector('summary').focus();
            }
            if (notifications && notifications.open) {
                notifications.open = false;
                notifications.querySelector('summary').focus();
            }
        }

        if (event.key !== 'Tab' || !document.body.hasAttribute('data-sidebar-open')) return;

        var focusable = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'))
            .filter(function (element) { return element.getClientRects().length > 0; });
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    if (profile) {
        profile.addEventListener('toggle', function () {
            if (profile.open && notifications) notifications.open = false;
        });
        document.addEventListener('click', function (event) {
            if (!profile.contains(event.target)) profile.open = false;
        });
        document.addEventListener('focusin', function (event) {
            if (!profile.contains(event.target)) profile.open = false;
        });
    }

    if (notifications) {
        notifications.addEventListener('toggle', function () {
            if (notifications.open && profile) profile.open = false;
        });
        document.addEventListener('click', function (event) {
            if (!notifications.contains(event.target)) notifications.open = false;
        });
        document.addEventListener('focusin', function (event) {
            if (!notifications.contains(event.target)) notifications.open = false;
        });
    }

    setSidebar(false, false);
}());
