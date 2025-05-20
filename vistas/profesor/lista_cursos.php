<?php
// vistas/profesor_ver_cursos.php

// Verificar rol (solo profesor puede acceder a esta vista específica)
verificar_rol(['profesor']);

$id_profesor_actual = $_SESSION['id_usuario']; // Obtener el ID del profesor logueado

// Conexión a la BD
$pdo = conexion();


$stmt_cursos = $pdo->prepare("
    SELECT
        c.id AS id_curso,
        c.periodo_academico,
        c.cupo_maximo,
        c.aula,
        c.horario AS horario_descripcion,
        a.codigo AS codigo_asignatura,
        a.nombre AS nombre_asignatura,
        a.creditos,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.id_curso = c.id AND i.estado = 'activa') AS inscritos_actualmente
    FROM
        cursos c
    JOIN
        asignaturas a ON c.id_asignatura = a.id
    WHERE
        c.id_profesor = :id_profesor_actual
    ORDER BY
        c.periodo_academico DESC, a.nombre ASC
");

$stmt_cursos->bindParam(':id_profesor_actual', $id_profesor_actual, PDO::PARAM_INT);
$stmt_cursos->execute();
$cursos_profesor = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-four-fifths">
            <h1 class="title has-text-centered">Mis Cursos Asignados</h1>

            <?php if (isset($_SESSION['mensaje_exito_curso_crear'])): ?>
                <div class="notification is-success is-light">
                    <button class="delete" onclick="this.parentElement.remove();"></button>
                    <?= $_SESSION['mensaje_exito_curso_crear']; ?>
                    <?php unset($_SESSION['mensaje_exito_curso_crear']); ?>
                </div>
            <?php endif; ?>
            
            <div class="box">
                <?php if (empty($cursos_profesor)): ?>
                    <div class="notification is-info is-light">
                        <p>Actualmente no tiene cursos asignados.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                            <thead>
                                <tr>
                                    <th>Código Asignatura</th>
                                    <th>Nombre Asignatura</th>
                                    <th>Periodo</th>
                                    <th>Créditos</th>
                                    <th>Aula</th>
                                    <th>Cupo Máximo</th>
                                    <th>Inscritos</th>
                                    <th>Horario (Desc.)</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursos_profesor as $curso): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($curso['codigo_asignatura']); ?></td>
                                        <td><?= htmlspecialchars($curso['nombre_asignatura']); ?></td>
                                        <td><?= htmlspecialchars($curso['periodo_academico']); ?></td>
                                        <td><?= htmlspecialchars($curso['creditos']); ?></td>
                                        <td><?= htmlspecialchars($curso['aula'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($curso['cupo_maximo']); ?></td>
                                        <td><?= htmlspecialchars($curso['inscritos_actualmente']); ?></td>
                                        <td><?= nl2br(htmlspecialchars($curso['horario_descripcion'] ?? 'No especificado')); ?></td>
                                        <td>
                                            <div class="buttons are-small">
                                                <a href="index.php?vista=curso_detalle&id=<?= $curso['id_curso']; ?>" class="button is-info is-light" title="Ver detalles del curso">
                                                    <span class="icon"><i class="fas fa-eye"></i></span>
                                                    <span>Detalles</span>
                                                </a>
                                                <a href="index.php?vista=curso_editar_formulario&id=<?= $curso['id_curso']; ?>" class="button is-warning is-light" title="Editar curso">
                                                    <span class="icon"><i class="fas fa-edit"></i></span>
                                                </a>
                                                 <a href="index.php?vista=profesor_calificaciones&curso_id=<?= $curso['id_curso']; ?>" class="button is-link is-light">Calificaciones</a>
                                                <a href="index.php?vista=profesor_asistencia&curso_id=<?= $curso['id_curso']; ?>" class="button is-primary is-light">Asistencia</a>
                                                
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                 <div class="mt-5 has-text-centered">
                    <a href="index.php?vista=cursos/formulario_curso" class="button is-link">
                        <span class="icon"><i class="fas fa-plus"></i></span>
                        <span>Crear Nuevo Curso</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .table-container {
        overflow-x: auto; /* Para tablas anchas en móviles */
    }
</style>
