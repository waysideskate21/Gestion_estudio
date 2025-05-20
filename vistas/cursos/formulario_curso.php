<?php
// vistas/cursos/formulario_curso.php

// Verificar rol (admin o profesor pueden crear cursos)
verificar_rol(['admin', 'profesor']);

// Conexión a la BD para obtener asignaturas y profesores
$pdo = conexion();

// Obtener lista de asignaturas
$stmt_asignaturas = $pdo->query("SELECT id, nombre, codigo FROM asignaturas ORDER BY nombre ASC");
$asignaturas = $stmt_asignaturas->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de profesores (solo si el usuario es admin)
$profesores = [];
if ($_SESSION['tipo_usuario'] === 'admin') {
    $stmt_profesores = $pdo->query("SELECT id, primer_nombre, primer_apellido FROM profesores ORDER BY primer_apellido ASC, primer_nombre ASC");
    $profesores = $stmt_profesores->fetchAll(PDO::FETCH_ASSOC);
}

// Recuperar datos del formulario en caso de error para rellenar los campos
$form_data = $_SESSION['form_data_curso_crear'] ?? [];
unset($_SESSION['form_data_curso_crear']); // Limpiar después de usar
?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-half">
            <form action="procesos/cursos/procesar_curso.php" method="POST" class="box login-box" id="crearCursoForm">
                <h1 class="title has-text-centered">Crear Nuevo Curso</h1>

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
                            <p class="help is-warning">No hay profesores registrados. Un administrador debe <a href="index.php?vista=auth/registrar_usuario">registrar un profesor</a> (o usar una vista de creación de usuarios específica para admin).</p>
                        <?php endif; ?>
                    </div>
                <?php else: // Si es un profesor, se autoasigna ?>
                    <input type="hidden" name="id_profesor" value="<?= $_SESSION['id_usuario']; ?>">
                    <div class="field">
                        <label class="label">Profesor Asignado</label>
                        <div class="control">
                            <input class="input" type="text" value="<?= htmlspecialchars($_SESSION['username']); ?> (Usted)" readonly>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label class="label" for="periodo_academico">Periodo Académico <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="periodo_academico" id="periodo_academico" 
                               placeholder="Ej: 2025-1, 2025-II" 
                               value="<?= htmlspecialchars($form_data['periodo_academico'] ?? date('Y') . '-' . (date('n') <= 6 ? '1' : '2')); ?>" required>
                    </div>
                    <p class="help">Formato sugerido: AAAA-N (donde N es 1 o 2 para el semestre, o I, II).</p>
                </div>

                <div class="field">
                    <label class="label" for="cupo_maximo">Cupo Máximo <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="number" name="cupo_maximo" id="cupo_maximo" 
                               placeholder="Ej: 30" 
                               value="<?= htmlspecialchars($form_data['cupo_maximo'] ?? '30'); ?>" min="1" max="200" required>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="aula">Aula (Opcional)</label>
                    <div class="control">
                        <input class="input" type="text" name="aula" id="aula" 
                               placeholder="Ej: Salón 301, Laboratorio B" 
                               value="<?= htmlspecialchars($form_data['aula'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="field">
                    <label class="label" for="horario_descripcion">Descripción del Horario (Opcional)</label>
                    <div class="control">
                        <textarea class="textarea" name="horario_descripcion" id="horario_descripcion" 
                                  placeholder="Ej: Lunes y Miércoles 8-10 AM. Viernes 2-4 PM. (Los horarios detallados se configuran aparte)"><?= htmlspecialchars($form_data['horario_descripcion'] ?? ''); ?></textarea>
                    </div>
                     <p class="help">Este es un resumen. Los horarios por día/hora se gestionarán en otra sección.</p>
                </div>

                <div class="field mt-5">
                    <div class="control">
                        <button type="submit" class="button is-success is-fullwidth is-medium">
                            Crear Curso
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .login-box { /* Reutilizando estilo para el contenedor del formulario */
        margin-top: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1), 0 6px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
</style>
