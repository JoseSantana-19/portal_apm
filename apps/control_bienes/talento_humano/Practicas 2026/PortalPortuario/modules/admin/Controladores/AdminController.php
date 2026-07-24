<?php
// modules/admin/Controladores/AdminController.php
// Módulo: Administración y Seguridad – Usuarios, Roles y Políticas

class AdminController extends Controller
{
    /** GET /admin/usuarios – Gestión de usuarios del sistema */
    public function usuarios(): void
    {
        $usuarios = [
            ['id' => 1, 'usuario' => 'admin.th',      'nombre' => 'ZAMBRANO DELGADO HECTOR',  'rol' => 'Administrador TH',  'email' => 'h.zambrano@apm.gob.ec',   'estado' => 'Activo',    'ultimo_acceso' => '2026-05-30 09:14:22', 'empleado_id' => 1],
            ['id' => 2, 'usuario' => 'j.perez',        'nombre' => 'PEREZ MORALES JUAN CARLOS', 'rol' => 'Consultor',         'email' => 'j.perez@apm.gob.ec',       'estado' => 'Activo',    'ultimo_acceso' => '2026-05-29 14:30:11', 'empleado_id' => 2],
            ['id' => 3, 'usuario' => 'a.torres',       'nombre' => 'TORRES VEGA ANA MARIA',     'rol' => 'Analista RRHH',     'email' => 'a.torres@apm.gob.ec',      'estado' => 'Activo',    'ultimo_acceso' => '2026-05-30 08:55:00', 'empleado_id' => 3],
            ['id' => 4, 'usuario' => 'm.palma',        'nombre' => 'PALMA TEJENA MICHAEL',      'rol' => 'Supervisor',        'email' => 'm.palma@apm.gob.ec',       'estado' => 'Inactivo',  'ultimo_acceso' => '2026-05-10 16:00:45', 'empleado_id' => 4],
            ['id' => 5, 'usuario' => 'superadmin',     'nombre' => 'SISTEMA APM',              'rol' => 'Super Administrador','email' => 'sistemas@apm.gob.ec',      'estado' => 'Activo',    'ultimo_acceso' => '2026-05-30 11:00:00', 'empleado_id' => null],
        ];

        $datos = [
            'usuarios'        => $usuarios,
            'total'           => count($usuarios),
            'activos'         => count(array_filter($usuarios, fn($u) => $u['estado'] === 'Activo')),
            'inactivos'       => count(array_filter($usuarios, fn($u) => $u['estado'] === 'Inactivo')),
        ];
        $this->cargarVista('admin', 'usuarios', $datos);
    }

    /** GET /admin/roles – Roles y Permisos (RBAC) */
    public function roles(): void
    {
        $roles = [
            [
                'id'          => 1,
                'nombre'      => 'Super Administrador',
                'descripcion' => 'Acceso total al sistema sin restricciones. Solo asignable por sistemas.',
                'usuarios'    => 1,
                'color'       => 'danger',
                'permisos'    => ['*'],
            ],
            [
                'id'          => 2,
                'nombre'      => 'Administrador TH',
                'descripcion' => 'Gestión completa del módulo de Talento Humano: empleados, asistencia, vacaciones y reportes.',
                'usuarios'    => 1,
                'color'       => 'primary',
                'permisos'    => ['empleados.*', 'asistencia.*', 'vacaciones.*', 'desempeno.*', 'capacitacion.*', 'reportes.ver'],
            ],
            [
                'id'          => 3,
                'nombre'      => 'Analista RRHH',
                'descripcion' => 'Puede consultar y editar expedientes, registrar asistencia y gestionar capacitaciones.',
                'usuarios'    => 1,
                'color'       => 'info',
                'permisos'    => ['empleados.ver', 'empleados.editar', 'asistencia.ver', 'asistencia.registrar', 'capacitacion.*'],
            ],
            [
                'id'          => 4,
                'nombre'      => 'Consultor',
                'descripcion' => 'Solo lectura. Puede ver el directorio, reportes y su propio expediente.',
                'usuarios'    => 1,
                'color'       => 'success',
                'permisos'    => ['empleados.ver', 'reportes.ver'],
            ],
            [
                'id'          => 5,
                'nombre'      => 'Supervisor',
                'descripcion' => 'Puede aprobar solicitudes de vacaciones y revisar asistencia de su equipo.',
                'usuarios'    => 1,
                'color'       => 'warning',
                'permisos'    => ['vacaciones.aprobar', 'asistencia.ver', 'empleados.ver'],
            ],
        ];

        $modulos = [
            'empleados'    => ['ver', 'crear', 'editar', 'eliminar'],
            'asistencia'   => ['ver', 'registrar', 'importar'],
            'vacaciones'   => ['ver', 'solicitar', 'aprobar', 'rechazar'],
            'desempeno'    => ['ver', 'evaluar'],
            'capacitacion' => ['ver', 'crear', 'editar'],
            'reportes'     => ['ver', 'exportar'],
            'admin'        => ['usuarios', 'roles', 'politicas'],
            'auditoria'    => ['logs'],
        ];

        $datos = [
            'roles'   => $roles,
            'modulos' => $modulos,
        ];
        $this->cargarVista('admin', 'roles', $datos);
    }

