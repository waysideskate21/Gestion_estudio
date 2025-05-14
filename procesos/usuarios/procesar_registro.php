<?php
// procesos/usuarios/procesar_registro.php

require_once __DIR__ . "/../../inc/session_start.php";
require_once __DIR__ . "/../../php/main.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_error_registro'] = "Error: Solicitud no válida.";
    header("Location: ../../index.php?vista=registrar_usuario");
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_error_registro'] = "Error: Token de seguridad inválido.";
    header("Location: ../../index.php?vista=registrar_usuario");
    exit();
}

// --- Recoger y Sanear Datos de la Cuenta ---
$username = limpiar_cadena($_POST['usuario'] ?? '');
$email = limpiar_cadena($_POST['email'] ?? '');
$clave = $_POST['clave'] ?? '';
$clave_confirmacion = $_POST['clave_confirmacion'] ?? '';
$tipo_usuario = limpiar_cadena($_POST['tipo_usuario'] ?? '');

// --- Recoger y Sanear Datos Personales Comunes ---
$primer_nombre = limpiar_cadena($_POST['primer_nombre'] ?? '');
$segundo_nombre = limpiar_cadena($_POST['segundo_nombre'] ?? ''); // Opcional
$primer_apellido = limpiar_cadena($_POST['primer_apellido'] ?? '');
$segundo_apellido = limpiar_cadena($_POST['segundo_apellido'] ?? ''); // Opcional
$tipo_documento = limpiar_cadena($_POST['tipo_documento'] ?? '');
$numero_documento = limpiar_cadena($_POST['numero_documento'] ?? '');
$fecha_expedicion_documento = limpiar_cadena($_POST['fecha_expedicion_documento'] ?? ''); // Opcional
$lugar_expedicion_documento = limpiar_cadena($_POST['lugar_expedicion_documento'] ?? ''); // Opcional
$fecha_nacimiento = limpiar_cadena($_POST['fecha_nacimiento'] ?? ''); // Opcional
$genero = limpiar_cadena($_POST['genero'] ?? ''); // Opcional
$pais_nacimiento = limpiar_cadena($_POST['pais_nacimiento'] ?? ''); // Opcional
$departamento_nacimiento = limpiar_cadena($_POST['departamento_nacimiento'] ?? ''); // Opcional
$ciudad_nacimiento = limpiar_cadena($_POST['ciudad_nacimiento'] ?? ''); // Opcional
$nacionalidad = limpiar_cadena($_POST['nacionalidad'] ?? ''); // Opcional
$direccion = limpiar_cadena($_POST['direccion'] ?? ''); // Opcional
$telefono = limpiar_cadena($_POST['telefono'] ?? ''); // Opcional
$estado_civil = limpiar_cadena($_POST['estado_civil'] ?? ''); // Opcional

// --- Recoger y Sanear Datos de Salud (Opcionales) ---
$tipo_sangre_rh = limpiar_cadena($_POST['tipo_sangre_rh'] ?? '');
$eps = limpiar_cadena($_POST['eps'] ?? '');

// --- Recoger y Sanear Contacto de Emergencia (Opcionales) ---
$contacto_emergencia_nombre = limpiar_cadena($_POST['contacto_emergencia_nombre'] ?? '');
$contacto_emergencia_telefono = limpiar_cadena($_POST['contacto_emergencia_telefono'] ?? '');
$contacto_emergencia_parentesco = limpiar_cadena($_POST['contacto_emergencia_parentesco'] ?? '');

// --- Recoger y Sanear Datos Específicos del Rol ---
$semestre = ($tipo_usuario === 'estudiante') ? limpiar_cadena($_POST['semestre'] ?? '') : null;
$carrera = ($tipo_usuario === 'estudiante') ? limpiar_cadena($_POST['carrera'] ?? '') : null;
$fecha_ingreso_str = ($tipo_usuario === 'estudiante') ? limpiar_cadena($_POST['fecha_ingreso'] ?? '') : null;

$especialidad = ($tipo_usuario === 'profesor') ? limpiar_cadena($_POST['especialidad'] ?? '') : null;
$departamento = ($tipo_usuario === 'profesor') ? limpiar_cadena($_POST['departamento'] ?? '') : null;
$fecha_contratacion_str = ($tipo_usuario === 'profesor') ? limpiar_cadena($_POST['fecha_contratacion'] ?? '') : null;

// --- Validaciones del Lado del Servidor ---
$errores = [];

