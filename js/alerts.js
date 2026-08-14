/**
 * PortalAlert — envoltorio delgado sobre SweetAlert2 para estandarizar
 * confirmaciones y mensajes flash en todo el panel (reemplaza confirm()/
 * alert() nativos y los <div class="alert"> estáticos).
 */
function portalAlertEscape(str) {
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

window.PortalAlert = {
    success(msg) {
        Swal.fire({
            icon: 'success',
            title: msg,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
        });
    },

    warning(msg) {
        Swal.fire({
            icon: 'warning',
            title: msg,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
        });
    },

    error(msg) {
        Swal.fire({
            icon: 'error',
            title: 'Ocurrió un problema',
            text: msg,
            confirmButtonColor: '#0284C7',
        });
    },

    errorList(title, items) {
        const html = '<ul style="text-align:left;margin:0;padding-left:18px;">'
            + items.map((i) => `<li>${portalAlertEscape(i)}</li>`).join('')
            + '</ul>';
        Swal.fire({
            icon: 'error',
            title: title || 'Revisa lo siguiente',
            html,
            confirmButtonColor: '#0284C7',
        });
    },

    /**
     * Reemplazo de confirm() nativo. `target` puede ser un <form> (se envía
     * al confirmar) o una función callback.
     */
    confirmDelete(message, target, opts = {}) {
        Swal.fire({
            icon: 'warning',
            title: opts.title || '¿Estás seguro?',
            text: message,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) return;
            if (typeof target === 'function') target();
            else if (target instanceof HTMLFormElement) target.submit();
        });
    },

    confirmAction(message, target, opts = {}) {
        Swal.fire({
            icon: 'question',
            title: opts.title || '¿Confirmas esta acción?',
            text: message,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0284C7',
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) return;
            if (typeof target === 'function') target();
            else if (target instanceof HTMLFormElement) target.submit();
        });
    },
};
