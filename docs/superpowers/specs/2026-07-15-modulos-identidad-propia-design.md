# Diseño: Módulos con identidad propia + Portal azul APM (2026-07-15)

Aprobado por el usuario el 2026-07-15 (AskUserQuestion).

## Objetivo
1. Cada módulo embebido (Talento Humano, Control de Bienes, Bitácoras) usa SU tema
   original de la fuente, no los temas t1/t2/t3 del portal.
2. Eliminar la franja superior del portal (APM_STRIP) de los 3 módulos.
3. Único punto de retorno: botón "Portal APM" como PRIMER ítem del menú lateral
   de cada módulo.
4. Menú lateral del portal APM: azul institucional FIJO (#0D2B4E) en los 3 temas.
5. Paneles hub (TH / Bienes / Bitácoras) mucho más complementarios: KPIs en vivo,
   actividad reciente, accesos rápidos y mini-gráficos.

## Decisiones del usuario
- Temas módulos: "Sí, 100% como el origen" — restaurar toggle nativo donde exista
  (Bitácoras theme_mode.js claro/oscuro; Bienes data-theme claro/oscuro; TH fijo).
- Hubs: KPIs en vivo + actividad reciente + accesos rápidos + mini-gráficos (los 4).
- Azul sidebar: #0D2B4E fondo, header #092038, texto #E8F0FA, activo #38BDF8
  (franja + rgba(56,189,248,.12)), bordes rgba(255,255,255,.08).
- Botón volver: arriba, primer ítem, estilo distintivo.

## Cambios
### A. Limpieza de temas del portal en módulos
- Bitácoras (`views/layouts/bit_navbar.php`, `bit_sidebar` portado): quitar
  portal_theme_bridge.css/js, script temprano de tema y APM_STRIP; restaurar
  theme_mode.js del origen; añadir ítem "Portal APM" primero en su sidebar.
- Bienes (`apps/control_bienes/modules/Central/views/layout.php`): quitar
  applyApmTheme + CSS de fidelidad APM + franja; conservar toggle original
  y el ítem "Portal APM" existente. NO tocar puente de sesión en index.php.
- TH (`apps/talento_humano/shared/menu.php`): quitar style#apm-theme-bridge,
  script applyTheme y franja; conservar ítem "Portal APM". NO tocar guard de
  sesión ni .htaccess.
- Borrar: `public/js/apm_portal_strip.js`, `public/css/portuaria/portal_theme_bridge.css`,
  `public/js/portuaria/portal_theme_bridge.js`. Grep final: cero referencias a
  APM_STRIP / apm_theme / apm-theme-bridge dentro de los módulos.

### B. Sidebar portal azul fijo
- CSS en el shell del portal con especificidad body.t1/.t2/.t3: paleta de arriba,
  idéntica en los 3 temas. El resto del shell sigue tematizado.

### C. Hubs complementarios
- Por módulo: fila de KPIs (consultas reales por BD), 1-2 mini-gráficos SVG inline
  (sin CDN), grid de accesos rápidos hacia pantallas del sistema origen
  (data-no-spa), tabla de actividad reciente (últimos 8). Los hubs heredan los
  temas del portal (son portal).

### D. Verificación
- php -l en tocados, robocopy a htdocs, auditoría curl (200 + cero referencias
  residuales + KPIs con datos), checklist visual con el usuario.
