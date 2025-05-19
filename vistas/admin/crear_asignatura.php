<?php
// vistas/crear_asignatura.php

// Verificar rol (solo admin puede crear asignaturas)
verificar_rol(['admin']);

// Conexión a la BD para obtener programas
$pdo = conexion();

// Obtener lista de programas académicos
$stmt_programas = $pdo->query("SELECT id, nombre FROM programas ORDER BY nombre ASC");
$programas = $stmt_programas->fetchAll(PDO::FETCH_ASSOC);

// Recuperar datos del formulario en caso de error para rellenar los campos
$form_data = $_SESSION['form_data_asignatura_crear'] ?? [];
unset($_SESSION['form_data_asignatura_crear']); // Limpiar después de usar
?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-half">
            <form action="procesos/asignaturas/procesar_asignatura.php" method="POST" class="box login-box" id="crearAsignaturaForm">
                <h1 class="title has-text-centered">Crear Nueva Asignatura</h1>

                <?php if (isset($_SESSION['mensaje_error_asignatura_crear'])): ?>
                    <div class="notification is-danger is-light">
                        <button class="delete" onclick="this.parentElement.remove();"></button>
                        <?= $_SESSION['mensaje_error_asignatura_crear']; ?>
                        <?php unset($_SESSION['mensaje_error_asignatura_crear']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_exito_asignatura_crear'])): ?>
                    <div class="notification is-success is-light">
                        <button class="delete" onclick="this.parentElement.remove();"></button>
                        <?= $_SESSION['mensaje_exito_asignatura_crear']; ?>
                        <?php unset($_SESSION['mensaje_exito_asignatura_crear']); ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="field">
                    <label class="label" for="codigo_asignatura">Código de Asignatura <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="codigo_asignatura" id="codigo_asignatura" 
                               placeholder="Ej: MAT101, PROG-002" 
                               value="<?= htmlspecialchars($form_data['codigo_asignatura'] ?? ''); ?>" required>
                    </div>
                    <p class="help">Debe ser único para cada asignatura.</p>
                </div>

                <div class="field">
                    <label class="label" for="nombre_asignatura">Nombre de la Asignatura <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="nombre_asignatura" id="nombre_asignatura" 
                               placeholder="Ej: Matemáticas Básicas, Introducción a la Programación" 
                               value="<?= htmlspecialchars($form_data['nombre_asignatura'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="descripcion_asignatura">Descripción (Opcional)</label>
                    <div class="control">
                        <textarea class="textarea" name="descripcion_asignatura" id="descripcion_asignatura" 
                                  placeholder="Breve descripción de la asignatura..."><?= htmlspecialchars($form_data['descripcion_asignatura'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="creditos_asignatura">Créditos <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="number" name="creditos_asignatura" id="creditos_asignatura" 
                               placeholder="Ej: 3" 
                               value="<?= htmlspecialchars($form_data['creditos_asignatura'] ?? ''); ?>" min="1" max="10" required>
                    </div>
                </div>
                
                <div class="field">
                    <label class="label" for="id_programa">Programa Académico (Opcional)</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="id_programa" id="id_programa">
                                <option value="">-- Ninguno / Asignatura general --</option>
                                <?php foreach ($programas as $programa): ?>
                                    <option value="<?= $programa['id']; ?>" <?= (isset($form_data['id_programa']) && $form_data['id_programa'] == $programa['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($programa['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <?php if (empty($programas)): ?>
                        <p class="help is-info">No hay programas académicos registrados. Puede crear la asignatura sin asociarla a uno por ahora.</p>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="semestre_recomendado">Semestre Recomendado (Opcional)</label>
                    <div class="control">
                        <input class="input" type="number" name="semestre_recomendado" id="semestre_recomendado" 
                               placeholder="Ej: 1, 2, etc." 
                               value="<?= htmlspecialchars($form_data['semestre_recomendado'] ?? ''); ?>" min="1" max="15">
                    </div>
                </div>

                <div class="field mt-5">
                    <div class="control">
                        <button type="submit" class="button is-link is-fullwidth is-medium">
                            Crear Asignatura
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
    .has-text-danger {
        color: red;
    }
</style>
