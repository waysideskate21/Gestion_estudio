<?php

# Conexión a la base de datos con PDO
function conexion()
{
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=sistema_gestion_educativa;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Evita emulación de sentencias
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

# Validar formato de datos con una expresión regular
function verificar_datos($filtro, $cadena)
{
    return !preg_match("/^" . $filtro . "$/", $cadena);
}

# Limpiar cadenas para prevenir inyecciones de HTML o JS
function limpiar_cadena($cadena)
{
    $cadena = trim($cadena); // Quita espacios al inicio/final
    $cadena = stripslashes($cadena); // Elimina barras invertidas
    $cadena = htmlspecialchars($cadena, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // Evita XSS
    return $cadena;
}

# Sanitizar entrada directamente
function sanitize_input($input)
{
    return htmlspecialchars(trim(stripslashes($input)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

# Validar email correctamente
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

# Validar números enteros
function validate_int($numero)
{
    return filter_var($numero, FILTER_VALIDATE_INT);
}

# Renombrar nombre de imagen para evitar problemas al subir
function renombrar_fotos($nombre_foto)
{
    // Eliminar acentos y caracteres especiales comunes
    $busca = ['á', 'é', 'í', 'ó', 'ú', ' ', '/', '#', '-', '$', '.', ','];
    $reemplaza = ['a', 'e', 'i', 'o', 'u', '_', '_', '_', '_', '_', '_', '_'];
    $nombre_foto = str_replace($busca, $reemplaza, $nombre_foto);

    // Agregar sufijo aleatorio para evitar duplicados
    $nombre_foto = strtolower($nombre_foto) . "_" . rand(1000, 9999);
    
    return $nombre_foto;
}

// ... (las funciones existentes)

# Función para verificar autenticación
function verificar_auth() {
    if (!isset($_SESSION['loggedin'])) {
        header("Location: index.php?vista=login");
        exit();
    }
}

# Función para verificar roles
function verificar_rol($roles_permitidos = []) {
    verificar_auth();
    if (!empty($roles_permitidos)) {
        if (!in_array($_SESSION['rol'], $roles_permitidos)) {
            header("Location: index.php?vista=home");
            exit();
        }
    }
}

# Función para hashear contraseñas
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}