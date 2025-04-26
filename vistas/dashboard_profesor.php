<?php
include 'db.php';
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] != 'profesor') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Profesor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Bienvenido, <?= $_SESSION['username'] ?> (Profesor)</h1>
    <p>Este es tu panel de Profesor.</p>
    <a href="logout.php">Cerrar sesión</a>
</div>
</body>
</html>
