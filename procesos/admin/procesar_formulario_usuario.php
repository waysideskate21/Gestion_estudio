<?php
// procesos/admin/procesar_formulario_usuario.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

verificar_rol(['admin']); // Solo administradores

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_usuario_accion'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=admin/editar_usuario");
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_usuario_accion'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=admin/editar_usuario");
    exit();
}

// --- Determinar Modo y Recoger IDs ---
$modo_edicion = false;
$id_usuario_a_editar = null;
if (isset($_POST['id_usuario_editar']) && !empty($_POST['id_usuario_editar'])) {
    $id_usuario_a_editar = limpiar_cadena($_POST['id_usuario_editar']);
    if (validar_entero($id_usuario_a_editar)) {
        $modo_edicion = true;
    } else {
        $_SESSION['mensaje_usuario_accion'] = "Error: ID de usuario para editar no válido.";
        header("Location: ../../index.php?vista=admin/editar_usuario");
        exit();
    }
}

// --- Recoger y Sanear Datos de la Cuenta ---
$username = limpiar_cadena($_POST['username'] ?? '');
$email_cuenta = limpiar_cadena($_POST['email'] ?? ''); // Email de la tabla 'usuarios'
$clave_nueva = $_POST['clave'] ?? ''; // No sanear con htmlspecialchars
$clave_confirmacion = $_POST['clave_confirmacion'] ?? '';
$tipo_usuario_form = limpiar_cadena($_POST['tipo_usuario'] ?? '');
$activo = limpiar_cadena($_POST['activo'] ?? '0'); // Default a inactivo si no se envía

// --- Recoger y Sanear Datos Personales Comunes ---
// (Debes añadir todos los campos que tienes en tu formulario de la tabla 'admin_gestion_usuarios_v1')
$primer_nombre = limpiar_cadena($_POST['primer_nombre'] ?? '');
$segundo_nombre = limpiar_cadena($_POST['segundo_nombre'] ?? null);
$primer_apellido = limpiar_cadena($_POST['primer_apellido'] ?? '');
$segundo_apellido = limpiar_cadena($_POST['segundo_apellido'] ?? null);
$tipo_documento = limpiar_cadena($_POST['tipo_documento'] ?? null);
$numero_documento = limpiar_cadena($_POST['numero_documento'] ?? '');
// ... (Añade aquí el resto de campos personales: fecha_exp_doc, lugar_exp_doc, fecha_nac, genero, etc.)
// ... (Añade campos de contacto y salud)
// ... (Añade campos de contacto de emergencia)

// --- Recoger y Sanear Datos Específicos del Rol (según el rol seleccionado en el formulario) ---
$datos_rol_especificos_post = [];
if ($tipo_usuario_form === 'estudiante') {
    $datos_rol_especificos_post['semestre'] = limpiar_cadena($_POST['semestre'] ?? null);
    $datos_rol_especificos_post['carrera'] = limpiar_cadena($_POST['carrera'] ?? null);
    $datos_rol_especificos_post['fecha_ingreso'] = limpiar_cadena($_POST['fecha_ingreso'] ?? null);
    // ... (más campos de estudiante)
} elseif ($tipo_usuario_form === 'profesor') {
    $datos_rol_especificos_post['especialidad'] = limpiar_cadena($_POST['especialidad'] ?? null);
    $datos_rol_especificos_post['departamento'] = limpiar_cadena($_POST['departamento'] ?? null);
    $datos_rol_especificos_post['fecha_contratacion'] = limpiar_cadena($_POST['fecha_contratacion'] ?? null);
    // ... (más campos de profesor)
}
// Para 'admin', los datos personales (primer_nombre, etc.) ya se recogieron arriba.

// --- Validaciones ---
$errores = [];

