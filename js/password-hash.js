/**
 * Hash de contraseñas en el navegador antes de enviarlas -- SHA-256 sobre
 * cada campo type=password indicado, IN-PLACE, justo antes del submit real.
 * El servidor combina este valor con el pepper compartido de TODO el
 * sistema (mismo secreto en portal, Talento Humano, Control de Bienes y
 * Bitácoras) y recién ahí aplica bcrypt -- ver helpers/security_helper.php
 * (o el equivalente Auth::/hash_password_secure() de cada módulo). La
 * contraseña en texto plano nunca sale del navegador.
 *
 * Un solo archivo físico, reusado por todos los módulos vía APP_URL/
 * PORTAL_ROOT_URL -- mismo patrón ya establecido para js/inactivity-warning.js.
 *
 * Requiere un contexto seguro (HTTPS, o localhost en desarrollo) porque usa
 * crypto.subtle -- si no está disponible, window.hashPasswordFieldsBeforeSubmit
 * queda undefined y cada formulario cae a envío normal sin hash cliente
 * (revisan `if (window.hashPasswordFieldsBeforeSubmit)` antes de usarla).
 *
 * Campos VACÍOS no se tocan -- necesario para formularios de "editar
 * usuario" donde dejar la clave en blanco significa "no cambiarla", no
 * "clave = cadena vacía".
 *
 * window.X explícito, no const/let de nivel superior: varias vistas de
 * este proyecto re-ejecutan sus <script> inline en navegación SPA sin
 * reload completo -- mismo gotcha documentado repetidas veces acá.
 */
window.hashPasswordFieldsBeforeSubmit = async function (form, fieldNames) {
    if (!window.crypto || !window.crypto.subtle) return;
    for (var i = 0; i < fieldNames.length; i++) {
        var field = form.querySelector('[name="' + fieldNames[i] + '"]');
        if (!field || field.value === '') continue;
        var buf = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(field.value));
        var bytes = new Uint8Array(buf);
        var hex = '';
        for (var j = 0; j < bytes.length; j++) hex += bytes[j].toString(16).padStart(2, '0');
        field.value = hex;
    }
};
