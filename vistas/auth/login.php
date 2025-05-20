<head>
    <?php require "./inc/navbar.php"; ?>
    <script src="..a"></script>
</head>

<body>
    <div class="section is-justify-content-center is-align-items-center is-flex">

        <!-- Mostrar mensajes de error -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="notification is-danger is-light">
                <button class="delete" onclick="this.parentElement.remove()"></button>
                <?= $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>

        <!-- Modificar el action del formulario -->
        <form class="box login" action="procesos/auth/procesar_login.php" method="POST" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <h5 class="title is-5 has-text-centered is-uppercase">Bienvenido Usuario</h5>
            <div class="field">
                <label class="label">Usuario</label>
                <div class="control">
                    <input class="input" type="text" name="login_usuario" pattern="[a-zA-Z0-9]{4,20}" maxlength="20" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Clave</label>
                <div class="control">
                    <input class="input" type="password" name="login_clave" pattern="[a-zA-Z0-9$@.-]{7,100}" maxlength="100" required>
                </div>
            </div>

            <p class="has-text-centered">
                <button type="submit" class="button is-rounded mb-3 mt-3" style="background-color:#74c4c4">Iniciar sesión</button>
            </p>
        </form>

    </div>
</body>