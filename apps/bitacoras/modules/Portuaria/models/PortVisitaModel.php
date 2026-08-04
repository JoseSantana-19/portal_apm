<?php
/**
 * Modelo de Visitas.
 */
class PortVisitaModel extends PortBaseModel
{
    public function __construct(string $connection = 'principal')
    {
        parent::__construct($connection);
        require_once ROOT_PATH . '/includes/bit_visitas_guardar_ingreso.php';
        require_once ROOT_PATH . '/apis/bit_sincronizar_funcionarios_dbf.php';
    }

    public function guardarIngresoDesdePost()
    {
        visitas_guardar_ingreso_desde_post($this->conn);
    }

    /**
     * Lista de funcionarios activos sincronizados desde el DBF.
     */
    public function funcionariosActivos(): array
    {
        $lista = obtener_funcionarios_activos_sincronizados($this->conn);
        return is_array($lista) ? $lista : [];
    }

    public function destinosActivos(): array
    {
        return $this->fetchAll("SELECT id_destino, nombre FROM dbo.bit_destinos WHERE estado = 1 ORDER BY nombre");
    }

    public function motivosActivos(): array
    {
        return $this->fetchAll("SELECT id_motivo, descripcion FROM dbo.bit_motivos WHERE estado = 1 ORDER BY descripcion");
    }

    public function nivelesIncidenteActivos(): array
    {
        return $this->fetchAll("SELECT id_incidentes, nivel, descripcion FROM dbo.bit_niveles_incidente WHERE estado = 1 ORDER BY nivel");
    }

    public function empresasActivas(): array
    {
        return $this->fetchAll("SELECT id_empresa, empresa, ruc FROM dbo.bit_empresas WHERE estado = 1 ORDER BY empresa");
    }

    public function tieneColumnaDetalleMotivo(): bool
    {
        $row = $this->fetchOne(
            "SELECT TOP 1 1 AS existe FROM sys.columns
             WHERE object_id = OBJECT_ID(N'dbo.bit_visitas') AND name = N'detalle_motivo'"
        );
        return $row !== false;
    }

    /**
     * Listado principal de visitas con JOIN completo.
     */
    public function listado(): array
    {
        $detalleSelect = $this->tieneColumnaDetalleMotivo()
            ? "v.detalle_motivo AS detalle_motivo"
            : "CAST(N'' AS NVARCHAR(500)) AS detalle_motivo";

        $sql = "
            SELECT
                v.id_visita, v.id_empresa, v.id_funcionario, v.id_destino, v.id_motivo, v.id_nivel_incidente,
                p.id_persona,
                p.nombres + ' ' + p.apellidos AS nombre_visitante,
                p.nidentificacion,
                v.tipo_visitante,
                e.empresa AS empresa,
                e.ruc AS empresa_ruc,
                ISNULL(f.nombre, N'Sin funcionario asignado') AS funcionario,
                d.nombre AS destino,
                m.descripcion AS motivo,
                {$detalleSelect},
                v.fecha_visita, v.hora_entrada, v.hora_salida,
                ni.descripcion AS nivel_incidente
            FROM dbo.bit_visitas v
            INNER JOIN dbo.bit_personas p ON v.id_persona = p.id_persona
            LEFT JOIN dbo.bit_empresas e ON v.id_empresa = e.id_empresa
            LEFT JOIN dbo.bit_funcionarios f ON v.id_funcionario = f.id_funcionario
            INNER JOIN dbo.bit_destinos d ON v.id_destino = d.id_destino
            INNER JOIN dbo.bit_motivos m ON v.id_motivo = m.id_motivo
            LEFT JOIN dbo.bit_niveles_incidente ni ON v.id_nivel_incidente = ni.id_incidentes
            ORDER BY v.fecha_visita DESC, v.hora_entrada DESC
        ";

        return $this->fetchAll($sql);
    }

