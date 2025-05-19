<?php
unset($_SESSION['mensaje_login']);
// procesos/usuarios/login.php

// 1. Iniciar la sesión (debe ser lo primero)
require_once __DIR__ . "/../../inc/session_start.php";

// 2. Incluir funciones principales
require_once __DIR__ . "/../../php/main.php";

// 3. Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_login'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=login");
    exit();
}

// 4. Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_login'] = "Error: Token de seguridad inválido. Por favor, intente de nuevo.";
    header("Location: ../../index.php?vista=login");
    exit();
}

// 5. Recoger y Sanear los Datos del Formulario
$username_login = limpiar_cadena($_POST['login_usuario'] ?? '');
$clave_login = $_POST['login_clave'] ?? ''; // La clave no se sanea con htmlspecialchars

// echo "DEBUG: Usuario intentando loguear: " . htmlspecialchars($username_login) . "<br>"; // DEBUG LINE

// 6. Validaciones del Lado del Servidor
$errores_login = [];

if (empty($username_login)) {
    $errores_login[] = "El nombre de usuario es obligatorio.";
}
// ...
if (password_verify($clave_login, $usuario_encontrado['password_hash'])) {
    // echo "DEBUG: Contraseña VERIFICADA.<br>"; // DEBUG LINE
    // Autenticación exitosa

    // Limpiar cualquier mensaje de login anterior
    unset($_SESSION['mensaje_login']); // <-- AÑADE ESTA LÍNEA

    $_SESSION['loggedin'] = true;
    $_SESSION['id_usuario'] = $usuario_encontrado['id'];
}
if (!empty($errores_login)) {
    $_SESSION['mensaje_login'] = implode("<br>", $errores_login);
    $_SESSION['form_data_login']['login_usuario'] = $username_login;
    header("Location: ../../index.php?vista=login");
    exit();
}

// 7. Conectar a la Base de Datos
$pdo = conexion();

try {
    // 8. Consultar el usuario en la tabla `usuarios`
    $stmt = $pdo->prepare("SELECT id, username, password_hash, tipo, email, activo FROM usuarios WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username_login);
    $stmt->execute();
    $usuario_encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_encontrado) {
        // echo "DEBUG: Usuario encontrado en BD: <pre>" . htmlspecialchars(print_r($usuario_encontrado, true)) . "</pre><br>"; // DEBUG LINE
        
        if ($usuario_encontrado['activo'] != 1) {
            // echo "DEBUG: Usuario inactivo.<br>"; // DEBUG LINE
            $_SESSION['mensaje_login'] = "Su cuenta de usuario está inactiva. Por favor, contacte al administrador.";
            $_SESSION['form_data_login']['login_usuario'] = $username_login;
            header("Location: ../../index.php?vista=login");
            exit();
        }

        // echo "DEBUG: Verificando contraseña...<br>"; // DEBUG LINE
        if (password_verify($clave_login, $usuario_encontrado['password_hash'])) {
            // echo "DEBUG: Contraseña VERIFICADA.<br>"; // DEBUG LINE
            // Autenticación exitosa

            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $usuario_encontrado['id']; 
            $_SESSION['username'] = $usuario_encontrado['username'];
            $_SESSION['tipo_usuario'] = $usuario_encontrado['tipo']; 
            $_SESSION['email_usuario'] = $usuario_encontrado['email']; 
            $_SESSION['last_activity'] = time(); 
            
            session_regenerate_id(true);
            $_SESSION['session_created_time'] = time(); 

            unset($_SESSION['csrf_token']);
            unset($_SESSION['form_data_login']); 

            $stmt_update_login = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id_usuario");
            $stmt_update_login->bindParam(':id_usuario', $_SESSION['id_usuario']);
            $stmt_update_login->execute();

            // echo "DEBUG: Sesión establecida. Redirigiendo a home...<br>"; // DEBUG LINE
            // echo "<pre>DEBUG: Contenido de SESSION: " . htmlspecialchars(print_r($_SESSION, true)) . "</pre>"; // DEBUG LINE

            header("Location: ../../index.php?vista=home");
            exit();

        } else {
            // echo "DEBUG: Contraseña INCORRECTA.<br>"; // DEBUG LINE
            $_SESSION['mensaje_login'] = "Nombre de usuario o contraseña incorrectos (debug: pass mismatch).";
            $_SESSION['form_data_login']['login_usuario'] = $username_login;
            header("Location: ../../index.php?vista=login");
            exit();
        }
    } else {
        // echo "DEBUG: Usuario NO encontrado en BD.<br>"; // DEBUG LINE
        $_SESSION['mensaje_login'] = "Nombre de usuario o contraseña incorrectos (debug: user not found).";
        $_SESSION['form_data_login']['login_usuario'] = $username_login;
        header("Location: ../../index.php?vista=login");
        exit();
    }

} catch (PDOException $e) {
    error_log("Error en login de usuario: " . $e->getMessage() . " - Usuario: " . $username_login);
    $_SESSION['mensaje_login'] = "Ocurrió un error durante el inicio de sesión. Por favor, inténtelo más tarde.";
    $_SESSION['form_data_login']['login_usuario'] = $username_login;
    header("Location: ../../index.php?vista=login");
    exit();
} finally {
    $pdo = null; 
}
?>
