<?php
require_once "./php/main.php";  

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token'])) {
    // Validar CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "Token de seguridad inválido";
        header("Location: ../../index.php?vista=login");
        exit();
    }

    $usuario = limpiar_cadena($_POST['login_usuario']);
    $clave = $_POST['login_clave'];

    // Validar campos
    if (empty($usuario) || empty($clave)) {
        $_SESSION['mensaje'] = "Todos los campos son obligatorios";
        header("Location: ../../index.php?vista=login");
        exit();
    }

    // Consulta preparada
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
    $stmt->execute([$usuario]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($clave, $user['password_hash'])) {
            // Autenticación exitosa
            $_SESSION = [
                'loggedin' => true,
                'id' => $user['id'],
                'username' => $user['username'],
                'rol' => $user['tipo'],
                'last_activity' => time()
            ];

            // Registrar login en la base de datos (opcional)
            $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            header("Location: ../../index.php?vista=home");
            exit();
        }
    }

    // Autenticación fallida
    $_SESSION['mensaje'] = "Usuario o contraseña incorrectos";
    header("Location: ../../index.php?vista=login");
    exit();
}