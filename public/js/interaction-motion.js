(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    function enhance(details) {
        if (details.dataset.motionEnhanced || reduce.matches || !details.animate) return;
        var summary = details.querySelector(':scope > summary');
        if (!summary) return;
        details.dataset.motionEnhanced = '1';
        var animation = null;

        summary.addEventListener('click', function (event) {
            if (reduce.matches) return;
            event.preventDefault();
            if (animation) animation.cancel();

            var start = details.getBoundingClientRect().height;
            var closing = details.open;
            if (!closing) details.open = true;
            var end = closing ? summary.getBoundingClientRect().height : details.getBoundingClientRect().height;
            if (Math.abs(end - start) < 2) {
                if (closing) details.open = false;
                return;
            }
            details.style.overflow = 'hidden';
            animation = details.animate([{ height: start + 'px', opacity: closing ? 1 : .82 },
                { height: end + 'px', opacity: closing ? .82 : 1 }],
                { duration: closing ? 190 : 260, easing: 'cubic-bezier(.2,.8,.2,1)' });
            animation.onfinish = function () {
                if (closing) details.open = false;
                details.style.overflow = '';
                animation = null;
            };
            animation.oncancel = function () { details.style.overflow = ''; animation = null; };
        });
    }

    window.ncEnhanceDetails = function (root) {
        (root || document).querySelectorAll('details:not(.nd-profile):not(.nd-notification-menu)').forEach(enhance);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.ncEnhanceDetails(document);
    });
})();
