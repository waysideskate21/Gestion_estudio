<?php
// vistas/admin/cursos_lista.php

// Verificar rol (solo admin puede acceder)
verificar_rol(['admin']);

// Conexión a la BD
$pdo = conexion();

// Paginación
$pagina = (isset($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$registros_por_pagina = 15;
$inicio = ($pagina > 0) ? (($pagina * $registros_por_pagina) - $registros_por_pagina) : 0;

// Filtros (opcional, para futura implementación)
// $filtro_periodo = $_GET['filtro_periodo'] ?? null;
// $filtro_profesor = $_GET['filtro_profesor'] ?? null;
// $filtro_asignatura = $_GET['filtro_asignatura'] ?? null;

// Construcción de la consulta base
$sql_base = "
    SELECT
        c.id AS id_curso,
        c.periodo_academico,
        c.cupo_maximo,
        c.aula,
        c.horario AS horario_descripcion,
        a.codigo AS codigo_asignatura,
        a.nombre AS nombre_asignatura,
        CONCAT(p.primer_nombre, ' ', p.primer_apellido) AS nombre_profesor,
        p.id AS id_profesor,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.id_curso = c.id AND i.estado = 'activa') AS inscritos_actualmente
    FROM
        cursos c
    JOIN
        asignaturas a ON c.id_asignatura = a.id
    JOIN
        profesores p ON c.id_profesor = p.id
";
$sql_where = " WHERE 1=1 "; // Para facilitar la adición de filtros
$params = [];

// Aplicar filtros si existen (ejemplo)
// if ($filtro_periodo) {
//     $sql_where .= " AND c.periodo_academico = :periodo ";
//     $params[':periodo'] = $filtro_periodo;
// }
// ... más filtros ...

// Obtener el total de cursos para la paginación (con filtros aplicados)
$total_cursos_stmt = $pdo->prepare("SELECT COUNT(c.id) FROM cursos c JOIN asignaturas a ON c.id_asignatura = a.id JOIN profesores p ON c.id_profesor = p.id " . $sql_where);
$total_cursos_stmt->execute($params);
$total_cursos = (int)$total_cursos_stmt->fetchColumn();
$total_paginas = ceil($total_cursos / $registros_por_pagina);

// Consulta para obtener los cursos con paginación y filtros
$stmt_cursos = $pdo->prepare($sql_base . $sql_where . " ORDER BY c.periodo_academico DESC, a.nombre ASC, p.primer_apellido ASC LIMIT :inicio, :registros_por_pagina");

// Bind de parámetros de paginación
$stmt_cursos->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt_cursos->bindParam(':registros_por_pagina', $registros_por_pagina, PDO::PARAM_INT);

// Bind de parámetros de filtro (si los hubiera)
foreach ($params as $key => $value) {
    $stmt_cursos->bindValue($key, $value); // bindValue para poder usar variables en el loop
}

$stmt_cursos->execute();
$lista_todos_los_cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-full">
            <h1 class="title has-text-centered">Gestionar Todos los Cursos</h1>

            <?php if (isset($_SESSION['mensaje_curso_accion'])): // Mensajes de acciones (editar, eliminar) ?>
                <div class="notification <?= strpos($_SESSION['mensaje_curso_accion'], 'exitosa') !== false ? 'is-success' : 'is-danger'; ?> is-light">
                    <button class="delete" onclick="this.parentElement.remove();"></button>
                    <?= $_SESSION['mensaje_curso_accion']; ?>
                    <?php unset($_SESSION['mensaje_curso_accion']); ?>
                </div>
            <?php endif; ?>
            
            <div class="box">
                <div class="level">
                    <div class="level-left">
                        <p>Mostrando <?= count($lista_todos_los_cursos) ?> de <?= $total_cursos ?> cursos.</p>
                    </div>
                    <div class="level-right">
                        <a href="index.php?vista=cursos/formulario_curso" class="button is-link">
                            <span class="icon"><i class="fas fa-plus"></i></span>
                            <span>Crear Nuevo Curso</span>
                        </a>
                    </div>
                </div>


                <?php if (empty($lista_todos_los_cursos) && $total_cursos > 0 && $pagina > 1): ?>
                     <div class="notification is-warning is-light">
                        <p>No hay cursos en esta página. <a href="index.php?vista=admin/cursos_lista&page=1">Volver a la primera página</a>.</p>
                    </div>
                <?php elseif (empty($lista_todos_los_cursos)): ?>
                    <div class="notification is-info is-light">
                        <p>No hay cursos registrados en el sistema.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                            <thead>
                                <tr>
                                    <th>ID Curso</th>
                                    <th>Asignatura (Código)</th>
                                    <th>Profesor Asignado</th>
                                    <th>Periodo</th>
                                    <th>Aula</th>
                                    <th>Cupo Máx.</th>
                                    <th>Inscritos</th>
                                    <th>Horario (Desc.)</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_todos_los_cursos as $curso): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($curso['id_curso']); ?></td>
                                        <td><?= htmlspecialchars($curso['nombre_asignatura'] . " (" . $curso['codigo_asignatura'] . ")"); ?></td>
                                        <td><?= htmlspecialchars($curso['nombre_profesor']); ?></td>
                                        <td><?= htmlspecialchars($curso['periodo_academico']); ?></td>
                                        <td><?= htmlspecialchars($curso['aula'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($curso['cupo_maximo']); ?></td>
                                        <td><?= htmlspecialchars($curso['inscritos_actualmente']); ?></td>
                                        <td><?= nl2br(htmlspecialchars($curso['horario_descripcion'] ?? 'N/D')); ?></td>
                                        <td>
                                            <div class="buttons are-small">
                                                <a href="index.php?vista=cursos/detalles_curso&id_curso=<?= $curso['id_curso']; ?>" class="button is-info is-light" title="Ver detalles del curso">
                                                    <span class="icon"><i class="fas fa-eye"></i></span>
                                                </a>
                                                <a href="index.php?vista=cursos/formulario_curso&id_curso=<?= $curso['id_curso']; ?>" class="button is-warning is-light" title="Editar curso">
                                                    <span class="icon"><i class="fas fa-edit"></i></span>
                                                </a>
                                                <form action="procesos/cursos/eliminar_curso.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro que desea ELIMINAR este curso? Esta acción no se puede deshacer.');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                    <input type="hidden" name="id_curso_eliminar" value="<?= $curso['id_curso']; ?>">
                                                    <button type="submit" class="button is-danger is-light" title="Eliminar curso">
                                                        <span class="icon"><i class="fas fa-trash-alt"></i></span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_paginas > 1): ?>
                        <nav class="pagination is-centered mt-5" role="navigation" aria-label="pagination">
                            <a class="pagination-previous" <?= ($pagina <= 1) ? 'disabled' : 'href="index.php?vista=admin/cursos_lista&page='.($pagina-1).'"'; ?>>Anterior</a>
                            <a class="pagination-next" <?= ($pagina >= $total_paginas) ? 'disabled' : 'href="index.php?vista=admin/cursos_lista&page='.($pagina+1).'"'; ?>>Siguiente</a>
                            <ul class="pagination-list">
                                <?php 
                                // Lógica para mostrar un número limitado de páginas si son muchas
                                $rango_paginas = 2;
                                $pagina_inicial = max(1, $pagina - $rango_paginas);
                                $pagina_final = min($total_paginas, $pagina + $rango_paginas);

                                if ($pagina_inicial > 1) {
                                    echo '<li><a class="pagination-link" href="index.php?vista=admin/cursos_lista&page=1">1</a></li>';
                                    if ($pagina_inicial > 2) {
                                        echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
                                    }
                                }

                                for ($i = $pagina_inicial; $i <= $pagina_final; $i++): ?>
                                    <li>
                                        <a class="pagination-link <?= ($i == $pagina) ? 'is-current' : ''; ?>" 
                                           href="index.php?vista=admin/cursos_lista&page=<?= $i; ?>"
                                           aria-label="Ir a página <?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; 

                                if ($pagina_final < $total_paginas) {
                                    if ($pagina_final < $total_paginas - 1) {
                                        echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
                                    }
                                    echo '<li><a class="pagination-link" href="index.php?vista=admin/cursos_lista&page='.$total_paginas.'">'.$total_paginas.'</a></li>';
                                }
                                ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

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
