<?php
// modules/Talento_Humano/models/EmpleadoModel.php
// RECTIFICADO v2.0 – Auditoría Técnica Autoridad Portuaria de Manta
//
// Consume EXCLUSIVAMENTE la vista vw_th_directorio_empleados (lectura)
// y los SPs sp_th_guardar_empleado / sp_th_modificar_empleado / sp_th_eliminar_empleado (escritura).

require_once ROOT_PATH . 'core/Model.php';

class EmpleadoModel extends Model
{
    private $logger;

    public function __construct()
    {
        $this->db = Database::getInstance('Talento_Humano')->getConnection();
        require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';
        $this->logger = new Logger('th');
    }

    /* ── LECTURA ──────────────────────────────────────────────────────── */

    /** Lista todos los empleados desde la vista normalizada de SQL Server */
    public function listarDirectorio(): array
    {
        try {
            $sql = "SELECT * FROM view_th_iddatosempledo ORDER BY apellidos ASC, nombres ASC";
            return $this->db->query($sql)->fetchAll();
        } catch (Exception $e) {
            $this->logger->warning("Error al listar directorio: " . $e->getMessage(), 'EmpleadoModel::listarDirectorio', $e);
            return [];
        }
    }

    /** Obtiene el personal para los dropdowns de Control de Bienes */
    public function obtenerPersonal(): array
    {
        try {
            $sql = "SELECT 
                        id, 
                        apellidos + ' ' + nombres AS nombre, 
                        direccion_area AS area_actual 
                    FROM view_th_iddatosempledo 
                    WHERE estado = 1
                    ORDER BY apellidos ASC, nombres ASC";
            return $this->db->query($sql)->fetchAll();
        } catch (Exception $e) {
            $this->logger->warning("Error al obtener personal para bines: " . $e->getMessage(), 'EmpleadoModel::obtenerPersonal', $e);
            return [];
        }
    }

    /** Lee el RBU vigente desde th_parametros */
    public function obtenerRbuVigente(): string
    {
        try {
            // Nota: th_parametros se accede a través del prefijo de base de datos
            $sql = "SELECT valor FROM th_parametros WHERE parametro_id = 'RBU_2026'";
            $res = $this->db->query($sql)->fetch();
            return $res ? $res['valor'] : '460.00';
        } catch (Exception $e) {
            $this->logger->warning("Error al obtener RBU vigente: " . $e->getMessage(), 'EmpleadoModel::obtenerRbuVigente', $e);
            return '460.00';
        }
    }

