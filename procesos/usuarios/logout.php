<?php
// procesos/usuarios/logout.php

// Es crucial iniciar la sesión para poder destruirla.
// session_start.php debería manejar la configuración segura de la sesión.
require_once __DIR__ . '/../../inc/session_start.php'; // Ruta a session_start.php en la carpeta inc/

// Opcional: Incluir main.php si necesitas alguna función de ahí antes de destruir,
// pero para un logout simple, usualmente no es necesario.
// Si lo necesitaras, la ruta correcta sería:
// require_once __DIR__ . '/../../php/main.php'; // Ruta a main.php en la carpeta php/

// 1. Limpiar todas las variables de sesión.
$_SESSION = array();

// 2. Si se desea destruir la sesión completamente, borra también la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, no solo los datos de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruir la sesión.
session_destroy();

// 4. Redirigir a la página de login (o a la home si prefieres).
// Asegúrate de que index.php maneje la vista 'login'.
header("Location: ../../index.php?vista=login&status=logout_success");
exit();
?>
