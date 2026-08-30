/* Combobox progresivo y accesible para catalogos extensos. */
document.addEventListener('DOMContentLoaded', () => {
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es');
    document.querySelectorAll('select[data-searchable-select]').forEach(select => {
        if (select.dataset.enhanced === '1') return;
        select.dataset.enhanced = '1'; select.classList.add('native-searchable-select');
        const wrapper=document.createElement('div'); wrapper.className='searchable-select';
        const input=document.createElement('input'); input.type='search'; input.className='searchable-select-input';
        input.placeholder=select.dataset.searchPlaceholder || 'Buscar y seleccionar…';
        input.autocomplete='off'; input.setAttribute('role','combobox'); input.setAttribute('aria-autocomplete','list'); input.setAttribute('aria-expanded','false');
        const icon=document.createElement('i'); icon.className='bi bi-search searchable-select-icon'; icon.setAttribute('aria-hidden','true');
        const list=document.createElement('div'); list.className='searchable-select-list'; list.hidden=true; list.setAttribute('role','listbox');
        select.parentNode.insertBefore(wrapper,select); wrapper.append(icon,input,list,select);
        if(select.required){select.required=false;input.required=true;}
        const availableOptions=()=>[...select.options].filter(option=>option.value!=='');
        const updateValidity=()=>input.setCustomValidity(input.required&&!select.value?'Seleccione una opción válida del catálogo.':'');
        const syncInput=()=>{const option=select.selectedOptions[0];input.value=option?.value?option.textContent.trim():'';updateValidity();};
        syncInput();
        const close=()=>{list.hidden=true;input.setAttribute('aria-expanded','false');};
        const render=()=>{
            const query=normalize(input.value); list.textContent='';
            const matches=availableOptions().filter(option=>normalize(option.textContent).includes(query)).slice(0,80);
            matches.forEach(option=>{
                const button=document.createElement('button'); button.type='button'; button.className='searchable-select-option';
                button.setAttribute('role','option'); button.dataset.value=option.value; button.textContent=option.textContent.trim();
                button.addEventListener('click',()=>{select.value=option.value;syncInput();select.dispatchEvent(new Event('change',{bubbles:true}));close();});
                list.append(button);
            });
            if(!matches.length){const empty=document.createElement('div');empty.className='searchable-select-empty';empty.textContent='Sin coincidencias';list.append(empty);}
            list.hidden=false;input.setAttribute('aria-expanded','true');
        };
        input.addEventListener('focus',()=>{input.select();render();});
        input.addEventListener('input',()=>{select.value='';updateValidity();render();});
        input.addEventListener('keydown',event=>{
            const buttons=[...list.querySelectorAll('button')];
            if(event.key==='Escape'){close();return;}
            if(event.key==='ArrowDown'){event.preventDefault();(buttons[0]||input).focus();}
        });
        list.addEventListener('keydown',event=>{
            const buttons=[...list.querySelectorAll('button')],index=buttons.indexOf(document.activeElement);
            if(event.key==='ArrowDown'){event.preventDefault();buttons[Math.min(index+1,buttons.length-1)]?.focus();}
            if(event.key==='ArrowUp'){event.preventDefault();index<=0?input.focus():buttons[index-1]?.focus();}
            if(event.key==='Escape'){close();input.focus();}
        });
        select.addEventListener('change',syncInput);
        document.addEventListener('click',event=>{if(!wrapper.contains(event.target))close();});
    });
});
