(function () {
    'use strict';

    var script = document.currentScript;
    var userId = script && script.dataset.userId ? script.dataset.userId : 'anonimo';
    var maxAgeMs = 7 * 24 * 60 * 60 * 1000;
    var pendingKey = 'sysport:borrador-pendiente:' + userId;

    if (document.querySelector('.toast-success')) {
        try {
            var completedKey = sessionStorage.getItem(pendingKey);
            if (completedKey) localStorage.removeItem(completedKey);
            sessionStorage.removeItem(pendingKey);
        } catch (_) {}
    }

    function routeKey() {
        var params = new URLSearchParams(window.location.search);
        return [window.location.pathname, params.get('route') || '', params.get('action') || ''].join('|');
    }

    function keyFor(form, index) {
        return 'sysport:borrador:' + userId + ':' + routeKey() + ':' + (form.id || form.getAttribute('name') || index);
    }

    function eligible(form) {
        return String(form.method || 'get').toLowerCase() === 'post'
            && !form.matches('[data-draft-disabled]')
            && !form.querySelector('input[type="password"]');
    }

    function serialize(form) {
        var values = {};
        Array.prototype.forEach.call(form.elements, function (field) {
            if (!field.name || field.disabled || /^(password|file|submit|button)$/i.test(field.type)) return;
            if (/token|csrf|contrasena/i.test(field.name)) return;
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
            if (field.tagName === 'SELECT' && field.multiple) {
                values[field.name] = Array.prototype.filter.call(field.options, function (option) { return option.selected; }).map(function (option) { return option.value; });
            } else {
                values[field.name] = field.value;
            }
        });
        return { savedAt: Date.now(), values: values };
    }

    function restore(form, draft) {
        if (!draft || !draft.values || Date.now() - Number(draft.savedAt || 0) > maxAgeMs) return;
        Object.keys(draft.values).forEach(function (name) {
            var fields = form.querySelectorAll('[name="' + CSS.escape(name) + '"]');
            Array.prototype.forEach.call(fields, function (field) {
                if (/^(password|file|hidden)$/i.test(field.type)) return;
                var value = draft.values[name];
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = Array.isArray(value) ? value.indexOf(field.value) !== -1 : String(value) === String(field.value);
                } else {
                    field.value = value == null ? '' : value;
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    }

    document.querySelectorAll('form').forEach(function (form, index) {
        if (!eligible(form)) return;
        var key = keyFor(form, index);
        try {
            var existing = JSON.parse(localStorage.getItem(key) || 'null');
            restore(form, existing);
        } catch (_) {}

        var timer = null;
        function save(immediate) {
            window.clearTimeout(timer);
            var persist = function () {
                try { localStorage.setItem(key, JSON.stringify(serialize(form))); } catch (_) {}
            };
            if (immediate === true) persist();
            else timer = window.setTimeout(persist, 250);
        }
        form.addEventListener('input', save);
        form.addEventListener('change', save);
        form.addEventListener('submit', function (event) {
            save(true);
            if (!event.defaultPrevented) {
                try { sessionStorage.setItem(pendingKey, key); } catch (_) {}
            }
        });
        window.addEventListener('offline', function () { save(true); });
        window.addEventListener('pagehide', function () { save(true); });
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') save(true);
        });
    });
})();
