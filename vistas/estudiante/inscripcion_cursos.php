<?php
// vistas/inscribir_curso.php

// Verificar rol (solo estudiante puede acceder a esta vista)
verificar_rol(['estudiante']);

$id_estudiante_actual = $_SESSION['id_usuario']; // Obtener el ID del estudiante logueado

// Conexión a la BD
$pdo = conexion();

// Consulta para obtener los cursos disponibles usando la VISTA que ya tienes.
// Esta vista ya filtra por periodo actual y cupos disponibles.
// También necesitamos saber si el estudiante actual ya está inscrito en cada curso listado.
$stmt_cursos_disponibles = $pdo->prepare("
    SELECT
        vcd.*,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.id_curso = vcd.id_curso AND i.id_estudiante = :id_estudiante_actual AND i.estado = 'activa') AS ya_inscrito
    FROM
        vista_cursos_disponibles vcd
    ORDER BY
        vcd.facultad, vcd.programa, vcd.asignatura
");
$stmt_cursos_disponibles->bindParam(':id_estudiante_actual', $id_estudiante_actual, PDO::PARAM_INT);
$stmt_cursos_disponibles->execute();
$cursos_disponibles = $stmt_cursos_disponibles->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-full">
            <h1 class="title has-text-centered">Cursos Disponibles para Inscripción</h1>

            <?php if (isset($_SESSION['mensaje_inscripcion'])): ?>
                <div class="notification <?= strpos($_SESSION['mensaje_inscripcion'], 'exitosa') !== false ? 'is-success' : 'is-danger'; ?> is-light">
                    <button class="delete" onclick="this.parentElement.remove();"></button>
                    <?= $_SESSION['mensaje_inscripcion']; ?>
                    <?php unset($_SESSION['mensaje_inscripcion']); ?>
                </div>
            <?php endif; ?>
            
            <div class="box">
                <?php if (empty($cursos_disponibles)): ?>
                    <div class="notification is-info is-light">
                        <p>Actualmente no hay cursos disponibles para inscripción que cumplan los criterios (periodo actual, cupos disponibles).</p>
                        <p class="mt-2">Por favor, consulte más tarde o contacte a la administración.</p>
                    </div>
                <?php else: ?>
                    <p class="mb-4 has-text-weight-bold">Los siguientes cursos están disponibles para el periodo académico actual y tienen cupos.</p>
                    <div class="table-container">
                        <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Asignatura</th>
                                    <th>Créditos</th>
                                    <th>Profesor</th>
                                    <th>Periodo</th>
                                    <th>Cupos Disp.</th>
                                    <th>Aula</th>
                                    <th>Horario</th>
                                    <th>Programa</th>
                                    <th>Facultad</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursos_disponibles as $curso): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($curso['codigo']); ?></td>
                                        <td><?= htmlspecialchars($curso['asignatura']); ?></td>
                                        <td><?= htmlspecialchars($curso['creditos']); ?></td>
                                        <td><?= htmlspecialchars($curso['profesor']); ?></td>
                                        <td><?= htmlspecialchars($curso['periodo_academico']); ?></td>
                                        <td><?= htmlspecialchars($curso['cupos_disponibles']); ?></td>
                                        <td><?= htmlspecialchars($curso['aula'] ?? 'N/A'); ?></td>
                                        <td><?= nl2br(htmlspecialchars($curso['horario'] ?? 'No especificado')); ?></td>
                                        <td><?= htmlspecialchars($curso['programa'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($curso['facultad'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($curso['ya_inscrito'] > 0): ?>
                                                <button class="button is-success is-small" disabled>
                                                    <span class="icon is-small"><i class="fas fa-check-circle"></i></span>
                                                    <span>Inscrito</span>
                                                </button>
                                            <?php elseif ($curso['cupos_disponibles'] > 0): ?>
                                                <form action="procesos/inscripciones/inscribir_estudiante.php" method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                    <input type="hidden" name="id_curso_a_inscribir" value="<?= $curso['id_curso']; ?>">
                                                    <button type="submit" class="button is-link is-small" 
                                                            onclick="return confirm('¿Está seguro que desea inscribirse en el curso: <?= htmlspecialchars(addslashes($curso['asignatura'])); ?>?');">
                                                        <span class="icon is-small"><i class="fas fa-user-plus"></i></span>
                                                        <span>Inscribirme</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="button is-danger is-small" disabled>
                                                    <span class="icon is-small"><i class="fas fa-times-circle"></i></span>
                                                    <span>Sin Cupos</span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<style>
    .table-container {
        overflow-x: auto; /* Para tablas anchas en móviles */
    }
</style>
