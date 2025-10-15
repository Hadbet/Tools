<?php
header('Content-Type: application/json');
include_once('db/db.php');

try {
    $conector = new ToolcribDB();
    $conexion = $conector->conectar();

    // --- ENFOQUE OPTIMIZADO DE DOS PASOS ---

    // PASO 1: Obtener una lista rápida de todos los IDs de los deudores.
    // Esta consulta es muy ligera y rápida.
    $stmtDeudores = $conexion->prepare("
        SELECT DISTINCT p.id_nomina
        FROM Prestamos p
        JOIN Herramientas h ON p.id_herramienta = h.id_herramienta
        WHERE h.nombre <> 'Total' AND p.cantidad > 0
    ");
    $stmtDeudores->execute();
    $resultadoDeudores = $stmtDeudores->get_result();

    // Crear un array asociativo para una búsqueda ultra-rápida (O(1)).
    $listaDeudores = [];
    while ($fila = $resultadoDeudores->fetch_assoc()) {
        $listaDeudores[$fila['id_nomina']] = true;
    }
    $stmtDeudores->close();

    // PASO 2: Obtener la lista completa de empleados.
    // Esta consulta también es muy simple y rápida.
    $stmtEmpleados = $conexion->prepare("
        SELECT id_nomina AS nomina, nombre
        FROM Empleados
        ORDER BY nombre ASC
    ");
    $stmtEmpleados->execute();
    $resultadoEmpleados = $stmtEmpleados->get_result();

    $empleados = [];
    while ($fila = $resultadoEmpleados->fetch_assoc()) {
        // Combinar los datos en PHP, lo cual es muy eficiente.
        // Se verifica si la nómina existe en la lista de deudores.
        $fila['estado'] = isset($listaDeudores[$fila['nomina']]) ? 'Deudor' : 'No Deudor';
        $empleados[] = $fila;
    }
    $stmtEmpleados->close();

    $conexion->close();

    echo json_encode($empleados);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>

