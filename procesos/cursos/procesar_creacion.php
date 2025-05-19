<?php
// procesos/cursos/procesar_creacion.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

// Verificar rol (admin o profesor pueden crear cursos)
verificar_rol(['admin', 'profesor']);

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_error_curso_crear'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=crear_curso");
    exit();
}

// Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_error_curso_crear'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=crear_curso");
    exit();
}

// Recoger y Sanear los Datos del Formulario
$id_asignatura = limpiar_cadena($_POST['id_asignatura'] ?? '');
$id_profesor = limpiar_cadena($_POST['id_profesor'] ?? ''); // Si es profesor, vendrá de $_SESSION['id_usuario']
$periodo_academico = limpiar_cadena($_POST['periodo_academico'] ?? '');
$cupo_maximo = limpiar_cadena($_POST['cupo_maximo'] ?? '');
$aula = limpiar_cadena($_POST['aula'] ?? null); // Opcional
$horario_descripcion = limpiar_cadena($_POST['horario_descripcion'] ?? null); // Opcional

// Si el que crea es un profesor, el id_profesor es su propio id de sesión
if ($_SESSION['tipo_usuario'] === 'profesor') {
    $id_profesor = $_SESSION['id_usuario'];
}

// Validaciones del Lado del Servidor
$errores = [];

if (empty($id_asignatura) || !validar_entero($id_asignatura)) {
    $errores[] = "Debe seleccionar una asignatura válida.";
}
if (empty($id_profesor) || !validar_entero($id_profesor)) {
    $errores[] = "Debe seleccionar un profesor válido.";
}
if (empty($periodo_academico) || verificar_datos("^[a-zA-Z0-9\- ]{4,20}$", $periodo_academico)) {
    $errores[] = "El periodo académico es obligatorio (4-20 caracteres, ej: 2025-1).";
}
if (empty($cupo_maximo) || !validar_entero($cupo_maximo, ['options' => ['min_range' => 1, 'max_range' => 500]])) {
    $errores[] = "El cupo máximo es obligatorio y debe ser un número entre 1 y 500.";
}
if (!empty($aula) && verificar_datos("^[a-zA-Z0-9\sÁÉÍÓÚáéíóúÑñ.,\-()#]{0,50}$", $aula)) {
    $errores[] = "El aula contiene caracteres no válidos o excede los 50 caracteres.";
}
if (!empty($horario_descripcion) && strlen($horario_descripcion) > 500) { // Ejemplo de límite
    $errores[] = "La descripción del horario excede los 500 caracteres.";
}


if (!empty($errores)) {
    $_SESSION['mensaje_error_curso_crear'] = implode("<br>", $errores);
    $_SESSION['form_data_curso_crear'] = $_POST; 
    header("Location: ../../index.php?vista=crear_curso");
    exit();
}

// Conectar a la Base de Datos
$pdo = conexion();

try {
    // Verificar que la asignatura y el profesor existan (importante si los IDs vienen del cliente)
    $stmt_check_asig = $pdo->prepare("SELECT id FROM asignaturas WHERE id = :id_asig");
    $stmt_check_asig->execute([':id_asig' => $id_asignatura]);
    if ($stmt_check_asig->rowCount() == 0) {
        $errores[] = "La asignatura seleccionada no es válida.";
    }

    $stmt_check_prof = $pdo->prepare("SELECT id FROM profesores WHERE id = :id_prof");
    $stmt_check_prof->execute([':id_prof' => $id_profesor]);
    if ($stmt_check_prof->rowCount() == 0) {
        $errores[] = "El profesor seleccionado no es válido.";
    }
    
    // Podrías añadir una verificación para no crear el mismo curso (misma asignatura, mismo profesor, mismo periodo)
    // $stmt_check_curso_exist = $pdo->prepare("SELECT id FROM cursos WHERE id_asignatura = :id_asig AND id_profesor = :id_prof AND periodo_academico = :periodo");
    // ... ejecutar y verificar ...

    if (!empty($errores)) {
        $_SESSION['mensaje_error_curso_crear'] = implode("<br>", $errores);
        $_SESSION['form_data_curso_crear'] = $_POST; 
        header("Location: ../../index.php?vista=crear_curso");
        exit();
    }


    // Insertar en la tabla `cursos`
    $stmt_insert_curso = $pdo->prepare("INSERT INTO cursos 
        (id_asignatura, id_profesor, periodo_academico, cupo_maximo, aula, horario) 
        VALUES (:id_asignatura, :id_profesor, :periodo_academico, :cupo_maximo, :aula, :horario_descripcion)");
    
    $stmt_insert_curso->bindParam(':id_asignatura', $id_asignatura, PDO::PARAM_INT);
    $stmt_insert_curso->bindParam(':id_profesor', $id_profesor, PDO::PARAM_INT);
    $stmt_insert_curso->bindParam(':periodo_academico', $periodo_academico);
    $stmt_insert_curso->bindParam(':cupo_maximo', $cupo_maximo, PDO::PARAM_INT);
    $stmt_insert_curso->bindParam(':aula', $aula); // PDO::PARAM_STR por defecto si es null o string
    $stmt_insert_curso->bindParam(':horario_descripcion', $horario_descripcion); // PDO::PARAM_STR por defecto
    
    $stmt_insert_curso->execute();

    unset($_SESSION['form_data_curso_crear']);
    unset($_SESSION['csrf_token']); // Invalidar el token usado

    $_SESSION['mensaje_exito_curso_crear'] = "¡Curso creado exitosamente!";
    header("Location: ../../index.php?vista=crear_curso"); // O redirigir a una lista de cursos
    exit();

} catch (PDOException $e) {
    error_log("Error en creación de curso: " . $e->getMessage() . " - Datos: " . json_encode($_POST));
    $_SESSION['mensaje_error_curso_crear'] = "Ocurrió un error al crear el curso. Por favor, inténtelo más tarde.";
    $_SESSION['form_data_curso_crear'] = $_POST;
    header("Location: ../../index.php?vista=crear_curso");
    exit();
} finally {
    $pdo = null;
}
?>
