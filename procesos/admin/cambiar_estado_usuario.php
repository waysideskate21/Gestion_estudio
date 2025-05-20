<?php
// procesos/admin/cambiar_estado_usuario.php

require_once __DIR__ . "/../../inc/session_start.php"; // Ajusta la profundidad si es necesario
require_once __DIR__ . "/../../php/main.php";         // Ajusta la profundidad si es necesario

// Verificar rol (solo admin puede cambiar estado de usuarios)
verificar_rol(['admin']);

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_usuario_accion'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=admin/usuarios_lista");
    exit();
}

// Validación del Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_usuario_accion'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=admin/usuarios_lista");
    exit();
}

// Recoger y Sanear los Datos del Formulario
$id_usuario_estado = limpiar_cadena($_POST['id_usuario_estado'] ?? '');
$nuevo_estado = limpiar_cadena($_POST['nuevo_estado'] ?? '');

// Validaciones
$errores = [];
if (empty($id_usuario_estado) || !validar_entero($id_usuario_estado)) {
    $errores[] = "ID de usuario no válido.";
}
if ($nuevo_estado !== '0' && $nuevo_estado !== '1') { // El nuevo estado debe ser 0 o 1
    $errores[] = "Estado no válido.";
}

// No permitir que el administrador se desactive a sí mismo
if ($id_usuario_estado == $_SESSION['id_usuario']) {
    $errores[] = "No puede cambiar su propio estado de activación.";
}


if (!empty($errores)) {
    $_SESSION['mensaje_usuario_accion'] = implode("<br>", $errores);
    header("Location: ../../index.php?vista=admin/usuarios_lista");
    exit();
}

$pdo = conexion();

try {
    // Verificar que el usuario a modificar exista
    $stmt_check_user = $pdo->prepare("SELECT id, activo FROM usuarios WHERE id = :id_usuario");
    $stmt_check_user->bindParam(':id_usuario', $id_usuario_estado, PDO::PARAM_INT);
    $stmt_check_user->execute();
    $usuario_a_modificar = $stmt_check_user->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_a_modificar) {
        $_SESSION['mensaje_usuario_accion'] = "Error: El usuario que intenta modificar no existe.";
        header("Location: ../../index.php?vista=admin/usuarios_lista");
        exit();
    }

    // Si el estado ya es el deseado, no hacer nada (opcional, pero evita queries innecesarias)
    if ($usuario_a_modificar['activo'] == $nuevo_estado) {
        $accion = ($nuevo_estado == 1) ? 'activar' : 'desactivar';
        $_SESSION['mensaje_usuario_accion'] = "El usuario ya se encontraba en estado de " . $accion . ".";
        header("Location: ../../index.php?vista=admin/usuarios_lista");
        exit();
    }

    // Actualizar el estado 'activo' en la tabla 'usuarios'
    $stmt_update_estado = $pdo->prepare("UPDATE usuarios SET activo = :nuevo_estado WHERE id = :id_usuario");
    $stmt_update_estado->bindParam(':nuevo_estado', $nuevo_estado, PDO::PARAM_INT);
    $stmt_update_estado->bindParam(':id_usuario', $id_usuario_estado, PDO::PARAM_INT);
    
    $stmt_update_estado->execute();

    if ($stmt_update_estado->rowCount() > 0) {
        $accion_texto = ($nuevo_estado == 1) ? 'activado' : 'desactivado';
        $_SESSION['mensaje_usuario_accion'] = "¡Usuario (ID: $id_usuario_estado) $accion_texto exitosamente!";
    } else {
        // Esto podría pasar si el estado ya era el deseado y no se hizo la verificación anterior,
        // o si el ID no existió (aunque ya lo verificamos).
        $_SESSION['mensaje_usuario_accion'] = "No se realizaron cambios en el estado del usuario (ID: $id_usuario_estado).";
    }

    unset($_SESSION['csrf_token']); // Invalidar el token usado

    header("Location: ../../index.php?vista=admin/gestion_usuarios");
    exit();

} catch (PDOException $e) {
    error_log("Error al cambiar estado de usuario: " . $e->getMessage() . " - Usuario ID: " . $id_usuario_estado . ", Nuevo Estado: " . $nuevo_estado);
    $_SESSION['mensaje_usuario_accion'] = "Ocurrió un error al cambiar el estado del usuario. Por favor, inténtelo más tarde.";
    header("Location: ../../index.php?vista=admin/gestion_usuarios");
    exit();
} finally {
    $pdo = null;
}
?>
