/**
 * PortalAlert — Sistema Universal de Alertas SweetAlert2 para Portal APM
 * Estandariza mensajes flash, confirmaciones, toasts y cuadros de diálogo.
 * Reemplaza de forma nativa e intercepta window.alert() y confirm() en todo el sistema.
 */
function portalAlertEscape(str) {
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

window.PortalAlert = {
    /**
     * Notificación Toast de Éxito (top-end, auto-cierre con barra de progreso)
     */
    success(msg, opts = {}) {
        if (!window.Swal) return;
        return Swal.fire({
            icon: 'success',
            title: typeof msg === 'string' ? msg : (msg?.title || 'Operación exitosa'),
            text: typeof msg === 'object' ? (msg?.text || '') : '',
            toast: opts.toast !== false,
            position: opts.position || 'top-end',
            showConfirmButton: false,
            timer: opts.timer || 3400,
            timerProgressBar: true,
            iconColor: '#10B981',
            customClass: {
                popup: 'portal-swal-toast'
            },
            ...opts
        });
    },

    /**
     * Notificación Toast Informativa
     */
    info(msg, opts = {}) {
        if (!window.Swal) return;
        return Swal.fire({
            icon: 'info',
            title: typeof msg === 'string' ? msg : (msg?.title || 'Información'),
            text: typeof msg === 'object' ? (msg?.text || '') : '',
            toast: opts.toast !== false,
            position: opts.position || 'top-end',
            showConfirmButton: false,
            timer: opts.timer || 3500,
            timerProgressBar: true,
            iconColor: '#0284C7',
            customClass: {
                popup: 'portal-swal-toast'
            },
            ...opts
        });
    },

    /**
     * Notificación Toast de Advertencia
     */
    warning(msg, opts = {}) {
        if (!window.Swal) return;
        return Swal.fire({
            icon: 'warning',
            title: typeof msg === 'string' ? msg : (msg?.title || 'Atención'),
            text: typeof msg === 'object' ? (msg?.text || '') : '',
            toast: opts.toast !== false,
            position: opts.position || 'top-end',
            showConfirmButton: false,
            timer: opts.timer || 4200,
            timerProgressBar: true,
            iconColor: '#F59E0B',
            customClass: {
                popup: 'portal-swal-toast'
            },
            ...opts
        });
    },

    /**
     * Modal de Error con botón de confirmación
     */
    error(msg, opts = {}) {
        if (!window.Swal) return;
        return Swal.fire({
            icon: 'error',
            title: opts.title || 'Ocurrió un problema',
            text: typeof msg === 'string' ? msg : (msg?.text || 'No se pudo completar la operación.'),
            confirmButtonColor: '#0284C7',
            confirmButtonText: opts.confirmText || 'Entendido',
            iconColor: '#EF4444',
            customClass: {
                popup: 'portal-swal-modal'
            },
            ...opts
        });
    },

    /**
     * Lista de Errores estructurados (para validación de formularios)
     */
    errorList(title, items, opts = {}) {
        if (!window.Swal) return;
        const html = '<ul style="text-align:left;margin:8px 0 0;padding-left:20px;font-size:0.88rem;line-height:1.6;">'
            + items.map((i) => `<li>${portalAlertEscape(i)}</li>`).join('')
            + '</ul>';
        return Swal.fire({
            icon: 'error',
            title: title || 'Por favor revisa los siguientes campos',
            html,
            confirmButtonColor: '#0284C7',
            confirmButtonText: 'Corregir datos',
            iconColor: '#EF4444',
            customClass: {
                popup: 'portal-swal-modal'
            },
            ...opts
        });
    },

    /**
     * Reemplazo de confirm() para Eliminaciones
     */
    confirmDelete(message, target, opts = {}) {
        if (!window.Swal) {
            if (confirm(message)) {
                if (typeof target === 'function') target();
                else if (target instanceof HTMLFormElement) target.submit();
            }
            return;
        }
        return Swal.fire({
            icon: 'warning',
            title: opts.title || '¿Estás seguro?',
            text: message,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Sí, eliminar',
            cancelButtonText: opts.cancelText || 'Cancelar',
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#64748B',
            reverseButtons: true,
            focusCancel: true,
            iconColor: '#EF4444',
            customClass: {
                popup: 'portal-swal-modal'
            },
            ...opts
        }).then((result) => {
            if (!result.isConfirmed) return;
            if (typeof target === 'function') target();
            else if (target instanceof HTMLFormElement) target.submit();
        });
    },

    /**
     * Reemplazo de confirm() para Acciones Generales
     */
    confirmAction(message, target, opts = {}) {
        if (!window.Swal) {
            if (confirm(message)) {
                if (typeof target === 'function') target();
                else if (target instanceof HTMLFormElement) target.submit();
            }
            return;
        }
        return Swal.fire({
            icon: opts.icon || 'question',
            title: opts.title || '¿Confirmas esta acción?',
            text: message,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Sí, continuar',
            cancelButtonText: opts.cancelText || 'Cancelar',
            confirmButtonColor: '#0284C7',
            cancelButtonColor: '#64748B',
            reverseButtons: true,
            focusCancel: false,
            iconColor: opts.iconColor || '#0284C7',
            customClass: {
                popup: 'portal-swal-modal'
            },
            ...opts
        }).then((result) => {
            if (!result.isConfirmed) return;
            if (typeof target === 'function') target();
            else if (target instanceof HTMLFormElement) target.submit();
        });
    },

    /**
     * Modal con contenido HTML personalizado
     */
    modal(title, html, icon = 'info', opts = {}) {
        if (!window.Swal) return;
        return Swal.fire({
            icon,
            title,
            html,
            confirmButtonColor: '#0284C7',
            confirmButtonText: opts.confirmText || 'Aceptar',
            customClass: {
                popup: 'portal-swal-modal'
            },
            ...opts
        });
    }
};

/**
 * Interceptar de forma global las llamadas nativas a window.alert()
 * para que NUNCA aparezca la ventana emergente gris del navegador.
 */
if (typeof window !== 'undefined') {
    const _originalAlert = window.alert;
    window.alert = function(message) {
        if (window.Swal) {
            Swal.fire({
                icon: 'info',
                title: 'Atención',
                text: String(message),
                confirmButtonColor: '#0284C7',
                confirmButtonText: 'Entendido',
                customClass: {
                    popup: 'portal-swal-modal'
                }
            });
        } else {
            _originalAlert(message);
        }
    };

    /**
     * Auto-interceptar clicks y submits que contengan confirm(...) o data-confirm
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Interceptar clicks con confirm inline
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-confirm], [onclick*="confirm("]');
            if (!trigger) return;

            if (trigger._swalConfirmed) {
                delete trigger._swalConfirmed;
                return;
            }

            const onclickAttr = trigger.getAttribute('onclick') || '';
            if (onclickAttr.includes('confirm(') || trigger.hasAttribute('data-confirm')) {
                e.preventDefault();
                e.stopImmediatePropagation();

                let message = trigger.getAttribute('data-confirm');
                if (!message) {
                    const match = onclickAttr.match(/confirm\(\s*(['"`])([\s\S]*?)\1\s*\)/);
                    message = match ? match[2].replace(/\\'/g, "'").replace(/\\"/g, '"') : '¿Confirmas esta acción?';
                }

                const isDelete = /eliminar|baja|borrar|retirar|delete|dar de baja/i.test(message) || 
                                 /btn-eliminar|danger|delete/i.test(trigger.className);

                const executeAction = () => {
                    trigger._swalConfirmed = true;
                    if (trigger.tagName === 'A' && trigger.href) {
                        window.location.href = trigger.href;
                    } else if (trigger.type === 'submit' && trigger.form) {
                        trigger.form.submit();
                    } else {
                        trigger.click();
                    }
                };

                if (isDelete) {
                    PortalAlert.confirmDelete(message, executeAction);
                } else {
                    PortalAlert.confirmAction(message, executeAction);
                }
            }
        }, true);

        // Interceptar forms con onsubmit="return confirm(...)"
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || !form.getAttribute) return;

            if (form._swalConfirmed) {
                delete form._swalConfirmed;
                return;
            }

            const onsubmitAttr = form.getAttribute('onsubmit') || '';
            if (onsubmitAttr.includes('confirm(')) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const match = onsubmitAttr.match(/confirm\(\s*(['"`])([\s\S]*?)\1\s*\)/);
                const message = match ? match[2].replace(/\\'/g, "'").replace(/\\"/g, '"') : '¿Confirmas enviar este formulario?';
                const isDelete = /eliminar|baja|borrar|retirar|delete|dar de baja/i.test(message);

                const executeSubmit = () => {
                    form._swalConfirmed = true;
                    form.submit();
                };

                if (isDelete) {
                    PortalAlert.confirmDelete(message, executeSubmit);
                } else {
                    PortalAlert.confirmAction(message, executeSubmit);
                }
            }
        }, true);
    });
}
