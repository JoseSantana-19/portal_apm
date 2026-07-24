/* toast.js – Sistema de notificaciones tipo Toast */

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: '<i class="bi bi-check-circle-fill" style="color:#10b981"></i>',
        error:   '<i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>',
        info:    '<i class="bi bi-info-circle-fill" style="color:#3b82f6"></i>'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        ${icons[type] || icons.info}
        <span>${message}</span>
        <button onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    `;
    container.appendChild(toast);
    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 3500);
}
