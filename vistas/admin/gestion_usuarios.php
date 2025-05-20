<?php
// vistas/admin/usuarios_lista.php

// Verificar rol (solo admin puede acceder)
verificar_rol(['admin']);

// Conexión a la BD
$pdo = conexion();

// Paginación (opcional, pero buena idea para muchos usuarios)
$pagina = (isset($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$registros_por_pagina = 15; // Puedes ajustar este número
$inicio = ($pagina > 0) ? (($pagina * $registros_por_pagina) - $registros_por_pagina) : 0;

// Obtener el total de usuarios para la paginación
$total_usuarios_stmt = $pdo->query("SELECT COUNT(id) FROM usuarios");
$total_usuarios = (int)$total_usuarios_stmt->fetchColumn();
$total_paginas = ceil($total_usuarios / $registros_por_pagina);

// Consulta para obtener los usuarios con paginación
// Usamos LEFT JOIN para obtener el nombre real desde las tablas de rol
$stmt_usuarios = $pdo->prepare("
    SELECT
        u.id,
        u.username,
        u.email AS email_cuenta, -- Email de la tabla usuarios
        u.tipo,
        u.activo,
        u.fecha_creacion,
        u.ultimo_login,
        CASE u.tipo
            WHEN 'admin' THEN CONCAT(adm.primer_nombre, ' ', adm.primer_apellido)
            WHEN 'profesor' THEN CONCAT(prof.primer_nombre, ' ', prof.primer_apellido)
            WHEN 'estudiante' THEN CONCAT(est.primer_nombre, ' ', est.primer_apellido)
            ELSE 'N/A'
        END AS nombre_real
        -- , CASE u.tipo  -- Opcional: si quieres mostrar el email específico del rol también
        --     WHEN 'admin' THEN adm.email
        --     WHEN 'profesor' THEN prof.email
        --     WHEN 'estudiante' THEN est.email
        --     ELSE 'N/A'
        -- END AS email_rol
    FROM
        usuarios u
    LEFT JOIN
        administradores adm ON u.id = adm.id AND u.tipo = 'admin'
    LEFT JOIN
        profesores prof ON u.id = prof.id AND u.tipo = 'profesor'
    LEFT JOIN
        estudiantes est ON u.id = est.id AND u.tipo = 'estudiante'
    ORDER BY
        u.fecha_creacion DESC
    LIMIT :inicio, :registros_por_pagina
");

$stmt_usuarios->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt_usuarios->bindParam(':registros_por_pagina', $registros_por_pagina, PDO::PARAM_INT);
$stmt_usuarios->execute();
$gestion_usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-full">
            <h1 class="title has-text-centered">Gestionar Usuarios del Sistema</h1>

            <?php if (isset($_SESSION['mensaje_usuario_accion'])): ?>
                <div class="notification <?= strpos(strtolower($_SESSION['mensaje_usuario_accion']), 'exitosa') !== false ? 'is-success' : 'is-danger'; ?> is-light">
                    <button class="delete" onclick="this.parentElement.remove();"></button>
                    <?= $_SESSION['mensaje_usuario_accion']; ?>
                    <?php unset($_SESSION['mensaje_usuario_accion']); ?>
                </div>
            <?php endif; ?>

            <div class="box">
                <div class="level">
                    <div class="level-left">
                        <p>Mostrando <?= count($gestion_usuarios) ?> de <?= $total_usuarios ?> usuarios.</p>
                    </div>
                    <div class="level-right">
                        <a href="index.php?vista=admin/crear_usuario" class="button is-link">
                            <span class="icon"><i class="fas fa-user-plus"></i></span>
                            <span>Crear Nuevo Usuario (Admin)</span>
                        </a>
                    </div>
                </div>


                <?php if (empty($gestion_usuarios) && $total_usuarios > 0 && $pagina > 1): ?>
                    <div class="notification is-warning is-light">
                        <p>No hay usuarios en esta página. <a href="index.php?vista=admin/usuarios_lista&page=1">Volver a la primera página</a>.</p>
                    </div>
                <?php elseif (empty($gestion_usuarios)): ?>
                    <div class="notification is-info is-light">
                        <p>No hay usuarios registrados en el sistema (aparte de usted, si es el único y no se lista a sí mismo en ciertas acciones).</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Nombre Real</th>
                                    <th>Email (Cuenta)</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Último Login</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gestion_usuarios as $usuario): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($usuario['id']); ?></td>
                                        <td><?= htmlspecialchars($usuario['username']); ?></td>
                                        <td><?= htmlspecialchars($usuario['nombre_real'] ?? 'N/D'); ?></td>
                                        <td><?= htmlspecialchars($usuario['email_cuenta']); ?></td>
                                        <td><?= htmlspecialchars(ucfirst($usuario['tipo'])); ?></td>
                                        <td>
                                            <?php if ($usuario['activo'] == 1): ?>
                                                <span class="tag is-success">Activo</span>
                                            <?php else: ?>
                                                <span class="tag is-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($usuario['fecha_creacion']))); ?></td>
                                        <td><?= $usuario['ultimo_login'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($usuario['ultimo_login']))) : 'Nunca'; ?></td>
                                        <td>
                                            <div class="buttons are-small">
                                                    <a href="index.php?vista=admin/editar_usuario&id_usuario=<?= $usuario['id']; ?>" class="button is-warning is-light" title="Editar usuario">
                                                    <span class="icon"><i class="fas fa-edit"></i></span>
                                                </a>
                                                <?php if ($usuario['id'] != $_SESSION['id_usuario']): // No permitir desactivarse/activarse a sí mismo 
                                                ?>
                                                    <?php if ($usuario['activo'] == 1): ?>
                                                        <form action="procesos/admin/cambiar_estado_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro que desea DESACTIVAR a este usuario?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                            <input type="hidden" name="id_usuario_estado" value="<?= $usuario['id']; ?>">
                                                            <input type="hidden" name="nuevo_estado" value="0">
                                                            <button type="submit" class="button is-danger is-light" title="Desactivar usuario">
                                                                <span class="icon"><i class="fas fa-user-slash"></i></span>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form action="procesos/admin/cambiar_estado_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro que desea ACTIVAR a este usuario?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                            <input type="hidden" name="id_usuario_estado" value="<?= $usuario['id']; ?>">
                                                            <input type="hidden" name="nuevo_estado" value="1">
                                                            <button type="submit" class="button is-success is-light" title="Activar usuario">
                                                                <span class="icon"><i class="fas fa-user-check"></i></span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_paginas > 1): ?>
                        <nav class="pagination is-centered mt-5" role="navigation" aria-label="pagination">
                            <a class="pagination-previous" <?= ($pagina <= 1) ? 'disabled' : 'href="index.php?vista=admin/usuarios_lista&page=' . ($pagina - 1) . '"'; ?>>Anterior</a>
                            <a class="pagination-next" <?= ($pagina >= $total_paginas) ? 'disabled' : 'href="index.php?vista=admin/usuarios_lista&page=' . ($pagina + 1) . '"'; ?>>Siguiente</a>
                            <ul class="pagination-list">
                                <?php
                                $rango_paginas = 2;
                                $pagina_inicial = max(1, $pagina - $rango_paginas);
                                $pagina_final = min($total_paginas, $pagina + $rango_paginas);

                                if ($pagina_inicial > 1) {
                                    echo '<li><a class="pagination-link" href="index.php?vista=admin/usuarios_lista&page=1">1</a></li>';
                                    if ($pagina_inicial > 2) {
                                        echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
                                    }
                                }

                                for ($i = $pagina_inicial; $i <= $pagina_final; $i++): ?>
                                    <li>
                                        <a class="pagination-link <?= ($i == $pagina) ? 'is-current' : ''; ?>"
                                            href="index.php?vista=admin/usuarios_lista&page=<?= $i; ?>"
                                            aria-label="Ir a página <?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor;

                                if ($pagina_final < $total_paginas) {
                                    if ($pagina_final < $total_paginas - 1) {
                                        echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
                                    }
                                    echo '<li><a class="pagination-link" href="index.php?vista=admin/usuarios_lista&page=' . $total_paginas . '">' . $total_paginas . '</a></li>';
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
        overflow-x: auto;
        /* Para tablas anchas en móviles */
    }
</style>