<?php
// procesos/inscripciones/inscribir_estudiante.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

// Verificar rol (solo estudiante puede realizar esta acción)
verificar_rol(['estudiante']);

$id_estudiante_actual = $_SESSION['id_usuario'];

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_inscripcion'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=inscribir_curso");
    exit();
}

// Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_inscripcion'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=inscribir_curso");
    exit();
}

// Recoger y Sanear los Datos del Formulario
$id_curso_a_inscribir = limpiar_cadena($_POST['id_curso_a_inscribir'] ?? '');

// Validaciones del Lado del Servidor
if (empty($id_curso_a_inscribir) || !validar_entero($id_curso_a_inscribir)) {
    $_SESSION['mensaje_inscripcion'] = "Error: El curso seleccionado no es válido.";
    header("Location: ../../index.php?vista=inscribir_curso");
    exit();
}

// Conectar a la Base de Datos
$pdo = conexion();

try {
    // Iniciar transacción para asegurar atomicidad
    $pdo->beginTransaction();

    // 1. Verificar que el curso exista y obtener cupo_maximo y periodo_academico
    // Usamos FOR UPDATE para bloquear la fila del curso y evitar condiciones de carrera con los cupos
    $stmt_curso_info = $pdo->prepare("SELECT id, cupo_maximo, periodo_academico FROM cursos WHERE id = :id_curso FOR UPDATE");
    $stmt_curso_info->bindParam(':id_curso', $id_curso_a_inscribir, PDO::PARAM_INT);
    $stmt_curso_info->execute();
    $curso_info = $stmt_curso_info->fetch(PDO::FETCH_ASSOC);

    if (!$curso_info) {
        $pdo->rollBack();
        $_SESSION['mensaje_inscripcion'] = "Error: El curso seleccionado no existe.";
        header("Location: ../../index.php?vista=inscribir_curso");
        exit();
    }

    // 2. Verificar si el estudiante ya está inscrito activamente en este curso
    $stmt_check_inscripcion = $pdo->prepare("SELECT id_estudiante FROM inscripciones WHERE id_estudiante = :id_estudiante AND id_curso = :id_curso AND estado = 'activa'");
    $stmt_check_inscripcion->bindParam(':id_estudiante', $id_estudiante_actual, PDO::PARAM_INT);
    $stmt_check_inscripcion->bindParam(':id_curso', $id_curso_a_inscribir, PDO::PARAM_INT);
    $stmt_check_inscripcion->execute();

    if ($stmt_check_inscripcion->fetch()) {
        $pdo->rollBack();
        $_SESSION['mensaje_inscripcion'] = "Error: Ya se encuentra inscrito en este curso.";
        header("Location: ../../index.php?vista=inscribir_curso");
        exit();
    }

    // 3. Verificar cupos disponibles
    $stmt_cupos_actuales = $pdo->prepare("SELECT COUNT(*) AS inscritos FROM inscripciones WHERE id_curso = :id_curso AND estado = 'activa'");
    $stmt_cupos_actuales->bindParam(':id_curso', $id_curso_a_inscribir, PDO::PARAM_INT);
    $stmt_cupos_actuales->execute();
    $inscritos = $stmt_cupos_actuales->fetchColumn();

    if ($inscritos >= $curso_info['cupo_maximo']) {
        $pdo->rollBack();
        $_SESSION['mensaje_inscripcion'] = "Error: No hay cupos disponibles para este curso.";
        header("Location: ../../index.php?vista=inscribir_curso");
        exit();
    }
    
    // (Opcional) Podrías añadir más validaciones aquí:
    // - Que el estudiante no exceda un límite de créditos por periodo.
    // - Que el curso pertenezca al periodo académico actual (aunque la vista ya debería filtrarlo).

    // 4. Si todo está bien, realizar la inscripción
    $stmt_insert_inscripcion = $pdo->prepare("INSERT INTO inscripciones (id_estudiante, id_curso, fecha_inscripcion, estado) VALUES (:id_estudiante, :id_curso, NOW(), 'activa')");
    $stmt_insert_inscripcion->bindParam(':id_estudiante', $id_estudiante_actual, PDO::PARAM_INT);
    $stmt_insert_inscripcion->bindParam(':id_curso', $id_curso_a_inscribir, PDO::PARAM_INT);
    $stmt_insert_inscripcion->execute();

    // Confirmar la transacción
    $pdo->commit();

    unset($_SESSION['csrf_token']); // Invalidar el token usado

    // Notificación (simple por ahora, podrías expandir esto)
    // $nombre_curso_inscrito = ... (obtener nombre del curso para el mensaje)
    $_SESSION['mensaje_inscripcion'] = "¡Inscripción exitosa en el curso!";
    header("Location: ../../index.php?vista=inscribir_curso"); // O a "mis cursos inscritos"
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Revertir si hay error
    }
    error_log("Error en inscripción de estudiante: " . $e->getMessage() . " - Estudiante ID: " . $id_estudiante_actual . ", Curso ID: " . $id_curso_a_inscribir);
    $_SESSION['mensaje_inscripcion'] = "Ocurrió un error durante el proceso de inscripción. Por favor, inténtelo más tarde.";
    header("Location: ../../index.php?vista=inscribir_curso");
    exit();
} finally {
    $pdo = null;
}
?>
