<?php
include './php/main.php';

$msg = "";  

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        // Guardar datos en sesión
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['tipo'] = $usuario['tipo'];

        // Redirigir según tipo
        switch ($usuario['tipo']) {
            case 'admin':
                header("Location: dashboard_admin.php");
                break;
            case 'profesor':
                header("Location: dashboard_profesor.php");
                break;
            case 'estudiante':
                header("Location: dashboard_estudiante.php");
                break;
        }
        exit;
    } else {
        $msg = "❌ Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema Educativo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Iniciar Sesión</h1>
    <?php if ($msg): ?>
        <p><?= $msg ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Usuario</label>
        <input type="text" name="username" required>
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <button type="submit">Ingresar</button>
    </form>
    <p><a href="registro_usuario.html">¿No tienes cuenta? Regístrate</a></p>
</div>
</body>
</html>
