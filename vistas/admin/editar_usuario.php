<?php
// vistas/admin/usuario_formulario.php

verificar_rol(['admin']); // Solo administradores

$pdo = conexion();

// Determinar si estamos en modo edición o creación
$modo_edicion = false;
$id_usuario_a_editar = null;
$datos_usuario_actual = [];
$datos_rol_actual = [];
$tipo_usuario_actual_edicion = null;

$titulo_pagina = "Crear Nuevo Usuario (Admin)";
$texto_boton = "Crear Usuario";

if (isset($_GET['id_usuario']) && !empty($_GET['id_usuario'])) {
    $id_usuario_a_editar = limpiar_cadena($_GET['id_usuario']);
    if (validar_entero($id_usuario_a_editar)) {
        $stmt_usr = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id_usuario");
        $stmt_usr->bindParam(':id_usuario', $id_usuario_a_editar, PDO::PARAM_INT);
        $stmt_usr->execute();
        $datos_usuario_actual = $stmt_usr->fetch(PDO::FETCH_ASSOC);

        if ($datos_usuario_actual) {
            $modo_edicion = true;
            $titulo_pagina = "Editar Usuario (ID: " . htmlspecialchars($id_usuario_a_editar) . ")";
            $texto_boton = "Actualizar Usuario";
            $tipo_usuario_actual_edicion = $datos_usuario_actual['tipo'];

            $tabla_rol = "";
            switch ($tipo_usuario_actual_edicion) {
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
            if ($tabla_rol) {
                $stmt_rol_data = $pdo->prepare("SELECT * FROM {$tabla_rol} WHERE id = :id_usuario");
                $stmt_rol_data->bindParam(':id_usuario', $id_usuario_a_editar, PDO::PARAM_INT);
                $stmt_rol_data->execute();
                $datos_rol_actual = $stmt_rol_data->fetch(PDO::FETCH_ASSOC);
                if (!$datos_rol_actual) $datos_rol_actual = [];
            }
        } else {
            $_SESSION['mensaje_usuario_accion'] = "Error: El usuario que intenta editar no existe.";
            header("Location: index.php?vista=admin/gestion_usuarios");
            exit();
        }
    } else {
        $_SESSION['mensaje_usuario_accion'] = "Error: ID de usuario no válido para editar.";
        header("Location: index.php?vista=admin/gestion_usuarios");
        exit();
    }
}

$form_data_session = $_SESSION['form_data_usuario_admin'] ?? [];
if (!empty($form_data_session)) {
    $form_data = $form_data_session; // Usar datos de sesión si existen (por error previo)
    $modo_edicion = isset($form_data['id_usuario_editar']);
    $id_usuario_a_editar = $form_data['id_usuario_editar'] ?? $id_usuario_a_editar;
    $tipo_usuario_actual_edicion = $form_data['tipo_usuario'] ?? $tipo_usuario_actual_edicion;
} else if ($modo_edicion) {
    // Si es modo edición y no hay form_data de sesión, combinar datos de BD
    $form_data = array_merge($datos_usuario_actual, $datos_rol_actual);
} else {
    $form_data = []; // Para creación, empezar con form_data vacío
}
unset($_SESSION['form_data_usuario_admin']);

// Definir los valores para los campos del formulario
$username_val = htmlspecialchars($form_data['username'] ?? '');
$email_val = htmlspecialchars($form_data['email'] ?? ''); // Email de la tabla usuarios
$tipo_usuario_val = $form_data['tipo_usuario'] ?? '';
$activo_val = $form_data['activo'] ?? '1'; // Activo por defecto en creación

// Datos personales (comunes a estudiante y profesor, algunos a admin)
$primer_nombre_val = htmlspecialchars($form_data['primer_nombre'] ?? '');
$segundo_nombre_val = htmlspecialchars($form_data['segundo_nombre'] ?? '');
$primer_apellido_val = htmlspecialchars($form_data['primer_apellido'] ?? '');
$segundo_apellido_val = htmlspecialchars($form_data['segundo_apellido'] ?? '');
$tipo_documento_val = htmlspecialchars($form_data['tipo_documento'] ?? '');
$numero_documento_val = htmlspecialchars($form_data['numero_documento'] ?? '');
$fecha_exp_val = htmlspecialchars($form_data['fecha_expedicion_documento'] ?? '');
$lugar_exp_val = htmlspecialchars($form_data['lugar_expedicion_documento'] ?? '');
$fecha_nac_val = htmlspecialchars($form_data['fecha_nacimiento'] ?? '');
$genero_val = htmlspecialchars($form_data['genero'] ?? '');
$pais_nac_val = htmlspecialchars($form_data['pais_nacimiento'] ?? '');
$dpto_nac_val = htmlspecialchars($form_data['departamento_nacimiento'] ?? '');
$ciudad_nac_val = htmlspecialchars($form_data['ciudad_nacimiento'] ?? '');
$nacionalidad_val = htmlspecialchars($form_data['nacionalidad'] ?? '');
$direccion_val = htmlspecialchars($form_data['direccion'] ?? '');
$telefono_val = htmlspecialchars($form_data['telefono'] ?? ''); // Teléfono general
$estado_civil_val = htmlspecialchars($form_data['estado_civil'] ?? '');
$tipo_sangre_val = htmlspecialchars($form_data['tipo_sangre_rh'] ?? '');
$eps_val = htmlspecialchars($form_data['eps'] ?? '');
$contacto_emerg_nombre_val = htmlspecialchars($form_data['contacto_emergencia_nombre'] ?? '');
$contacto_emerg_telefono_val = htmlspecialchars($form_data['contacto_emergencia_telefono'] ?? '');
$contacto_emerg_parentesco_val = htmlspecialchars($form_data['contacto_emergencia_parentesco'] ?? '');

// Datos específicos de rol
$semestre_val = htmlspecialchars($form_data['semestre'] ?? '');
$carrera_val = htmlspecialchars($form_data['carrera'] ?? '');
$fecha_ingreso_val = htmlspecialchars($form_data['fecha_ingreso'] ?? '');
$especialidad_val = htmlspecialchars($form_data['especialidad'] ?? '');
$departamento_val = htmlspecialchars($form_data['departamento'] ?? '');
$fecha_contratacion_val = htmlspecialchars($form_data['fecha_contratacion'] ?? '');

?>

<div class="container is-fluid mt-5 mb-5">
    <div class="columns is-centered">
        <div class="column is-two-thirds">
            <form action="procesos/admin/procesar_formulario_usuario.php" method="POST" class="box login-box" id="adminUsuarioForm">
                <h1 class="title has-text-centered"><?= $titulo_pagina ?></h1>

                <?php if (isset($_SESSION['mensaje_usuario_accion'])): ?>
                    <div class="notification <?= strpos(strtolower($_SESSION['mensaje_usuario_accion']), 'exitosa') !== false ? 'is-success' : 'is-danger'; ?> is-light">
                        <button class="delete" onclick="this.parentElement.remove();"></button>
                        <?= $_SESSION['mensaje_usuario_accion']; ?>
                        <?php unset($_SESSION['mensaje_usuario_accion']); ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <?php if ($modo_edicion && $id_usuario_a_editar): ?>
                    <input type="hidden" name="id_usuario_editar" value="<?= htmlspecialchars($id_usuario_a_editar); ?>">
                <?php endif; ?>

                <h2 class="subtitle is-5 mt-2">Información de la Cuenta</h2>
                <div class="field">
                    <label class="label" for="username">Nombre de Usuario <span class="has-text-danger">*</span></label>
                    <input class="input" type="text" name="username" id="username" value="<?= $username_val; ?>" required>
                </div>
                <div class="field">
                    <label class="label" for="email">Correo<span class="has-text-danger">*</span></label>
                    <input class="input" type="email" name="email" id="email" value="<?= $email_val; ?>" required>
                </div>
                <div class="field">
                    <label class="label" for="clave">Nueva Contraseña (Opcional)</label>
                    <input class="input" type="password" name="clave" id="clave" placeholder="<?= $modo_edicion ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'; ?>">
                </div>
                <div class="field">
                    <label class="label" for="clave_confirmacion">Confirmar Nueva Contraseña</label>
                    <input class="input" type="password" name="clave_confirmacion" id="clave_confirmacion">
                </div>
                <div class="field">
                    <label class="label" for="tipo_usuario">Rol (Tipo de Usuario) <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="tipo_usuario" id="tipo_usuario_admin_form" required>
                                <option value="">-- Seleccione un rol --</option>
                                <option value="admin" <?= ($tipo_usuario_val == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                <option value="profesor" <?= ($tipo_usuario_val == 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                                <option value="estudiante" <?= ($tipo_usuario_val == 'estudiante') ? 'selected' : ''; ?>>Estudiante</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="activo">Estado de la cuenta</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="activo" id="activo" required>
                                <option value="1" <?= ($activo_val == '1') ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?= ($activo_val == '0') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <h2 class="subtitle is-5 mt-4">Información Personal</h2>
                <div class="columns is-multiline">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" for="primer_nombre">Primer Nombre <span class="has-text-danger">*</span></label>
                            <input class="input" type="text" name="primer_nombre" id="primer_nombre" value="<?= $primer_nombre_val; ?>" required>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" for="segundo_nombre">Segundo Nombre</label>
                            <input class="input" type="text" name="segundo_nombre" id="segundo_nombre" value="<?= $segundo_nombre_val; ?>">
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" for="primer_apellido">Primer Apellido <span class="has-text-danger">*</span></label>
                            <input class="input" type="text" name="primer_apellido" id="primer_apellido" value="<?= $primer_apellido_val; ?>" required>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" for="segundo_apellido">Segundo Apellido</label>
                            <input class="input" type="text" name="segundo_apellido" id="segundo_apellido" value="<?= $segundo_apellido_val; ?>">
                        </div>
                    </div>
                    <div class="column is-6 campo_no_admin">
                        <div class="field">
                            <label class="label" for="tipo_documento">Tipo de Documento <span class="has-text-danger">*</span></label>
                            <div class="select is-fullwidth">
                                <select name="tipo_documento" id="tipo_documento">
                                    <option value="">-- Selecciona --</option>
                                    <option value="CC" <?= ($tipo_documento_val == 'CC') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                                    <option value="TI" <?= ($tipo_documento_val == 'TI') ? 'selected' : ''; ?>>Tarjeta de Identidad</option>
                                    <option value="CE" <?= ($tipo_documento_val == 'CE') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                                    <option value="Pasaporte" <?= ($tipo_documento_val == 'Pasaporte') ? 'selected' : ''; ?>>Pasaporte</option>
                                    <option value="Otro" <?= ($tipo_documento_val == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="column is-6 campo_no_admin">
                        <div class="field">
                            <label class="label" for="numero_documento">Número de Documento <span class="has-text-danger">*</span></label>
                            <input class="input" type="text" name="numero_documento" id="numero_documento" value="<?= $numero_documento_val; ?>">
                        </div>
                    </div>
                    <div class="column is-6 campo_no_admin">
                        <div class="field">
                            <label class="label" for="fecha_expedicion_documento">Fecha Expedición Documento</label>
                            <input class="input" type="date" name="fecha_expedicion_documento" id="fecha_expedicion_documento" value="<?= $fecha_exp_val; ?>">
                        </div>
                    </div>
                    <div class="column is-6 campo_no_admin">
                        <div class="field">
                            <label class="label" for="lugar_expedicion_documento">Lugar Expedición Documento</label>
                            <input class="input" type="text" name="lugar_expedicion_documento" id="lugar_expedicion_documento" value="<?= $lugar_exp_val; ?>">
                        </div>
                    </div>
                    <div class="column is-6 campo_no_admin">
                        <div class="field">
                            <label class="label" for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input class="input" type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="<?= $fecha_nac_val; ?>">
                        </div>
                    </div>
                    <div class="column is-6 campo_no_admin">
                        <div class="field">
                            <label class="label" for="genero">Género</label>
                            <div class="select is-fullwidth">
                                <select name="genero" id="genero">
                                    <option value="">-- Selecciona --</option>
                                    <option value="Masculino" <?= ($genero_val == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="Femenino" <?= ($genero_val == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?= ($genero_val == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                    <option value="Prefiero no decirlo" <?= ($genero_val == 'Prefiero no decirlo') ? 'selected' : ''; ?>>Prefiero no decirlo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


                <div id="campos_rol_especificos_admin_form">
                    <div class="campos_estudiante_admin_form is-hidden">
                        <hr>
                        <h3 class="subtitle is-6">Datos de Estudiante</h3>
                        <div class="field">
                            <label class="label" for="semestre">Semestre <span class="has-text-danger">*</span></label>
                            <input class="input" type="number" name="semestre" id="semestre_form_admin" value="<?= $semestre_val; ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="carrera">Carrera <span class="has-text-danger">*</span></label>
                            <input class="input" type="text" name="carrera" id="carrera_form_admin" value="<?= $carrera_val; ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="fecha_ingreso">Fecha de Ingreso <span class="has-text-danger">*</span></label>
                            <input class="input" type="date" name="fecha_ingreso" id="fecha_ingreso_form_admin" value="<?= $fecha_ingreso_val; ?>">
                        </div>
                    </div>

                    <div class="campos_profesor_admin_form is-hidden">
                        <hr>
                        <h3 class="subtitle is-6">Datos de Profesor</h3>
                        <div class="field">
                            <label class="label" for="especialidad">Especialidad <span class="has-text-danger">*</span></label>
                            <input class="input" type="text" name="especialidad" id="especialidad_form_admin" value="<?= $especialidad_val; ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="departamento">Departamento <span class="has-text-danger">*</span></label>
                            <input class="input" type="text" name="departamento" id="departamento_form_admin" value="<?= $departamento_val; ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="fecha_contratacion">Fecha de Contratación <span class="has-text-danger">*</span></label>
                            <input class="input" type="date" name="fecha_contratacion" id="fecha_contratacion_form_admin" value="<?= $fecha_contratacion_val; ?>">
                        </div>
                    </div>
                </div>


                <div class="field mt-5">
                    <button type="submit" class="button is-success is-fullwidth is-medium">
                        <?= $texto_boton ?>
                    </button>
                </div>
                <div class="field mt-3">
                    <a href="index.php?vista=admin/gestion_usuarios" class="button is-light is-fullwidth">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoUsuarioSelect = document.getElementById('tipo_usuario_admin_form');
        const camposEstudianteDiv = document.querySelector('.campos_estudiante_admin_form');
        const camposProfesorDiv = document.querySelector('.campos_profesor_admin_form');
        const camposNoAdmin = document.querySelectorAll('.campo_no_admin'); // Para campos como documento

        function toggleCamposRolYDocumento() {
            const tipoSeleccionado = tipoUsuarioSelect.value;

            // Ocultar/mostrar campos de documento y otros no admin
            camposNoAdmin.forEach(campo => {
                if (tipoSeleccionado === 'admin') {
                    campo.classList.add('is-hidden');
                    campo.querySelectorAll('input, select').forEach(input => input.required = false);
                } else {
                    campo.classList.remove('is-hidden');
                    // Establecer 'required' para tipo_documento y numero_documento si no es admin
                    // (Asegúrate que los inputs tengan el atributo 'required' en el HTML si siempre lo son para est/prof)
                    // O hazlo aquí dinámicamente:
                    if (campo.querySelector('#tipo_documento')) campo.querySelector('#tipo_documento').required = true;
                    if (campo.querySelector('#numero_documento')) campo.querySelector('#numero_documento').required = true;
                }
            });

            // Ocultar/mostrar campos específicos de rol (estudiante/profesor)
            camposEstudianteDiv.classList.add('is-hidden');
            camposEstudianteDiv.querySelectorAll('input, select').forEach(input => input.required = false);
            camposProfesorDiv.classList.add('is-hidden');
            camposProfesorDiv.querySelectorAll('input, select').forEach(input => input.required = false);

            if (tipoSeleccionado === 'estudiante') {
                camposEstudianteDiv.classList.remove('is-hidden');
                document.getElementById('semestre_form_admin').required = true;
                document.getElementById('carrera_form_admin').required = true;
                document.getElementById('fecha_ingreso_form_admin').required = true;
            } else if (tipoSeleccionado === 'profesor') {
                camposProfesorDiv.classList.remove('is-hidden');
                document.getElementById('especialidad_form_admin').required = true;
                document.getElementById('departamento_form_admin').required = true;
                document.getElementById('fecha_contratacion_form_admin').required = true;
            }
        }

        if (tipoUsuarioSelect) {
            toggleCamposRolYDocumento();
            tipoUsuarioSelect.addEventListener('change', toggleCamposRolYDocumento);
        }

        const claveInput = document.getElementById('clave');
        const claveConfirmacionInput = document.getElementById('clave_confirmacion');
        const form = document.getElementById('adminUsuarioForm');

        form.addEventListener('submit', function(event) {
            if (claveInput.value !== "") {
                if (claveInput.value !== claveConfirmacionInput.value) {
                    alert('Las nuevas contraseñas no coinciden.');
                    claveConfirmacionInput.setCustomValidity("Las nuevas contraseñas no coinciden.");
                    event.preventDefault();
                } else {
                    claveConfirmacionInput.setCustomValidity("");
                }
            } else {
                claveConfirmacionInput.setCustomValidity("");
            }
        });
        if (claveConfirmacionInput) claveConfirmacionInput.addEventListener('input', () => claveConfirmacionInput.setCustomValidity(""));
        if (claveInput) claveInput.addEventListener('input', () => {
            if (claveInput.value === claveConfirmacionInput.value || claveInput.value === "") claveConfirmacionInput.setCustomValidity("")
        });
    });
</script>
<style>
    .login-box {
        margin-top: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 6px 20px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .is-hidden {
        display: none !important;
    }

    .has-text-danger {
        color: red;
    }
</style>