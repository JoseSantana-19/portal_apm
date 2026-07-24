/**
 * Sistema de notificaciones toast (esquina superior derecha).
 * Uso: showToast('Mensaje', 'success' | 'info' | 'error' [, { key: 'cedula' }])
 * Cerrar por key: closeToast('cedula')
 */
(function () {
    'use strict';

    var CONTAINER_ID = 'toast-container';
    var DURATION = 5500;
    var toastsByKey = {};

    function ensureContainer() {
        var el = document.getElementById(CONTAINER_ID);
        if (!el) {
            el = document.createElement('div');
            el.id = CONTAINER_ID;
            document.body.appendChild(el);
        }
        return el;
    }

    function removeToast(item, key) {
        if (key && toastsByKey[key] === item) {
            delete toastsByKey[key];
        }
        item.classList.add('toast-out');
        setTimeout(function () {
            if (item.parentNode) {
                item.parentNode.removeChild(item);
            }
        }, 260);
    }

    function showToast(message, type, options) {
        if (!message) return null;
        type = type === 'error' || type === 'info' || type === 'success' ? type : 'info';
        options = options || {};
        var key = options.key || null;

        if (key && toastsByKey[key]) {
            removeToast(toastsByKey[key], key);
        }

        var container = ensureContainer();
        var item = document.createElement('div');
        item.className = 'toast-item toast-' + type;
        item.setAttribute('role', 'alert');

        var text = document.createElement('span');
        text.className = 'toast-item-text';
        text.textContent = message;
        item.appendChild(text);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Cerrar');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function () {
            removeToast(item, key);
        });
        item.appendChild(closeBtn);

        container.appendChild(item);

        if (key) {
            toastsByKey[key] = item;
        }

        var timer = setTimeout(function () {
            removeToast(item, key);
        }, DURATION);

        item._toastTimer = timer;
        item._toastKey = key;

        return item;
    }

    function closeToast(key) {
        if (key && toastsByKey[key]) {
            var item = toastsByKey[key];
            if (item._toastTimer) clearTimeout(item._toastTimer);
            removeToast(item, key);
        }
    }

    window.showToast = showToast;
    window.closeToast = closeToast;
})();
