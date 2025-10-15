<?php
header('Content-Type: application/json');
require_once 'db/db.php';

try {
    $conector = new LocalConector();
    $conexion = $conector->conectar();

    // Consulta para obtener todos los empleados de la tabla Empleados
    $stmt = $conexion->prepare(
        "SELECT id_nomina, nombre FROM Empleados"
    );
    $stmt->execute();
    $resultado = $stmt->get_result();

    $empleados = [];
    while ($fila = $resultado->fetch_assoc()) {
        $empleados[$fila['id_nomina']] = [
            'nomina' => $fila['id_nomina'],
            'nombre' => $fila['nombre'],
            'estado' => 'No Deudor' // Por defecto, no son deudores
        ];
    }
    $stmt->close();

    // Consulta para verificar quiénes tienen préstamos (son deudores)
    // Se excluyen las herramientas llamadas 'Total' para determinar el estado
    $stmtDeudores = $conexion->prepare(
        "SELECT DISTINCT p.id_nomina 
         FROM Prestamos p
         JOIN Herramientas h ON p.id_herramienta = h.id_herramienta
         WHERE h.nombre <> 'Total'"
    );
    $stmtDeudores->execute();
    $resultadoDeudores = $stmtDeudores->get_result();

    while ($filaDeudor = $resultadoDeudores->fetch_assoc()) {
        if (isset($empleados[$filaDeudor['id_nomina']])) {
            $empleados[$filaDeudor['id_nomina']]['estado'] = 'Deudor';
        }
    }
    $stmtDeudores->close();

    $conexion->close();

    // Convertir el array asociativo a un array indexado para el JSON
    echo json_encode(array_values($empleados));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>