    /** GET /admin/politicas – Repositorio de políticas y normativas */
    public function politicas(): void
    {
        $documentos = [
            [
                'id'           => 1,
                'titulo'       => 'Reglamento Interno de Trabajo',
                'categoria'    => 'Normativa',
                'tipo_archivo' => 'PDF',
                'tamaño'       => '2.4 MB',
                'version'      => '3.0',
                'fecha_subida' => '2026-01-15',
                'subido_por'   => 'Admin TH',
                'descripcion'  => 'Reglamento que regula las relaciones laborales, derechos y obligaciones de los servidores de la APM.',
                'vigente'      => true,
                'descargas'    => 47,
            ],
            [
                'id'           => 2,
                'titulo'       => 'Código de Ética y Conducta',
                'categoria'    => 'Ética',
                'tipo_archivo' => 'PDF',
                'tamaño'       => '1.8 MB',
                'version'      => '2.1',
                'fecha_subida' => '2026-02-01',
                'subido_por'   => 'Admin TH',
                'descripcion'  => 'Principios y valores éticos que deben guiar el comportamiento de todos los funcionarios.',
                'vigente'      => true,
                'descargas'    => 89,
            ],
            [
                'id'           => 3,
                'titulo'       => 'Manual de Seguridad Portuaria',
                'categoria'    => 'Seguridad',
                'tipo_archivo' => 'PDF',
                'tamaño'       => '5.1 MB',
                'version'      => '1.0',
                'fecha_subida' => '2025-11-20',
                'subido_por'   => 'Dir. Operaciones',
                'descripcion'  => 'Procedimientos obligatorios de seguridad en zonas de operación portuaria.',
                'vigente'      => true,
                'descargas'    => 112,
            ],
            [
                'id'           => 4,
                'titulo'       => 'Memorando Circular 2026-001',
                'categoria'    => 'Memorando',
                'tipo_archivo' => 'PDF',
                'tamaño'       => '0.3 MB',
                'version'      => '1.0',
                'fecha_subida' => '2026-05-15',
                'subido_por'   => 'Dirección General',
                'descripcion'  => 'Disposiciones sobre el uso del sistema de control de asistencia biométrico.',
                'vigente'      => true,
                'descargas'    => 23,
            ],
            [
                'id'           => 5,
                'titulo'       => 'Política de Uso de TIC',
                'categoria'    => 'Tecnología',
                'tipo_archivo' => 'PDF',
                'tamaño'       => '1.2 MB',
                'version'      => '2.0',
                'fecha_subida' => '2025-09-10',
                'subido_por'   => 'Dir. Sistemas',
                'descripcion'  => 'Normativa interna sobre uso aceptable de recursos tecnológicos y sistemas de información.',
                'vigente'      => false,
                'descargas'    => 34,
            ],
        ];

        $categorias = array_unique(array_column($documentos, 'categoria'));
        sort($categorias);

        $datos = [
            'documentos'       => $documentos,
            'categorias'       => $categorias,
            'total'            => count($documentos),
            'vigentes'         => count(array_filter($documentos, fn($d) => $d['vigente'])),
            'total_descargas'  => array_sum(array_column($documentos, 'descargas')),
        ];
        $this->cargarVista('admin', 'politicas', $datos);
    }
}
