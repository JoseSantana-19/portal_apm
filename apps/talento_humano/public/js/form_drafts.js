/* Borradores cifrados del lado servidor para formularios institucionales. */
document.addEventListener('DOMContentLoaded', () => {
    const formatDraftDate = value => {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        return match ? `${match[3]}/${match[2]}/${match[1]} a las ${match[4]}:${match[5]}` : String(value || '');
    };
    const forms = [...document.querySelectorAll('form[data-draft-context]')];
    forms.forEach(form => {
        const context = form.dataset.draftContext;
        const csrf = form.querySelector('[name="_csrf"]')?.value || '';
        if (!context || !csrf) return;
        let contextInput = form.querySelector('[name="_draft_context"]');
        if (!contextInput) {
            contextInput = document.createElement('input');
            contextInput.type = 'hidden';
            contextInput.name = '_draft_context';
            form.append(contextInput);
        }
        contextInput.value = context;
        let timer = 0;
        let saving = false;
        let dirty = false;
        const status = document.createElement('small');
        status.className = 'draft-status';
        status.setAttribute('role', 'status');
        form.prepend(status);

        const serialize = () => {
            const fields = {};
            [...form.elements].forEach(el => {
                if (!el.name || el.disabled || el.type === 'file' || el.type === 'password' || el.dataset.noDraft === 'true' || el.name === '_csrf' || el.name === '_draft_context') return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (!fields[el.name]) fields[el.name] = [];
                    if (el.checked) fields[el.name].push(el.value);
                } else if (el.multiple) {
                    fields[el.name] = [...el.selectedOptions].map(option => option.value);
                } else {
                    fields[el.name] = el.value;
                }
            });
            return fields;
        };

        const save = async () => {
            if (saving || !dirty) return;
            saving = true; dirty = false; status.textContent = 'Guardando borrador…';
            try {
                const response = await fetch(`${window.BASE_URL || ''}/borradores/guardar`, {
                    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
                    body:JSON.stringify({context,fields:serialize()}), credentials:'same-origin'
                });
                if (!response.ok) throw new Error('draft');
                const data = await response.json();
                status.textContent = `Borrador guardado a las ${data.saved_at || ''}`;
            } catch (_) {
                dirty = true; status.textContent = 'Borrador pendiente de sincronización';
            } finally { saving = false; }
        };

        form.addEventListener('input', () => { dirty=true; status.textContent='Cambios pendientes'; clearTimeout(timer); timer=setTimeout(save,800); });
        form.addEventListener('change', () => { dirty=true; clearTimeout(timer); timer=setTimeout(save,250); });
        window.addEventListener('pagehide', save);

        fetch(`${window.BASE_URL || ''}/borradores/obtener?context=${encodeURIComponent(context)}`, {
            headers:{'X-CSRF-Token':csrf}, credentials:'same-origin'
        }).then(r => r.ok ? r.json() : null).then(async data => {
            const draft = data?.draft;
            if (!draft?.fields) return;
            const recover = window.portalConfirm
                ? await window.portalConfirm({
                    title:'Información pendiente encontrada',
                    message:`Existe un borrador guardado${draft.updated_at ? ` el ${formatDraftDate(draft.updated_at)}` : ''}. Puede recuperarlo para continuar sin perder los datos ingresados.`,
                    confirmText:'Recuperar borrador',
                    cancelText:'Continuar sin recuperar',
                    icon:'bi-cloud-arrow-down'
                })
                : window.confirm('Existe un borrador guardado. ¿Desea recuperarlo?');
            if (!recover) return;
            Object.entries(draft.fields).forEach(([name,value]) => {
                const controls = [...form.querySelectorAll(`[name="${CSS.escape(name)}"]`)];
                controls.forEach(el => {
                    if (el.type === 'checkbox' || el.type === 'radio') el.checked = Array.isArray(value) && value.includes(el.value);
                    else if (el.multiple && Array.isArray(value)) [...el.options].forEach(o => o.selected=value.includes(o.value));
                    else if (!Array.isArray(value)) el.value = value ?? '';
                    el.dispatchEvent(new Event('change',{bubbles:true}));
                });
            });
            status.textContent='Borrador recuperado';
            window.showToast?.('Se recuperó la información pendiente del formulario.','success');
        }).catch(()=>{});
    });
});
