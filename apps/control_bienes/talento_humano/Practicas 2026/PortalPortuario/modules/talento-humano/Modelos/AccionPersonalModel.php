<?php
// modules/talento-humano/Modelos/AccionPersonalModel.php
// Módulo: Talento Humano – Fase 2: Acción de Personal
// Hereda de Model (accede a $this->db mediante Conexion::conectar())

class AccionPersonalModel extends Model
{
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Genera el siguiente número de acción correlativo del año en curso.
     * Formato: APM-TH-YYYY-NNN  (ej: APM-TH-2026-001)
     *
     * Consulta el registro más reciente del año para obtener el número más
     * alto y sumar 1. Si no existe ninguno, arranca desde 001.
     *
     * @return string  Secuencial formateado y listo para mostrar al usuario
     */
    public function generarSiguienteSecuencial(): string
    {
        $anio    = date('Y');
        $prefijo = "APM-TH-{$anio}-";

        try {
            // TOP 1 ordenado por accion_id DESC garantiza el número más reciente,
            // independientemente de brechas o eliminaciones en el historial.
            $sql = "SELECT TOP 1 numero_accion
                    FROM th_acciones_personal
                    WHERE numero_accion LIKE :prefijo
                    ORDER BY accion_id DESC";

            $stmt = $this->db->prepare($sql);
            $like = $prefijo . '%';
            $stmt->bindParam(':prefijo', $like, PDO::PARAM_STR);
            $stmt->execute();

            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                // Extrae el segmento numérico final (después del último guion)
                $partes        = explode('-', $fila['numero_accion']);
                $ultimoNumero  = (int) end($partes);
                $siguienteNum  = $ultimoNumero + 1;
            } else {
                // Primera acción del año
                $siguienteNum = 1;
            }

            return $prefijo . str_pad($siguienteNum, 3, '0', STR_PAD_LEFT);

        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            // Fallback seguro: no detiene la carga del formulario
            return $prefijo . '001';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Inserta una Acción de Personal formal en th_acciones_personal.
     * Todos los parámetros se vinculan con bindParam() para prevenir SQLi.
     *
     * @param  array $d  Payload saneado proveniente del controlador
     * @return bool       true si el INSERT fue exitoso, false en caso de error
     */
    public function registrarAccion(array $d): bool
    {
        try {
            $sql = "INSERT INTO th_acciones_personal (
                        numero_accion,
                        fecha_elaboracion,
                        empleado_id,
                        tipo_accion,
                        fecha_rige_desde,
                        fecha_rige_hasta,
                        explicacion_legal,
                        actual_unidad_id,
                        actual_puesto_id,
                        actual_lugar_trabajo,
                        actual_remuneracion,
                        propuesta_unidad_id,
                        propuesta_puesto_id,
                        propuesta_lugar_trabajo,
                        propuesta_remuneracion,
                        estado_documento,
                        usuario_crea
                    ) VALUES (
                        :numero_accion,
                        GETDATE(),
                        :empleado_id,
                        :tipo_accion,
                        :fecha_rige_desde,
                        :fecha_rige_hasta,
                        :explicacion_legal,
                        :actual_unidad_id,
                        :actual_puesto_id,
                        :actual_lugar_trabajo,
                        :actual_remuneracion,
                        :propuesta_unidad_id,
                        :propuesta_puesto_id,
                        :propuesta_lugar_trabajo,
                        :propuesta_remuneracion,
                        'Aprobado',
                        :usuario_crea
                    )";

            $stmt = $this->db->prepare($sql);

            // ── Cabecera del documento ────────────────────────────────────
            $stmt->bindParam(':numero_accion',   $d['numero_accion'],   PDO::PARAM_STR);
            $stmt->bindParam(':empleado_id',     $d['empleado_id'],     PDO::PARAM_INT);
            $stmt->bindParam(':tipo_accion',     $d['tipo_accion'],     PDO::PARAM_STR);
            $stmt->bindParam(':fecha_rige_desde',$d['fecha_rige_desde'],PDO::PARAM_STR);

            // fecha_rige_hasta es opcional (NULL = permanente)
            $rigeHasta = !empty($d['fecha_rige_hasta']) ? $d['fecha_rige_hasta'] : null;
            $stmt->bindParam(':fecha_rige_hasta', $rigeHasta,
                             $rigeHasta ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $stmt->bindParam(':explicacion_legal',     $d['explicacion_legal'],     PDO::PARAM_STR);

            // ── Situación Actual ──────────────────────────────────────────
            $stmt->bindParam(':actual_unidad_id',      $d['actual_unidad_id'],      PDO::PARAM_INT);
            $stmt->bindParam(':actual_puesto_id',      $d['actual_puesto_id'],      PDO::PARAM_INT);
            $stmt->bindParam(':actual_lugar_trabajo',  $d['actual_lugar_trabajo'],  PDO::PARAM_STR);
            $stmt->bindParam(':actual_remuneracion',   $d['actual_remuneracion'],   PDO::PARAM_STR);

            // ── Situación Propuesta ───────────────────────────────────────
            $stmt->bindParam(':propuesta_unidad_id',   $d['propuesta_unidad_id'],   PDO::PARAM_INT);
            $stmt->bindParam(':propuesta_puesto_id',   $d['propuesta_puesto_id'],   PDO::PARAM_INT);
            $stmt->bindParam(':propuesta_lugar_trabajo',$d['propuesta_lugar_trabajo'],PDO::PARAM_STR);
            $stmt->bindParam(':propuesta_remuneracion',$d['propuesta_remuneracion'], PDO::PARAM_STR);

            // ── Auditoría ─────────────────────────────────────────────────
            $stmt->bindParam(':usuario_crea', $d['usuario_crea'], PDO::PARAM_STR);

            return $stmt->execute();

        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Recupera una Acción de Personal por su ID para la vista "ver".
     *
     * @param  int        $id  accion_id
     * @return array|null      Fila completa o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM th_acciones_personal WHERE accion_id = :id"
            );
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Obtiene una Acción de Personal cruzando todas las tablas de catálogo.
     * Devuelve los textos reales de área y cargo (actual y propuesta) junto
     * con los datos del empleado, listos para renderizar en el PDF oficial.
     *
     * @param  int        $accionId  accion_id de th_acciones_personal
     * @return array|null            Fila cruzada o null si no existe / error
     */
    public function obtenerAccionCruzada(int $accionId): ?array
    {
        try {
            $sql = "SELECT
                        a.*,
                        e.cedula          AS identificacion,
                        e.nombres         AS nombres,
                        e.apellidos       AS apellidos,
                        u_act.nombre_unidad  AS actual_area,
                        p_act.nombre_puesto  AS actual_cargo,
                        u_prop.nombre_unidad AS propuesta_area,
                        p_prop.nombre_puesto AS propuesta_cargo
                    FROM th_acciones_personal a
                    INNER JOIN th_empleados e
                        ON a.empleado_id = e.empleado_id
                    LEFT JOIN th_unidades_organizacionales u_act
                        ON a.actual_unidad_id = u_act.unidad_id
                    LEFT JOIN th_puestos p_act
                        ON a.actual_puesto_id = p_act.puesto_id
                    LEFT JOIN th_unidades_organizacionales u_prop
                        ON a.propuesta_unidad_id = u_prop.unidad_id
                    LEFT JOIN th_puestos p_prop
                        ON a.propuesta_puesto_id = p_prop.puesto_id
                    WHERE a.accion_id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $accionId, PDO::PARAM_INT);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return null;
        }
    }
}