// Cuenta
if (empty($username) || verificar_datos("^[a-zA-Z0-9_]{4,20}$", $username)) $errores[] = "Nombre de Usuario inválido.";
if (empty($email_cuenta) || !validar_email($email_cuenta)) $errores[] = "Email de cuenta inválido.";
if (!empty($clave_nueva) && strlen($clave_nueva) < 6) $errores[] = "La nueva contraseña debe tener al menos 6 caracteres.";
if (!empty($clave_nueva) && $clave_nueva !== $clave_confirmacion) $errores[] = "Las nuevas contraseñas no coinciden.";
$tipos_validos = ['admin', 'profesor', 'estudiante'];
if (empty($tipo_usuario_form) || !in_array($tipo_usuario_form, $tipos_validos)) $errores[] = "Tipo de usuario inválido.";
if ($activo !== '0' && $activo !== '1') $errores[] = "Estado de cuenta inválido.";

// Personales (solo los obligatorios, adapta según tu formulario)
if (empty($primer_nombre)) $errores[] = "Primer nombre es obligatorio.";
if (empty($primer_apellido)) $errores[] = "Primer apellido es obligatorio.";

// Si el rol NO es admin, numero_documento y tipo_documento son obligatorios
if ($tipo_usuario_form !== 'admin') {
    if (empty($numero_documento) || verificar_datos("^[a-zA-Z0-9-]{5,20}$", $numero_documento)) $errores[] = "Número de documento inválido.";
    if (empty($tipo_documento)) $errores[] = "Tipo de documento es obligatorio.";
}


// Validaciones específicas del rol (si aplica)
if ($tipo_usuario_form === 'estudiante') {
    if (empty($datos_rol_especificos_post['semestre']) || !validar_entero($datos_rol_especificos_post['semestre'])) $errores[] = "Semestre de estudiante inválido.";
    if (empty($datos_rol_especificos_post['carrera'])) $errores[] = "Carrera de estudiante es obligatoria.";
    // ... más validaciones de estudiante
} elseif ($tipo_usuario_form === 'profesor') {
    if (empty($datos_rol_especificos_post['especialidad'])) $errores[] = "Especialidad de profesor es obligatoria.";
    // ... más validaciones de profesor
}

// --- Redirigir si hay errores de validación ---
if (!empty($errores)) {
    $_SESSION['mensaje_usuario_accion'] = implode("<br>", $errores);
    $_SESSION['form_data_usuario_admin'] = $_POST; // Guardar datos para rellenar
    $redirect_url = "../../index.php?vista=admin/gestion_usuarios";
    if ($modo_edicion && $id_usuario_a_editar) {
        $redirect_url .= "&id_usuario=" . $id_usuario_a_editar;
    }
    header("Location: " . $redirect_url);
    exit();
}

$pdo = conexion();

