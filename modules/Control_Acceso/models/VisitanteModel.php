<?php
class VisitanteModel extends Model {

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->query(
            "SELECT v.id_visitante,
                    v.nombres + ' ' + v.apellidos AS nombre,
                    v.cedula AS documento_identidad,
                    v.empresa, v.correo, v.telefono, v.fecha_creacion
             FROM ACCESO_Visitantes v
             ORDER BY v.apellidos, v.nombres
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [[$offset, SQLSRV_PARAM_IN], [$perPage, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array {
        $stmt = $this->query(
            "SELECT v.id_visitante,
                    v.nombres + ' ' + v.apellidos AS nombre,
                    v.cedula AS documento_identidad,
                    v.nombres, v.apellidos,
                    v.empresa, v.correo, v.telefono, v.foto, v.fecha_creacion
             FROM ACCESO_Visitantes v
             WHERE v.id_visitante = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function findByDocumento(string $doc): ?array {
        $stmt = $this->query(
            "SELECT v.id_visitante,
                    v.nombres + ' ' + v.apellidos AS nombre,
                    v.cedula AS documento_identidad,
                    v.nombres, v.apellidos, v.empresa, v.correo, v.telefono
             FROM ACCESO_Visitantes v
             WHERE v.cedula = ?",
            [[$doc, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function create(array $data): int {
        $stmt = $this->query(
            "INSERT INTO ACCESO_Visitantes
                (nombres, apellidos, cedula, empresa, correo, telefono)
             OUTPUT INSERTED.id_visitante
             VALUES (?,?,?,?,?,?)",
            [
                [trim($data['nombres'] ?? ''),          SQLSRV_PARAM_IN],
                [trim($data['apellidos'] ?? ''),        SQLSRV_PARAM_IN],
                [$data['cedula'],                       SQLSRV_PARAM_IN],
                [$data['empresa']  ?? null,             SQLSRV_PARAM_IN],
                [$data['correo']   ?? null,             SQLSRV_PARAM_IN],
                [$data['telefono'] ?? null,             SQLSRV_PARAM_IN],
            ]
        );
        $row = $this->fetch($stmt);
        return (int)($row['id_visitante'] ?? 0);
    }
}
