<?php
// generar_hash_admin.php
require './php/main.php'; // Asegúrate que la ruta a main.php sea correcta

$contrasena_admin = 'admini'; // Elige una contraseña segura
echo "Contraseña: " . $contrasena_admin . "<br>";
echo "Hash: " . hash_password($contrasena_admin);
?>