try {
    $pdo->beginTransaction();

    // --- Verificar Duplicados (Username, Email cuenta, Numero Documento) ---
    // Username
    $sql_check_username = "SELECT id FROM usuarios WHERE username = :username";
    $params_check_username = [':username' => $username];
    if ($modo_edicion) {
        $sql_check_username .= " AND id != :id_usuario_edit";
        $params_check_username[':id_usuario_edit'] = $id_usuario_a_editar;
    }
    $stmt_check = $pdo->prepare($sql_check_username);
    $stmt_check->execute($params_check_username);
    if ($stmt_check->fetch()) {
        $errores[] = "El nombre de usuario '$username' ya está en uso.";
    }

    // Email de cuenta
    $sql_check_email = "SELECT id FROM usuarios WHERE email = :email";
    $params_check_email = [':email' => $email_cuenta];
    if ($modo_edicion) {
        $sql_check_email .= " AND id != :id_usuario_edit";
        $params_check_email[':id_usuario_edit'] = $id_usuario_a_editar;
    }
    $stmt_check = $pdo->prepare($sql_check_email);
    $stmt_check->execute($params_check_email);
    if ($stmt_check->fetch()) {
        $errores[] = "El email de cuenta '$email_cuenta' ya está en uso.";
    }
    
    // Numero de Documento (solo si no es admin y se proporcionó)
    if ($tipo_usuario_form !== 'admin' && !empty($numero_documento)) {
        $tabla_rol_check_doc = $tipo_usuario_form === 'estudiante' ? 'estudiantes' : 'profesores';
        $sql_check_doc = "SELECT id FROM {$tabla_rol_check_doc} WHERE numero_documento = :numero_documento";
        $params_check_doc = [':numero_documento' => $numero_documento];
        if ($modo_edicion) {
            // Si el tipo de usuario no cambió, excluimos el ID actual.
            // Si el tipo de usuario cambió, no necesitamos excluir porque se buscará en una tabla diferente
            // o se insertará como nuevo. Esta lógica puede ser compleja si el número de doc es globalmente único.
            // Por ahora, asumimos que es único dentro de su tabla de rol.
            $stmt_old_type = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = :id_usr");
            $stmt_old_type->execute([':id_usr' => $id_usuario_a_editar]);
            $old_type = $stmt_old_type->fetchColumn();
            if ($old_type === $tipo_usuario_form) {
                 $sql_check_doc .= " AND id != :id_usuario_edit";
                 $params_check_doc[':id_usuario_edit'] = $id_usuario_a_editar;
            }
        }
        $stmt_check = $pdo->prepare($sql_check_doc);
        $stmt_check->execute($params_check_doc);
        if ($stmt_check->fetch()) {
            $errores[] = "El número de documento '$numero_documento' ya está registrado para un $tipo_usuario_form.";
        }
    }


    if (!empty($errores)) {
        $_SESSION['mensaje_usuario_accion'] = implode("<br>", $errores);
        $_SESSION['form_data_usuario_admin'] = $_POST;
        $redirect_url = "../../index.php?vista=admin/gestion_usuarios";
        if ($modo_edicion && $id_usuario_a_editar) $redirect_url .= "&id_usuario=" . $id_usuario_a_editar;
        header("Location: " . $redirect_url);
        exit();
    }

    // --- Operaciones en la Base de Datos ---
    $id_usuario_operacion = $modo_edicion ? $id_usuario_a_editar : null;

    if ($modo_edicion) {
        // --- MODO EDICIÓN ---
        // 1. Actualizar tabla 'usuarios'
        $sql_update_usuarios = "UPDATE usuarios SET username = :username, email = :email, tipo = :tipo, activo = :activo";
        $params_update_usuarios = [
            ':username' => $username,
            ':email' => $email_cuenta,
            ':tipo' => $tipo_usuario_form,
            ':activo' => $activo,
            ':id_usuario' => $id_usuario_a_editar
        ];
        if (!empty($clave_nueva)) {
            $password_hashed = hash_password($clave_nueva);
            $sql_update_usuarios .= ", password_hash = :password_hash";
            $params_update_usuarios[':password_hash'] = $password_hashed;
        }
        $sql_update_usuarios .= " WHERE id = :id_usuario";
        $stmt_update = $pdo->prepare($sql_update_usuarios);
        $stmt_update->execute($params_update_usuarios);

        // 2. Actualizar tabla de rol específica
        // Esta parte puede ser compleja si el rol cambia.
        // Por simplicidad V1: si el rol cambia, borramos de la tabla de rol anterior e insertamos en la nueva.
        // Si el rol no cambia, actualizamos la tabla de rol actual.

        $stmt_old_role = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = :id_usuario_actual_para_rol"); // Obtener el rol ANTES de esta actualización
        $stmt_old_role->execute([':id_usuario_actual_para_rol' => $id_usuario_a_editar]);
        $rol_antiguo_db = $stmt_old_role->fetchColumn();

        $tabla_rol_antigua = "";
        if ($rol_antiguo_db) {
            switch ($rol_antiguo_db) {
                case 'admin': $tabla_rol_antigua = "administradores"; break;
                case 'profesor': $tabla_rol_antigua = "profesores"; break;
                case 'estudiante': $tabla_rol_antigua = "estudiantes"; break;
            }
        }
        
        $tabla_rol_nueva = "";
        switch ($tipo_usuario_form) {
            case 'admin': $tabla_rol_nueva = "administradores"; break;
            case 'profesor': $tabla_rol_nueva = "profesores"; break;
            case 'estudiante': $tabla_rol_nueva = "estudiantes"; break;
        }

        if ($tabla_rol_antigua && $tabla_rol_antigua !== $tabla_rol_nueva) {
            // El rol cambió, eliminar de la tabla antigua
            $stmt_delete_old_role = $pdo->prepare("DELETE FROM {$tabla_rol_antigua} WHERE id = :id_usuario");
            $stmt_delete_old_role->execute([':id_usuario' => $id_usuario_a_editar]);
            // Luego se insertará en la nueva tabla de rol (ver más abajo)
        }

        // Preparar datos para insertar/actualizar en tabla de rol
        // (Debes añadir todos los campos aquí)
        $datos_para_rol_tabla = [
            'id' => $id_usuario_a_editar,
            'primer_nombre' => $primer_nombre,
            'segundo_nombre' => $segundo_nombre ?: null,
            'primer_apellido' => $primer_apellido,
            'segundo_apellido' => $segundo_apellido ?: null,
            'email' => $email_cuenta, // Usar el email de la cuenta o uno específico si lo tienes
            'telefono' => $_POST['telefono'] ?? null, // Ejemplo, añade todos tus campos
            // ... más campos comunes ...
        ];
        if ($tipo_usuario_form === 'estudiante') {
            $datos_para_rol_tabla = array_merge($datos_para_rol_tabla, $datos_rol_especificos_post);
            // Añadir campos que no vienen del POST pero sí de la tabla estudiantes
            $datos_para_rol_tabla['tipo_documento'] = $tipo_documento;
            $datos_para_rol_tabla['numero_documento'] = $numero_documento;
            // ... etc ...
        } elseif ($tipo_usuario_form === 'profesor') {
            $datos_para_rol_tabla = array_merge($datos_para_rol_tabla, $datos_rol_especificos_post);
            $datos_para_rol_tabla['tipo_documento'] = $tipo_documento;
            $datos_para_rol_tabla['numero_documento'] = $numero_documento;
            // ... etc ...
        }
        // Para admin, los campos son menos, solo los que están en la tabla 'administradores'

        // Construir la sentencia de UPDATE o INSERT para la tabla de rol
        $columnas_rol = array_keys($datos_para_rol_tabla);
        unset($columnas_rol[array_search('id', $columnas_rol)]); // Quitar 'id' para el SET
        $set_parts_rol = [];
        foreach ($columnas_rol as $col) {
            $set_parts_rol[] = "{$col} = :{$col}";
        }
        
        // Intentar UPDATE, si no afecta filas (porque el rol cambió o no existía), hacer INSERT
        $sql_update_rol = "UPDATE {$tabla_rol_nueva} SET " . implode(', ', $set_parts_rol) . " WHERE id = :id";
        $stmt_update_rol_table = $pdo->prepare($sql_update_rol);
        $stmt_update_rol_table->execute($datos_para_rol_tabla);

        if ($stmt_update_rol_table->rowCount() == 0) { // Si no se actualizó (ej. rol cambió o no existía el registro)
            $columnas_insert_rol = array_keys($datos_para_rol_tabla);
            $placeholders_insert_rol = array_map(function($col){ return ":".$col; }, $columnas_insert_rol);
            $sql_insert_rol = "INSERT INTO {$tabla_rol_nueva} (" . implode(', ', $columnas_insert_rol) . ") VALUES (" . implode(', ', $placeholders_insert_rol) . ")";
            $stmt_insert_rol_table = $pdo->prepare($sql_insert_rol);
            $stmt_insert_rol_table->execute($datos_para_rol_tabla);
        }
        $_SESSION['mensaje_usuario_accion'] = "¡Usuario (ID: $id_usuario_a_editar) actualizado exitosamente!";

    } else {
        // --- MODO CREACIÓN ---
        // 1. Insertar en tabla 'usuarios'
        $password_hashed = hash_password($clave_nueva ?: bin2hex(random_bytes(8))); // Generar pass si está vacío
        $stmt_insert_usuarios = $pdo->prepare("INSERT INTO usuarios (username, email, password_hash, tipo, activo) VALUES (:username, :email, :password_hash, :tipo, :activo)");
        $stmt_insert_usuarios->execute([
            ':username' => $username,
            ':email' => $email_cuenta,
            ':password_hash' => $password_hashed,
            ':tipo' => $tipo_usuario_form,
            ':activo' => $activo
        ]);
        $id_usuario_operacion = $pdo->lastInsertId();

        // 2. Insertar en tabla de rol específica
        $tabla_rol_nueva = "";
        $datos_para_rol_tabla = [
            'id' => $id_usuario_operacion,
            'primer_nombre' => $primer_nombre,
            'segundo_nombre' => $segundo_nombre ?: null,
            'primer_apellido' => $primer_apellido,
            'segundo_apellido' => $segundo_apellido ?: null,
            'email' => $email_cuenta, // El email de la tabla de rol puede ser el mismo que el de la cuenta
            'telefono' => $_POST['telefono'] ?? null,
            // ... añade todos los campos comunes y específicos del rol aquí
        ];

        if ($tipo_usuario_form === 'admin') {
            $tabla_rol_nueva = "administradores";
            // Solo campos de admin
        } elseif ($tipo_usuario_form === 'profesor') {
            $tabla_rol_nueva = "profesores";
            $datos_para_rol_tabla['tipo_documento'] = $tipo_documento;
            $datos_para_rol_tabla['numero_documento'] = $numero_documento;
            $datos_para_rol_tabla = array_merge($datos_para_rol_tabla, $datos_rol_especificos_post);
        } elseif ($tipo_usuario_form === 'estudiante') {
            $tabla_rol_nueva = "estudiantes";
            $datos_para_rol_tabla['tipo_documento'] = $tipo_documento;
            $datos_para_rol_tabla['numero_documento'] = $numero_documento;
            $datos_para_rol_tabla = array_merge($datos_para_rol_tabla, $datos_rol_especificos_post);
        }
        
        if ($tabla_rol_nueva) {
            $columnas_insert_rol = array_keys($datos_para_rol_tabla);
            $placeholders_insert_rol = array_map(function($col){ return ":".$col; }, $columnas_insert_rol);
            $sql_insert_rol = "INSERT INTO {$tabla_rol_nueva} (" . implode(', ', $columnas_insert_rol) . ") VALUES (" . implode(', ', $placeholders_insert_rol) . ")";
            $stmt_insert_rol_table = $pdo->prepare($sql_insert_rol);
            $stmt_insert_rol_table->execute($datos_para_rol_tabla);
        }
        $_SESSION['mensaje_usuario_accion'] = "¡Usuario creado exitosamente (ID: $id_usuario_operacion)!";
    }

    $pdo->commit();
    unset($_SESSION['form_data_usuario_admin']);
    unset($_SESSION['csrf_token']);

    $redirect_url = "../../index.php?vista=admin/editar_usuario";
    // if ($modo_edicion && $id_usuario_a_editar) {
    //     $redirect_url = "../../index.php?vista=admin/gestion_usuarios&id_usuario=" . $id_usuario_a_editar;
    // }
    header("Location: " . $redirect_url);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en procesamiento de formulario de usuario (Admin): " . $e->getMessage() . " - Datos: " . json_encode($_POST));
    $_SESSION['mensaje_usuario_accion'] = "Ocurrió un error al procesar el formulario del usuario. Por favor, inténtelo más tarde.";
    $_SESSION['form_data_usuario_admin'] = $_POST;
    $redirect_url = "../../index.php?vista=admin/gestion_usuarios";
    if ($modo_edicion && $id_usuario_a_editar) $redirect_url .= "&id_usuario=" . $id_usuario_a_editar;
    header("Location: " . $redirect_url);
    exit();
} finally {
    $pdo = null;
}
?>
