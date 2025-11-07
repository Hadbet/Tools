<?php
// api_cargar.php
header('Content-Type: application/json');
include_once('db/db.php');

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

// Las claves 'tools' y 'employeeData' deben coincidir con el payload de JS
if (empty($input) || !isset($input['tools']) || !isset($input['employeeData'])) {
    $response['message'] = 'Datos incompletos o en formato incorrecto.';
    echo json_encode($response);
    exit;
}

$con = new ToolcribDB();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 1. Insertar o actualizar herramientas
    $stmt_tool = $conex->prepare("INSERT INTO Herramientas (nombre, costo) VALUES (?, ?) ON DUPLICATE KEY UPDATE costo = VALUES(costo)");
    $tool_ids = [];
    foreach ($input['tools'] as $tool) {
        $stmt_tool->bind_param("sd", $tool['name'], $tool['cost']);
        $stmt_tool->execute();

        // Obtenemos el ID de la herramienta para usarlo en los préstamos
        $query = "SELECT id_herramienta FROM Herramientas WHERE nombre = '" . $conex->real_escape_string($tool['name']) . "'";
        $result = $conex->query($query);
        if ($result && $result->num_rows > 0) {
            $tool_ids[$tool['name']] = $result->fetch_assoc()['id_herramienta'];
        }
    }
    $stmt_tool->close();

    // 2. Insertar o actualizar empleados y sus préstamos
    $stmt_emp = $conex->prepare("INSERT INTO Empleados (id_nomina, nombre, departamento, fecha_ingreso) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), departamento = VALUES(departamento), fecha_ingreso = VALUES(fecha_ingreso)");
    $stmt_del_prestamos = $conex->prepare("DELETE FROM Prestamos WHERE id_nomina = ?");

    // <-- INICIA BLOQUE MODIFICADO: INSERTAR FECHA DE PRESTAMO -->
    // Se añade fecha_prestamo al INSERT. Ya no se usa CURDATE().
    $stmt_ins_prestamo = $conex->prepare("INSERT INTO Prestamos (id_nomina, id_herramienta, cantidad, fecha_prestamo) VALUES (?, ?, ?, ?)");
    // <-- FIN BLOQUE MODIFICADO -->

    foreach ($input['employeeData'] as $employee) {
        // Insertar/Actualizar empleado
        $stmt_emp->bind_param("isss", $employee['nomina'], $employee['nombre'], $employee['departamento'], $employee['fecha_ingreso']);
        $stmt_emp->execute();

        // Borrar préstamos antiguos para empezar de cero
        $stmt_del_prestamos->bind_param("i", $employee['nomina']);
        $stmt_del_prestamos->execute();

        // Insertar los nuevos préstamos desde el archivo
        foreach ($employee['prestamos'] as $prestamo) {
            // Asegurarse que el 'toolName' exista en nuestro array de IDs
            if (isset($tool_ids[$prestamo['toolName']])) {
                $id_herramienta = $tool_ids[$prestamo['toolName']];

                // <-- INICIA BLOQUE MODIFICADO: BIND_PARAM CON FECHA -->
                // Obtenemos la fecha (puede ser null si no venía en el Excel)
                $fecha_entrega = $prestamo['fecha_prestamo'];

                // Se actualiza el bind_param a 4 parámetros: i (nomina), i (id_herramienta), i (cantidad), s (fecha)
                $stmt_ins_prestamo->bind_param("iiis", $employee['nomina'], $id_herramienta, $prestamo['quantity'], $fecha_entrega);
                // <-- FIN BLOQUE MODIFICADO -->

                $stmt_ins_prestamo->execute();
            }
        }
    }
    $stmt_emp->close();
    $stmt_del_prestamos->close();
    $stmt_ins_prestamo->close();

    // Si todo salió bien, confirmamos la transacción
    $conex->commit();
    $response['success'] = true;
    $response['message'] = 'La base de datos se actualizó correctamente.';

} catch (Exception $e) {
    // Si algo falló, revertimos todos los cambios
    $conex->rollback();
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}

echo json_encode($response);
?>