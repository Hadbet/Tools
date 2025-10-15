<?php
header('Content-Type: application/json');
require_once 'db/db.php';

try {
    // Se usa el nombre de la clase correcto para la conexión.
    $conector = new ToolcribDB();
    $conexion = $conector->conectar();

    // CONSULTA OPTIMIZADA:
    // Se combina la obtención de empleados y la verificación de deudas en una sola consulta
    // usando LEFT JOIN y un CASE para determinar el estado. Esto es mucho más eficiente.
    $stmt = $conexion->prepare("
        SELECT
            e.id_nomina AS nomina,
            e.nombre,
            CASE
                WHEN COUNT(DISTINCT CASE WHEN h.nombre <> 'Total' THEN p.id_prestamo END) > 0 THEN 'Deudor'
                ELSE 'No Deudor'
            END AS estado
        FROM
            Empleados e
        LEFT JOIN
            Prestamos p ON e.id_nomina = p.id_nomina
        LEFT JOIN
            Herramientas h ON p.id_herramienta = h.id_herramienta
        GROUP BY
            e.id_nomina, e.nombre
        ORDER BY
            e.nombre ASC
    ");

    $stmt->execute();
    $resultado = $stmt->get_result();

    // Se obtienen todos los resultados directamente en un array.
    $empleados = $resultado->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conexion->close();

    echo json_encode($empleados);

} catch (Exception $e) {
    // Se envía una respuesta JSON detallada en caso de error.
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>

