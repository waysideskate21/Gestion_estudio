<?php
// index.php (Controlador Frontal Principal - Reestructurado)

// 1. Iniciar la sesión (debe ser lo primero para que $_SESSION esté disponible)
require "./inc/session_start.php";

// 2. Manejar autenticación, seguridad de sesión y cargar funciones principales
// auth.php ya incluye main.php
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
    // $current_vista_path se define en auth.php (ej: 'auth/login'), o aquí como fallback.
    if (!isset($current_vista_path)) { // $current_vista_path es la variable que usa auth.php
        $current_vista_path = $_GET['vista'] ?? '';
    }

    $mostrar_navbar = false;
    // Vistas públicas que NO muestran el navbar principal cuando el usuario no está logueado
    $paginas_publicas_sin_navbar = ['auth/login'];

    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        // Si está logueado, mostrar navbar, excepto si está intentando acceder a login/registro
        // (aunque auth.php debería redirigir en esos casos)
        if ($current_vista_path !== 'auth/login' && $current_vista_path !== 'auth/registrar_usuario') {
            $mostrar_navbar = true;
        }
    } else {
        // Si NO está logueado, mostrar navbar EXCEPTO en las páginas definidas en $paginas_publicas_sin_navbar
        if (!in_array($current_vista_path, $paginas_publicas_sin_navbar)) {
            $mostrar_navbar = true;
        }
    }
    
    // Caso especial para la URL raíz (sin ?vista=...)
    if (empty($current_vista_path) && !(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true)) {
        // Si es la raíz Y no está logueado, va a 'auth/login', no mostrar navbar.
        $mostrar_navbar = false;
    } elseif (empty($current_vista_path) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        // Si es la raíz Y está logueado, va a 'shared/home', sí mostrar navbar.
        $mostrar_navbar = true;
    }

    if ($mostrar_navbar) {
        include "./inc/navbar.php";
    }
    ?>

    <div class="main-container pt-5 pb-5">
        <?php
        // 5. Lógica de Enrutamiento de Vistas con Lista Blanca

        // Definir la vista a cargar por defecto según el estado de sesión
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            $vista_a_cargar = $_GET['vista'] ?? 'auth/login'; // Default a login si no está logueado
            // Asegurar que solo pueda acceder a login o registro
            if ($vista_a_cargar !== 'auth/registrar_usuario' && $vista_a_cargar !== 'auth/login') {
                $vista_a_cargar = 'auth/login';
            }
        } else {
            $vista_a_cargar = $_GET['vista'] ?? 'shared/home'; // Default a home si está logueado
        }

        // LISTA BLANCA de vistas permitidas (¡DEBES ACTUALIZAR ESTO CON TUS RUTAS EXACTAS!)
        $vistas_permitidas = [
            // Auth
            'auth/login',
            'auth/registrar_usuario',
            // Shared (comunes a usuarios logueados)
            'shared/home',
            'shared/perfil_usuario',
            'shared/session_info',
            // Admin
            'admin/panel_admin',
            'admin/usuarios_lista',
            'admin/crear_asignatura', // Asumo que es el formulario (antes asignatura_crear_formulario)
            'admin/lista_asignaturas',
            'admin/crear_programa',   // Asumo que es el formulario
            'admin/lista_programas',
            'admin/lista_curso',      // Asumo que es cursos_lista_admin
            'admin/configuracion_sistema',
            'admin/crear_usuario',    // Formulario para admin crear usuarios
            // 'admin/curso_formulario_admin', // Si tienes uno específico para admin editar cursos

            // Profesor
            'profesor/panel_profesor',
            'profesor/lista_cursos',     // Asumo que es para ver sus cursos (antes profesor_ver_cursos)
            'profesor/crear_curso',      // Asumo que es el formulario (antes curso_crear_formulario)
            // 'profesor/gestion_notas',
            // 'profesor/gestion_asistencias',

            // Estudiante
            'estudiante/panel_estudiante',
            'estudiante/inscripcion_cursos', // Asumo que es para ver disponibles e inscribirse
            'estudiante/cursos_inscritos',
            // 'estudiante/calificaciones_estudiante',

            // Raíz de vistas (si alguno quedó ahí y es intencional)
            '404', // Asumiendo vistas/404.php
        ];

        // Construir la ruta completa al archivo de la vista
        $ruta_archivo_vista = "./vistas/" . $vista_a_cargar . ".php";

        if (in_array($vista_a_cargar, $vistas_permitidas) && is_file($ruta_archivo_vista)) {
            include $ruta_archivo_vista;
        } else {
            // Si la vista es vacía (URL raíz) y el usuario no está logueado, ya se redirigió a 'auth/login'
            // Si la vista es vacía y el usuario está logueado, ya se redirigió a 'shared/home'
            // Este 'else' se alcanza si $vista_a_cargar tiene un valor pero no es válido/no existe en la lista blanca.
            include "./vistas/404.php";
        }
        ?>
    </div>

    <?php
    // 6. Incluir scripts JS globales al final del body
    include "./inc/script.php"; 
    echo '<script src="./js/ajax.js"></script>'; // Asegúrate que esta línea esté activa y la ruta sea correcta
    ?>
</body>
</html>
