(function () {
    'use strict';

    function highlight(id) {
        var post = document.getElementById(id);
        if (!post || !post.classList.contains('discussion-post')) return false;
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        post.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'center' });
        post.focus({ preventScroll: true });
        post.classList.remove('is-reply-target');
        requestAnimationFrame(function () { post.classList.add('is-reply-target'); });
        window.setTimeout(function () { post.classList.remove('is-reply-target'); }, 3500);
        return true;
    }

    document.addEventListener('click', function (event) {
        var quote = event.target.closest('[data-discussion-quote]');
        if (!quote || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (highlight(quote.dataset.discussionQuote)) {
            event.preventDefault();
            history.replaceState(null, '', '#' + quote.dataset.discussionQuote);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (/^#discussion-post-[a-f0-9-]+$/i.test(location.hash)) highlight(location.hash.slice(1));
    });

})();