    /**
     * Detalle completo de una visita.
     */
    public function detalle(int $idVisita)
    {
        $detalleSelect = $this->tieneColumnaDetalleMotivo() ? 'v.detalle_motivo' : "CAST('' AS NVARCHAR(MAX))";

        $sql = "SELECT v.id_visita, v.tipo_visitante, v.fecha_visita, v.hora_entrada, v.hora_salida,
                p.nombres, p.apellidos, p.nidentificacion,
                e.empresa, e.ruc, ISNULL(f.nombre, N'Sin funcionario asignado') AS funcionario,
                d.nombre AS destino, m.descripcion AS motivo,
                {$detalleSelect} AS detalle_motivo, ni.descripcion AS nivel_desc
            FROM dbo.bit_visitas v
            INNER JOIN dbo.bit_personas p ON p.id_persona = v.id_persona
            LEFT JOIN dbo.bit_empresas e ON e.id_empresa = v.id_empresa
            LEFT JOIN dbo.bit_funcionarios f ON f.id_funcionario = v.id_funcionario
            INNER JOIN dbo.bit_destinos d ON d.id_destino = v.id_destino
            INNER JOIN dbo.bit_motivos m ON m.id_motivo = v.id_motivo
            LEFT JOIN dbo.bit_niveles_incidente ni ON ni.id_incidentes = v.id_nivel_incidente
            WHERE v.id_visita = ?";

        return $this->fetchOne($sql, [$idVisita]);
    }

    public function registrarSalida(int $idVisita): bool
    {
        $stmt = $this->query(
            "UPDATE dbo.bit_visitas SET hora_salida = CONVERT(time, GETDATE())
             WHERE id_visita = ? AND hora_salida IS NULL",
            [$idVisita]
        );
        return $stmt !== false;
    }

    public function actualizarHoras(int $idVisita, string $fechaVisita, string $horaEntrada, ?string $horaSalida): bool
    {
        $stmt = $this->query(
            "UPDATE dbo.bit_visitas SET fecha_visita = ?, hora_entrada = ?, hora_salida = ? WHERE id_visita = ?",
            [$fechaVisita, $horaEntrada, $horaSalida, $idVisita]
        );
        return $stmt !== false;
    }

    public function obtenerFechaHoraActual(int $idVisita)
    {
        return $this->fetchOne(
            "SELECT fecha_visita, hora_entrada FROM dbo.bit_visitas WHERE id_visita = ?",
            [$idVisita]
        );
    }

    public function actualizarCompleta(array $datos): bool
    {
        $detalleSelect = $this->tieneColumnaDetalleMotivo();

        if ($detalleSelect) {
            $sql = "UPDATE dbo.bit_visitas
                    SET id_empresa = ?, tipo_visitante = ?, id_funcionario = ?, id_destino = ?, id_motivo = ?, detalle_motivo = ?,
                        fecha_visita = ?, hora_entrada = ?, hora_salida = ?
                    WHERE id_visita = ?";
            $params = [
                $datos['id_empresa'], $datos['tipo_visitante'], $datos['id_funcionario'],
                $datos['id_destino'], $datos['id_motivo'], $datos['detalle_motivo'],
                $datos['fecha_visita'], $datos['hora_entrada'], $datos['hora_salida'],
                $datos['id_visita'],
            ];
        } else {
            $sql = "UPDATE dbo.bit_visitas
                    SET id_empresa = ?, tipo_visitante = ?, id_funcionario = ?, id_destino = ?, id_motivo = ?,
                        fecha_visita = ?, hora_entrada = ?, hora_salida = ?
                    WHERE id_visita = ?";
            $params = [
                $datos['id_empresa'], $datos['tipo_visitante'], $datos['id_funcionario'],
                $datos['id_destino'], $datos['id_motivo'],
                $datos['fecha_visita'], $datos['hora_entrada'], $datos['hora_salida'],
                $datos['id_visita'],
            ];
        }

        $stmt = $this->query($sql, $params);
        return $stmt !== false;
    }

    public function tieneVisitaActiva(int $idPersona): bool
    {
        $row = $this->fetchOne(
            "SELECT TOP 1 id_visita FROM dbo.bit_visitas WHERE id_persona = ? AND hora_salida IS NULL ORDER BY id_visita DESC",
            [$idPersona]
        );
        return $row !== false;
    }
}