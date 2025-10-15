<?php
header('Content-Type: application/json');
require_once 'db/db.php';

try {
    $conector = new LocalConector();
    $conexion = $conector->conectar();

    // Consulta para obtener todos los empleados únicos de la tabla de préstamos
    $stmt = $conexion->prepare(
        "SELECT DISTINCT e.nomina, e.nombre 
         FROM Empleados e"
    );
    $stmt->execute();
    $resultado = $stmt->get_result();

    $empleados = [];
    while ($fila = $resultado->fetch_assoc()) {
        $empleados[$fila['nomina']] = [
            'nomina' => $fila['nomina'],
            'nombre' => $fila['nombre'],
            'estado' => 'No Deudor' // Por defecto, no son deudores
        ];
    }
    $stmt->close();

    // Consulta para verificar quiénes tienen préstamos (son deudores)
    // Se excluyen las herramientas llamadas 'Total' para determinar el estado
    $stmtDeudores = $conexion->prepare(
        "SELECT DISTINCT p.nomina_empleado 
         FROM Prestamos p
         JOIN Herramientas h ON p.id_herramienta = h.id
         WHERE h.nombre <> 'Total'"
    );
    $stmtDeudores->execute();
    $resultadoDeudores = $stmtDeudores->get_result();

    while ($filaDeudor = $resultadoDeudores->fetch_assoc()) {
        if (isset($empleados[$filaDeudor['nomina_empleado']])) {
            $empleados[$filaDeudor['nomina_empleado']]['estado'] = 'Deudor';
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