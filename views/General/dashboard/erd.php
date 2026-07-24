<?php
/**
 * ERD relational schema explorer view.
 * Features dynamic system theme synchronization, instant search filter, and complete entity relationship mapping.
 */
?>
<div class="erd-container" style="padding: 10px 0;">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #0f172a, #1e293b); border-radius: 12px; padding: 24px 28px; border-bottom: 4px solid var(--accent-color); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <div class="welcome-text">
            <h2 class="welcome-title" style="margin: 0; font-family: 'Sora', sans-serif; font-size: 1.6rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="database" style="color: var(--accent-color); width: 28px; height: 28px;"></i>
                Esquema de Base de Datos y Diccionario Relacional
            </h2>
            <p class="welcome-desc" style="margin: 8px 0 0 0; font-size: 0.95rem; color: #cbd5e1; line-height: 1.5; font-weight: 400;">
                Explorador de metadatos en tiempo real consultado directamente desde los catálogos del servidor MS SQL Server. Incluye el diagrama relacional Mermaid dinámico y la estructura física completa de campos.
            </p>
        </div>
    </div>

    <!-- DB catalog statistics -->
    <div class="kpi-grid" style="margin-top: 20px;">
        <div class="kpi-card">
            <div class="kpi-card-left">
                <span class="kpi-label">TABLAS FÍSICAS</span>
                <span class="kpi-value"><?= $dbStats['tables'] ?? 0 ?></span>
                <span class="kpi-trend text-info">sys.tables</span>
            </div>
            <div class="kpi-card-right" style="background: rgba(3, 105, 161, 0.1);">
                <i data-lucide="table-2" class="kpi-icon" style="color: var(--accent-color);"></i>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-card-left">
                <span class="kpi-label">VISTAS DEL SISTEMA</span>
                <span class="kpi-value"><?= $dbStats['views'] ?? 0 ?></span>
                <span class="kpi-trend text-success">sys.views</span>
            </div>
            <div class="kpi-card-right" style="background: rgba(34, 197, 94, 0.1);">
                <i data-lucide="eye" class="kpi-icon" style="color: #22c55e;"></i>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-card-left">
                <span class="kpi-label">PROCEDIMIENTOS</span>
                <span class="kpi-value"><?= $dbStats['procedures'] ?? 0 ?></span>
                <span class="kpi-trend text-warning">sys.procedures</span>
            </div>
            <div class="kpi-card-right" style="background: rgba(234, 179, 8, 0.1);">
                <i data-lucide="terminal" class="kpi-icon" style="color: #eab308;"></i>
            </div>
        </div>
    </div>

    <!-- Relational Diagram with Mermaid -->
    <div class="section-container" style="margin-top: 30px; background: var(--surface-app); border: 1px solid var(--border-app); border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
        <h3 class="section-title" style="margin: 0; font-family: 'Sora', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text-app); display: flex; align-items: center; gap: 8px;">
            <i data-lucide="git-fork" style="color: var(--accent-color); width: 22px; height: 22px;"></i>
            Diagrama de Relación de Entidades Core
        </h3>
        <p class="section-subtitle" style="margin: 6px 0 20px 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.4;">
            Visualización relacional generada mediante modelado lógico directo. Muestra llaves primarias, foráneas, seguridad centralizada y relaciones del módulo de Talento Humano.
        </p>
        
        <!-- Mermaid rendering card -->
        <div class="card" style="padding: 24px; overflow-x: auto; background: rgba(0, 0, 0, 0.15); border: 1px solid var(--border-app); border-radius: 8px; text-align: center;">
            <!-- Load Mermaid JS from CDN -->
            <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
            <script>
                // Dynamic theme detection based on active system theme
                const activeTheme = localStorage.getItem('apm_theme') || '1';
                let mermaidTheme = 'dark';
                let mermaidVars = {
                    primaryColor: '#0d162b',
                    primaryTextColor: '#e2e8f0',
                    lineColor: '#3b82f6',
                    actorBorder: '#3b82f6',
                    actorBkg: '#0d162b',
                    mainBkg: '#0d162b'
                };

                if (activeTheme === '1') {
                    mermaidTheme = 'default';
                    mermaidVars = {
                        primaryColor: '#ebf4fd',
                        primaryTextColor: '#1a3a5c',
                        lineColor: '#2e75b6',
                        actorBorder: '#2e75b6',
                        actorBkg: '#ebf4fd',
                        mainBkg: '#ffffff'
                    };
                } else if (activeTheme === '3') {
                    mermaidTheme = 'dark';
                    mermaidVars = {
                        primaryColor: 'rgba(15, 29, 58, 0.6)',
                        primaryTextColor: '#f8fafc',
                        lineColor: '#10b981',
                        actorBorder: '#10b981',
                        actorBkg: 'rgba(15, 29, 58, 0.4)',
                        mainBkg: 'rgba(15, 29, 58, 0.2)'
                    };
                }

                mermaid.initialize({
                    theme: mermaidTheme,
                    securityLevel: 'loose',
                    themeVariables: mermaidVars
                });
            </script>
            
            <pre class="mermaid" style="display:inline-block; background: transparent; text-align: left; font-family: inherit;">
