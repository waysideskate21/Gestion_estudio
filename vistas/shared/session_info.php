<?php
// vistas/session_info.php

// verificar_rol() ya debería haber sido llamado por index.php a través de auth.php
// si esta vista está en la lista blanca y requiere autenticación.
// Si queremos ser explícitos o si esta vista tiene requisitos de rol específicos:
verificar_rol(['admin', 'profesor', 'estudiante']); // Permite a cualquier usuario logueado ver su info de sesión

// No es necesario incluir auth.php o main.php aquí si index.php los maneja.
?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-half">
            <div class="box login-box">
                <h1 class="title is-4 has-text-centered">Información de Sesión Actual</h1>
                
                <div class="content">
                    <table class="table is-fullwidth is-striped">
                        <tbody>
                            <tr>
                                <th>ID de Usuario:</th>
                                <td><?= htmlspecialchars($_SESSION['id_usuario'] ?? 'No definido'); ?></td>
                            </tr>
                            <tr>
                                <th>Nombre de Usuario:</th>
                                <td><?= htmlspecialchars($_SESSION['username'] ?? 'No definido'); ?></td>
                            </tr>
                            <tr>
                                <th>Rol (Tipo de Usuario):</th>
                                <td><?= htmlspecialchars(ucfirst($_SESSION['tipo_usuario'] ?? 'No definido')); ?></td>
                            </tr>
                            <tr>
                                <th>Email Registrado:</th>
                                <td><?= htmlspecialchars($_SESSION['email_usuario'] ?? 'No definido'); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actividad Registrada:</th>
                                <td><?= isset($_SESSION['last_activity']) ? htmlspecialchars(date('d/m/Y H:i:s', $_SESSION['last_activity'])) : 'No definida'; ?></td>
                            </tr>
                            <tr>
                                <th>Hora de Creación/Regeneración de Sesión:</th>
                                <td><?= isset($_SESSION['session_created_time']) ? htmlspecialchars(date('d/m/Y H:i:s', $_SESSION['session_created_time'])) : 'No definida'; ?></td>
                            </tr>
                            <tr>
                                <th>ID de Sesión PHP:</th>
                                <td><?= htmlspecialchars(session_id()); ?></td>
                            </tr>
                             <tr>
                                <th>Token CSRF Actual:</th>
                                <td style="word-break: break-all;"><?= htmlspecialchars($_SESSION['csrf_token'] ?? 'No definido'); ?></td>
                            </tr>
                            <tr>
                                <th>Estado de Sesión:</th>
                                <td>
                                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                                        <span class="tag is-success is-medium">ACTIVA</span>
                                    <?php else: ?>
                                        <span class="tag is-danger is-medium">INACTIVA O NO INICIADA</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="buttons is-centered mt-5">
                    <a href="index.php?vista=home" class="button is-link">
                        <span class="icon"><i class="fas fa-home"></i></span>
                        <span>Volver al Inicio</span>
                    </a>
                    <a href="procesos/auth/procesar_logout.php" class="button is-danger">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .login-box { /* Reutilizando estilo */
        margin-top: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1), 0 6px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
</style>