<?php
// modules/Talento_Humano/controllers/NominasController.php

class NominasController extends Controller
{
    public function index()
    {
        $this->render('nominas/index', [], 'Nóminas - Talento Humano');
    }
}
