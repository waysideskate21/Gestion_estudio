<?php
// vistas/perfil_usuario.php

verificar_rol(['admin', 'profesor', 'estudiante']); // Cualquier usuario logueado puede ver su perfil

$id_usuario_actual = $_SESSION['id_usuario'];
$tipo_usuario_actual = $_SESSION['tipo_usuario'];

$pdo = conexion();
$datos_usuario = null;
$datos_rol_especifico = null;

// Obtener datos de la tabla 'usuarios'
$stmt_usuario = $pdo->prepare("SELECT username, email, fecha_creacion, ultimo_login FROM usuarios WHERE id = :id_usuario");
$stmt_usuario->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
$stmt_usuario->execute();
$datos_usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

// Obtener datos de la tabla específica del rol
$tabla_rol = "";
switch ($tipo_usuario_actual) {
    case 'admin':
        $tabla_rol = "administradores";
        break;
    case 'profesor':
        $tabla_rol = "profesores";
        break;
    case 'estudiante':
        $tabla_rol = "estudiantes";
        break;
}

if ($tabla_rol && $datos_usuario) {
    // Seleccionar todas las columnas de la tabla de rol para mostrarlas
    $stmt_rol = $pdo->prepare("SELECT * FROM {$tabla_rol} WHERE id = :id_usuario");
    $stmt_rol->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
    $stmt_rol->execute();
    $datos_rol_especifico = $stmt_rol->fetch(PDO::FETCH_ASSOC);
}

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-two-thirds">
            <div class="box login-box">
                <h1 class="title is-4 has-text-centered">Mi Perfil</h1>

                <?php if (!$datos_usuario || !$datos_rol_especifico): ?>
                    <div class="notification is-danger">
                        No se pudo cargar la información del perfil. Por favor, contacte al administrador.
                    </div>
                <?php else: ?>
                    <div class="tabs is-centered is-boxed">
                        <ul>
                            <li class="is-active" data-tab="info-cuenta"><a><span class="icon is-small"><i class="fas fa-user-cog"></i></span><span>Cuenta</span></a></li>
                            <li data-tab="info-personal"><a><span class="icon is-small"><i class="fas fa-address-card"></i></span><span>Personal</span></a></li>
                            <?php if ($tipo_usuario_actual === 'estudiante' || $tipo_usuario_actual === 'profesor'): ?>
                                <li data-tab="info-contacto-salud"><a><span class="icon is-small"><i class="fas fa-briefcase-medical"></i></span><span>Contacto y Salud</span></a></li>
                            <?php endif; ?>
                            <?php if (isset($datos_rol_especifico['semestre']) || isset($datos_rol_especifico['especialidad'])): ?>
                                <li data-tab="info-rol"><a><span class="icon is-small"><i class="fas fa-graduation-cap"></i></span><span>Específico del Rol</span></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div id="tab-content">
                        <div class="content-tab" id="info-cuenta">
                            <h3 class="subtitle is-5 mt-4">Información de la Cuenta</h3>
                            <table class="table is-fullwidth is-hoverable">
                                <tbody>
                                    <tr>
                                        <th>Nombre de Usuario:</th>
                                        <td><?= htmlspecialchars($datos_usuario['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email Principal:</th>
                                        <td><?= htmlspecialchars($datos_usuario['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tipo de Usuario:</th>
                                        <td><?= htmlspecialchars(ucfirst($tipo_usuario_actual)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Fecha de Creación:</th>
                                        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($datos_usuario['fecha_creacion']))); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Último Inicio de Sesión:</th>
                                        <td><?= $datos_usuario['ultimo_login'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($datos_usuario['ultimo_login']))) : 'Nunca'; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="content-tab is-hidden" id="info-personal">
                            <h3 class="subtitle is-5 mt-4">Información Personal</h3>
                            <table class="table is-fullwidth is-hoverable">
                                <tbody>
                                    <tr>
                                        <th>Primer Nombre:</th>
                                        <td><?= htmlspecialchars($datos_rol_especifico['primer_nombre']); ?></td>
                                    </tr>
                                    <?php if (!empty($datos_rol_especifico['segundo_nombre'])): ?>
                                        <tr>
                                            <th>Segundo Nombre:</th>
                                            <td><?= htmlspecialchars($datos_rol_especifico['segundo_nombre']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Primer Apellido:</th>
                                        <td><?= htmlspecialchars($datos_rol_especifico['primer_apellido']); ?></td>
                                    </tr>
                                    <?php if (!empty($datos_rol_especifico['segundo_apellido'])): ?>
                                        <tr>
                                            <th>Segundo Apellido:</th>
                                            <td><?= htmlspecialchars($datos_rol_especifico['segundo_apellido']); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($tipo_usuario_actual !== 'admin'): // Admins no tienen estos campos en la tabla 'administradores' por defecto 
                                    ?>
                                        <tr>
                                            <th>Tipo de Documento:</th>
                                            <td><?= htmlspecialchars($datos_rol_especifico['tipo_documento']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Número de Documento:</th>
                                            <td><?= htmlspecialchars($datos_rol_especifico['numero_documento']); ?></td>
                                        </tr>
                                        <?php if (!empty($datos_rol_especifico['fecha_expedicion_documento'])): ?>
                                            <tr>
                                                <th>Fecha Expedición Doc.:</th>
                                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($datos_rol_especifico['fecha_expedicion_documento']))); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['lugar_expedicion_documento'])): ?>
                                            <tr>
                                                <th>Lugar Expedición Doc.:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['lugar_expedicion_documento']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['fecha_nacimiento'])): ?>
                                            <tr>
                                                <th>Fecha de Nacimiento:</th>
                                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($datos_rol_especifico['fecha_nacimiento']))); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['genero'])): ?>
                                            <tr>
                                                <th>Género:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['genero']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['pais_nacimiento'])): ?>
                                            <tr>
                                                <th>País de Nacimiento:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['pais_nacimiento']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['departamento_nacimiento'])): ?>
                                            <tr>
                                                <th>Dpto. de Nacimiento:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['departamento_nacimiento']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['ciudad_nacimiento'])): ?>
                                            <tr>
                                                <th>Ciudad de Nacimiento:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['ciudad_nacimiento']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['nacionalidad'])): ?>
                                            <tr>
                                                <th>Nacionalidad:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['nacionalidad']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($tipo_usuario_actual === 'estudiante' || $tipo_usuario_actual === 'profesor'): ?>
                            <div class="content-tab is-hidden" id="info-contacto-salud">
                                <h3 class="subtitle is-5 mt-4">Información de Contacto y Salud</h3>
                                <table class="table is-fullwidth is-hoverable">
                                    <tbody>
                                        <?php if (!empty($datos_rol_especifico['direccion'])): ?>
                                            <tr>
                                                <th>Dirección:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['direccion']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['telefono'])): ?>
                                            <tr>
                                                <th>Teléfono:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['telefono']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['estado_civil'])): ?>
                                            <tr>
                                                <th>Estado Civil:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['estado_civil']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['tipo_sangre_rh'])): ?>
                                            <tr>
                                                <th>Tipo de Sangre y RH:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['tipo_sangre_rh']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['eps'])): ?>
                                            <tr>
                                                <th>EPS:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['eps']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="has-text-weight-bold">
                                            <td colspan="2" class="has-text-centered pt-4">Contacto de Emergencia</td>
                                        </tr>
                                        <?php if (!empty($datos_rol_especifico['contacto_emergencia_nombre'])): ?>
                                            <tr>
                                                <th>Nombre Contacto Emergencia:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['contacto_emergencia_nombre']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['contacto_emergencia_telefono'])): ?>
                                            <tr>
                                                <th>Teléfono Contacto Emergencia:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['contacto_emergencia_telefono']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($datos_rol_especifico['contacto_emergencia_parentesco'])): ?>
                                            <tr>
                                                <th>Parentesco Contacto Emergencia:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['contacto_emergencia_parentesco']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($datos_rol_especifico['semestre']) || isset($datos_rol_especifico['especialidad'])): ?>
                            <div class="content-tab is-hidden" id="info-rol">
                                <h3 class="subtitle is-5 mt-4">Información Específica del Rol</h3>
                                <table class="table is-fullwidth is-hoverable">
                                    <tbody>
                                        <?php if ($tipo_usuario_actual === 'estudiante'): ?>
                                            <tr>
                                                <th>Carrera:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['carrera']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Semestre:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['semestre']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Fecha de Ingreso:</th>
                                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($datos_rol_especifico['fecha_ingreso']))); ?></td>
                                            </tr>
                                        <?php elseif ($tipo_usuario_actual === 'profesor'): ?>
                                            <tr>
                                                <th>Especialidad:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['especialidad']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Departamento:</th>
                                                <td><?= htmlspecialchars($datos_rol_especifico['departamento']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Fecha de Contratación:</th>
                                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($datos_rol_especifico['fecha_contratacion']))); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="buttons is-centered mt-5">
                        <a href="index.php?vista=perfil_editar_formulario" class="button is-primary">
                            <span class="icon"><i class="fas fa-edit"></i></span>
                            <span>Editar Perfil</span>
                        </a>
                        <a href="index.php?vista=home" class="button is-link">
                            <span class="icon"><i class="fas fa-arrow-left"></i></span>
                            <span>Volver</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tabs li');
        const tabContentBoxes = document.querySelectorAll('#tab-content > div');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('is-active'));
                tab.classList.add('is-active');
                const target = tab.dataset.tab;
                tabContentBoxes.forEach(box => {
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
    .login-box {
        /* Reutilizando estilo */
        margin-top: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 6px 20px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .content-tab.is-hidden {
        display: none;
    }

    .tabs ul {
        /* Para centrar las pestañas */
        justify-content: center;
    }
</style>