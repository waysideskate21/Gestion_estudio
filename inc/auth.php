<?php

require_once "session_start.php";
require_once "main.php";

// Redirige usuarios no autenticados
if (!isset($_SESSION['loggedin']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: index.php?vista=login");
    exit();
}

// Protección contra CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}