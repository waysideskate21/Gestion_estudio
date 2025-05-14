<?php

# Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_gestion_educativa'); // Asegúrate que este sea el nombre correcto de tu BD
define('DB_USER', 'root'); // Cambia esto por tu usuario de BD
define('DB_PASS', '');     // Cambia esto por tu contraseña de BD
define('DB_CHARSET', 'utf8mb4');

# Conexión a la base de datos con PDO
function conexion() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos por defecto
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva la emulación de sentencias preparadas para mayor seguridad
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // En un entorno de producción, no mostrarías $e->getMessage() directamente al usuario.
        // Lo registrarías en un archivo de log y mostrarías un mensaje genérico.
        error_log("Error de conexión PDO: " . $e->getMessage());
        die("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
    }
}

# Limpiar cadenas para prevenir inyecciones de HTML o JS (XSS)
function limpiar_cadena($cadena) {
    if (is_array($cadena)) {
        // Si es un array, limpiar cada elemento recursivamente
        return array_map('limpiar_cadena', $cadena);
    }
    // Trim quita espacios al inicio/final
    $cadena = trim($cadena);
    // stripslashes elimina las barras invertidas que PHP podría haber añadido automáticamente (si magic_quotes_gpc está activado, aunque está obsoleto)
    $cadena = stripslashes($cadena);
    // htmlspecialchars convierte caracteres especiales en entidades HTML.
    // ENT_QUOTES convierte tanto comillas simples como dobles.
    // ENT_SUBSTITUTE reemplaza caracteres inválidos para el charset con un carácter de reemplazo Unicode.
    $cadena = htmlspecialchars($cadena, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $cadena;
}

# Validar formato de datos con una expresión regular
# Devuelve true si la cadena NO CUMPLE con el filtro, false si CUMPLE.
function verificar_datos($filtro, $cadena) {
    if (preg_match("/^" . $filtro . "$/", $cadena)) {
        return false; // La cadena cumple con el filtro
    } else {
        return true; // La cadena NO cumple con el filtro
    }
}

# Validar email correctamente
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

# Validar números enteros
function validar_entero($numero, $opciones = []) {
    return filter_var($numero, FILTER_VALIDATE_INT, $opciones);
}

# Validar números decimales/flotantes
function validar_decimal($numero, $opciones = []) {
    return filter_var($numero, FILTER_VALIDATE_FLOAT, $opciones);
}

# Función para hashear contraseñas
function hash_password($password) {
    // PASSWORD_BCRYPT es el algoritmo recomendado actualmente.
    // El costo (cost) determina cuántos recursos se dedican al hashing. Un valor más alto es más seguro pero más lento.
    // El valor por defecto es 10, pero 12 es una buena opción para mayor seguridad si el servidor lo soporta bien.
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

# Función para verificar autenticación
function verificar_auth() {
    // Asegurarse de que la sesión esté iniciada (aunque session_start.php debería encargarse)
    if (session_status() == PHP_SESSION_NONE) {
        // Esto es un fallback, idealmente session_start.php ya lo hizo.
        require_once __DIR__ . "/../inc/session_start.php";
    }

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Si no está logueado, redirigir a la página de login
        // Es importante usar una ruta absoluta o una construcción de URL robusta
        // Asumiendo que index.php está en la raíz del proyecto.
        header("Location: index.php?vista=login");
        exit();
    }
}

# Función para verificar roles
# $roles_permitidos debe ser un array de strings con los roles permitidos.
function verificar_rol($roles_permitidos = []) {
    verificar_auth(); // Primero, asegurar que el usuario esté autenticado

    if (empty($roles_permitidos)) {
        return true; // Si no se especifican roles, solo se requiere autenticación
    }

    if (!isset($_SESSION['tipo_usuario'])) {
        // Si no hay rol definido en la sesión, denegar acceso y redirigir
        // Podrías redirigir a una página de error o a home.
        header("Location: index.php?vista=home&error=rol_no_definido");
        exit();
    }

    if (!in_array($_SESSION['tipo_usuario'], $roles_permitidos)) {
        // Si el rol del usuario no está en la lista de roles permitidos, denegar acceso
        header("Location: index.php?vista=home&error=acceso_denegado"); // O una página específica de "acceso denegado"
        exit();
    }
    return true; // El usuario tiene el rol permitido
}


# Renombrar nombre de imagen para evitar problemas al subir (ejemplo, si implementas subida de archivos)
function renombrar_fotos($nombre_foto) {
    // Eliminar acentos y caracteres especiales comunes
    $busca = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', ' ', '/', '#', '-', '$', '.', ','];
    $reemplaza = ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N', '_', '_', '_', '_', '_', '_', '_'];
    $nombre_foto_limpio = str_replace($busca, $reemplaza, $nombre_foto);

    // Obtener extensión del archivo
    $extension = pathinfo($nombre_foto, PATHINFO_EXTENSION);
    $nombre_base = pathinfo($nombre_foto_limpio, PATHINFO_FILENAME);

    // Agregar sufijo aleatorio para evitar duplicados y convertir a minúsculas
    $nombre_final = strtolower($nombre_base) . "_" . uniqid() . "." . strtolower($extension);
    
    return $nombre_final;
}

?>
