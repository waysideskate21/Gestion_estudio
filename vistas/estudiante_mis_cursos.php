<?php
// vistas/estudiante_mis_cursos.php

// Verificar rol (solo estudiante puede acceder a esta vista)
verificar_rol(['estudiante']);

$id_estudiante_actual = $_SESSION['id_usuario']; // Obtener el ID del estudiante logueado

// Conexión a la BD
$pdo = conexion();

// Consulta para obtener los cursos en los que el estudiante está inscrito activamente
$stmt_mis_cursos = $pdo->prepare("
    SELECT
        c.id AS id_curso,
        c.periodo_academico,
        c.aula,
        c.horario AS horario_descripcion_curso, -- El horario resumido del curso
        a.codigo AS codigo_asignatura,
        a.nombre AS nombre_asignatura,
        a.creditos AS creditos_asignatura,
        CONCAT(p.primer_nombre, ' ', p.primer_apellido) AS nombre_profesor,
        i.fecha_inscripcion,
        i.estado AS estado_inscripcion,
        GROUP_CONCAT(DISTINCT CONCAT(h.dia_semana,' ', TIME_FORMAT(h.hora_inicio,'%H:%i'),'-', TIME_FORMAT(h.hora_fin,'%H:%i')) ORDER BY FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), h.hora_inicio ASC SEPARATOR '<br>') AS horario_detallado
    FROM
        inscripciones i
    JOIN
        cursos c ON i.id_curso = c.id
    JOIN
        asignaturas a ON c.id_asignatura = a.id
    JOIN
        profesores p ON c.id_profesor = p.id
    LEFT JOIN
        horarios h ON c.id = h.id_curso
    WHERE
        i.id_estudiante = :id_estudiante_actual AND i.estado = 'activa'
    GROUP BY
        c.id, i.fecha_inscripcion, i.estado -- Agrupar por curso para el GROUP_CONCAT de horarios
    ORDER BY
        c.periodo_academico DESC, a.nombre ASC
");

$stmt_mis_cursos->bindParam(':id_estudiante_actual', $id_estudiante_actual, PDO::PARAM_INT);
$stmt_mis_cursos->execute();
$mis_cursos_inscritos = $stmt_mis_cursos->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-four-fifths">
            <h1 class="title has-text-centered">Mis Cursos Inscritos</h1>

            <?php if (isset($_SESSION['mensaje_inscripcion_retirada'])): // Para futuros mensajes de retiro ?>
                <div class="notification is-info is-light">
                    <button class="delete" onclick="this.parentElement.remove();"></button>
                    <?= $_SESSION['mensaje_inscripcion_retirada']; ?>
                    <?php unset($_SESSION['mensaje_inscripcion_retirada']); ?>
                </div>
            <?php endif; ?>
            
            <div class="box">
                <?php if (empty($mis_cursos_inscritos)): ?>
                    <div class="notification is-info is-light">
                        <p>Actualmente no te encuentras inscrito en ningún curso.</p>
                        <p class="mt-2">Puedes <a href="index.php?vista=estudiante_inscribir_curso">inscribirte a cursos aquí</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                            <thead>
                                <tr>
                                    <th>Código Asignatura</th>
                                    <th>Nombre Asignatura</th>
                                    <th>Periodo</th>
                                    <th>Profesor</th>
                                    <th>Créditos</th>
                                    <th>Aula</th>
                                    <th>Horario Detallado</th>
                                    <th>Fecha Inscripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mis_cursos_inscritos as $curso): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($curso['codigo_asignatura']); ?></td>
                                        <td><?= htmlspecialchars($curso['nombre_asignatura']); ?></td>
                                        <td><?= htmlspecialchars($curso['periodo_academico']); ?></td>
                                        <td><?= htmlspecialchars($curso['nombre_profesor']); ?></td>
                                        <td><?= htmlspecialchars($curso['creditos_asignatura']); ?></td>
                                        <td><?= htmlspecialchars($curso['aula'] ?? 'N/A'); ?></td>
                                        <td><?= $curso['horario_detallado'] ? $curso['horario_detallado'] : htmlspecialchars($curso['horario_descripcion_curso'] ?? 'No especificado'); // Muestra horario detallado si existe, sino la descripción ?></td>
                                        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($curso['fecha_inscripcion']))); ?></td>
                                        <td>
                                            <div class="buttons are-small">
                                                <a href="index.php?vista=curso_contenido&id_curso=<?= $curso['id_curso']; ?>" class="button is-link is-light" title="Ver contenido del curso">
                                                    <span class="icon"><i class="fas fa-book-open"></i></span>
                                                    <span>Contenido</span>
                                                </a>
                                               <form action="procesos/inscripciones/retirar_curso.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                    <input type="hidden" name="id_curso_a_retirar" value="<?= $curso['id_curso']; ?>">
                                                    <button type="submit" class="button is-danger is-light" 
                                                            onclick="return confirm('¿Está seguro que desea retirar el curso: <?= htmlspecialchars(addslashes($curso['nombre_asignatura'])); ?>? Esta acción podría no ser reversible.');">
                                                        <span class="icon"><i class="fas fa-user-minus"></i></span>
                                                        <span>Retirar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                 <div class="mt-5 has-text-centered">
                    <a href="index.php?vista=estudiante_inscribir_curso" class="button is-success">
                        <span class="icon"><i class="fas fa-plus-circle"></i></span>
                        <span>Inscribir más Cursos</span>
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
