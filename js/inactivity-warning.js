/**
 * Aviso de inactividad — cuenta regresiva antes de cerrar sesión.
 * Requiere `window.APP_INACTIVIDAD = {timeoutSegundos, avisoSegundos, keepaliveUrl, logoutUrl}`
 * y SweetAlert2 (Swal) ya cargado. Reusado sin cambios por Portal, Talento
 * Humano, Control de Bienes y Bitácoras — cada app solo define su propio
 * APP_INACTIVIDAD con su URL de keepalive/logout.
 *
 * Campos opcionales (Talento Humano: sus endpoints propios exigen CSRF y
 * solo aceptan POST, a diferencia del resto):
 *   csrfToken:     si está presente, se envía como header X-CSRF-TOKEN en
 *                  el ping de keepalive.
 *   logoutViaPost: si es true, el cierre por inactividad se hace con un
 *                  <form> POST real (con _csrf) en vez de una navegación
 *                  GET simple.
 */
(function () {
    var cfg = window.APP_INACTIVIDAD;
    if (!cfg || !cfg.timeoutSegundos || typeof Swal === 'undefined') return;

    var TIMEOUT_MS = cfg.timeoutSegundos * 1000;
    var AVISO_MS   = Math.min((cfg.avisoSegundos || 60) * 1000, Math.max(TIMEOUT_MS - 1000, 1000));
    // No golpear el servidor más de 1 vez por minuto EN CONDICIONES NORMALES
    // (timeouts de 15-30+ min) — pero si el timeout configurado es corto
    // (ej. una prueba de 60s), un piso fijo de 60s nunca llega a dispararse
    // dentro de la ventana: el cliente resetea SU cuenta regresiva con
    // cualquier actividad del mouse, pero el servidor nunca se entera
    // (ningún keepalive real llega a tiempo) y termina cerrando la sesión
    // por su cuenta, antes de que el aviso local alcance a mostrarse. El
    // ping se escala para que SIEMPRE quepan varias oportunidades dentro
    // del propio timeout, sin importar qué tan corto sea.
    var PING_MS = Math.min(60000, Math.max(5000, Math.floor(TIMEOUT_MS / 3)));

    var warningTimer, logoutTimer, countdownInterval;
    var lastPing   = Date.now();
    var warningOn  = false;
    var loggedOut  = false; // idempotencia: ver doLogout()
    var deadline   = null; // timestamp ms en el que se cierra la sesión, mientras el aviso está visible

    function schedule() {
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);
        warningOn = false;
        warningTimer = setTimeout(showWarning, TIMEOUT_MS - AVISO_MS);
        logoutTimer  = setTimeout(doLogout, TIMEOUT_MS);
    }

    function onActivity() {
        if (warningOn) return; // con el aviso abierto, solo "Seguir conectado" cuenta
        var now = Date.now();
        if (now - lastPing >= PING_MS) {
            lastPing = now;
            ping();
        }
        schedule();
    }

    function ping(cb) {
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (cfg.csrfToken) headers['X-CSRF-TOKEN'] = cfg.csrfToken;
        fetch(cfg.keepaliveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: headers
        }).then(function (r) {
            if (!r.ok) throw new Error('expired');
            return r.json();
        }).then(function (data) {
            if (data && data.timeoutSegundos) {
                TIMEOUT_MS = data.timeoutSegundos * 1000;
                AVISO_MS   = Math.min((data.avisoSegundos || 60) * 1000, Math.max(TIMEOUT_MS - 1000, 1000));
            }
            if (cb) cb(true);
        }).catch(function () {
            if (cb) cb(false);
        });
    }

    function doLogout() {
        // Bug real: doLogout() tenía 3 caminos que podían dispararla casi al
        // mismo tiempo (el setInterval de abajo, el .then() del Swal cuando
        // Swal.close() lo resuelve como no-confirmado, y el logoutTimer de
        // schedule() que nunca se cancela mientras el aviso está abierto).
        // 2-3 GET /logout casi simultáneos recreaban la sesión más de una
        // vez -- el <input _csrf_token> que quedaba en el HTML terminaba sin
        // coincidir con el de la sesión real al momento de reenviar el
        // login ("CSRF token mismatch"). Guardia simple: solo la primera
        // llamada real navega.
        if (loggedOut) return;
        loggedOut = true;
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);
        clearInterval(countdownInterval);
        if (cfg.logoutViaPost) {
            var f = document.createElement('form');
            f.method = 'POST';
            f.action = cfg.logoutUrl;
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_csrf';
            csrf.value = cfg.csrfToken || '';
            f.appendChild(csrf);
            document.body.appendChild(f);
            f.submit();
            return;
        }
        window.location.href = cfg.logoutUrl;
    }

    function fmt(ms) {
        var s = Math.max(0, Math.ceil(ms / 1000));
        var m = Math.floor(s / 60);
        var r = s % 60;
        return (m > 0 ? m + ':' + (r < 10 ? '0' : '') + r : r + 's');
    }

    // Anillo SVG de cuenta regresiva — el único elemento decorativo de esta
    // pantalla, el resto se apoya en tipografía y color para no distraer.
    var RADIUS = 42, CIRC = 2 * Math.PI * RADIUS;
    function ringSvg() {
        return '' +
            '<svg width="104" height="104" viewBox="0 0 104 104" style="display:block;margin:0 auto 14px;">' +
            '  <circle cx="52" cy="52" r="' + RADIUS + '" fill="none" stroke="currentColor" stroke-opacity=".15" stroke-width="7"/>' +
            '  <circle id="apm-inact-ring" cx="52" cy="52" r="' + RADIUS + '" fill="none" stroke="#fd7e14" stroke-width="7" ' +
            '          stroke-linecap="round" stroke-dasharray="' + CIRC + '" stroke-dashoffset="0" ' +
            '          transform="rotate(-90 52 52)" style="transition:stroke-dashoffset 1s linear, stroke .4s ease;"/>' +
            '  <text id="apm-inact-num" x="52" y="59" text-anchor="middle" font-size="24" font-weight="800" fill="currentColor">--</text>' +
            '</svg>';
    }

    function showWarning() {
        warningOn = true;
        deadline  = Date.now() + AVISO_MS;

        Swal.fire({
            title: 'Tu sesión está por cerrarse',
            html:
                '<div style="color:inherit;">' +
                    ringSvg() +
                    '<p style="margin:0 0 4px;font-size:.92rem;opacity:.85;">' +
                        'No detectamos actividad en un buen rato. Por seguridad, la sesión se cerrará sola si no respondés.' +
                    '</p>' +
                '</div>',
            iconHtml: '<i class="fa-solid fa-clock" style="font-size:1.4rem;"></i>',
            customClass: { icon: 'apm-inact-icon', popup: 'apm-inact-popup' },
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-check"></i>&nbsp; Seguir conectado',
            cancelButtonText: 'Cerrar sesión ahora',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: 'transparent',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showClass: { popup: 'swal2-noanimation' },
            didOpen: function () {
                updateRing();
                countdownInterval = setInterval(function () {
                    var left = deadline - Date.now();
                    if (left <= 0) {
                        clearInterval(countdownInterval);
                        Swal.close();
                        doLogout();
                        return;
                    }
                    updateRing(left);
                }, 250);
            },
            willClose: function () {
                clearInterval(countdownInterval);
            },
        }).then(function (result) {
            warningOn = false;
            if (result.isConfirmed) {
                ping(function (ok) {
                    if (ok) { lastPing = Date.now(); schedule(); }
                    else { doLogout(); }
                });
            } else {
                doLogout();
            }
        });
    }

    function updateRing(msLeftOverride) {
        var left = typeof msLeftOverride === 'number' ? msLeftOverride : AVISO_MS;
        var ring = document.getElementById('apm-inact-ring');
        var num  = document.getElementById('apm-inact-num');
        if (!ring || !num) return;
        var frac = Math.max(0, Math.min(1, left / AVISO_MS));
        ring.setAttribute('stroke-dashoffset', String(CIRC * (1 - frac)));
        ring.setAttribute('stroke', frac < 0.25 ? '#dc3545' : (frac < 0.55 ? '#fd7e14' : '#198754'));
        num.textContent = fmt(left);
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (evt) {
        document.addEventListener(evt, onActivity, { passive: true });
    });

    schedule();
})();
