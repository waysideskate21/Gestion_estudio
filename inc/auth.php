<?php
// inc/auth.php

// Asegurarse de que session_start.php ya se haya incluido y la sesión esté activa.
// Si no, incluirlo. Esto es un fallback, idealmente index.php ya lo hizo.
if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__ . "/session_start.php"; // Asume que session_start.php está en el mismo directorio 'inc'
}

// Incluir el archivo de funciones principales
require_once __DIR__ . "/../php/main.php"; // main.php está en la carpeta php/

// --- Configuración de Seguridad de Sesión ---
define('SESSION_TIMEOUT', 1800); // 30 minutos de inactividad (1800 segundos)
define('SESSION_REGENERATE_TIME', 300); // Regenerar ID de sesión cada 5 minutos (300 segundos)

// --- Determinar la vista actual ---
// Si index.php es el único punto de entrada, $_GET['vista'] controlará la página.
$current_vista = $_GET['vista'] ?? ''; // Obtener la vista actual, default a vacío si no está seteada

// --- Definir páginas públicas que no requieren autenticación ---
$paginas_publicas = [
    'login',
    'registrar_usuario',
    // Puedes añadir otras vistas públicas aquí si es necesario (ej. 'recuperar_password')
];

// --- Protección CSRF ---
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Lógica de Autenticación y Seguridad de Sesión ---
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Usuario está logueado

    // 1. Manejo de Inactividad de Sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // Tiempo de inactividad excedido
        session_unset();     // Eliminar todas las variables de sesión
        session_destroy();   // Destruir la sesión
        
        // Es importante reiniciar session_start para poder establecer el mensaje
        if (session_status() == PHP_SESSION_NONE) {
            require_once __DIR__ . "/session_start.php";
        }
        $_SESSION['mensaje_login'] = "Su sesión ha expirado por inactividad.";
        header("Location: index.php?vista=login");
        exit();
    }
    $_SESSION['last_activity'] = time(); // Actualizar el tiempo de la última actividad

    // 2. Regeneración Periódica del ID de Sesión
    if (!isset($_SESSION['session_created_time'])) {
        $_SESSION['session_created_time'] = time();
    } elseif ((time() - $_SESSION['session_created_time']) > SESSION_REGENERATE_TIME) {
        session_regenerate_id(true); // Regenera el ID y elimina el antiguo
        $_SESSION['session_created_time'] = time(); // Reiniciar el contador de tiempo de creación de sesión
    }

} else {
    // Usuario NO está logueado

    // Redirigir a login si la página actual no es pública
    if (!in_array($current_vista, $paginas_publicas) && $current_vista !== "") {
        // Si la vista no es pública y no es la página de login por defecto (cuando $current_vista es vacío y se va a login)
        // Guardar la URL solicitada para redirigir después del login (opcional)
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
        
        header("Location: index.php?vista=login");
        exit();
    }
    // Si es una página pública o la vista es vacía (que index.php redirigirá a login), no hacer nada aquí.
}

?>
