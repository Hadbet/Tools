<?php
header('Content-Type: application/json');
require_once 'db/db.php';

// --- NOTA: No se necesitaron cambios en este archivo ---
// Su lógica solo comprueba SI existen deudas (COUNT > 0),
// no le importa qué herramientas son o cuándo se entregaron.

if (!isset($_GET['nomina']) || empty($_GET['nomina'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Número de nómina no proporcionado.']);
    exit;
}

$id_nomina = intval($_GET['nomina']);
$response = [];

try {
    $conector = new ToolcribDB();
    $conexion = $conector->conectar();

    // 1. Obtener datos básicos del empleado
    $stmt_emp = $conexion->prepare("SELECT id_nomina, nombre FROM Empleados WHERE id_nomina = ?");
    $stmt_emp->bind_param("i", $id_nomina);
    $stmt_emp->execute();
    $result_emp = $stmt_emp->get_result();

    if ($result_emp->num_rows === 0) {
        throw new Exception('Empleado no encontrado con esa nómina.');
    }
    $empleado = $result_emp->fetch_assoc();
    $response['nomina'] = $empleado['id_nomina'];
    $response['nombre'] = $empleado['nombre'];
    $stmt_emp->close();


    // 2. Determinar si es deudor
    $stmtDeudor = $conexion->prepare("
        SELECT COUNT(p.id_prestamo) as total_prestamos
        FROM Prestamos p
        JOIN Herramientas h ON p.id_herramienta = h.id_herramienta
        WHERE p.id_nomina = ? AND h.nombre <> 'Total' AND p.cantidad > 0
    ");
    $stmtDeudor->bind_param("i", $id_nomina);
    $stmtDeudor->execute();
    $resultDeudor = $stmtDeudor->get_result();
    $deudas = $resultDeudor->fetch_assoc();

    $response['estado'] = ($deudas['total_prestamos'] > 0) ? 'Deudor' : 'No Deudor';
    $stmtDeudor->close();

    $conexion->close();

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>