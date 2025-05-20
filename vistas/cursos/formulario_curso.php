<?php
// vistas/cursos/formulario_curso.php

// Verificar rol (admin o profesor)
verificar_rol(['admin', 'profesor']);

$pdo = conexion();

// Determinar si estamos en modo edición o creación
$modo_edicion = false;
$datos_curso_actual = []; // Para almacenar datos del curso si estamos editando
$id_curso_a_editar = null;

if (isset($_GET['id_curso']) && !empty($_GET['id_curso'])) {
    $id_curso_a_editar = limpiar_cadena($_GET['id_curso']);
    if (validar_entero($id_curso_a_editar)) {
        $stmt_curso = $pdo->prepare("SELECT * FROM cursos WHERE id = :id_curso");
        $stmt_curso->bindParam(':id_curso', $id_curso_a_editar, PDO::PARAM_INT);
        $stmt_curso->execute();
        $datos_curso_actual = $stmt_curso->fetch(PDO::FETCH_ASSOC);

        if ($datos_curso_actual) {
            // Verificar permisos para editar: admin puede editar cualquier curso, profesor solo los suyos.
            if ($_SESSION['tipo_usuario'] === 'profesor' && $datos_curso_actual['id_profesor'] != $_SESSION['id_usuario']) {
                $_SESSION['mensaje_error_curso_crear'] = "Error: No tiene permiso para editar este curso.";
                // Redirigir a una página apropiada, por ejemplo, la lista de sus cursos o home.
                header("Location: index.php?vista=profesor/lista_cursos");
                exit();
            }
            $modo_edicion = true;
        } else {
            $_SESSION['mensaje_error_curso_crear'] = "Error: El curso que intenta editar no existe.";
            $id_curso_a_editar = null; // Resetear si no se encontró
        }
    } else {
        $_SESSION['mensaje_error_curso_crear'] = "Error: ID de curso no válido para editar.";
        $id_curso_a_editar = null; // Resetear si no es válido
    }
}

// Llenar $form_data: si hay error de validación previo, usar esos datos; si es edición, usar $datos_curso_actual; sino, vacío.
$form_data = $_SESSION['form_data_curso_crear'] ?? ($modo_edicion ? $datos_curso_actual : []);
if (isset($_SESSION['form_data_curso_crear'])) { // Si venimos de un error, $modo_edicion podría ser falso
    $modo_edicion = isset($form_data['id_curso_editar']); // Re-evaluar modo edición si hay form_data
    $id_curso_a_editar = $form_data['id_curso_editar'] ?? $id_curso_a_editar;
}
unset($_SESSION['form_data_curso_crear']);


// --- Obtener listas para los select ---
$stmt_asignaturas = $pdo->query("SELECT id, nombre, codigo FROM asignaturas ORDER BY nombre ASC");
$asignaturas = $stmt_asignaturas->fetchAll(PDO::FETCH_ASSOC);

$profesores = [];
if ($_SESSION['tipo_usuario'] === 'admin') {
    $stmt_profesores = $pdo->query("SELECT id, primer_nombre, primer_apellido FROM profesores ORDER BY primer_apellido ASC, primer_nombre ASC");
    $profesores = $stmt_profesores->fetchAll(PDO::FETCH_ASSOC);
}

