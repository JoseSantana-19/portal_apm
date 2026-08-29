<?php
/**
 * NotificacionGeneradorModel — genera notificaciones REALES a partir de
 * eventos reales cross-DB (Talento Humano, Control de Bienes, Bitácoras,
 * seguridad del propio portal). Antes de esto CORE_Notificaciones existía
 * completa en el esquema (tipo/prioridad/leida/url_accion) pero nunca tuvo
 * ni una sola fila: nada en el código insertaba ahí. Este es el primer
 * escritor real de esa tabla.
 *
 * Se llama de forma perezosa (generarSiCorresponde()) desde el dashboard y
 * desde el propio módulo de notificaciones -- no hay un cron/Task
 * Scheduler en este proyecto, así que el "job" corre disparado por
 * tráfico real, con un throttle en CORE_Config para no re-escanear en
 * cada request (mismo patrón de secreto/config ya usado por
 * PASSWORD_PEPPER, SSO_SECRET, etc.).
 */
class NotificacionGeneradorModel extends Model {

    private const CLAVE_ULTIMO_SCAN   = 'NOTIF_ULTIMO_SCAN';
    private const INTERVALO_MINUTOS   = 15;
    private const VENTANA_DEDUP_HORAS = 24; // no repetir el mismo título+mensaje dentro de esta ventana

    /** Punto de entrada normal: solo escanea si pasó el intervalo. Devuelve cuántas creó (0 si no tocaba escanear). */
    public function generarSiCorresponde(): int {
        $db = self::db();
        $row = $db->fetch($db->query(
            "SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave=? AND estado=1",
            [self::CLAVE_ULTIMO_SCAN]
        ));

        if ($row && !empty($row['valor'])) {
            $ultimo = strtotime((string)$row['valor']);
            if ($ultimo !== false && (time() - $ultimo) < self::INTERVALO_MINUTOS * 60) {
                return 0;
            }
        }

        try {
            $creadas = $this->generar();
        } catch (Throwable $e) {
            // Un módulo caído (BD no disponible, etc.) no debe tumbar el
            // dashboard -- se registra y se sigue como si no hubiera nada
            // nuevo esta vuelta.
            error_log('NotificacionGeneradorModel: ' . $e->getMessage());
            $creadas = 0;
        }
        $this->marcarUltimoScan();
        return $creadas;
    }

    private function marcarUltimoScan(): void {
        $db = self::db();
        $ahora = date('Y-m-d H:i:s');
        $db->query(
            "IF EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave=?)
                 UPDATE CORE_Config SET valor=? WHERE modulo='CORE' AND clave=?
             ELSE
                 INSERT INTO CORE_Config (modulo, clave, valor, descripcion, estado)
                 VALUES ('CORE', ?, ?, 'Última vez que corrió el generador de notificaciones reales -- autogenerado, no editar a mano.', 1)",
            [
                self::CLAVE_ULTIMO_SCAN, $ahora, self::CLAVE_ULTIMO_SCAN,
                self::CLAVE_ULTIMO_SCAN, $ahora,
            ]
        );
    }

    /** Corre todos los escaneos reales. Público para poder forzarlo (ej. botón "Actualizar" o pruebas). */
    public function generar(): int {
        $creadas = 0;
        $creadas += $this->notificarEmpleadosNuevos();
        $creadas += $this->notificarCumpleanosHoy();
        $creadas += $this->notificarBienesEnMantenimiento();
        $creadas += $this->notificarVisitasSinSalida();
        $creadas += $this->notificarCuentasBloqueadas();
        return $creadas;
    }

    // ── 1. Talento Humano: empleados dados de alta en las últimas 48h ──────
    private function notificarEmpleadosNuevos(): int {
        $db = self::db();
        try {
            $rows = $db->fetchAll($db->query(
                "SELECT empleado_id, apellidos_nombres, cargo
                 FROM Talento_Humano.dbo.vw_th_directorio_empleados
                 WHERE estado = 1 AND fecha_ingreso >= DATEADD(HOUR, -48, GETDATE())"
            ));
        } catch (Throwable $e) {
            return 0;
        }
        $creadas = 0;
        foreach ($rows as $r) {
            $nombre = trim((string)($r['apellidos_nombres'] ?? 'Nuevo funcionario'));
            $cargo  = trim((string)($r['cargo'] ?? ''));
            $titulo = 'Nuevo ingreso en Talento Humano';
            $mensaje = $cargo !== '' ? "{$nombre} se incorporó como {$cargo}." : "{$nombre} se incorporó a la institución.";
            if ($this->crear(null, $titulo, $mensaje, 'info', 1, '/apps/talento_humano/talento-humano/directorio')) {
                $creadas++;
            }
        }
        return $creadas;
    }

