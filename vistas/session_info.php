<?php
require_once "../inc/auth.php";
verificar_rol(['admin', 'profesor', 'estudiante']); // Solo usuarios logueados
?>

<div class="container mt-5">
    <div class="box">
        <h1 class="title is-4">Información de Sesión</h1>
        
        <div class="content">
            <ul>
                <li><strong>ID de Usuario:</strong> <?= $_SESSION['id'] ?></li>
                <li><strong>Nombre de Usuario:</strong> <?= htmlspecialchars($_SESSION['username']) ?></li>
                <li><strong>Rol:</strong> <?= $_SESSION['rol'] ?></li>
                <li><strong>Última Actividad:</strong> 
                    <?= date('d/m/Y H:i:s', $_SESSION['last_activity'] ?? time()) ?>
                </li>
                <li><strong>Estado:</strong> 
                    <span class="tag is-success">ACTIVA</span>
                </li>
            </ul>
        </div>

        <div class="buttons">
            <a href="index.php?vista=home" class="button is-info">Volver al Inicio</a>
            <a href="procesos/usuarios/logout.php" class="button is-danger">Cerrar Sesión</a>
        </div>
    </div>
</div>