<?php
// modules/Talento_Humano/models/NominaModel.php

require_once ROOT_PATH . 'core/Model.php';

class NominaModel extends Model
{
    public function __construct()
    {
        $this->db = Database::getInstance('Talento_Humano')->getConnection();
    }
}
