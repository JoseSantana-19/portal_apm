<?php
// Inicializa sesión de forma centralizada.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

