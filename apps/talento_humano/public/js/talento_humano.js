/* talento_humano.js – Lógica interactiva del módulo Talento Humano
   Incluye: tabs del formulario, discapacidad condicional, filtros de tabla,
   cálculo de tercera edad e inicialización de eventos de interfaz. */

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
        btn.setAttribute('aria-selected', String(active));
        btn.setAttribute('tabindex', active ? '0' : '-1');
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

function sincronizarJornadaBase() {
    const condicion = document.getElementById('condicion_especial');
    const jornada = document.getElementById('jornada');
    const horas = document.getElementById('horas_jornada');
    const ayuda = document.getElementById('ayuda_horas_jornada');
    if (!condicion || !jornada || !horas) return;
    if (condicion.value === 'Sustituto') {
        jornada.value = 'Especial'; horas.value = '6'; horas.readOnly = true;
        if (ayuda) ayuda.textContent = 'La condición de sustituto establece una jornada base especial de 6 horas.';
    } else if (jornada.value === 'Completa') {
        horas.value = '8'; horas.readOnly = true;
        if (ayuda) ayuda.textContent = 'La jornada completa establece automáticamente 8 horas base diarias.';
    } else {
        horas.readOnly = false;
        if (!Number(horas.value) || Number(horas.value) > 24) horas.value = '';
        if (ayuda) ayuda.textContent = 'Ingrese las horas base contractuales. Las excepciones temporales se registran mediante Acción de Personal.';
    }
}

/* ── FECHA ACTUAL ─────────────────────────────────────────────────────── */
function setCurrentDate() {
    const el = document.getElementById('currentDate');
    if (!el) return;
    const parts=(el.dataset.institutionalDate||'').split('-').map(Number);
    const value=parts.length===3&&parts.every(Number.isFinite)?new Date(parts[0],parts[1]-1,parts[2],12):new Date();
    el.textContent = value.toLocaleDateString('es-EC', { day: '2-digit', month: 'long', year: 'numeric' });
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

    // Navegación accesible entre pestañas con flechas, Inicio y Fin.
    const tabButtons = [...document.querySelectorAll('.form-tabs-nav [role="tab"]')];
    tabButtons.forEach((button, index) => {
        button.addEventListener('keydown', event => {
            let targetIndex = null;
            if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabButtons.length;
            if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabButtons.length) % tabButtons.length;
            if (event.key === 'Home') targetIndex = 0;
            if (event.key === 'End') targetIndex = tabButtons.length - 1;
            if (targetIndex === null) return;

            event.preventDefault();
            const target = tabButtons[targetIndex];
            switchTab(target.id.replace(/^tab-/, ''));
            target.focus();
        });
    });

    const employeeForm = document.getElementById('empleadoForm');
    employeeForm?.addEventListener('invalid', event => {
        const panel = event.target.closest('.form-tab-panel');
        if (!panel || panel.classList.contains('active')) return;
        switchTab(panel.id.replace(/^panel-/, ''));
        requestAnimationFrame(() => event.target.focus());
    }, true);

    const positionSelect = document.getElementById('puesto_id');
    const salaryInput = document.getElementById('sueldo');
    positionSelect?.addEventListener('change', () => {
        const referenceSalary = Number(positionSelect.selectedOptions[0]?.dataset.rmu || 0);
        if (salaryInput && Number(salaryInput.value || 0) === 0 && referenceSalary > 0) {
            salaryInput.value = referenceSalary.toFixed(2);
        }
    });
    const conditionSelect=document.getElementById('condicion_especial');
    const scheduleSelect=document.getElementById('jornada');
    conditionSelect?.addEventListener('change',sincronizarJornadaBase);
    scheduleSelect?.addEventListener('change',sincronizarJornadaBase);
    sincronizarJornadaBase();
});
