<?php
// index.php (Controlador Frontal Principal - Reestructurado)

require "./inc/session_start.php"; 
require "./inc/auth.php"; 

if (!isset($solicitud_vista_actual)) { // $solicitud_vista_actual es definida en auth.php
    $solicitud_vista_actual = $_GET['vista'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include "./inc/head.php"; ?>
</head>
<body>
    <?php
    $mostrar_navbar = false;
    $paginas_publicas_sin_navbar = ['auth/login'];

    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        if ($solicitud_vista_actual !== 'auth/login' && $solicitud_vista_actual !== 'auth/registrar_usuario') {
            $mostrar_navbar = true;
        }
    } else {
        if (!in_array($solicitud_vista_actual, $paginas_publicas_sin_navbar)) {
            $mostrar_navbar = true;
        }
    }
    if (empty($solicitud_vista_actual) && !(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true)) {
        $mostrar_navbar = false;
    } elseif (empty($solicitud_vista_actual) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        $mostrar_navbar = true;
    }

    if ($mostrar_navbar) {
        include "./inc/navbar.php";
    }
    ?>

    <div class="main-container">
        <?php
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            $vista_a_incluir = $_GET['vista'] ?? 'auth/login';
            if ($vista_a_incluir !== 'auth/registrar_usuario' && $vista_a_incluir !== 'auth/login') {
                $vista_a_incluir = 'auth/login';
            }
        } else {
            $vista_a_incluir = $_GET['vista'] ?? 'shared/home';
        }

        // LISTA BLANCA de vistas permitidas (ACTUALIZADA SEGÚN TU ESTRUCTURA)
        $vistas_permitidas = [
            // Auth
            'auth/login',
            'auth/registrar_usuario',
            // Shared
            'shared/home',
            'shared/perfil_usuario',
            'shared/session_info',
            // Admin
            'admin/panel_admin',
            'admin/lista_usuarios',
            'admin/crear_asignatura',
            'admin/lista_asignaturas',
            'admin/crear_programa',
            'admin/lista_programas',
            'admin/cursos_lista', 
            'admin/configuracion_sistema',
            'admin/crear_usuario',
            // Profesor
            'profesor/panel_profesor',
            'profesor/lista_cursos',    
            'profesor/gestion_notas',
            'profesor/gestion_asistencias',
            // Estudiante
            'estudiante/panel_estudiante',
            'estudiante/inscripcion_cursos',
            'estudiante/cursos_inscritos',
            'estudiante/calificaciones_estudiante',
            // Módulo de Cursos (Formularios y vistas comunes)
            'cursos/formulario_curso', // <--- RUTA ACTUALIZADA AQUÍ
            // 'cursos/detalle_curso', 

            // Raíz de vistas
            '404', 
        ];

        $ruta_archivo_vista = "./vistas/" . $vista_a_incluir . ".php";

        if (in_array($vista_a_incluir, $vistas_permitidas) && is_file($ruta_archivo_vista)) {
            include $ruta_archivo_vista;
        } else {
            if (empty($vista_a_incluir) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                 include "./vistas/shared/home.php"; 
            } elseif (empty($vista_a_incluir)) {
                 include "./vistas/auth/login.php"; 
            } else {
                 include "./vistas/404.php"; 
            }
        }
        ?>
    </div>

    <?php
    include "./inc/script.php"; 
    echo '<script src="./js/ajax.js"></script>';
    ?>
</body>
</html>
