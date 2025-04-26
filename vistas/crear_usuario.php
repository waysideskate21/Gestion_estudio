<?php if ($_SESSION['rol'] !== 'admin') { header("Location: index.php?vista=home"); exit(); } ?>

<div class="section">
    <div class="container">
        <h1 class="title is-4 has-text-centered">Registrar Nuevo Usuario</h1>
        <form method="POST" action="procesos/registrar_usuario.php" class="box" autocomplete="off">

            <div class="field">
                <label class="label">Nombre completo</label>
                <div class="control">
                    <input class="input" type="text" name="nombre_completo" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Email</label>
                <div class="control">
                    <input class="input" type="email" name="email" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Usuario</label>
                <div class="control">
                    <input class="input" type="text" name="username" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Contraseña</label>
                <div class="control">
                    <input class="input" type="password" name="password" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Tipo de usuario</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="tipo" id="tipo" required onchange="mostrarCampos()">
                            <option value="">Seleccione un tipo</option>
                            <option value="admin">Administrador</option>
                            <option value="profesor">Profesor</option>
                            <option value="estudiante">Estudiante</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Campos adicionales -->
            <div id="profesorCampos" style="display:none;">
                <div class="field">
                    <label class="label">Especialidad</label>
                    <input class="input" type="text" name="especialidad">
                </div>
                <div class="field">
                    <label class="label">Departamento</label>
                    <input class="input" type="text" name="departamento">
                </div>
                <div class="field">
                    <label class="label">Fecha contratación</label>
                    <input class="input" type="date" name="fecha_contratacion">
                </div>
            </div>

            <div id="estudianteCampos" style="display:none;">
                <div class="field">
                    <label class="label">Teléfono</label>
                    <input class="input" type="text" name="telefono_est">
                </div>
                <div class="field">
                    <label class="label">Semestre</label>
                    <input class="input" type="number" name="semestre">
                </div>
                <div class="field">
                    <label class="label">Carrera</label>
                    <input class="input" type="text" name="carrera">
                </div>
                <div class="field">
                    <label class="label">Fecha de ingreso</label>
                    <input class="input" type="date" name="fecha_ingreso">
                </div>
            </div>

            <div class="field has-text-centered">
                <button type="submit" class="button is-success">Registrar Usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
    function mostrarCampos() {
        const tipo = document.getElementById('tipo').value;
        document.getElementById('profesorCampos').style.display = (tipo === 'profesor') ? 'block' : 'none';
        document.getElementById('estudianteCampos').style.display = (tipo === 'estudiante') ? 'block' : 'none';
    }
</script>
