(function () {
    'use strict';

    var page = document.querySelector('[data-order-status-url]');
    if (!page) return;

    var countdown = page.querySelector('[data-payment-countdown]');
    var live = page.querySelector('[data-payment-live]');
    var expiresAt = page.dataset.orderExpires ? new Date(page.dataset.orderExpires).getTime() : 0;

    function updateCountdown() {
        if (!countdown || !expiresAt) return;
        var seconds = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        countdown.textContent = seconds > 0
            ? 'Sisa waktu bayar: ' + hours + ' jam ' + minutes + ' menit'
            : 'Batas waktu bayar telah lewat. Status akhir sedang dikonfirmasi.';
    }

    function pollStatus() {
        if (document.hidden || page.dataset.orderStatus !== 'pending') return;
        fetch(page.dataset.orderStatusUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (response) {
                if (!response.ok) throw new Error('Status belum tersedia');
                return response.json();
            })
            .then(function (data) {
                if (live) live.textContent = '';
                if (data && (data.status !== page.dataset.orderStatus || (data.payment_status || '') !== page.dataset.paymentStatus)) window.location.reload();
            }).catch(function () { if (live) live.textContent = 'Status belum dapat diperbarui. Muat ulang halaman bila pesan ini berlanjut.'; });
    }

    page.querySelectorAll('[data-copy-text]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!navigator.clipboard) { button.textContent = 'Salin manual'; return; }
            navigator.clipboard.writeText(button.dataset.copyText).then(function () {
                button.textContent = 'Tersalin';
                window.setTimeout(function () { button.textContent = button.dataset.copyLabel || 'Salin'; }, 2000);
            }).catch(function () { button.textContent = 'Salin manual'; });
        });
    });

    updateCountdown();
    if (page.dataset.orderStatus === 'pending') {
        window.setInterval(function () { updateCountdown(); pollStatus(); }, 5000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) pollStatus(); });
    }
}());
