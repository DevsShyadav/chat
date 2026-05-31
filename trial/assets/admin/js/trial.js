/**
 * WP AI Chatbot - Trial Live Countdown
 * Ticks the 24-hour countdown every second and reloads on expiry
 * so the server-side auto-delete logic kicks in.
 */
(function () {
    'use strict';

    var banner = document.getElementById('wpaicb-trial-banner');
    var countdownEl = document.getElementById('wpaicb-countdown');
    if (!countdownEl) {
        return;
    }

    var expiry = parseInt(countdownEl.getAttribute('data-expiry'), 10);
    if (!expiry || isNaN(expiry)) {
        return;
    }

    var hEl = document.getElementById('wpaicb-cd-h');
    var mEl = document.getElementById('wpaicb-cd-m');
    var sEl = document.getElementById('wpaicb-cd-s');

    function pad(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    function tick() {
        var now = Date.now();
        var remaining = Math.max(0, Math.floor((expiry - now) / 1000));

        var hours = Math.floor(remaining / 3600);
        var minutes = Math.floor((remaining % 3600) / 60);
        var seconds = remaining % 60;

        if (hEl) { hEl.textContent = pad(hours); }
        if (mEl) { mEl.textContent = pad(minutes); }
        if (sEl) { sEl.textContent = pad(seconds); }

        // Urgent styling when under 1 hour remaining
        if (banner && remaining <= 3600 && remaining > 0) {
            banner.classList.add('wpaicb-urgent');
        }

        // Trial expired -> reload so server deletes the plugin
        if (remaining <= 0) {
            clearInterval(timer);
            if (hEl) { hEl.textContent = '00'; }
            if (mEl) { mEl.textContent = '00'; }
            if (sEl) { sEl.textContent = '00'; }
            // Reload after a brief pause to trigger server-side self-destruct
            setTimeout(function () {
                window.location.reload();
            }, 1500);
        }
    }

    tick();
    var timer = setInterval(tick, 1000);
})();
