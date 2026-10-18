(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const tooltip = document.createElement('div');
        tooltip.className = 'nc-action-tooltip';
        tooltip.id = 'admin-action-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.hidden = true;
        document.body.appendChild(tooltip);
        let active = null;
        let hideTimer;
        function hide() {
            clearTimeout(hideTimer);
            if (active) active.removeAttribute('aria-describedby');
            active = null;
            tooltip.hidden = true;
        }
        function show(button) {
            hide();
            active = button;
            tooltip.textContent = button.dataset.actionTooltip;
            tooltip.hidden = false;
            button.setAttribute('aria-describedby', tooltip.id);
            const rect = button.getBoundingClientRect();
            const tip = tooltip.getBoundingClientRect();
            tooltip.style.left = Math.max(8, Math.min(window.innerWidth - tip.width - 8, rect.left + (rect.width - tip.width) / 2)) + 'px';
            tooltip.style.top = (rect.top >= tip.height + 12 ? rect.top - tip.height - 8 : rect.bottom + 8) + 'px';
        }
        document.addEventListener('pointerover', function (event) {
            const button = event.target.closest('[data-action-tooltip]');
            if (button && button !== active) show(button);
            if (event.target === tooltip || button === active) clearTimeout(hideTimer);
        });
        document.addEventListener('pointerout', function (event) {
            if (event.target.closest('[data-action-tooltip]') || event.target === tooltip) {
                hideTimer = setTimeout(function () { if (document.activeElement !== active) hide(); }, 120);
            }
        });
        document.addEventListener('focusin', function (event) {
            const button = event.target.closest('[data-action-tooltip]');
            if (button) show(button); else hide();
        });
        document.addEventListener('click', hide);
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') hide(); });
        document.addEventListener('scroll', hide, true);
        window.addEventListener('resize', hide);
    });
})();
