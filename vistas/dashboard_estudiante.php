<?php
include 'db.php';
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] != 'estudiante') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Estudiante</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Bienvenido, <?= $_SESSION['username'] ?> (Estudiante)</h1>
    <p>Este es tu panel de Estudiante.</p>
    <a href="logout.php">Cerrar sesión</a>
</div>
</body>
</html>
