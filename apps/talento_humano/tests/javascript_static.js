const fs = require('fs');
const vm = require('vm');

const publicScripts = fs.readdirSync('public/js')
    .filter((name) => name.endsWith('.js'))
    .map((name) => `public/js/${name}`);

const criticalViews = [
    'modules/talento-humano/Vistas/directorio.php',
    'modules/talento-humano/Vistas/accion_personal.php',
    'modules/talento-humano/Vistas/estudio_seguridad.php',
    'modules/talento-humano/Vistas/formulario.php',
    'shared/catalogo_rapido.php',
];

const failures = [];
const validate = (source, filename) => {
    try {
        new vm.Script(source, { filename });
    } catch (error) {
        failures.push(`${filename}: ${error.message}`);
    }
};

for (const filename of publicScripts) {
    validate(fs.readFileSync(filename, 'utf8'), filename);
}

for (const filename of criticalViews) {
    const html = fs.readFileSync(filename, 'utf8');
    let index = 0;
    for (const match of html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/gi)) {
        const javascript = match[1].replace(/<\?(?:php|=)[\s\S]*?\?>/g, 'null');
        validate(javascript, `${filename}#inline-${++index}`);
    }
}

if (failures.length) {
    console.error(failures.join('\n'));
    process.exit(1);
}

console.log('[OK] sintaxis JavaScript publica y vistas criticas');
