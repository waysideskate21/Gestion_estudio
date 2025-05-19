<?php
// vistas/home.php

// Asegurarse de que el usuario esté autenticado para acceder a esta página
verificar_auth(); // Esta función ya debería estar disponible

// Usar directamente el username de la sesión para el saludo
$nombre_para_saludo = $_SESSION['username'] ?? 'Usuario'; // Valor por defecto si no está seteado

?>

<div class="container is-fluid mt-6">
    <section class="section">
        <div class="container has-text-centered">
            <figure class="image";>
                <img class="is-rounded" style="height: auto; width: 300px; display: inline-block" src="./Assets/img/foto_fondo.png" alt="Logo o imagen de bienvenida" style="max-height: 100px;"/>
            </figure>
            
            <h1 class="title is-3">
                ¡Hola, <?= htmlspecialchars(ucfirst($nombre_para_saludo)); ?>!
            </h1>
            <h2 class="subtitle is-5">
                Bienvenido(a) al sistema de Gestion Universitario SAS.
            </h2>
            


        </div>
    </section>

</div>
