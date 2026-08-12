<?php
// core/Model.php – Clase base; provee $this->db usando el nuevo Conexión::conectar()

class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    protected function auditarLectura(string $modulo, string $recurso): void
    {
        try {
            $stmt = $this->db->prepare('EXEC dbo.sp_th_auditar_lectura :usuario,:modulo,:recurso,:ip');
            $stmt->execute([
                ':usuario' => Auth::username(),
                ':modulo' => substr($modulo, 0, 50),
                ':recurso' => substr($recurso, 0, 200),
                ':ip' => Auth::clientIp(),
            ]);
            while ($stmt->nextRowset()) {}
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e, 'Core', false);
        }
    }
}
