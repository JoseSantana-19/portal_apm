function showToast(message, type = 'success') {
    if (window.PortalAlert) {
        if (type === 'success') return PortalAlert.success(message);
        if (type === 'error') return PortalAlert.error(message);
        if (type === 'warning') return PortalAlert.warning(message);
        return PortalAlert.info(message);
    }
    if (window.Swal) {
        return Swal.fire({
            icon: type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success'),
            title: String(message),
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    }
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: ['bi-check-circle-fill', '#10b981'],
        error:   ['bi-exclamation-triangle-fill', '#ef4444'],
        info:    ['bi-info-circle-fill', '#3b82f6']
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const iconConfig = icons[type] || icons.info;
    const icon = document.createElement('i');
    icon.className = `bi ${iconConfig[0]}`;
    icon.style.color = iconConfig[1];
    const copy = document.createElement('span');
    copy.textContent = String(message);
    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Cerrar notificación');
    const closeIcon = document.createElement('i');
    closeIcon.className = 'bi bi-x';
    close.append(closeIcon);
    close.addEventListener('click', () => toast.remove());
    toast.append(icon, copy, close);
    container.appendChild(toast);
    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 3500);
}

/** Confirmación institucional accesible; reemplaza los cuadros nativos del navegador. */
function portalConfirm({ title = 'Confirmar acción', message = '', confirmText = 'Aceptar', cancelText = 'Cancelar', icon = 'bi-cloud-arrow-down' } = {}) {
    if (typeof HTMLDialogElement === 'undefined') return Promise.resolve(window.confirm(message));

    return new Promise(resolve => {
        const dialog = document.createElement('dialog');
        dialog.className = 'portal-confirm';

        const card = document.createElement('div');
        card.className = 'portal-confirm-card';
        const iconBox = document.createElement('div');
        iconBox.className = 'portal-confirm-icon';
        const iconElement = document.createElement('i');
        iconElement.className = `bi ${icon}`;
        iconBox.append(iconElement);
        const heading = document.createElement('h2');
        heading.textContent = title;
        const copy = document.createElement('p');
        copy.textContent = message;
        const actions = document.createElement('div');
        actions.className = 'portal-confirm-actions';
        const cancel = document.createElement('button');
        cancel.type = 'button'; cancel.className = 'btn btn-outline'; cancel.textContent = cancelText;
        const accept = document.createElement('button');
        accept.type = 'button'; accept.className = 'btn btn-primary';
        const acceptIcon = document.createElement('i');
        acceptIcon.className = 'bi bi-check-lg';
        accept.append(acceptIcon, document.createTextNode(String(confirmText)));
        actions.append(cancel, accept);
        card.append(iconBox, heading, copy, actions);
        dialog.append(card);
        document.body.append(dialog);

        let finished = false;
        const finish = value => {
            if (finished) return;
            finished = true;
            dialog.close();
            dialog.remove();
            resolve(value);
        };
        cancel.addEventListener('click', () => finish(false));
        accept.addEventListener('click', () => finish(true));
        dialog.addEventListener('cancel', event => { event.preventDefault(); finish(false); });
        dialog.addEventListener('click', event => { if (event.target === dialog) finish(false); });
        dialog.showModal();
        accept.focus();
    });
}

window.portalConfirm = portalConfirm;
