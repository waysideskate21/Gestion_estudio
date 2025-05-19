<?php
// inc/navbar.php
// Se asume que session_start() y auth.php ya se han ejecutado
// y las variables de sesión como $_SESSION['loggedin'], $_SESSION['username'] están disponibles.

// Definir $tipo_usuario_actual para evitar múltiples isset checks
$tipo_usuario_actual = $_SESSION['tipo_usuario'] ?? null; // Usar el operador de fusión de null

?>
<nav class="navbar" role="navigation" aria-label="main navigation" style="background-color: #74c4c4;">
  <div class="navbar-brand">
    <a class="navbar-item" href="index.php?vista=home">
      <img src="./Assets/img/libros.png" alt="Logo Sistema Gestión Educativo" width="35" height="35">
    </a>
    <p class="navbar-item has-text-white is-size-6 has-text-weight-bold" style="padding-left: 0;">Sistema de Gestión Educativo SAS</p>
    <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarMenuPrincipal">
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
    </a>
  </div>

  <div id="navbarMenuPrincipal" class="navbar-menu">
    <div class="navbar-start">
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <a class="navbar-item has-text-weight-bold " href="index.php?vista=home">
          Inicio
        </a>
        <?php // Verificar que $tipo_usuario_actual no sea null antes de compararlo ?>
        <?php if ($tipo_usuario_actual === 'admin'): ?>
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link">
              Administración
            </a>
            <div class="navbar-dropdown is-boxed">
              <a class="navbar-item" href="index.php?vista=admin_usuarios">Gestionar Usuarios</a>
              <a class="navbar-item" href="index.php?vista=admin_cursos">Gestionar Cursos</a>
              <a class="navbar-item" href="index.php?vista=crear_curso">Crear Nuevo Curso</a>
              <a class="navbar-item" href="index.php?vista=crear_asignatura">Crear Nueva Asignatura</a>
              <a class="navbar-item" href="index.php?vista=admin_programas">Gestionar Programas</a>
              <hr class="navbar-divider">
              <a class="navbar-item" href="index.php?vista=admin_configuracion">Configuración</a>
            </div>
          </div>
        <?php elseif ($tipo_usuario_actual === 'profesor'): ?>
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link ">
              Mis Cursos
            </a>
            <div class="navbar-dropdown is-boxed">
              <a class="navbar-item" href="index.php?vista=profesor_ver_cursos">Ver Mis Cursos</a>
              <a class="navbar-item" href="index.php?vista=crear_curso">Crear Nuevo Curso</a>
              <a class="navbar-item" href="index.php?vista=profesor_calificaciones">Gestionar Calificaciones</a>
              <a class="navbar-item" href="index.php?vista=profesor_asistencia">Registrar Asistencia</a>
            </div>
          </div>
        <?php elseif ($tipo_usuario_actual === 'estudiante'): ?>
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link">
              Académico
            </a>
            <div class="navbar-dropdown is-boxed">
              <a class="navbar-item" href="index.php?vista=cursos_inscritos">Mis Cursos Inscritos</a>
              <a class="navbar-item" href="index.php?vista=estudiante_ver_calificaciones">Ver Calificaciones</a>
              <a class="navbar-item" href="index.php?vista=inscribir_curso">Inscribir Cursos</a>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="navbar-end">
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <div class="navbar-item has-dropdown is-hoverable">
          <a class="navbar-link ">
            <span class="icon-text">
              <span class="icon">
                <i class="fas fa-user"></i>
              </span>
              <span><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?></span>
              <?php // También verificar $tipo_usuario_actual para el tag del rol ?>
              <span class="tag is-info is-light ml-2"><?= htmlspecialchars(ucfirst($tipo_usuario_actual ?? 'Rol')); ?></span>
            </span>
          </a>
          <div class="navbar-dropdown is-right is-boxed"> 
            <a class="navbar-item" href="index.php?vista=perfil_usuario"> 
                <span class="icon-text"><span class="icon"><i class="fas fa-id-card"></i></span><span>Mi Perfil</span></span>
            </a>
            <a class="navbar-item" href="index.php?vista=session_info">
              <span class="icon-text"><span class="icon"><i class="fas fa-info-circle"></i></span><span>Ver sesión</span></span>
            </a>
            <hr class="navbar-divider">
            <a class="navbar-item" href="procesos/auth/procesar_logout.php">
              <span class="icon-text"><span class="icon"><i class="fas fa-sign-out-alt"></i></span><span>Cerrar sesión</span></span>
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="navbar-item">
          <div class="buttons">
            <a class="button is-primary" href="index.php?vista=auth/login">
              <strong>Iniciar sesión</strong>
            </a>
            <a class="button is-light" href="index.php?vista=auth/registrar_usuario">
              Regístrate
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
