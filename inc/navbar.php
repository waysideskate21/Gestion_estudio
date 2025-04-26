<nav class="navbar" role="navigation" aria-label="main navigation" style="background-color: #74c4c4;">
  <div class="navbar-brand">
    <a class="navbar-item" href="index.php?vista=crear_usuario">
      <img src="./img/libros.png" alt="" width="40" height="50">
    </a>

    <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
    </a>
    <p class="navbar-item has-text-white">Sistema de Gestion Educativo SAS</p>
  </div>

  <!-- <div id="navbarBasicExample" class="navbar-menu">
    <div class="navbar-start">
      <div class="navbar-item has-dropdown is-hoverable">
        <a class="navbar-link">
          Usuarios  
        </a>
        <div class="navbar-dropdown">
          <a class="navbar-item">
            Nuevo
          </a>
          <a class="navbar-item">
            Lista
          </a>
          <a class="navbar-item">
            Buscar
          </a>
        </div>
      </div>
      <div class="navbar-item has-dropdown is-hoverable">
        <a class="navbar-link">
          Categorias
        </a>

        <div class="navbar-dropdown">
          <a class="navbar-item">
            Nuevo
          </a>
          <a class="navbar-item">
            Lista
          </a>
          <a class="navbar-item">
            Buscar
          </a>

        </div>
      </div>
      <div class="navbar-item has-dropdown is-hoverable">
        <a class="navbar-link">
          Productos
        </a>

        <div class="navbar-dropdown">
          <a class="navbar-item">
            Nuevo
          </a>
          <a class="navbar-item">
            Lista
          </a>
          <a class="navbar-item">
            por Categorias
          </a>
          <a class="navbar-item">
            Buscar
          </a>

        </div>
      </div>
    </div> -->

  <div class="navbar-end is-rounded">
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
      <div class="navbar-item has-dropdown is-hoverable">
        <a class="navbar-link">
          <span class="icon-text">
            <span class="icon">
              <i class="fas fa-user"></i>
            </span>
            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            <span class="tag is-light ml-2"><?= $_SESSION['rol'] ?></span>
          </span>
        </a>
        <div class="navbar-dropdown">
          <a class="navbar-item" href="index.php?vista=session_info">
            <span class="icon-text">
              <span class="icon">
                <i class="fas fa-info-circle"></i>
              </span>
              <span>Ver sesión</span>
            </span>
          </a>
          <hr class="navbar-divider">
          <a class="navbar-item" href="procesos/usuarios/logout.php">
            <span class="icon-text">
              <span class="icon">
                <i class="fas fa-sign-out-alt"></i>
              </span>
              <span>Cerrar sesión</span>
            </span>
          </a>
        </div>
      </div>
    <?php else: ?>

      <div class="navbar-end">
        <div class="navbar-item">
          <p class="button is-link is-rounded">
            <a href="index.php?vista=login" class="has-text-light">
              Iniciar sesión
            </a>
          </p>
        </div>

        </p>
      <?php endif; ?>
      </div>
</nav>