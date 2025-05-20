<?php
// procesos/admin/curso_eliminar_proceso.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

// Verificar rol (solo admin puede eliminar cursos)
verificar_rol(['admin']);

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_curso_accion'] = "Error: Solicitud no válida para eliminar.";
    header("Location: ../../index.php?vista=admin/cursos_lista");
    exit();
}

// Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_curso_accion'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=admin/cursos_lista");
    exit();
}

// Recoger y Sanear el ID del curso a eliminar
$id_curso_eliminar = limpiar_cadena($_POST['id_curso_eliminar'] ?? '');

if (empty($id_curso_eliminar) || !validar_entero($id_curso_eliminar)) {
    $_SESSION['mensaje_curso_accion'] = "Error: ID de curso no válido para eliminar.";
    header("Location: ../../index.php?vista=admin/cursos_lista");
    exit();
}

$pdo = conexion();

try {
    // Antes de eliminar, considerar dependencias:
    // 1. Inscripciones: ¿Qué pasa con los estudiantes inscritos?
    //    Opción A: No permitir eliminar si hay inscripciones activas.
    //    Opción B: Eliminar las inscripciones (CASCADE si está configurado en la BD, o manualmente).
    //    Opción C: Marcar el curso como 'eliminado' o 'inactivo' en lugar de borrarlo físicamente.
    // Por ahora, implementaremos la Opción A (más segura para empezar).

    $stmt_check_inscripciones = $pdo->prepare("SELECT COUNT(id_estudiante) FROM inscripciones WHERE id_curso = :id_curso AND estado = 'activa'");
    $stmt_check_inscripciones->bindParam(':id_curso', $id_curso_eliminar, PDO::PARAM_INT);
    $stmt_check_inscripciones->execute();
    $num_inscripciones_activas = (int)$stmt_check_inscripciones->fetchColumn();

    if ($num_inscripciones_activas > 0) {
        $_SESSION['mensaje_curso_accion'] = "Error: No se puede eliminar el curso (ID: $id_curso_eliminar) porque tiene $num_inscripciones_activas estudiante(s) inscrito(s) activamente. Primero debe gestionar estas inscripciones.";
        header("Location: ../../index.php?vista=admin/cursos_lista");
        exit();
    }

    // Considerar otras dependencias: horarios, materiales, anuncios.
    // Por simplicidad, primero eliminaremos estas dependencias si existen.
    // O, si tienes ON DELETE CASCADE en tus claves foráneas, la BD podría manejarlas.
    // Es más seguro hacerlo explícitamente aquí.

    $pdo->beginTransaction();

    // Eliminar de tablas dependientes (horarios, materiales, anuncios, inscripciones no activas)
    $stmt_del_horarios = $pdo->prepare("DELETE FROM horarios WHERE id_curso = :id_curso");
    $stmt_del_horarios->execute([':id_curso' => $id_curso_eliminar]);

    $stmt_del_materiales = $pdo->prepare("DELETE FROM materiales WHERE id_curso = :id_curso");
    $stmt_del_materiales->execute([':id_curso' => $id_curso_eliminar]);

    $stmt_del_anuncios = $pdo->prepare("DELETE FROM anuncios WHERE id_curso = :id_curso");
    $stmt_del_anuncios->execute([':id_curso' => $id_curso_eliminar]);
    
    // Eliminar todas las inscripciones (activas ya se verificó que no hay, pero por si acaso o para futuras lógicas)
    $stmt_del_inscripciones = $pdo->prepare("DELETE FROM inscripciones WHERE id_curso = :id_curso");
    $stmt_del_inscripciones->execute([':id_curso' => $id_curso_eliminar]);

    // Finalmente, eliminar el curso de la tabla 'cursos'
    $stmt_delete_curso = $pdo->prepare("DELETE FROM cursos WHERE id = :id_curso");
    $stmt_delete_curso->bindParam(':id_curso', $id_curso_eliminar, PDO::PARAM_INT);
    $stmt_delete_curso->execute();

    if ($stmt_delete_curso->rowCount() > 0) {
        $pdo->commit();
        $_SESSION['mensaje_curso_accion'] = "¡Curso (ID: $id_curso_eliminar) eliminado exitosamente!";
    } else {
        $pdo->rollBack(); // Aunque si no hay inscripciones, debería poderse eliminar si existe.
        $_SESSION['mensaje_curso_accion'] = "Error: No se pudo eliminar el curso (ID: $id_curso_eliminar) o el curso no fue encontrado.";
    }

    unset($_SESSION['csrf_token']);

    header("Location: ../../index.php?vista=admin/cursos_lista");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al eliminar curso: " . $e->getMessage() . " - Curso ID: " . $id_curso_eliminar);
    $_SESSION['mensaje_curso_accion'] = "Ocurrió un error al eliminar el curso. Verifique las dependencias.";
    header("Location: ../../index.php?vista=admin/cursos_lista");
    exit();
} finally {
    $pdo = null;
}
?>