$titulo_formulario = $modo_edicion ? "Editar Curso" : "Crear Nuevo Curso";
$texto_boton = $modo_edicion ? "Actualizar Curso" : "Crear Curso";

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-half">
            <form action="procesos/cursos/procesar_curso.php" method="POST" class="box login-box" id="cursoForm">
                <h1 class="title has-text-centered"><?= $titulo_formulario ?></h1>

                <?php if (isset($_SESSION['mensaje_error_curso_crear'])): ?>
                    <div class="notification is-danger is-light">
                        <button class="delete" onclick="this.parentElement.remove();"></button>
                        <?= $_SESSION['mensaje_error_curso_crear']; ?>
                        <?php unset($_SESSION['mensaje_error_curso_crear']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_exito_curso_crear'])): ?>
                    <div class="notification is-success is-light">
                        <button class="delete" onclick="this.parentElement.remove();"></button>
                        <?= $_SESSION['mensaje_exito_curso_crear']; ?>
                        <?php unset($_SESSION['mensaje_exito_curso_crear']); ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <?php if ($modo_edicion && $id_curso_a_editar): ?>
                    <input type="hidden" name="id_curso_editar" value="<?= htmlspecialchars($id_curso_a_editar); ?>">
                <?php endif; ?>

                <div class="field">
                    <label class="label" for="id_asignatura">Asignatura <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="id_asignatura" id="id_asignatura" required>
                                <option value="">-- Seleccione una asignatura --</option>
                                <?php foreach ($asignaturas as $asignatura): ?>
                                    <option value="<?= $asignatura['id']; ?>" <?= (isset($form_data['id_asignatura']) && $form_data['id_asignatura'] == $asignatura['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($asignatura['codigo'] . " - " . $asignatura['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (empty($asignaturas)): ?>
                        <p class="help is-warning">No hay asignaturas registradas. Por favor, <a href="index.php?vista=admin/crear_asignatura">cree una asignatura</a> primero.</p>
                    <?php endif; ?>
                </div>

                <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
                    <div class="field">
                        <label class="label" for="id_profesor">Profesor Asignado <span class="has-text-danger">*</span></label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="id_profesor" id="id_profesor" required>
                                    <option value="">-- Seleccione un profesor --</option>
                                    <?php foreach ($profesores as $profesor): ?>
                                        <option value="<?= $profesor['id']; ?>" <?= (isset($form_data['id_profesor']) && $form_data['id_profesor'] == $profesor['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($profesor['primer_nombre'] . " " . $profesor['primer_apellido']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php if (empty($profesores)): ?>
                            <p class="help is-warning">No hay profesores registrados. Un administrador debe <a href="index.php?vista=auth/registrar_usuario">registrar un profesor</a>.</p>
                        <?php endif; ?>
                    </div>
                <?php else: // Si es un profesor, se autoasigna (o se muestra el actual si está editando SU curso) 
                ?>
                    <input type="hidden" name="id_profesor" value="<?= $modo_edicion ? htmlspecialchars($datos_curso_actual['id_profesor']) : $_SESSION['id_usuario']; ?>">
                    <div class="field">
                        <label class="label">Profesor Asignado</label>
                        <div class="control">
                            <?php
                            $nombre_prof_display = $_SESSION['username']; // Por defecto el logueado
                            if ($modo_edicion && $datos_curso_actual['id_profesor'] == $_SESSION['id_usuario']) {
                                $nombre_prof_display = $_SESSION['username'] . " (Usted)";
                            } elseif ($modo_edicion) { // Si el admin edita un curso de otro profesor
                                // Necesitaríamos buscar el nombre del profesor actual del curso
                                $stmt_p = $pdo->prepare("SELECT primer_nombre, primer_apellido FROM profesores WHERE id = :id_p");
                                $stmt_p->execute([':id_p' => $datos_curso_actual['id_profesor']]);
                                $p_info = $stmt_p->fetch();
                                $nombre_prof_display = $p_info ? htmlspecialchars($p_info['primer_nombre'] . " " . $p_info['primer_apellido']) : 'Profesor Desconocido';
                            }
                            ?>
                            <input class="input" type="text" value="<?= $nombre_prof_display; ?>" readonly>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label class="label" for="periodo_academico">Periodo Académico <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="periodo_academico" id="periodo_academico"
                            placeholder="Ej: 2025-1, 2025-II"
                            value="<?= htmlspecialchars($form_data['periodo_academico'] ?? ($modo_edicion ? $datos_curso_actual['periodo_academico'] : date('Y') . '-' . (date('n') <= 6 ? '1' : '2'))); ?>" required>
                    </div>
                    <p class="help">Formato sugerido: AAAA-N (donde N es 1 o 2 para el semestre, o I, II).</p>
                </div>

                <div class="field">
                    <label class="label" for="cupo_maximo">Cupo Máximo <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="number" name="cupo_maximo" id="cupo_maximo"
                            placeholder="Ej: 30"
                            value="<?= htmlspecialchars($form_data['cupo_maximo'] ?? ($modo_edicion ? $datos_curso_actual['cupo_maximo'] : '30')); ?>" min="1" max="200" required>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="aula">Aula (Opcional)</label>
                    <div class="control">
                        <input class="input" type="text" name="aula" id="aula"
                            placeholder="Ej: Salón 301, Laboratorio B"
                            value="<?= htmlspecialchars($form_data['aula'] ?? ($modo_edicion ? $datos_curso_actual['aula'] : '')); ?>">
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="horario_descripcion">Descripción del Horario (Opcional)</label>
                    <div class="control">
                        <textarea class="textarea" name="horario_descripcion" id="horario_descripcion"
                            placeholder="Ej: Lunes y Miércoles 8-10 AM..."><?= htmlspecialchars($form_data['horario'] ?? ($modo_edicion ? $datos_curso_actual['horario'] : '')); ?></textarea>
                    </div>
                    <p class="help">Resumen. Horarios detallados en otra sección.</p>
                </div>

                <div class="field mt-5">
                    <div class="control">
                        <button type="submit" class="button is-success is-fullwidth is-medium">
                            <?= $texto_boton ?>
                        </button>
                    </div>
                </div>
                <?php if ($modo_edicion): ?>
                    <div class="field mt-3">
                        <div class="control">
                            <a href="index.php?vista=admin/cursos_lista" class="button is-light is-fullwidth">
                                Cancelar Edición
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<style>
    .login-box {
        margin-top: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 6px 20px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
</style>