    /** Trae el expediente completo para el modo EDICIÓN */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM th_empleados WHERE empleado_id = :id"
            );
            $stmt->execute([':id' => $id]);
            $res = $stmt->fetch();
            return $res ?: null;
        } catch (Exception $e) {
            $this->logger->warning("Error al obtener empleado por ID {$id}: " . $e->getMessage(), 'EmpleadoModel::obtenerPorId', $e);
            return null;
        }
    }

    /**
     * Busca el expediente integral de un funcionario por su número de cédula.
     *
     * @param  string     $cedula  Número de cédula / pasaporte del funcionario
     * @return array|null          Expediente completo o null si no existe
     */
    public function obtenerPorCedula(string $cedula): ?array
    {
        try {
            $sql = "SELECT id, cedula, apellidos, nombres, cargo, direccion_area,
                           correo_institucional, estado, cargas_familiares,
                           tipo_cuenta_bancaria, numero_cuenta_bancaria, institucion_bancaria
                    FROM view_th_iddatosempledo
                    WHERE cedula = :cedula";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            $this->logger->warning("Error al obtener empleado por cédula {$cedula}: " . $e->getMessage(), 'EmpleadoModel::obtenerPorCedula', $e);
            return null;
        }
    }

    /** Carga todas las unidades/áreas activas para los <select> del formulario */
    public function listarAreas(): array
    {
        try {
            $sql = "SELECT unidad_id, nombre_unidad FROM th_unidades_organizacionales WHERE activo = 1 ORDER BY nombre_unidad";
            return $this->db->query($sql)->fetchAll();
        } catch (Exception $e) {
            $this->logger->warning("Error al listar áreas: " . $e->getMessage(), 'EmpleadoModel::listarAreas', $e);
            return [];
        }
    }

    /** Carga todos los puestos/cargos activos para los <select> del formulario */
    public function listarCargos(): array
    {
        try {
            $sql = "SELECT puesto_id, nombre_puesto FROM th_puestos WHERE activo = 1 ORDER BY nombre_puesto";
            return $this->db->query($sql)->fetchAll();
        } catch (Exception $e) {
            $this->logger->warning("Error al listar cargos: " . $e->getMessage(), 'EmpleadoModel::listarCargos', $e);
            return [];
        }
    }

    /**
     * Obtiene el expediente integral de un funcionario por su ID.
     *
     * @param int $id ID del empleado
     * @return array|null Retorna el arreglo con los datos o null si no se encuentra
     */
    public function obtenerDetalleCompleto(int $id): ?array
    {
        try {
            $sql = "SELECT id, cedula, apellidos, nombres, cargo, direccion_area,
                           correo_institucional, estado, cargas_familiares,
                           tipo_cuenta_bancaria, numero_cuenta_bancaria, institucion_bancaria
                    FROM view_th_iddatosempledo
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            $this->logger->warning("Error al obtener detalle completo del empleado ID {$id}: " . $e->getMessage(), 'EmpleadoModel::obtenerDetalleCompleto', $e);
            return null;
        }
    }

    /**
     * RECTIFICADO: Consulta segura del historial laboral jerárquico con fusiones.
     */
    public function obtenerReporteFiltrado(?string $tipoCargo = null, ?int $empleadoId = null): array
    {
        try {
            // Construcción base apuntando a la vista de historial jerárquico
            $sql = "SELECT * FROM vw_th_reporte_historial_jerarquico WHERE 1=1";

            // Filtro por cargo específico (ej: 'GERENTE' o 'DIRECTOR')
            if ($tipoCargo !== null && $tipoCargo !== '') {
                $sql .= " AND nombre_puesto LIKE :cargo";
            }

            // Filtro para expediente individual de un funcionario
            if ($empleadoId !== null) {
                $sql .= " AND empleado_id = :empleado_id";
            }

            // Ordenamiento jerárquico: tipo de proceso → área → funcionario → fecha
            $sql .= " ORDER BY tipo_proceso DESC, direccion_actual_unificada, funcionario, fecha_desde ASC";

            $stmt = $this->db->prepare($sql);

            // ANTI-SQLi: uso obligatorio de bindParam() con tipo explícito
            if ($tipoCargo !== null && $tipoCargo !== '') {
                $buscar = "%" . $tipoCargo . "%";
                $stmt->bindParam(':cargo', $buscar, PDO::PARAM_STR);
            }

            if ($empleadoId !== null) {
                $stmt->bindParam(':empleado_id', $empleadoId, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll();

        } catch (Exception $e) {
            $this->logger->warning("Error al obtener reporte filtrado: " . $e->getMessage(), 'EmpleadoModel::obtenerReporteFiltrado', $e);
            return [];
        }
    }

    /* ── ESCRITURA ────────────────────────────────────────────────────── */

    /** INSERT mediante SP transaccional con secuenciales controlados */
    public function insertar(array $d): bool
    {
        try {
            $sql = "EXEC sp_th_guardar_empleado
                    :cedula, :nombres, :fecha_nac, :condicion, :tipo_disc, :porcentaje_disc,
                    :sexo, :estado_civil, :nacionalidad, :tipo_sangre,
                    :depto, :puesto, :tipo_contrato, :fecha_ing, :sueldo, :jornada,
                    :correo, :celular, :convencional, :ciudad, :direccion,
                    :contacto_emerg, :parentesco, :tel_emerg,
                    :nivel_estudio, :titulo, :iess, :foto, :obs";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($this->_mapParams(null, $d));
        } catch (Exception $e) {
            $this->logger->warning("Error al insertar empleado: " . $e->getMessage(), 'EmpleadoModel::insertar', $e);
            return false;
        }
    }

    /** UPDATE mediante SP de modificación */
    public function modificar(int $id, array $d): bool
    {
        try {
            $sql = "EXEC sp_th_modificar_empleado
                    :id, :cedula, :nombres, :fecha_nac, :condicion, :tipo_disc, :porcentaje_disc,
                    :sexo, :estado_civil, :nacionalidad, :tipo_sangre,
                    :depto, :puesto, :tipo_contrato, :fecha_ing, :sueldo, :jornada,
                    :correo, :celular, :convencional, :ciudad, :direccion,
                    :contacto_emerg, :parentesco, :tel_emerg,
                    :nivel_estudio, :titulo, :iess, :foto, :obs";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($this->_mapParams($id, $d));
        } catch (Exception $e) {
            $this->logger->warning("Error al modificar empleado ID {$id}: " . $e->getMessage(), 'EmpleadoModel::modificar', $e);
            return false;
        }
    }

    /** DELETE lógico mediante SP */
    public function eliminar(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("EXEC sp_th_eliminar_empleado :id");
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            $this->logger->warning("Error al eliminar empleado ID {$id}: " . $e->getMessage(), 'EmpleadoModel::eliminar', $e);
            return false;
        }
    }

    /* ── HELPERS PRIVADOS ─────────────────────────────────────────────── */

    /** Construye el array de parámetros PDO para insertar/modificar */
    private function _mapParams(?int $id, array $d): array
    {
        $params = [
            ':cedula'          => $d['cedula'],
            ':nombres'         => strtoupper($d['nombres']),
            ':fecha_nac'       => $d['fecha_nac'] ?? null,
            ':condicion'       => $d['condicion_especial'] ?? 'Ninguna',
            ':tipo_disc'       => !empty($d['tipo_discapacidad'])      ? $d['tipo_discapacidad']      : null,
            ':porcentaje_disc' => !empty($d['porcentaje_discapacidad']) ? $d['porcentaje_discapacidad'] : null,
            ':sexo'            => $d['genero'] ?? null,
            ':estado_civil'    => $d['estado_civil'] ?? null,
            ':nacionalidad'    => $d['nacionalidad'] ?? null,
            ':tipo_sangre'     => $d['sangre'] ?? null,
            ':depto'           => $d['unidad_id']    ?? $d['departamento'] ?? null,
            ':puesto'          => $d['puesto_id']    ?? $d['cargo']        ?? null,
            ':tipo_contrato'   => $d['tipo_contrato'] ?? null,
            ':fecha_ing'       => $d['fecha_ingreso'] ?? null,
            ':sueldo'          => $d['sueldo'] ?? null,
            ':jornada'         => $d['jornada'] ?? 'Completa',
            ':correo'          => $d['correo'] ?? null,
            ':celular'         => $d['telefono'] ?? null,
            ':convencional'    => $d['telefono_convencional'] ?? null,
            ':ciudad'          => $d['ciudad_residencia'],
            ':direccion'       => $d['direccion'] ?? null,
            ':contacto_emerg'  => $d['contacto_emergencia'] ?? null,
            ':parentesco'      => $d['emergencia_relacion'] ?? null,
            ':tel_emerg'       => $d['tel_emergencia'] ?? null,
            ':nivel_estudio'   => $d['nivel_estudio'] ?? null,
            ':titulo'          => $d['titulo'] ?? null,
            ':iess'            => $d['iess'] ?? null,
            ':foto'            => $d['ruta_foto'] ?? 'public/img/default_avatar.png',
            ':obs'             => $d['observaciones'] ?? null,
        ];

        if ($id !== null) {
            $params[':id'] = $id;
        }

        return $params;
    }
}
class_alias('EmpleadoModel', 'InvAsignacionTalento');