erDiagram
    Departamentos_Modulos {
        int id_dep_modulo PK
        string nombre_departamento
        string codigo_depto
        string color_tema
    }
    Usuarios {
        int id_usuario PK
        string nombre_usuario
        string nombre_completo
        string contrasena_hash
        int id_dep_principal FK
    }
    Grupos_Roles {
        int id_grupo_rol PK
        string nombre_grupo_rol
        int id_dep_modulo FK
        int nivel_jerarquia
    }
    Formularios {
        int idform PK
        string nombre_formulario
        int id_dep_modulo FK
    }
    Menu_Opciones {
        int id_menu_op PK
        int id_dep_modulo FK
        int id_form_asociado FK
        string descripcion_interfaz
        string url_formulario
    }
    Usuarios_Grupos_Roles {
        int id PK
        int id_usuario FK
        int id_grupo_rol FK
    }
    Permisos_Grupos_Roles {
        int id_permiso PK
        int id_grupo_rol FK
        int id_menu_op FK
        int tipo_permiso
    }
    Per_Formulario {
        int id_pf PK
        int id_grupo_rol FK
        int id_form FK
        int permiso
    }
    TH_Empleados {
        int id_empleado PK
        int id_usuario FK
        string cedula
        string nombre_completo
        int id_dep_principal FK
    }
    TH_Contratos {
        int id_contrato PK
        int id_empleado FK
        string tipo_contrato
        decimal remuneracion
        string estado
    }

    Departamentos_Modulos ||--o{ Usuarios : "contiene"
    Departamentos_Modulos ||--o{ Grupos_Roles : "organiza"
    Departamentos_Modulos ||--o{ Formularios : "posee"
    Departamentos_Modulos ||--o{ Menu_Opciones : "publica"
    Usuarios ||--o{ Usuarios_Grupos_Roles : "tiene"
    Grupos_Roles ||--o{ Usuarios_Grupos_Roles : "asigna"
    Grupos_Roles ||--o{ Permisos_Grupos_Roles : "recibe"
    Menu_Opciones ||--o{ Permisos_Grupos_Roles : "aplica"
    Grupos_Roles ||--o{ Per_Formulario : "concede"
    Formularios ||--o{ Per_Formulario : "restringe"
    Usuarios ||--o| TH_Empleados : "vincula"
    Departamentos_Modulos ||--o{ TH_Empleados : "adscribe"
    TH_Empleados ||--o{ TH_Contratos : "emplea"
            </pre>
        </div>
    </div>

    <!-- Live Data Dictionary Explorer -->
    <div class="section-container" style="margin-top: 30px; background: var(--surface-app); border: 1px solid var(--border-app); border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
        <h3 class="section-title" style="margin: 0; font-family: 'Sora', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text-app); display: flex; align-items: center; gap: 8px;">
            <i data-lucide="book-open" style="color: var(--accent-color); width: 22px; height: 22px;"></i>
            Diccionario de Datos del Sistema
        </h3>
        <p class="section-subtitle" style="margin: 6px 0 20px 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.4;">
            Explora la definición física de columnas, tipos de datos reales, longitudes máximas de caracteres y claves detectadas dinámicamente.
        </p>
        
        <div class="dictionary-wrapper" style="display: grid; grid-template-columns: 290px 1fr; gap: 24px; margin-top: 15px;">
            <!-- Tables sidebar selector -->
            <div class="dict-sidebar" style="background: rgba(0, 0, 0, 0.08); border: 1px solid var(--border-app); border-radius: 10px; padding: 12px; max-height: 520px; display: flex; flex-direction: column;">
                <span class="dict-sidebar-title" style="display: block; font-weight: 700; padding: 4px 8px 10px; font-size: 0.78rem; letter-spacing: 0.08em; color: var(--text-muted); text-transform: uppercase; border-bottom: 1.5px solid var(--border-app); margin-bottom: 12px;">Tablas Detectadas</span>
                
                <!-- Table Instant Search Bar -->
                <div style="position: relative; margin-bottom: 12px; padding: 0 4px;">
                    <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-muted);"></i>
                    <input type="text" id="dict-search" placeholder="Buscar tabla..." style="width: 100%; padding: 8px 10px 8px 30px; border-radius: 6px; border: 1px solid var(--border-app); background: var(--surface-app); color: var(--text-app); font-size: 0.8rem; font-family: inherit; outline: none; transition: all 0.2s;">
                </div>

                <div class="dict-tab-list" style="overflow-y: auto; flex: 1; padding: 0 4px; display: flex; flex-direction: column; gap: 4px;">
                    <?php foreach (array_keys($dictionary) as $index => $tblName): ?>
                        <button class="dict-tab-btn <?= $index === 0 ? 'active' : '' ?>" onclick="switchDictTable('<?= $tblName ?>', this)" style="display: flex; align-items: center; width: 100%; padding: 10px 12px; border: none; background: transparent; color: var(--text-app); font-family: var(--font-code); font-size: 0.82rem; border-radius: 6px; text-align: left; cursor: pointer; transition: all 0.2s; border-left: 3px solid transparent;">
                            <i data-lucide="table-2" style="width: 14px; height: 14px; margin-right: 8px; flex-shrink: 0; color: var(--text-muted);"></i>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;"><?= $tblName ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Table definition columns viewer -->
            <div class="dict-content" style="background: var(--surface-app); border: 1px solid var(--border-app); border-radius: 10px; padding: 24px; box-shadow: var(--shadow-sm); min-height: 400px; display: flex; flex-direction: column;">
                <?php foreach ($dictionary as $tblName => $cols): ?>
                    <div id="dict-table-<?= $tblName ?>" class="dict-table-panel" style="display: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--border-app); padding-bottom: 14px; margin-bottom: 18px;">
                            <h4 style="margin: 0; font-family: var(--font-code); font-size: 1.25rem; font-weight: 700; color: var(--accent-color);">dbo.<?= $tblName ?></h4>
                            <span style="font-size: 0.78rem; background: rgba(2, 132, 199, 0.1); border: 1px solid var(--accent-color); color: var(--accent-color); padding: 3px 10px; border-radius: 12px; font-weight: bold; letter-spacing: 0.05em; text-transform: uppercase;">Tabla física</span>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="font-family: var(--font-code); padding: 12px 14px;">Columna</th>
                                        <th style="font-family: var(--font-code); padding: 12px 14px;">Tipo de Dato</th>
                                        <th style="text-align: center; font-family: var(--font-code); padding: 12px 14px;">Nulo</th>
                                        <th style="text-align: center; font-family: var(--font-code); padding: 12px 14px;">Atributos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cols as $c): ?>
                                        <tr style="transition: background-color 0.15s;">
                                            <td style="font-weight: 700; font-family: var(--font-code); padding: 12px 14px;">
                                                <?php if ($c['pk']): ?>
                                                    <i data-lucide="key" style="width: 13px; height: 13px; display: inline-block; vertical-align: middle; color: #eab308; margin-right: 6px;" title="Llave Primaria (Primary Key)"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($c['column']) ?>
                                            </td>
                                            <td style="font-family: var(--font-code); color: var(--accent-hover); padding: 12px 14px;">
                                                <?= htmlspecialchars($c['type']) ?><?= $c['max_length'] > 0 && !in_array($c['type'], ['int', 'datetime', 'date', 'bigint', 'tinyint', 'bit', 'text', 'ntext']) ? '(' . ($c['type'] === 'nvarchar' ? $c['max_length']/2 : $c['max_length']) . ')' : '' ?>
                                            </td>
                                            <td style="text-align: center; font-family: var(--font-code); padding: 12px 14px;">
                                                <?= $c['nullable'] ? '<span style="color:#22c55e; font-weight:600;">SÍ</span>' : '<span style="color:var(--text-muted);">NO</span>' ?>
                                            </td>
                                            <td style="text-align: center; font-family: var(--font-code); padding: 12px 14px;">
                                                <?= $c['pk'] ? '<span style="font-size: 0.72rem; background:rgba(234,179,8,0.12); border: 1px solid rgba(234,179,8,0.25); color:#eab308; padding: 2px 8px; border-radius: 4px; font-weight:bold; letter-spacing:0.02em;">PRIMARY KEY</span>' : '<span style="color:var(--text-muted); font-size:0.8rem;">-</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Premium Hover & active styles matching professional standard sidebars */
    .dict-tab-btn {
        border-left: 3px solid transparent !important;
    }
    .dict-tab-btn:hover {
        background-color: rgba(255, 255, 255, 0.04) !important;
        border-left: 3px solid rgba(2, 132, 199, 0.4) !important;
        padding-left: 14px !important;
    }
    .dict-tab-btn.active {
        background-color: rgba(2, 132, 199, 0.15) !important;
        color: var(--accent-hover) !important;
        border-left: 3px solid var(--accent-color) !important;
        font-weight: 700 !important;
        padding-left: 14px !important;
    }
    .dict-tab-btn.active i {
        color: var(--accent-hover) !important;
    }
    
    /* Input focus visual border animation */
    #dict-search:focus {
        border-color: var(--accent-color) !important;
        box-shadow: 0 0 0 3px rgba(3, 105, 161, 0.15);
    }
</style>

<script>
    function switchDictTable(tableName, button) {
        // Hide all table panels
        document.querySelectorAll(".dict-table-panel").forEach(p => p.style.display = "none");
        
        // Remove active class from buttons
        document.querySelectorAll(".dict-tab-btn").forEach(b => b.classList.remove("active"));
        
        // Show current panel
        const panel = document.getElementById("dict-table-" + tableName);
        if (panel) {
            panel.style.display = "block";
        }
        
        // Add active class to clicked button
        if (button) {
            button.classList.add("active");
        }
    }
    
    // Auto-trigger first table and search logic
    document.addEventListener("DOMContentLoaded", function() {
        // Search Filter Logic
        const searchInput = document.getElementById("dict-search");
        if (searchInput) {
            searchInput.addEventListener("input", function(e) {
                const query = e.target.value.toLowerCase().trim();
                document.querySelectorAll(".dict-tab-btn").forEach(btn => {
                    const tblName = btn.querySelector("span").textContent.toLowerCase();
                    if (tblName.includes(query)) {
                        btn.style.display = "flex";
                    } else {
                        btn.style.display = "none";
                    }
                });
            });
        }

        const firstBtn = document.querySelector(".dict-tab-btn");
        if (firstBtn) {
            firstBtn.click();
        }
        lucide.createIcons();
    });
</script>