// Cuenta
if (empty($username) || verificar_datos("^[a-zA-Z0-9_]{4,20}$", $username)) $errores[] = "Usuario inválido (4-20 caracteres, letras, números, _).";
if (empty($email) || !validar_email($email)) $errores[] = "Correo electrónico inválido.";
if (empty($clave) || strlen($clave) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres.";
if ($clave !== $clave_confirmacion) $errores[] = "Las contraseñas no coinciden.";
$tipos_validos = ['estudiante', 'profesor'];
if (empty($tipo_usuario) || !in_array($tipo_usuario, $tipos_validos)) $errores[] = "Tipo de usuario inválido.";

// Personales (solo los obligatorios)
if (empty($primer_nombre) || verificar_datos("^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$", $primer_nombre)) $errores[] = "Primer nombre inválido.";
if (empty($primer_apellido) || verificar_datos("^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$", $primer_apellido)) $errores[] = "Primer apellido inválido.";
if (empty($tipo_documento) || !in_array($tipo_documento, ['CC', 'TI', 'CE', 'Pasaporte', 'Otro'])) $errores[] = "Tipo de documento inválido.";
if (empty($numero_documento) || verificar_datos("^[a-zA-Z0-9-]{5,20}$", $numero_documento)) $errores[] = "Número de documento inválido.";

// Validar formatos de fecha (si se proporcionaron)
if (!empty($fecha_expedicion_documento) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_expedicion_documento)) $errores[] = "Formato de fecha de expedición inválido (AAAA-MM-DD).";
if (!empty($fecha_nacimiento) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_nacimiento)) $errores[] = "Formato de fecha de nacimiento inválido (AAAA-MM-DD).";

// Validaciones específicas de rol (solo los obligatorios)
if ($tipo_usuario === 'estudiante') {
    if (empty($semestre) || !validar_entero($semestre, ['options' => ['min_range' => 1, 'max_range' => 20]])) $errores[] = "Semestre de estudiante inválido.";
    if (empty($carrera) || verificar_datos("^[a-zA-Z0-9\sÁÉÍÓÚáéíóúÑñ.,-]{3,100}$", $carrera)) $errores[] = "Carrera de estudiante inválida.";
    if (empty($fecha_ingreso_str) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_ingreso_str)) $errores[] = "Fecha de ingreso de estudiante inválida (AAAA-MM-DD).";
}
if ($tipo_usuario === 'profesor') {
    if (empty($especialidad) || verificar_datos("^[a-zA-Z0-9\sÁÉÍÓÚáéíóúÑñ.,-]{3,100}$", $especialidad)) $errores[] = "Especialidad de profesor inválida.";
    if (empty($departamento) || verificar_datos("^[a-zA-Z0-9\sÁÉÍÓÚáéíóúÑñ.,-]{3,100}$", $departamento)) $errores[] = "Departamento de profesor inválido.";
    if (empty($fecha_contratacion_str) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_contratacion_str)) $errores[] = "Fecha de contratación de profesor inválida (AAAA-MM-DD).";
}

// Convertir fechas vacías a NULL para la BD
$fecha_expedicion_documento = empty($fecha_expedicion_documento) ? null : $fecha_expedicion_documento;
$fecha_nacimiento = empty($fecha_nacimiento) ? null : $fecha_nacimiento;
$fecha_ingreso_str = ($tipo_usuario === 'estudiante' && empty($fecha_ingreso_str)) ? null : $fecha_ingreso_str;
$fecha_contratacion_str = ($tipo_usuario === 'profesor' && empty($fecha_contratacion_str)) ? null : $fecha_contratacion_str;


if (!empty($errores)) {
    $_SESSION['mensaje_error_registro'] = implode("<br>", $errores);
    $_SESSION['form_data_registro'] = $_POST; 
    header("Location: ../../index.php?vista=registrar_usuario");
    exit();
}