    // ── 2. Talento Humano: cumpleaños de hoy ────────────────────────────────
    private function notificarCumpleanosHoy(): int {
        $db = self::db();
        try {
            $rows = $db->fetchAll($db->query(
                "SELECT apellidos_nombres
                 FROM Talento_Humano.dbo.vw_th_directorio_empleados
                 WHERE estado = 1
                   AND fecha_nacimiento IS NOT NULL
                   AND MONTH(fecha_nacimiento) = MONTH(GETDATE())
                   AND DAY(fecha_nacimiento) = DAY(GETDATE())"
            ));
        } catch (Throwable $e) {
            return 0;
        }
        if (empty($rows)) return 0;

        $nombres = array_map(fn($r) => trim((string)($r['apellidos_nombres'] ?? '')), $rows);
        $nombres = array_values(array_filter($nombres));
        if (empty($nombres)) return 0;

        $titulo = count($nombres) === 1 ? 'Cumpleaños de hoy' : 'Cumpleaños de hoy (' . count($nombres) . ')';
        $mensaje = count($nombres) <= 3
            ? implode(', ', $nombres) . '.'
            : implode(', ', array_slice($nombres, 0, 3)) . ' y ' . (count($nombres) - 3) . ' más.';

        return $this->crear(null, $titulo, $mensaje, 'success', 1, '/apps/talento_humano/talento-humano/inicio') ? 1 : 0;
    }

    // ── 3. Control de Bienes: bienes que entraron a mantenimiento recientemente ──
    private function notificarBienesEnMantenimiento(): int {
        $db = self::db();
        try {
            $row = $db->fetch($db->query(
                "SELECT COUNT(*) AS total FROM inventario.dbo.inv_inventario WHERE activo=1 AND estado_id=2"
            ));
        } catch (Throwable $e) {
            return 0;
        }
        $total = (int)($row['total'] ?? 0);
        if ($total === 0) return 0;

        $titulo  = 'Bienes en mantenimiento';
        $mensaje = $total === 1
            ? 'Hay 1 bien registrado en mantenimiento.'
            : "Hay {$total} bienes registrados en mantenimiento.";

        return $this->crear(null, $titulo, $mensaje, 'warning', 2, '/apps/control_bienes/index.php?route=inventario') ? 1 : 0;
    }

    // ── 4. Bitácoras: visitas sin hora de salida hace más de 12 horas ──────
    private function notificarVisitasSinSalida(): int {
        $db = self::db();
        try {
            $row = $db->fetch($db->query(
                "SELECT COUNT(*) AS total FROM PortuariaDemo.dbo.bit_visitas
                 WHERE hora_salida IS NULL AND fecha_visita <= DATEADD(HOUR, -12, GETDATE())"
            ));
        } catch (Throwable $e) {
            return 0;
        }
        $total = (int)($row['total'] ?? 0);
        if ($total === 0) return 0;

        $titulo  = 'Visitas sin registro de salida';
        $mensaje = $total === 1
            ? 'Hay 1 visita registrada hace más de 12 horas sin hora de salida.'
            : "Hay {$total} visitas registradas hace más de 12 horas sin hora de salida.";

        return $this->crear(null, $titulo, $mensaje, 'warning', 2, '/apps/bitacoras/visitas') ? 1 : 0;
    }

    // ── 5. Seguridad: cuentas del portal actualmente bloqueadas ────────────
    private function notificarCuentasBloqueadas(): int {
        $db = self::db();
        $rows = $db->fetchAll($db->query(
            "SELECT nombre_completo, cedula, fecha_bloqueo, minutos_bloqueo
             FROM CORE_Usuarios
             WHERE estado = 1
               AND fecha_bloqueo IS NOT NULL
               AND GETDATE() < DATEADD(MINUTE, minutos_bloqueo, fecha_bloqueo)"
        ));
        $creadas = 0;
        foreach ($rows as $r) {
            $nombre = trim((string)($r['nombre_completo'] ?? $r['cedula'] ?? 'Cuenta'));
            $titulo = 'Cuenta bloqueada por intentos fallidos';
            $mensaje = "{$nombre} está bloqueada temporalmente por intentos de acceso fallidos.";
            if ($this->crear(null, $titulo, $mensaje, 'danger', 3, '/admin/usuarios')) {
                $creadas++;
            }
        }
        return $creadas;
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function yaExiste(string $titulo, string $mensaje): bool {
        $db = self::db();
        $row = $db->fetch($db->query(
            "SELECT TOP 1 id_notif FROM CORE_Notificaciones
             WHERE titulo = ? AND mensaje = ? AND fecha_creacion >= DATEADD(HOUR, ?, GETDATE())",
            [$titulo, $mensaje, -self::VENTANA_DEDUP_HORAS]
        ));
        return $row !== null;
    }

    private function crear(?int $idUsuario, string $titulo, string $mensaje, string $tipo, int $prioridad, ?string $urlAccion): bool {
        $titulo  = mb_substr($titulo, 0, 150);
        $mensaje = mb_substr($mensaje, 0, 500);
        if ($this->yaExiste($titulo, $mensaje)) {
            return false;
        }
        $db = self::db();
        $db->query(
            "INSERT INTO CORE_Notificaciones (id_usuario, titulo, mensaje, tipo, prioridad, leida, url_accion, fecha_creacion, estado)
             VALUES (?, ?, ?, ?, ?, 0, ?, GETDATE(), 1)",
            [$idUsuario, $titulo, $mensaje, $tipo, $prioridad, $urlAccion]
        );
        return true;
    }
}
