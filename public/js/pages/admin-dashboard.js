(function () {
    'use strict';

    var sidebar = document.getElementById('dashboard-sidebar');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('.nd-backdrop');
    var workspace = document.querySelector('.nd-workspace');
    var profile = document.querySelector('[data-profile-menu]');
    var notifications = document.querySelector('[data-notification-menu]');
    var mobile = window.matchMedia('(max-width: 1023px)');
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (!sidebar || !toggle || !backdrop || !workspace) return;

    function closeMenu(menu) {
        if (!menu || !menu.open || menu.dataset.closing) return;
        var popover = menu.querySelector('.nd-profile-dropdown, .nd-notification-popover');
        if (reducedMotion.matches || !popover || !popover.animate) {
            menu.open = false;
            return;
        }
        menu.dataset.closing = '1';
        popover.animate([{ opacity: 1, transform: 'translateY(0)' },
            { opacity: 0, transform: 'translateY(-6px)' }],
            { duration: 150, easing: 'ease-in' }).finished.catch(function () {}).then(function () {
                menu.open = false;
                delete menu.dataset.closing;
            });
    }

    [profile, notifications].forEach(function (menu) {
        if (!menu) return;
        menu.querySelector('summary').addEventListener('click', function (event) {
            if (!menu.open) return;
            event.preventDefault();
            closeMenu(menu);
        });
    });

    function setSidebar(open, restoreFocus) {
        open = Boolean(open && mobile.matches);
        document.body.toggleAttribute('data-sidebar-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
        backdrop.hidden = !open;
        workspace.inert = open;
        sidebar.inert = mobile.matches && !open;

        if (open) {
            closeMenu(profile);
            closeMenu(notifications);
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
                closeMenu(profile);
                profile.querySelector('summary').focus();
            }
            if (notifications && notifications.open) {
                closeMenu(notifications);
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
            if (profile.open) closeMenu(notifications);
        });
        document.addEventListener('click', function (event) {
            if (!profile.contains(event.target)) closeMenu(profile);
        });
        document.addEventListener('focusin', function (event) {
            if (!profile.contains(event.target)) closeMenu(profile);
        });
    }

    if (notifications) {
        notifications.addEventListener('toggle', function () {
            if (notifications.open) closeMenu(profile);
        });
        document.addEventListener('click', function (event) {
            if (!notifications.contains(event.target)) closeMenu(notifications);
        });
        document.addEventListener('focusin', function (event) {
            if (!notifications.contains(event.target)) closeMenu(notifications);
        });
    }

    setSidebar(false, false);
}());
