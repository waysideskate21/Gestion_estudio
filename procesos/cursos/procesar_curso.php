<?php
// procesos/cursos/procesar_curso.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

// Verificar rol (admin o profesor)
verificar_rol(['admin', 'profesor']);

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_error_curso_crear'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=cursos/formulario_curso");
    exit();
}

// Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_error_curso_crear'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=cursos/formulario_curso");
    exit();
}

// Determinar si es modo edición o creación
$modo_edicion = false;
$id_curso_a_editar = null;
if (isset($_POST['id_curso_editar']) && !empty($_POST['id_curso_editar'])) {
    $id_curso_a_editar = limpiar_cadena($_POST['id_curso_editar']);
    if (validar_entero($id_curso_a_editar)) {
        $modo_edicion = true;
    } else {
        $_SESSION['mensaje_error_curso_crear'] = "Error: ID de curso para editar no válido.";
        header("Location: ../../index.php?vista=cursos/formulario_curso");
        exit();
    }
}

// Recoger y Sanear los Datos del Formulario
$id_asignatura = limpiar_cadena($_POST['id_asignatura'] ?? '');
$id_profesor_formulario = limpiar_cadena($_POST['id_profesor'] ?? ''); // Profesor seleccionado en el form (si es admin)
$periodo_academico = limpiar_cadena($_POST['periodo_academico'] ?? '');
$cupo_maximo = limpiar_cadena($_POST['cupo_maximo'] ?? '');
$aula = limpiar_cadena($_POST['aula'] ?? null); 
$horario_descripcion = limpiar_cadena($_POST['horario_descripcion'] ?? null);

// Determinar el ID del profesor final a asignar/mantener
$id_profesor_final = null;
if ($_SESSION['tipo_usuario'] === 'admin') {
    $id_profesor_final = $id_profesor_formulario; // Admin puede cambiar/asignar profesor
} elseif ($_SESSION['tipo_usuario'] === 'profesor') {
    if ($modo_edicion) {
        // Si un profesor edita, solo puede editar SUS cursos, no puede cambiar el profesor.
        // Se tomará el id_profesor del curso existente (que ya se validó en el form que es él mismo)
        // o su propio ID si por alguna razón el campo oculto no viniera (aunque debería).
        // Esta validación de permiso para editar ya se hizo en el formulario.
        // Aquí solo nos aseguramos de que el ID del profesor no cambie si un profesor edita.
        // Para estar seguros, podríamos volver a cargar el id_profesor del curso desde la BD si es modo edición y rol profesor.
        $pdo_temp = conexion();
        $stmt_temp_prof = $pdo_temp->prepare("SELECT id_profesor FROM cursos WHERE id = :id_curso_edit");
        $stmt_temp_prof->execute([':id_curso_edit' => $id_curso_a_editar]);
        $curso_existente_prof_id = $stmt_temp_prof->fetchColumn();
        if ($curso_existente_prof_id == $_SESSION['id_usuario']) {
            $id_profesor_final = $_SESSION['id_usuario'];
        } else {
            // Esto no debería pasar si la validación del formulario fue correcta.
            $_SESSION['mensaje_error_curso_crear'] = "Error de permisos al intentar actualizar el profesor del curso.";
            header("Location: ../../index.php?vista=cursos/formulario_curso" . ($modo_edicion ? "&id_curso=" . $id_curso_a_editar : ""));
            exit();
        }
        $pdo_temp = null;

    } else { // Creación por profesor
        $id_profesor_final = $_SESSION['id_usuario'];
    }
}


// Validaciones del Lado del Servidor
$errores = [];

