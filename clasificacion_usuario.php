<?php
include 'db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo'];
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];

    // Insertar en tabla usuarios
    $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash, tipo) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $tipo);
    
    if ($stmt->execute()) {
        $id_usuario = $conn->insert_id;

        // Insertar en tabla según el tipo
        switch ($tipo) {
            case 'estudiante':
                $stmt2 = $conn->prepare("INSERT INTO estudiantes (id, nombre_completo, email, telefono, semestre, carrera, fecha_ingreso) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $semestre = $_POST['semestre'];
                $carrera = $_POST['carrera'];
                $fecha_ingreso = $_POST['fecha_ingreso'];
                $stmt2->bind_param("isssiss", $id_usuario, $nombre, $email, $telefono, $semestre, $carrera, $fecha_ingreso);
                break;

            case 'profesor':
                $stmt2 = $conn->prepare("INSERT INTO profesores (id, nombre_completo, email, telefono, especialidad, departamento, fecha_contratacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $especialidad = $_POST['especialidad'];
                $departamento = $_POST['departamento'];
                $fecha_contratacion = $_POST['fecha_contratacion'];
                $stmt2->bind_param("issssss", $id_usuario, $nombre, $email, $telefono, $especialidad, $departamento, $fecha_contratacion);
                break;

            case 'admin':
                $stmt2 = $conn->prepare("INSERT INTO administradores (id, nombre_completo, email, telefono) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("isss", $id_usuario, $nombre, $email, $telefono);
                break;
        }

        if ($stmt2->execute()) {
            $msg = "✅ Usuario registrado correctamente.";
        } else {
            $msg = "❌ Error al registrar detalles: " . $stmt2->error;
        }

    } else {
        $msg = "❌ Error al registrar usuario: " . $stmt->error;
    }
}
?>