$pdo = conexion();
try {
    $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE username = :username OR email = :email UNION SELECT id FROM estudiantes WHERE numero_documento = :numero_documento UNION SELECT id FROM profesores WHERE numero_documento = :numero_documento_prof");
    $stmt_check->bindParam(':username', $username);
    $stmt_check->bindParam(':email', $email);
    $stmt_check->bindParam(':numero_documento', $numero_documento);
    $stmt_check->bindParam(':numero_documento_prof', $numero_documento);
    $stmt_check->execute();

    if ($stmt_check->fetch()) {
        $_SESSION['mensaje_error_registro'] = "El nombre de usuario, correo electrónico o número de documento ya están registrados.";
        $_SESSION['form_data_registro'] = $_POST;
        header("Location: ../../index.php?vista=registrar_usuario");
        exit();
    }

    $pdo->beginTransaction();
    $password_hashed = hash_password($clave);

    $stmt_usuarios = $pdo->prepare("INSERT INTO usuarios (username, password_hash, tipo, email) VALUES (:username, :password_hash, :tipo, :email)");
    $stmt_usuarios->execute([
        ':username' => $username,
        ':password_hash' => $password_hashed,
        ':tipo' => $tipo_usuario,
        ':email' => $email
    ]);
    $id_usuario = $pdo->lastInsertId();

    if ($tipo_usuario === 'estudiante') {
        $stmt_rol = $pdo->prepare("INSERT INTO estudiantes 
            (id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, numero_documento, fecha_expedicion_documento, lugar_expedicion_documento, genero, fecha_nacimiento, pais_nacimiento, departamento_nacimiento, ciudad_nacimiento, nacionalidad, direccion, telefono, email, tipo_sangre_rh, eps, estado_civil, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_parentesco, semestre, carrera, fecha_ingreso) 
            VALUES (:id, :pn, :sn, :pa, :sa, :td, :nd, :fed, :led, :gen, :fn, :pna, :dna, :cna, :nac, :dir, :tel, :em, :tsr, :eps, :ec, :cen, :cet, :cep, :sem, :car, :fi)");
        $stmt_rol->execute([
            ':id' => $id_usuario, ':pn' => $primer_nombre, ':sn' => $segundo_nombre ?: null, ':pa' => $primer_apellido, ':sa' => $segundo_apellido ?: null,
            ':td' => $tipo_documento, ':nd' => $numero_documento, ':fed' => $fecha_expedicion_documento, ':led' => $lugar_expedicion_documento ?: null,
            ':gen' => $genero ?: null, ':fn' => $fecha_nacimiento, ':pna' => $pais_nacimiento ?: null, ':dna' => $departamento_nacimiento ?: null, ':cna' => $ciudad_nacimiento ?: null,
            ':nac' => $nacionalidad ?: null, ':dir' => $direccion ?: null, ':tel' => $telefono ?: null, ':em' => $email, /* email de la tabla usuarios */
            ':tsr' => $tipo_sangre_rh ?: null, ':eps' => $eps ?: null, ':ec' => $estado_civil ?: null,
            ':cen' => $contacto_emergencia_nombre ?: null, ':cet' => $contacto_emergencia_telefono ?: null, ':cep' => $contacto_emergencia_parentesco ?: null,
            ':sem' => $semestre, ':car' => $carrera, ':fi' => $fecha_ingreso_str
        ]);
    } elseif ($tipo_usuario === 'profesor') {
        $stmt_rol = $pdo->prepare("INSERT INTO profesores 
            (id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, numero_documento, fecha_expedicion_documento, lugar_expedicion_documento, genero, fecha_nacimiento, pais_nacimiento, departamento_nacimiento, ciudad_nacimiento, nacionalidad, direccion, telefono, email, tipo_sangre_rh, eps, estado_civil, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_parentesco, especialidad, departamento, fecha_contratacion) 
            VALUES (:id, :pn, :sn, :pa, :sa, :td, :nd, :fed, :led, :gen, :fn, :pna, :dna, :cna, :nac, :dir, :tel, :em, :tsr, :eps, :ec, :cen, :cet, :cep, :esp, :dep, :fc)");
        $stmt_rol->execute([
            ':id' => $id_usuario, ':pn' => $primer_nombre, ':sn' => $segundo_nombre ?: null, ':pa' => $primer_apellido, ':sa' => $segundo_apellido ?: null,
            ':td' => $tipo_documento, ':nd' => $numero_documento, ':fed' => $fecha_expedicion_documento, ':led' => $lugar_expedicion_documento ?: null,
            ':gen' => $genero ?: null, ':fn' => $fecha_nacimiento, ':pna' => $pais_nacimiento ?: null, ':dna' => $departamento_nacimiento ?: null, ':cna' => $ciudad_nacimiento ?: null,
            ':nac' => $nacionalidad ?: null, ':dir' => $direccion ?: null, ':tel' => $telefono ?: null, ':em' => $email, /* email de la tabla usuarios */
            ':tsr' => $tipo_sangre_rh ?: null, ':eps' => $eps ?: null, ':ec' => $estado_civil ?: null,
            ':cen' => $contacto_emergencia_nombre ?: null, ':cet' => $contacto_emergencia_telefono ?: null, ':cep' => $contacto_emergencia_parentesco ?: null,
            ':esp' => $especialidad, ':dep' => $departamento, ':fc' => $fecha_contratacion_str
        ]);
    }

    $pdo->commit();
    unset($_SESSION['form_data_registro']);
    unset($_SESSION['csrf_token']);
    $_SESSION['mensaje_exito_registro'] = "¡Registro exitoso! Ahora puedes iniciar sesión.";
    header("Location: ../../index.php?vista=login");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en registro de usuario: " . $e->getMessage() . " - Datos: " . json_encode($_POST));
    $_SESSION['mensaje_error_registro'] = "Ocurrió un error durante el registro (Ref: DB). Por favor, inténtelo más tarde.";
    $_SESSION['form_data_registro'] = $_POST;
    header("Location: ../../index.php?vista=registrar_usuario");
    exit();
} finally {
    $pdo = null;
}
?>