if (empty($id_asignatura) || !validar_entero($id_asignatura)) {
    $errores[] = "Debe seleccionar una asignatura válida.";
}
if (empty($id_profesor_final) || !validar_entero($id_profesor_final)) {
    // Este error es más probable si un admin no selecciona un profesor.
    $errores[] = "Debe asignar un profesor válido al curso.";
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
if (!empty($horario_descripcion) && strlen($horario_descripcion) > 500) { 
    $errores[] = "La descripción del horario excede los 500 caracteres.";
}

// Si hay errores, redirigir de vuelta al formulario
if (!empty($errores)) {
    $_SESSION['mensaje_error_curso_crear'] = implode("<br>", $errores);
    $_SESSION['form_data_curso_crear'] = $_POST; // Guardar los datos para rellenar
    // Si estábamos editando, añadir el id_curso a la URL de redirección
    $redirect_url = "../../index.php?vista=cursos/formulario_curso";
    if ($modo_edicion && $id_curso_a_editar) {
        $redirect_url .= "&id_curso=" . $id_curso_a_editar;
    }
    header("Location: " . $redirect_url);
    exit();
}

$pdo = conexion();

try {
    // Verificar que la asignatura y el profesor (final) existan
    $stmt_check_asig = $pdo->prepare("SELECT id FROM asignaturas WHERE id = :id_asig");
    $stmt_check_asig->execute([':id_asig' => $id_asignatura]);
    if ($stmt_check_asig->rowCount() == 0) {
        $errores[] = "La asignatura seleccionada no es válida.";
    }

    $stmt_check_prof = $pdo->prepare("SELECT id FROM profesores WHERE id = :id_prof");
    $stmt_check_prof->execute([':id_prof' => $id_profesor_final]);
    if ($stmt_check_prof->rowCount() == 0) {
        $errores[] = "El profesor asignado no es válido.";
    }
    
    // (Opcional) Verificar que no se cree/edite un curso duplicado
    // (misma asignatura, mismo profesor, mismo periodo, EXCEPTO si es el mismo curso que se está editando)
    $sql_check_duplicado = "SELECT id FROM cursos WHERE id_asignatura = :id_asig AND id_profesor = :id_prof AND periodo_academico = :periodo";
    $params_duplicado = [
        ':id_asig' => $id_asignatura,
        ':id_prof' => $id_profesor_final,
        ':periodo' => $periodo_academico
    ];
    if ($modo_edicion) {
        $sql_check_duplicado .= " AND id != :id_curso_edit";
        $params_duplicado[':id_curso_edit'] = $id_curso_a_editar;
    }
    $stmt_check_curso_exist = $pdo->prepare($sql_check_duplicado);
    $stmt_check_curso_exist->execute($params_duplicado);
    if ($stmt_check_curso_exist->fetch()) {
        $errores[] = "Ya existe un curso con la misma asignatura, profesor y periodo académico.";
    }


    if (!empty($errores)) {
        $_SESSION['mensaje_error_curso_crear'] = implode("<br>", $errores);
        $_SESSION['form_data_curso_crear'] = $_POST; 
        $redirect_url = "../../index.php?vista=cursos/formulario_curso";
        if ($modo_edicion && $id_curso_a_editar) {
            $redirect_url .= "&id_curso=" . $id_curso_a_editar;
        }
        header("Location: " . $redirect_url);
        exit();
    }

    if ($modo_edicion) {
        // --- MODO EDICIÓN: Actualizar curso existente ---
        $stmt_update_curso = $pdo->prepare("UPDATE cursos SET 
            id_asignatura = :id_asignatura, 
            id_profesor = :id_profesor, 
            periodo_academico = :periodo_academico, 
            cupo_maximo = :cupo_maximo, 
            aula = :aula, 
            horario = :horario_descripcion
            WHERE id = :id_curso_editar");
        
        $stmt_update_curso->bindParam(':id_asignatura', $id_asignatura, PDO::PARAM_INT);
        $stmt_update_curso->bindParam(':id_profesor', $id_profesor_final, PDO::PARAM_INT);
        $stmt_update_curso->bindParam(':periodo_academico', $periodo_academico);
        $stmt_update_curso->bindParam(':cupo_maximo', $cupo_maximo, PDO::PARAM_INT);
        $stmt_update_curso->bindParam(':aula', $aula); 
        $stmt_update_curso->bindParam(':horario_descripcion', $horario_descripcion); 
        $stmt_update_curso->bindParam(':id_curso_editar', $id_curso_a_editar, PDO::PARAM_INT);
        
        $stmt_update_curso->execute();
        $_SESSION['mensaje_exito_curso_crear'] = "¡Curso actualizado exitosamente!";

    } else {
        // --- MODO CREACIÓN: Insertar nuevo curso ---
        $stmt_insert_curso = $pdo->prepare("INSERT INTO cursos 
            (id_asignatura, id_profesor, periodo_academico, cupo_maximo, aula, horario) 
            VALUES (:id_asignatura, :id_profesor, :periodo_academico, :cupo_maximo, :aula, :horario_descripcion)");
        
        $stmt_insert_curso->bindParam(':id_asignatura', $id_asignatura, PDO::PARAM_INT);
        $stmt_insert_curso->bindParam(':id_profesor', $id_profesor_final, PDO::PARAM_INT);
        $stmt_insert_curso->bindParam(':periodo_academico', $periodo_academico);
        $stmt_insert_curso->bindParam(':cupo_maximo', $cupo_maximo, PDO::PARAM_INT);
        $stmt_insert_curso->bindParam(':aula', $aula); 
        $stmt_insert_curso->bindParam(':horario_descripcion', $horario_descripcion); 
        
        $stmt_insert_curso->execute();
        $_SESSION['mensaje_exito_curso_crear'] = "¡Curso creado exitosamente!";
    }

    unset($_SESSION['form_data_curso_crear']);
    unset($_SESSION['csrf_token']); 

    // Redirigir al formulario (para ver el mensaje) o a una lista de cursos
    $redirect_url = "../../index.php?vista=cursos/formulario_curso";
    if ($modo_edicion && $id_curso_a_editar) {
        // Si se editó, es bueno volver al mismo formulario de edición para ver los cambios
        // o a una lista de cursos donde se vea el curso editado.
        // Por ahora, volvemos al formulario con el ID.
        $redirect_url .= "&id_curso=" . $id_curso_a_editar; 
    }
    header("Location: " . $redirect_url); 
    exit();

} catch (PDOException $e) {
    error_log("Error en procesamiento de curso: " . $e->getMessage() . " - Datos: " . json_encode($_POST));
    $_SESSION['mensaje_error_curso_crear'] = "Ocurrió un error al procesar el curso. Por favor, inténtelo más tarde.";
    $_SESSION['form_data_curso_crear'] = $_POST;
    $redirect_url = "../../index.php?vista=cursos/formulario_curso";
    if ($modo_edicion && $id_curso_a_editar) {
        $redirect_url .= "&id_curso=" . $id_curso_a_editar;
    }
    header("Location: " . $redirect_url);
    exit();
} finally {
    $pdo = null;
}
?>
