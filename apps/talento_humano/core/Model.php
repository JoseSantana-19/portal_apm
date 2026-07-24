<?php
// core/Model.php – Clase base; provee $this->db usando el nuevo Conexión::conectar()

class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }
}
