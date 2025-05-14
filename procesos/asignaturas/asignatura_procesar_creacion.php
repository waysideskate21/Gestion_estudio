<?php
// procesos/asignaturas/asignatura_procesar_creacion.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

// Verificar rol (solo admin puede crear asignaturas)
verificar_rol(['admin']);

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_error_asignatura_crear'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=asignatura_crear_formulario");
    exit();
}

// Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_error_asignatura_crear'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=asignatura_crear_formulario");
    exit();
}

// Recoger y Sanear los Datos del Formulario
$codigo_asignatura = limpiar_cadena($_POST['codigo_asignatura'] ?? '');
$nombre_asignatura = limpiar_cadena($_POST['nombre_asignatura'] ?? '');
$descripcion_asignatura = limpiar_cadena($_POST['descripcion_asignatura'] ?? null); // Opcional
$creditos_asignatura = limpiar_cadena($_POST['creditos_asignatura'] ?? '');
$id_programa = limpiar_cadena($_POST['id_programa'] ?? null); // Opcional
$semestre_recomendado = limpiar_cadena($_POST['semestre_recomendado'] ?? null); // Opcional

// Convertir opcionales vacíos a NULL para la BD
$id_programa = empty($id_programa) ? null : $id_programa;
$semestre_recomendado = empty($semestre_recomendado) ? null : $semestre_recomendado;
$descripcion_asignatura = empty($descripcion_asignatura) ? null : $descripcion_asignatura;


// Validaciones del Lado del Servidor
$errores = [];

if (empty($codigo_asignatura) || verificar_datos("^[a-zA-Z0-9\-]{3,20}$", $codigo_asignatura)) {
    $errores[] = "El código de asignatura es obligatorio (3-20 caracteres, letras, números, guion).";
}
if (empty($nombre_asignatura) || verificar_datos("^[a-zA-Z0-9\sÁÉÍÓÚáéíóúÑñ.,\-()]{3,100}$", $nombre_asignatura)) {
    $errores[] = "El nombre de la asignatura es obligatorio (3-100 caracteres).";
}
if (empty($creditos_asignatura) || !validar_entero($creditos_asignatura, ['options' => ['min_range' => 1, 'max_range' => 10]])) {
    $errores[] = "Los créditos son obligatorios y deben ser un número entre 1 y 10.";
}
if ($id_programa !== null && !validar_entero($id_programa)) { // Si se proporciona, debe ser un entero
    $errores[] = "El programa académico seleccionado no es válido.";
}
if ($semestre_recomendado !== null && !validar_entero($semestre_recomendado, ['options' => ['min_range' => 1, 'max_range' => 15]])) { // Si se proporciona
    $errores[] = "El semestre recomendado debe ser un número entre 1 y 15.";
}
if (strlen($descripcion_asignatura ?? '') > 1000) { // Límite de ejemplo para descripción
    $errores[] = "La descripción excede los 1000 caracteres.";
}


if (!empty($errores)) {
    $_SESSION['mensaje_error_asignatura_crear'] = implode("<br>", $errores);
    $_SESSION['form_data_asignatura_crear'] = $_POST; 
    header("Location: ../../index.php?vista=asignatura_crear_formulario");
    exit();
}

// Conectar a la Base de Datos
$pdo = conexion();

try {
    // Verificar si el código de asignatura ya existe (debe ser UNIQUE)
    $stmt_check_codigo = $pdo->prepare("SELECT id FROM asignaturas WHERE codigo = :codigo");
    $stmt_check_codigo->execute([':codigo' => $codigo_asignatura]);
    if ($stmt_check_codigo->fetch()) {
        $_SESSION['mensaje_error_asignatura_crear'] = "El código de asignatura ingresado ya existe.";
        $_SESSION['form_data_asignatura_crear'] = $_POST;
        header("Location: ../../index.php?vista=asignatura_crear_formulario");
        exit();
    }

    // Verificar si el id_programa existe (si se proporcionó)
    if ($id_programa !== null) {
        $stmt_check_prog = $pdo->prepare("SELECT id FROM programas WHERE id = :id_prog");
        $stmt_check_prog->execute([':id_prog' => $id_programa]);
        if ($stmt_check_prog->rowCount() == 0) {
            $_SESSION['mensaje_error_asignatura_crear'] = "El programa académico seleccionado no existe.";
            $_SESSION['form_data_asignatura_crear'] = $_POST;
            header("Location: ../../index.php?vista=asignatura_crear_formulario");
            exit();
        }
    }

    // Insertar en la tabla `asignaturas`
    $stmt_insert = $pdo->prepare("INSERT INTO asignaturas 
        (codigo, nombre, descripcion, creditos, id_programa, semestre_recomendado) 
        VALUES (:codigo, :nombre, :descripcion, :creditos, :id_programa, :semestre_recomendado)");
    
    $stmt_insert->bindParam(':codigo', $codigo_asignatura);
    $stmt_insert->bindParam(':nombre', $nombre_asignatura);
    $stmt_insert->bindParam(':descripcion', $descripcion_asignatura); // Puede ser null, PDO::PARAM_STR por defecto si no es null
    $stmt_insert->bindParam(':creditos', $creditos_asignatura, PDO::PARAM_INT);
    
    // CORRECCIÓN PARA PDO::PARAM_INT_OR_NULL
    if ($id_programa === null) {
        $stmt_insert->bindParam(':id_programa', $id_programa, PDO::PARAM_NULL);
    } else {
        $stmt_insert->bindParam(':id_programa', $id_programa, PDO::PARAM_INT);
    }

    if ($semestre_recomendado === null) {
        $stmt_insert->bindParam(':semestre_recomendado', $semestre_recomendado, PDO::PARAM_NULL);
    } else {
        $stmt_insert->bindParam(':semestre_recomendado', $semestre_recomendado, PDO::PARAM_INT);
    }
    
    $stmt_insert->execute();

    unset($_SESSION['form_data_asignatura_crear']);
    unset($_SESSION['csrf_token']); 

    $_SESSION['mensaje_exito_asignatura_crear'] = "¡Asignatura creada exitosamente!";
    header("Location: ../../index.php?vista=asignatura_crear_formulario"); // O redirigir a una lista de asignaturas
    exit();

} catch (PDOException $e) {
    error_log("Error en creación de asignatura: " . $e->getMessage() . " - Datos: " . json_encode($_POST));
    $_SESSION['mensaje_error_asignatura_crear'] = "Ocurrió un error al crear la asignatura. Por favor, inténtelo más tarde.";
    $_SESSION['form_data_asignatura_crear'] = $_POST;
    header("Location: ../../index.php?vista=asignatura_crear_formulario");
    exit();
} finally {
    $pdo = null;
}
?>
