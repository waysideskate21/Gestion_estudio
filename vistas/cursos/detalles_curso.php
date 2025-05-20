<?php
// vistas/cursos/detalle_curso.php

verificar_auth(); // Todos los usuarios logueados podrían ver detalles, permisos específicos abajo

$id_curso_solicitado = limpiar_cadena($_GET['id_curso'] ?? null);

if (empty($id_curso_solicitado) || !validar_entero($id_curso_solicitado)) {
    // Redirigir o mostrar error si no hay ID de curso o no es válido
    $_SESSION['mensaje_error_generico'] = "ID de curso no válido.";
    header("Location: index.php?vista=shared/home"); // O a una lista de cursos apropiada
    exit();
}

$pdo = conexion();
$id_usuario_actual = $_SESSION['id_usuario'];
$tipo_usuario_actual = $_SESSION['tipo_usuario'];

// Obtener información principal del curso
$stmt_curso = $pdo->prepare("
    SELECT
        c.*,
        a.nombre AS nombre_asignatura,
        a.codigo AS codigo_asignatura,
        a.descripcion AS descripcion_asignatura,
        a.creditos,
        CONCAT(p.primer_nombre, ' ', p.primer_apellido) AS nombre_profesor,
        p.email AS email_profesor,
        prog.nombre AS nombre_programa
    FROM cursos c
    JOIN asignaturas a ON c.id_asignatura = a.id
    JOIN profesores p ON c.id_profesor = p.id
    LEFT JOIN programas prog ON a.id_programa = prog.id
    WHERE c.id = :id_curso
");
$stmt_curso->bindParam(':id_curso', $id_curso_solicitado, PDO::PARAM_INT);
$stmt_curso->execute();
$curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    $_SESSION['mensaje_error_generico'] = "Curso no encontrado.";
    header("Location: index.php?vista=shared/home");
    exit();
}

// Lógica de permisos para ver detalles (ejemplo básico)
$puede_ver_detalles_completos = false;
if ($tipo_usuario_actual === 'admin' || ($tipo_usuario_actual === 'profesor' && $curso['id_profesor'] == $id_usuario_actual)) {
    $puede_ver_detalles_completos = true;
}

// Para estudiantes, verificar si están inscritos
$estudiante_inscrito = false;
if ($tipo_usuario_actual === 'estudiante') {
    $stmt_insc = $pdo->prepare("SELECT id_estudiante FROM inscripciones WHERE id_curso = :id_curso AND id_estudiante = :id_estudiante AND estado = 'activa'");
    $stmt_insc->execute([':id_curso' => $id_curso_solicitado, ':id_estudiante' => $id_usuario_actual]);
    if ($stmt_insc->fetch()) {
        $estudiante_inscrito = true;
        $puede_ver_detalles_completos = true; // Estudiante inscrito puede ver detalles
    }
}

// Si no es admin, ni el profesor del curso, ni un estudiante inscrito, podría no tener acceso completo
// (Podrías tener una versión pública más limitada de los detalles si el curso no requiere inscripción para ver info básica)
// Por ahora, si no cumple, redirigimos o mostramos mensaje.
if (!$puede_ver_detalles_completos) {
    $_SESSION['mensaje_error_generico'] = "No tiene permiso para ver los detalles completos de este curso.";
    // Redirigir según el rol, o mostrar una vista parcial.
    if ($tipo_usuario_actual === 'estudiante') {
        header("Location: index.php?vista=estudiante/inscripcion_cursos");
    } else {
        header("Location: index.php?vista=shared/home");
    }
    exit();
}


// Obtener horarios detallados
$stmt_horarios = $pdo->prepare("SELECT dia_semana, TIME_FORMAT(hora_inicio, '%h:%i %p') AS hora_inicio_f, TIME_FORMAT(hora_fin, '%h:%i %p') AS hora_fin_f FROM horarios WHERE id_curso = :id_curso ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), hora_inicio ASC");
$stmt_horarios->execute([':id_curso' => $id_curso_solicitado]);
$horarios_detallados = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

