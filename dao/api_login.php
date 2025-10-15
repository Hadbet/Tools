<?php
session_start();
header('Content-Type: application/json');
require_once 'db/db.php';

$response = ['success' => false, 'message' => ''];

if (empty($_POST['IdUsuario']) || empty($_POST['Password'])) {
    $response['message'] = 'Usuario y contraseña son requeridos.';
    echo json_encode($response);
    exit;
}

$idUsuario = $_POST['IdUsuario'];
$password = $_POST['Password'];

try {
    $conector = new ToolcribDB();
    $conexion = $conector->conectar();

    // Prepara la consulta para evitar inyección SQL
    $stmt = $conexion->prepare("SELECT IdUsuario, Nombre, Rol, Password, Estado FROM Usuarios WHERE IdUsuario = ?");
    $stmt->bind_param("s", $idUsuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificar si el usuario está activo
        if ($usuario['Estado'] == 1) {
            // Verificar la contraseña
            if (password_verify($password, $usuario['Password'])) {
                // Credenciales correctas, iniciar sesión
                $_SESSION['IdUsuario'] = $usuario['IdUsuario'];
                $_SESSION['Nombre'] = $usuario['Nombre'];
                $_SESSION['Rol'] = $usuario['Rol'];

                $response['success'] = true;
            } else {
                $response['message'] = 'Contraseña incorrecta.';
            }
        } else {
            $response['message'] = 'Este usuario se encuentra inactivo.';
        }
    } else {
        $response['message'] = 'Usuario no encontrado.';
    }

    $stmt->close();
    $conexion->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
