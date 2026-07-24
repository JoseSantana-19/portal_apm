/* talento_humano.js – Lógica interactiva del módulo Talento Humano
   Incluye: tabs del formulario, discapacidad condicional, filtros de tabla,
   cálculo de tercera edad e inicialización de eventos demo. */

/* ── PESTAÑAS ────────────────────────────────────────────────────────── */
const TAB_IDS = ['personal', 'laboral', 'contacto', 'formacion', 'obs'];

function switchTab(tabId) {
    TAB_IDS.forEach(id => {
        const panel = document.getElementById('panel-' + id);
        const btn = document.getElementById('tab-' + id);
        if (!panel || !btn) return;
        const active = id === tabId;
        panel.classList.toggle('active', active);
        btn.classList.toggle('active', active);
        btn.setAttribute('aria-selected', active);
    });
}

/* ── TERCERA EDAD ────────────────────────────────────────────────────── */
function calcularCondicion() {
    const fechaInput = document.getElementById('fecha_nac')?.value;
    const selectCondicion = document.getElementById('condicion_especial');
    if (!fechaInput || !selectCondicion) return;

    const hoy = new Date();
    const nac = new Date(fechaInput);
    let edad = hoy.getFullYear() - nac.getFullYear();
    const mes = hoy.getMonth() - nac.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < nac.getDate())) edad--;

    const esTercera = edad >= 65;
    const actual = selectCondicion.value;

    if (esTercera) {
        if (actual === 'Ninguna' || actual === 'Tercera Edad') selectCondicion.value = 'Tercera Edad';
        else if (actual === 'Discapacidad') selectCondicion.value = 'Ambas';
    } else {
        if (actual === 'Tercera Edad') selectCondicion.value = 'Ninguna';
        else if (actual === 'Ambas') selectCondicion.value = 'Discapacidad';
    }
}

/* ── DISCAPACIDAD CONDICIONAL ─────────────────────────────────────────── */
function evaluarDiscapacidad() {
    calcularCondicion();
    const condicion = document.getElementById('condicion_especial')?.value;
    const subBloque = document.getElementById('sub_bloque_discapacidad');
    const tipoInput = document.getElementById('tipo_discapacidad');
    const pctInput = document.getElementById('porcentaje_discapacidad');
    if (!subBloque || !tipoInput || !pctInput) return;

    const tieneDisc = condicion === 'Discapacidad' || condicion === 'Ambas';
    if (tieneDisc) {
        subBloque.style.display = 'flex';
        requestAnimationFrame(() => subBloque.classList.add('visible'));
        tipoInput.required = true;
        pctInput.required = true;
    } else {
        subBloque.classList.remove('visible');
        setTimeout(() => { if (!subBloque.classList.contains('visible')) subBloque.style.display = 'none'; }, 300);
        tipoInput.required = false; pctInput.required = false;
        tipoInput.value = ''; pctInput.value = '';
    }
}

/* ── FECHA ACTUAL ─────────────────────────────────────────────────────── */
function setCurrentDate() {
    const el = document.getElementById('currentDate');
    if (!el) return;
    el.textContent = new Date().toLocaleDateString('es-EC', { day: '2-digit', month: 'long', year: 'numeric' });
}

/* ── INICIALIZACIÓN ────────────────────────────────────────────────────── */
window.addEventListener('DOMContentLoaded', () => {
    setCurrentDate();
    syncSidebarToggleState?.();

    // Nav items: resaltar el ítem al pasar por encima durante la navegación.
    // IMPORTANTE: NO usar e.preventDefault() aquí porque bloquearía todos los
    // enlaces del menú lateral. El click navega libremente; solo ajustamos la
    // clase active visualmente ANTES de que el navegador cambie de página.
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('mousedown', () => {
            document.querySelectorAll('.nav-item.active').forEach(a => a.classList.remove('active'));
            item.classList.add('active');
        });
    });

    // Action cards
    document.querySelectorAll('.action-card').forEach(card => {
        card.addEventListener('click', () => showToast?.(`Acción: ${card.querySelector('h4')?.textContent}`, 'success'));
    });

    // Botones con data-toast
    document.querySelectorAll('[data-toast]').forEach(el => {
        el.addEventListener('click', () => showToast?.(el.dataset.toast, 'info'));
    });
});
