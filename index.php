<?php 
// index.php

// 1. Iniciar la sesión (debe ser lo primero para que $_SESSION esté disponible)
require "./inc/session_start.php"; 

// 2. Manejar autenticación, seguridad de sesión y cargar funciones principales
// auth.php ya incluye main.php, así que no es necesario requerir main.php aquí de nuevo.
require "./inc/auth.php"; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    // 3. Incluir la cabecera HTML (metatags, CSS, título)
    include "./inc/head.php";
    ?>
</head>
<body>
    <?php
    // 4. Determinar la vista actual para decidir si mostrar el navbar
    // (auth.php ya define $current_vista y $paginas_publicas)
    // $current_vista fue definida en auth.php, si no, la definimos aquí como fallback.
    if (!isset($current_vista)) {
        $current_vista = $_GET['vista'] ?? '';
    }
    if (!isset($paginas_publicas)) { // Fallback si auth.php no las definió (aunque debería)
        $paginas_publicas = ['login', 'registrar_usuario'];
    }

    // Mostrar el navbar solo si la vista actual no es una de las páginas públicas
    // que usualmente no tienen el navbar principal (como login o registro).
    if (!in_array($current_vista, $paginas_publicas) || 
        (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) ) { // O si está logueado, siempre mostrar navbar
        
        // Excepción: si está logueado pero la vista es login/registro (ej. por URL directa), no mostrar navbar.
        if (!( (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) && in_array($current_vista, $paginas_publicas) )) {
             include "./inc/navbar.php";
        }
    }
    ?>

        <?php
        // 5. Lógica de Enrutamiento de Vistas con Lista Blanca

        // Definir la vista solicitada (con un valor por defecto)
        // Esta lógica es similar a la de auth.php pero adaptada para el enrutador principal.
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            // Si no está logueado, la vista por defecto es 'login'
            // a menos que explícitamente se pida 'registrar_usuario'
            $vista_a_cargar = $_GET['vista'] ?? 'login';
            if ($vista_a_cargar !== 'registrar_usuario' && $vista_a_cargar !== 'login') {
                $vista_a_cargar = 'login';
            }
        } else {
            // Si está logueado, la vista por defecto es 'home'
            $vista_a_cargar = $_GET['vista'] ?? 'home';
        }

        // Definir la LISTA BLANCA de vistas permitidas
        $vistas_permitidas = [
            'login',
            'registrar_usuario',
            'home',
            '404',
            'session_info',
            'perfil_usuario', 
            // Vistas de Admin
            'admin_panel', 
            'admin_usuarios',
            'admin_cursos',
            'admin_programas',
            'admin_configuracion',
            // Vistas de Profesor
            'profesor_panel', 
            'profesor_ver_cursos',
            'profesor_calificaciones',
            'profesor_asistencia',
            // Vistas de Estudiante
            'estudiante_panel', 
            'estudiante_mis_cursos',
            'estudiante_ver_calificaciones',
            'estudiante_inscribir_curso',
            // Vista para crear cursos (accesible por admin y profesor)
            'curso_crear_formulario',
            'asignatura_crear_formulario',
            'p_visualizar_cursos',
            'perfil_usuario'
            // ... otras vistas que vayas creando ...
            // 'asignatura_crear_formulario', 
        ];

        // Verificar si la vista solicitada está en la lista blanca y si el archivo existe
        if (in_array($vista_a_cargar, $vistas_permitidas) && is_file("./vistas/" . $vista_a_cargar . ".php")) {
            include "./vistas/" . $vista_a_cargar . ".php";
        } else {
            // Si no está en la lista o el archivo no existe, mostrar 404
            // Puedes también verificar si la vista es vacía y redirigir a login/home según el estado de sesión
            // pero la lógica anterior de $vista_a_cargar ya maneja un default.
            include "./vistas/404.php";
        }
        ?>
    </div>

    <?php
    // 6. Incluir scripts JS globales al final del body
    include "./inc/script.php"; 
    // Si tienes ajax.js u otros scripts principales, inclúyelos aquí también:
    // echo '<script src="./js/ajax.js"></script>';
    ?>
</body>
</html>