// Obtener materiales (si el usuario tiene permiso)
$materiales = [];
if ($puede_ver_detalles_completos) {
    $stmt_materiales = $pdo->prepare("SELECT titulo, descripcion, url_archivo, fecha_publicacion FROM materiales WHERE id_curso = :id_curso ORDER BY fecha_publicacion DESC");
    $stmt_materiales->execute([':id_curso' => $id_curso_solicitado]);
    $materiales = $stmt_materiales->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener anuncios (si el usuario tiene permiso)
$anuncios = [];
if ($puede_ver_detalles_completos) {
    $stmt_anuncios = $pdo->prepare("SELECT titulo, contenido, fecha_publicacion, importancia FROM anuncios WHERE id_curso = :id_curso ORDER BY fecha_publicacion DESC");
    $stmt_anuncios->execute([':id_curso' => $id_curso_solicitado]);
    $anuncios = $stmt_anuncios->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener estudiantes inscritos (solo para admin y profesor del curso)
$estudiantes_inscritos = [];
if ($tipo_usuario_actual === 'admin' || ($tipo_usuario_actual === 'profesor' && $curso['id_profesor'] == $id_usuario_actual)) {
    $stmt_est_insc = $pdo->prepare("
        SELECT e.id, CONCAT(e.primer_nombre, ' ', e.primer_apellido) as nombre_estudiante, e.email, i.fecha_inscripcion
        FROM inscripciones i
        JOIN estudiantes e ON i.id_estudiante = e.id
        WHERE i.id_curso = :id_curso AND i.estado = 'activa'
        ORDER BY e.primer_apellido, e.primer_nombre
    ");
    $stmt_est_insc->execute([':id_curso' => $id_curso_solicitado]);
    $estudiantes_inscritos = $stmt_est_insc->fetchAll(PDO::FETCH_ASSOC);
}

?>
<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-three-quarters">
            <div class="box">
                <nav class="breadcrumb" aria-label="breadcrumbs">
                    <ul>
                        <li><a href="index.php?vista=shared/home">Inicio</a></li>
                        <?php if ($tipo_usuario_actual === 'admin'): ?>
                            <li><a href="index.php?vista=admin/cursos_lista">Gestionar Cursos</a></li>
                        <?php elseif ($tipo_usuario_actual === 'profesor'): ?>
                            <li><a href="index.php?vista=profesor/lista_cursos">Mis Cursos</a></li>
                        <?php elseif ($tipo_usuario_actual === 'estudiante'): ?>
                            <li><a href="index.php?vista=estudiante/cursos_inscritos">Mis Cursos Inscritos</a></li>
                        <?php endif; ?>
                        <li class="is-active"><a href="#" aria-current="page"><?= htmlspecialchars($curso['nombre_asignatura']) ?></a></li>
                    </ul>
                </nav>
                <hr>

                <h1 class="title is-3 has-text-centered"><?= htmlspecialchars($curso['nombre_asignatura']) ?></h1>
                <h2 class="subtitle is-5 has-text-centered"><?= htmlspecialchars($curso['codigo_asignatura']) ?> - Periodo: <?= htmlspecialchars($curso['periodo_academico']) ?></h2>

                <?php if ($tipo_usuario_actual === 'admin' || ($tipo_usuario_actual === 'profesor' && $curso['id_profesor'] == $id_usuario_actual)): ?>
                    <div class="buttons is-centered mb-4">
                        <a href="index.php?vista=cursos/formulario_curso&id_curso=<?= $curso['id'] ?>" class="button is-warning">
                            <span class="icon"><i class="fas fa-edit"></i></span>
                            <span>Editar Curso</span>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="tabs is-centered is-boxed">
                    <ul>
                        <li class="is-active" data-tab="info-general"><a><span class="icon is-small"><i class="fas fa-info-circle"></i></span><span>General</span></a></li>
                        <li data-tab="info-horarios"><a><span class="icon is-small"><i class="fas fa-clock"></i></span><span>Horarios</span></a></li>
                        <?php if (!empty($materiales) || $puede_ver_detalles_completos): ?>
                            <li data-tab="info-materiales"><a><span class="icon is-small"><i class="fas fa-folder-open"></i></span><span>Materiales</span></a></li>
                        <?php endif; ?>
                        <?php if (!empty($anuncios) || $puede_ver_detalles_completos): ?>
                            <li data-tab="info-anuncios"><a><span class="icon is-small"><i class="fas fa-bullhorn"></i></span><span>Anuncios</span></a></li>
                        <?php endif; ?>
                        <?php if (!empty($estudiantes_inscritos)): ?>
                            <li data-tab="info-inscritos"><a><span class="icon is-small"><i class="fas fa-users"></i></span><span>Inscritos (<?= count($estudiantes_inscritos) ?>)</span></a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div id="tab-content-curso">
                    <div class="content-tab-curso" id="info-general">
                        <h3 class="subtitle is-4 mt-2">Información General del Curso</h3>
                        <table class="table is-fullwidth is-hoverable">
                            <tbody>
                                <tr>
                                    <th>Asignatura:</th>
                                    <td><?= htmlspecialchars($curso['nombre_asignatura'] . " (" . $curso['codigo_asignatura'] . ")") ?></td>
                                </tr>
                                <tr>
                                    <th>Descripción Asignatura:</th>
                                    <td><?= nl2br(htmlspecialchars($curso['descripcion_asignatura'] ?? 'No disponible')) ?></td>
                                </tr>
                                <tr>
                                    <th>Créditos:</th>
                                    <td><?= htmlspecialchars($curso['creditos']) ?></td>
                                </tr>
                                <tr>
                                    <th>Profesor:</th>
                                    <td><?= htmlspecialchars($curso['nombre_profesor']) ?> (<?= htmlspecialchars($curso['email_profesor']) ?>)</td>
                                </tr>
                                <tr>
                                    <th>Periodo Académico:</th>
                                    <td><?= htmlspecialchars($curso['periodo_academico']) ?></td>
                                </tr>
                                <tr>
                                    <th>Programa Académico:</th>
                                    <td><?= htmlspecialchars($curso['nombre_programa'] ?? 'General / No aplica') ?></td>
                                </tr>
                                <tr>
                                    <th>Aula:</th>
                                    <td><?= htmlspecialchars($curso['aula'] ?? 'No asignada') ?></td>
                                </tr>
                                <tr>
                                    <th>Cupo Máximo:</th>
                                    <td><?= htmlspecialchars($curso['cupo_maximo']) ?></td>
                                </tr>
                                <tr>
                                    <th>Horario (Resumen):</th>
                                    <td><?= nl2br(htmlspecialchars($curso['horario'] ?? 'No especificado')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="content-tab-curso is-hidden" id="info-horarios">
                        <h3 class="subtitle is-4 mt-2">Horarios Detallados</h3>
                        <?php if (!empty($horarios_detallados)): ?>
                            <ul>
                                <?php foreach ($horarios_detallados as $h): ?>
                                    <li><strong><?= htmlspecialchars($h['dia_semana']) ?>:</strong> <?= htmlspecialchars($h['hora_inicio_f']) ?> - <?= htmlspecialchars($h['hora_fin_f']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay horarios detallados registrados para este curso.</p>
                        <?php endif; ?>
                        <?php if ($tipo_usuario_actual === 'admin' || ($tipo_usuario_actual === 'profesor' && $curso['id_profesor'] == $id_usuario_actual)): ?>
                            <a href="index.php?vista=cursos/gestionar_horarios&id_curso=<?= $curso['id'] ?>" class="button is-small is-link mt-3">Gestionar Horarios Detallados</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($materiales) || $puede_ver_detalles_completos): ?>
                        <div class="content-tab-curso is-hidden" id="info-materiales">
                            <h3 class="subtitle is-4 mt-2">Materiales del Curso</h3>
                            <?php if (!empty($materiales)): ?>
                                <?php foreach ($materiales as $mat): ?>
                                    <div class="box mb-3">
                                        <h4 class="title is-5"><?= htmlspecialchars($mat['titulo']) ?></h4>
                                        <p><small>Publicado: <?= htmlspecialchars(date("d/m/Y", strtotime($mat['fecha_publicacion']))) ?></small></p>
                                        <div class="content"><?= nl2br(htmlspecialchars($mat['descripcion'] ?? '')) ?></div>
                                        <?php if (!empty($mat['url_archivo'])): ?>
                                            <a href="<?= htmlspecialchars($mat['url_archivo']) ?>" target="_blank" class="button is-small is-link">
                                                <span class="icon"><i class="fas fa-download"></i></span>
                                                <span>Descargar/Ver Archivo</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No hay materiales disponibles para este curso.</p>
                            <?php endif; ?>
                            <?php if ($tipo_usuario_actual === 'admin' || ($tipo_usuario_actual === 'profesor' && $curso['id_profesor'] == $id_usuario_actual)): ?>
                                <a href="index.php?vista=cursos/gestionar_materiales&id_curso=<?= $curso['id'] ?>" class="button is-small is-success mt-3">Añadir Material</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($anuncios) || $puede_ver_detalles_completos): ?>
                        <div class="content-tab-curso is-hidden" id="info-anuncios">
                            <h3 class="subtitle is-4 mt-2">Anuncios del Curso</h3>
                            <?php if (!empty($anuncios)): ?>
                                <?php foreach ($anuncios as $anuncio): ?>
                                    <article class="message <?= $anuncio['importancia'] === 'alta' ? 'is-danger' : ($anuncio['importancia'] === 'media' ? 'is-warning' : 'is-info') ?> mb-3">
                                        <div class="message-header">
                                            <p><?= htmlspecialchars($anuncio['titulo']) ?> <small>(Publicado: <?= htmlspecialchars(date("d/m/Y H:i", strtotime($anuncio['fecha_publicacion']))) ?>)</small></p>
                                        </div>
                                        <div class="message-body">
                                            <?= nl2br(htmlspecialchars($anuncio['contenido'])) ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No hay anuncios para este curso.</p>
                            <?php endif; ?>
                            <?php if ($tipo_usuario_actual === 'admin' || ($tipo_usuario_actual === 'profesor' && $curso['id_profesor'] == $id_usuario_actual)): ?>
                                <a href="index.php?vista=cursos/crear_anuncio&id_curso=<?= $curso['id'] ?>" class="button is-small is-primary mt-3">Crear Anuncio</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($estudiantes_inscritos)): ?>
                        <div class="content-tab-curso is-hidden" id="info-inscritos">
                            <h3 class="subtitle is-4 mt-2">Estudiantes Inscritos</h3>
                            <table class="table is-fullwidth is-striped is-narrow">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Fecha Inscripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estudiantes_inscritos as $est): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($est['nombre_estudiante']) ?></td>
                                            <td><?= htmlspecialchars($est['email']) ?></td>
                                            <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($est['fecha_inscripcion']))) ?></td>
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
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabsCurso = document.querySelectorAll('.tabs li[data-tab]');
        const tabContentBoxesCurso = document.querySelectorAll('#tab-content-curso > div.content-tab-curso');

        tabsCurso.forEach(tab => {
            tab.addEventListener('click', () => {
                tabsCurso.forEach(item => item.classList.remove('is-active'));
                tab.classList.add('is-active');
                const target = tab.dataset.tab;
                tabContentBoxesCurso.forEach(box => {
                    if (box.id === target) {
                        box.classList.remove('is-hidden');
                    } else {
                        box.classList.add('is-hidden');
                    }
                });
            });
        });
    });
</script>
<style>
    .content-tab-curso.is-hidden {
        display: none;
    }

    .tabs ul {
        justify-content: center;
    }
</style>