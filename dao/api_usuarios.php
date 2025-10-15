<?php
session_start();
/*
if (!isset($_SESSION['IdUsuario'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}*/

header('Content-Type: application/json');
require_once 'db/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conector = new ToolcribDB();
$conexion = $conector->conectar();
$response = ['success' => false, 'message' => ''];

switch ($method) {
    case 'GET':
        // Obtener todos los usuarios
        $resultado = $conexion->query("SELECT IdUsuario, Nombre, Rol, Estado FROM Usuarios ORDER BY Nombre");
        $usuarios = $resultado->fetch_all(MYSQLI_ASSOC);
        echo json_encode($usuarios);
        break;

    case 'POST':
        // Crear un nuevo usuario
        $data = json_decode(file_get_contents('php://input'), true);
        $hashedPassword = password_hash($data['Password'], PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("INSERT INTO Usuarios (IdUsuario, Nombre, Rol, Password, Estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $data['IdUsuario'], $data['Nombre'], $data['Rol'], $hashedPassword, $data['Estado']);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Usuario creado exitosamente.';
        } else {
            $response['message'] = 'Error al crear el usuario: ' . $stmt->error;
        }
        $stmt->close();
        echo json_encode($response);
        break;

    case 'PUT':
        // Actualizar contraseña o estado
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['Password'])) { // Actualizar contraseña
            $hashedPassword = password_hash($data['Password'], PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE Usuarios SET Password = ? WHERE IdUsuario = ?");
            $stmt->bind_param("ss", $hashedPassword, $data['IdUsuario']);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Contraseña actualizada.';
            } else {
                $response['message'] = 'Error al actualizar contraseña.';
            }
        } elseif (isset($data['Estado'])) { // Actualizar estado
            $stmt = $conexion->prepare("UPDATE Usuarios SET Estado = ? WHERE IdUsuario = ?");
            $stmt->bind_param("is", $data['Estado'], $data['IdUsuario']);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Estado actualizado.';
            } else {
                $response['message'] = 'Error al actualizar estado.';
            }
        }
        $stmt->close();
        echo json_encode($response);
        break;
}

$conexion->close();
?>
