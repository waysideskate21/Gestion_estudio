<?php
$form_data = $_SESSION['form_data_registro'] ?? [];
unset($_SESSION['form_data_registro']);
?>

<section class="section is-justify-content-center is-align-items-center">
    <div class="container column">
        <form action="procesos/auth/procesar_registro.php" method="POST" class="box login-box" id="registroForm">
            <h1 class="title has-text-centered">Regístrate</h1>

            <?php if (isset($_SESSION['mensaje_error_registro'])): ?>
                <div class="notification is-danger is-light">
                    <button class="delete" onclick="this.parentElement.remove();"></button>
                    <?= $_SESSION['mensaje_error_registro']; ?>
                    <?php unset($_SESSION['mensaje_error_registro']); ?>
                </div>
            <?php endif; ?>
            
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="columns">
                <div class="column">
                    <h2 class="subtitle is-5 mt-2 has-text-weight-bold">Información de la Cuenta</h2>
                    <div class="field">
                        <label class="label" for="usuario">Nombre de Usuario <span class="has-text-danger">*</span></label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" name="usuario" id="usuario" placeholder="Ingresa tu nombre de usuario" 
                                   value="<?= htmlspecialchars($form_data['usuario'] ?? ''); ?>" required pattern="^[a-zA-Z0-9_]{4,25}$" 
                                   title="Entre 4 y 20 caracteres (letras, números y guion bajo).">
                            <span class="icon is-small is-left"><i class="fas fa-user-tag"></i></span>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="email">Correo electrónico <span class="has-text-danger">*</span></label>
                        <div class="control has-icons-left">
                            <input class="input" type="email" name="email" id="email" placeholder="Ingresa un correo" 
                                   value="<?= htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            <span class="icon is-small is-left"><i class="fas fa-envelope"></i></span>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="clave">Contraseña <span class="has-text-danger">*</span></label>
                        <div class="control has-icons-left">
                            <input class="input" type="password" name="clave" id="clave" placeholder="Mínimo 6 caracteres" required minlength="6">
                            <span class="icon is-small is-left"><i class="fas fa-lock"></i></span>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="clave_confirmacion">Confirmar Contraseña <span class="has-text-danger">*</span></label>
                        <div class="control has-icons-left">
                            <input class="input" type="password" name="clave_confirmacion" id="clave_confirmacion" placeholder="Repite tu contraseña" required minlength="6">
                            <span class="icon is-small is-left"><i class="fas fa-check-circle"></i></span>
                        </div>
                    </div>
                     <div class="field">
                        <label class="label" for="tipo_usuario">Soy un: <span class="has-text-danger">*</span></label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="tipo_usuario" id="tipo_usuario" required>
                                    <option value="">-- Selecciona un rol --</option>
                                    <option value="estudiante" <?= (isset($form_data['tipo_usuario']) && $form_data['tipo_usuario'] == 'estudiante') ? 'selected' : ''; ?>>Estudiante</option>
                                    <option value="profesor" <?= (isset($form_data['tipo_usuario']) && $form_data['tipo_usuario'] == 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            <h2 class="subtitle is-5 mt-4 has-text-weight-bold">Información Personal</h2>
            
            <div class="columns is-multiline">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="primer_nombre">Primer Nombre <span class="has-text-danger">*</span></label>
                        <div class="control">
                            <input class="input" type="text" name="primer_nombre" id="primer_nombre" value="<?= htmlspecialchars($form_data['primer_nombre'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="segundo_nombre">Segundo Nombre</label>
                        <div class="control">
                            <input class="input" type="text" name="segundo_nombre" id="segundo_nombre" value="<?= htmlspecialchars($form_data['segundo_nombre'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="primer_apellido">Primer Apellido <span class="has-text-danger">*</span></label>
                        <div class="control">
                            <input class="input" type="text" name="primer_apellido" id="primer_apellido" value="<?= htmlspecialchars($form_data['primer_apellido'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="segundo_apellido">Segundo Apellido</label>
                        <div class="control">
                            <input class="input" type="text" name="segundo_apellido" id="segundo_apellido" value="<?= htmlspecialchars($form_data['segundo_apellido'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="tipo_documento">Tipo de Documento <span class="has-text-danger">*</span></label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="tipo_documento" id="tipo_documento" required>
                                    <option value="">-- Selecciona --</option>
                                    <option value="CC" <?= (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'CC') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                                    <option value="TI" <?= (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'TI') ? 'selected' : ''; ?>>Tarjeta de Identidad</option>
                                    <option value="CE" <?= (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'CE') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                                    <option value="Pasaporte" <?= (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'Pasaporte') ? 'selected' : ''; ?>>Pasaporte</option>
                                    <option value="Otro" <?= (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="numero_documento">Número de Documento <span class="has-text-danger">*</span></label>
                        <div class="control">
                            <input class="input" type="text" name="numero_documento" id="numero_documento" value="<?= htmlspecialchars($form_data['numero_documento'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="fecha_expedicion_documento">Fecha Expedición Documento</label>
                        <div class="control">
                            <input class="input" type="date" name="fecha_expedicion_documento" id="fecha_expedicion_documento" value="<?= htmlspecialchars($form_data['fecha_expedicion_documento'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="lugar_expedicion_documento">Lugar Expedición Documento</label>
                        <div class="control">
                            <input class="input" type="text" name="lugar_expedicion_documento" id="lugar_expedicion_documento" value="<?= htmlspecialchars($form_data['lugar_expedicion_documento'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                 <div class="column is-6">
                    <div class="field">
                        <label class="label" for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <div class="control">
                            <input class="input" type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="<?= htmlspecialchars($form_data['fecha_nacimiento'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="genero">Género</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="genero" id="genero">
                                    <option value="">-- Selecciona --</option>
                                    <option value="Masculino" <?= (isset($form_data['genero']) && $form_data['genero'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="Femenino" <?= (isset($form_data['genero']) && $form_data['genero'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?= (isset($form_data['genero']) && $form_data['genero'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                    <option value="Prefiero no decirlo" <?= (isset($form_data['genero']) && $form_data['genero'] == 'Prefiero no decirlo') ? 'selected' : ''; ?>>Prefiero no decirlo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="pais_nacimiento">País de Nacimiento</label>
                        <div class="control">
                            <input class="input" type="text" name="pais_nacimiento" id="pais_nacimiento" value="<?= htmlspecialchars($form_data['pais_nacimiento'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="departamento_nacimiento">Departamento de Nacimiento</label>
                        <div class="control">
                            <input class="input" type="text" name="departamento_nacimiento" id="departamento_nacimiento" value="<?= htmlspecialchars($form_data['departamento_nacimiento'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="ciudad_nacimiento">Ciudad de Nacimiento</label>
                        <div class="control">
                            <input class="input" type="text" name="ciudad_nacimiento" id="ciudad_nacimiento" value="<?= htmlspecialchars($form_data['ciudad_nacimiento'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                 <div class="column is-6">
                    <div class="field">
                        <label class="label" for="nacionalidad">Nacionalidad</label>
                        <div class="control">
                            <input class="input" type="text" name="nacionalidad" id="nacionalidad" value="<?= htmlspecialchars($form_data['nacionalidad'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-12">
                    <div class="field">
                        <label class="label" for="direccion">Dirección de Residencia</label>
                        <div class="control">
                            <input class="input" type="text" name="direccion" id="direccion" placeholder="Ej: Calle 5 # 20-30" value="<?= htmlspecialchars($form_data['direccion'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="telefono">Teléfono de Contacto</label>
                        <div class="control">
                            <input class="input" type="tel" name="telefono" id="telefono" placeholder="Ej: 3001234567" value="<?= htmlspecialchars($form_data['telefono'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="estado_civil">Estado Civil</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="estado_civil" id="estado_civil">
                                    <option value="">-- Selecciona --</option>
                                    <option value="Soltero/a" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Soltero/a') ? 'selected' : ''; ?>>Soltero/a</option>
                                    <option value="Casado/a" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Casado/a') ? 'selected' : ''; ?>>Casado/a</option>
                                    <option value="Unión libre" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Unión libre') ? 'selected' : ''; ?>>Unión libre</option>
                                    <option value="Divorciado/a" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Divorciado/a') ? 'selected' : ''; ?>>Divorciado/a</option>
                                    <option value="Viudo/a" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Viudo/a') ? 'selected' : ''; ?>>Viudo/a</option>
                                    <option value="Otro" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                    <option value="Prefiero no decirlo" <?= (isset($form_data['estado_civil']) && $form_data['estado_civil'] == 'Prefiero no decirlo') ? 'selected' : ''; ?>>Prefiero no decirlo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <h2 class="subtitle is-5 mt-4 has-text-weight-bold">Información de Salud (Opcional)</h2>
            <div class="columns is-multiline">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="tipo_sangre_rh">Tipo de Sangre y RH</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="tipo_sangre_rh" id="tipo_sangre_rh">
                                    <option value="">-- Selecciona --</option>
                                    <option value="O+" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                    <option value="A+" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                    <option value="No sabe" <?= (isset($form_data['tipo_sangre_rh']) && $form_data['tipo_sangre_rh'] == 'No sabe') ? 'selected' : ''; ?>>No sabe</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="eps">EPS</label>
                        <div class="control">
                            <input class="input" type="text" name="eps" id="eps" value="<?= htmlspecialchars($form_data['eps'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <h2 class="subtitle is-5 mt-4 has-text-weight-bold">Contacto de Emergencia (Opcional) </h2>
            <div class="columns is-multiline">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="contacto_emergencia_nombre">Nombre Completo</label>
                        <div class="control">
                            <input class="input" type="text" name="contacto_emergencia_nombre" id="contacto_emergencia_nombre" value="<?= htmlspecialchars($form_data['contacto_emergencia_nombre'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <label class="label" for="contacto_emergencia_telefono">Teléfono</label>
                        <div class="control">
                            <input class="input" type="tel" name="contacto_emergencia_telefono" id="contacto_emergencia_telefono" value="<?= htmlspecialchars($form_data['contacto_emergencia_telefono'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="column is-12">
                    <div class="field">
                        <label class="label" for="contacto_emergencia_parentesco">Parentesco</label>
                        <div class="control">
                            <input class="input" type="text" name="contacto_emergencia_parentesco" id="contacto_emergencia_parentesco" value="<?= htmlspecialchars($form_data['contacto_emergencia_parentesco'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            <div id="campos_estudiante" class="is-hidden">
                <h2 class="subtitle is-5 mt-4">Información Específica de Estudiante</h2>
                <div class="field">
                    <label class="label" for="semestre">Semestre <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="number" name="semestre" id="semestre_est" placeholder="Ej: 3" 
                               value="<?= htmlspecialchars($form_data['semestre'] ?? ''); ?>" min="1" max="15">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="carrera">Carrera <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="carrera" id="carrera_est" placeholder="Ej: Ingeniería de Software" 
                               value="<?= htmlspecialchars($form_data['carrera'] ?? ''); ?>">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="fecha_ingreso">Fecha de Ingreso <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="date" name="fecha_ingreso" id="fecha_ingreso_est" 
                               value="<?= htmlspecialchars($form_data['fecha_ingreso'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div id="campos_profesor" class="is-hidden">
                <h2 class="subtitle is-5 mt-4">Información Específica de Profesor</h2>
                <div class="field">
                    <label class="label" for="especialidad">Especialidad <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="especialidad" id="especialidad_prof" placeholder="Ej: Matemáticas Aplicadas" 
                               value="<?= htmlspecialchars($form_data['especialidad'] ?? ''); ?>">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="departamento">Departamento <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="departamento" id="departamento_prof" placeholder="Ej: Ciencias Básicas" 
                               value="<?= htmlspecialchars($form_data['departamento'] ?? ''); ?>">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="fecha_contratacion">Fecha de Contratación <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="date" name="fecha_contratacion" id="fecha_contratacion_prof" 
                               value="<?= htmlspecialchars($form_data['fecha_contratacion'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="field mt-5">
                <div class="control">
                    <button type="submit" class="button is-success is-fullwidth is-medium">
                        Registrar Usuario
                    </button>
                </div>
            </div>
            <p class="has-text-centered mt-3">
                ¿Ya tienes una cuenta? <a href="index.php?vista=login">Inicia sesión aquí</a>.
            </p>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipoUsuarioSelect = document.getElementById('tipo_usuario');
    const camposEstudianteDiv = document.getElementById('campos_estudiante');
    const camposProfesorDiv = document.getElementById('campos_profesor');

    // Inputs dentro de los campos específicos
    // Es importante seleccionar los inputs correctos DENTRO de sus contenedores específicos
    const inputsEstudiante = camposEstudianteDiv.querySelectorAll('input[type="number"], input[type="text"], input[type="date"]');
    const inputsProfesor = camposProfesorDiv.querySelectorAll('input[type="text"], input[type="date"]');


    function toggleCamposEspecificos() {
        const tipoSeleccionado = tipoUsuarioSelect.value;
        
        // Primero, resetear todos los 'required' de campos específicos
        inputsEstudiante.forEach(input => input.required = false);
        inputsProfesor.forEach(input => input.required = false);

        camposEstudianteDiv.classList.add('is-hidden');
        camposProfesorDiv.classList.add('is-hidden');

        if (tipoSeleccionado === 'estudiante') {
            camposEstudianteDiv.classList.remove('is-hidden');
            // Hacer campos de estudiante requeridos
            document.getElementById('semestre_est').required = true;
            document.getElementById('carrera_est').required = true;
            document.getElementById('fecha_ingreso_est').required = true;
        } else if (tipoSeleccionado === 'profesor') {
            camposProfesorDiv.classList.remove('is-hidden');
            // Hacer campos de profesor requeridos
            document.getElementById('especialidad_prof').required = true;
            document.getElementById('departamento_prof').required = true;
            document.getElementById('fecha_contratacion_prof').required = true;
        }
    }

    toggleCamposEspecificos(); // Ejecutar al cargar
    tipoUsuarioSelect.addEventListener('change', toggleCamposEspecificos); // Ejecutar al cambiar

    const claveInput = document.getElementById('clave');
    const claveConfirmacionInput = document.getElementById('clave_confirmacion');
    const form = document.getElementById('registroForm');

    form.addEventListener('submit', function(event) {
        if (claveInput.value !== claveConfirmacionInput.value) {
            // alert('Las contraseñas no coinciden.'); // Puedes usar una notificación de Bulma
            claveConfirmacionInput.setCustomValidity("Las contraseñas no coinciden.");
            // Mostrar notificación de Bulma
            const errorDiv = document.createElement('div');
            errorDiv.className = 'notification is-danger is-light mt-2';
            errorDiv.innerHTML = '<button class="delete" onclick="this.parentElement.remove();"></button>Las contraseñas no coinciden.';
            // Insertar antes del botón de submit o en un lugar visible
            form.querySelector('.mt-5').insertAdjacentElement('beforebegin', errorDiv);
            event.preventDefault(); 
        } else {
            claveConfirmacionInput.setCustomValidity("");
        }
    });
    // Limpiar validación custom al escribir
    claveConfirmacionInput.addEventListener('input', () => claveConfirmacionInput.setCustomValidity(""));
    claveInput.addEventListener('input', () => {
      if(claveInput.value === claveConfirmacionInput.value) claveConfirmacionInput.setCustomValidity("")
    });
});
</script>
<style>
    .login-box {
        margin-top: 1rem; /* Reducido para pantallas más llenas */
        padding: 1.5rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1), 0 6px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    .is-hidden {
        display: none !important;
    }
    .has-text-danger { /* Para los asteriscos de campos requeridos */
        color: red;
    }
</style>
