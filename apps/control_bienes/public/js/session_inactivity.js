(function () {
    'use strict';

    var modal = document.getElementById('session-warning-modal');
    if (!modal) return;

    var inactivityMs = Math.max(10000, Number(modal.dataset.inactivitySeconds || 600) * 1000);
    // La tolerancia después de mostrar el aviso es fija por decisión funcional.
    var graceMs = 300000;
    var continueButton = document.getElementById('session-continue-btn');
    var countdown = document.getElementById('session-countdown');
    var lastActivity = Date.now();
    var warningStartedAt = 0;
    var warningVisible = false;
    var lastPingAt = Date.now();
    var pingInProgress = false;

    function formatTime(milliseconds) {
        var seconds = Math.max(0, Math.ceil(milliseconds / 1000));
        var minutes = Math.floor(seconds / 60);
        var rest = seconds % 60;
        return String(minutes).padStart(2, '0') + ':' + String(rest).padStart(2, '0');
    }

    function showWarning() {
        if (warningVisible) return;
        warningVisible = true;
        warningStartedAt = Date.now();
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('session-warning-open');
        if (continueButton) continueButton.focus();
    }

    function hideWarning() {
        warningVisible = false;
        warningStartedAt = 0;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('session-warning-open');
    }

    function goToLogin() {
        window.location.href = 'index.php?route=logout&timeout=1';
    }

    function pingSession(force) {
        var now = Date.now();
        if (pingInProgress || (!force && now - lastPingAt < 60000)) {
            return Promise.resolve(true);
        }

        pingInProgress = true;
        return fetch('index.php?route=mantener_sesion', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok || response.redirected || (response.url && response.url.indexOf('route=inv_login') !== -1)) {
                throw new Error('session-expired');
            }
            return response.json();
        }).then(function (data) {
            if (!data || !data.success) throw new Error('session-expired');
            lastPingAt = Date.now();
            return true;
        }).catch(function () {
            goToLogin();
            return false;
        }).finally(function () {
            pingInProgress = false;
        });
    }

    function registerActivity() {
        if (warningVisible) return;
        lastActivity = Date.now();
        pingSession(false);
    }

    ['pointerdown', 'keydown', 'scroll', 'touchstart', 'mousemove'].forEach(function (eventName) {
        document.addEventListener(eventName, registerActivity, { passive: true });
    });

    if (continueButton) {
        continueButton.addEventListener('click', function () {
            continueButton.disabled = true;
            pingSession(true).then(function (success) {
                if (!success) return;
                lastActivity = Date.now();
                hideWarning();
                continueButton.disabled = false;
            });
        });
    }

    window.setInterval(function () {
        var now = Date.now();
        if (!warningVisible && now - lastActivity >= inactivityMs) {
            showWarning();
        }

        if (warningVisible) {
            var remaining = graceMs - (now - warningStartedAt);
            if (countdown) countdown.textContent = formatTime(remaining);
            if (remaining <= 0) goToLogin();
        }
    }, 1000);
})();
