<?php
// modules/Talento_Humano/models/CapacitacionModel.php

require_once ROOT_PATH . 'core/Model.php';

class CapacitacionModel extends Model
{
    public function __construct()
    {
        $this->db = Database::getInstance('Talento_Humano')->getConnection();
    }
}
