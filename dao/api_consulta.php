<?php
// api_consulta.php
header('Content-Type: application/json');
include_once('db/db.php'); // Ajustada la ruta de inclusión

$response = [];

if (!isset($_GET['nomina']) || empty($_GET['nomina'])) {
    http_response_code(400);
    $response['error'] = 'Número de nómina no proporcionado.';
    echo json_encode($response);
    exit;
}

$id_nomina = intval($_GET['nomina']);

try {
    $con = new ToolcribDB();
    $conex = $con->conectar();

    // 1. Obtener datos del empleado
    $stmt_emp = $conex->prepare("SELECT nombre, departamento FROM Empleados WHERE id_nomina = ?");
    $stmt_emp->bind_param("i", $id_nomina);
    $stmt_emp->execute();
    $result_emp = $stmt_emp->get_result();

    if ($result_emp->num_rows > 0) {
        $response['empleado'] = $result_emp->fetch_assoc();
    } else {
        $response['error'] = 'Empleado no encontrado.';
        echo json_encode($response);
        $conex->close();
        exit;
    }
    $stmt_emp->close();

    // 2. Obtener préstamos de herramientas (SE AÑADE p.fecha_prestamo)
    $stmt = $conex->prepare(
        "SELECT h.nombre AS herramienta, p.cantidad, h.costo, p.fecha_prestamo 
         FROM Prestamos p
         JOIN Herramientas h ON p.id_herramienta = h.id_herramienta
         WHERE p.id_nomina = ?"
    );
    $stmt->bind_param("i", $id_nomina);
    $stmt->execute();
    $result = $stmt->get_result();

    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }

    $response['prestamos'] = $prestamos;

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = 'Error en el servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>