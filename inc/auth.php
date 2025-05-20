<?php
// inc/auth.php

if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__ . "/session_start.php";
}
require_once __DIR__ . "/../php/main.php";

define('SESSION_TIMEOUT', 1800); 
define('SESSION_REGENERATE_TIME', 300);

// Obtener la ruta de la vista solicitada desde el parámetro GET
// Asegúrate que esta variable se llame igual que en index.php si la compartes.
$solicitud_vista_actual = $_GET['vista'] ?? ''; 

// Páginas públicas que NO requieren autenticación
$paginas_publicas = [
    'auth/login',
    'auth/registrar_usuario',
    // 'public/otra_pagina_publica' // Ejemplo
];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // --- USUARIO LOGUEADO ---

    // Manejo de Inactividad
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset(); session_destroy();
        if (session_status() == PHP_SESSION_NONE) { require_once __DIR__ . "/session_start.php"; }
        $_SESSION['mensaje_login'] = "Su sesión ha expirado por inactividad.";
        header("Location: index.php?vista=auth/login"); // Redirigir a la página de login correcta
        exit();
    }
    $_SESSION['last_activity'] = time();

    // Regeneración Periódica del ID de Sesión
    if (!isset($_SESSION['session_created_time'])) {
        $_SESSION['session_created_time'] = time();
    } elseif ((time() - $_SESSION['session_created_time']) > SESSION_REGENERATE_TIME) {
        session_regenerate_id(true);
        $_SESSION['session_created_time'] = time();
    }

    // Si un usuario logueado intenta acceder a una página pública (login/registro), redirigir a home.
    if (in_array($solicitud_vista_actual, $paginas_publicas)) {
        header("Location: index.php?vista=shared/home"); // Redirigir a la página home correcta
        exit();
    }

} else {
    // --- USUARIO NO LOGUEADO ---

    // Si la vista solicitada NO es pública Y NO es la raíz (que index.php manejará)
    // entonces redirigir a login.
    if (!in_array($solicitud_vista_actual, $paginas_publicas) && $solicitud_vista_actual !== "") {
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Opcional: guardar para redirigir después
        header("Location: index.php?vista=auth/login"); // Redirigir a la página de login correcta
        exit();
    }
    // Si es una página pública o la raíz, no hacer nada aquí, index.php decidirá.
}
?>
