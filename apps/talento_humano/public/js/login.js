document.addEventListener('DOMContentLoaded',()=>{
  const toggle=document.querySelector('[data-password-toggle]');
  const password=document.getElementById('clave');
  toggle?.addEventListener('click',()=>{if(!password)return;const show=password.type==='password';password.type=show?'text':'password';toggle.innerHTML=`<i class="bi ${show?'bi-eye-slash':'bi-eye'}"></i>`;toggle.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña')});
  document.querySelectorAll('form[data-login-form]').forEach(form=>form.addEventListener('submit',e=>{
    const button=form.querySelector('button[type="submit"]');
    if(button){button.disabled=true;button.dataset.original=button.textContent;button.textContent='Verificando...'}
    // Hash SHA-256 de la clave en el navegador (ver js/password-hash.js en
    // la raíz del portal) -- el servidor combina ese hash con el pepper
    // compartido de todo el sistema, ver Auth::attempt().
    if(!window.hashPasswordFieldsBeforeSubmit)return;
    e.preventDefault();
    hashPasswordFieldsBeforeSubmit(form,['clave']).then(()=>form.submit());
  }));
  const code=document.querySelector('input[data-otp]');
  code?.addEventListener('input',()=>{code.value=code.value.replace(/\D/g,'').slice(0,6)});
});
