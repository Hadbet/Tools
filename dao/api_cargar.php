<?php
// api_cargar.php
header('Content-Type: application/json');
include_once('../db/db.php'); // Ajustada la ruta de inclusión

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

// Se cambió 'tools' a 'herramientas' y 'employeeData' a 'empleados' para coincidir con el JS
if (empty($input) || !isset($input['herramientas']) || !isset($input['empleados'])) {
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
    foreach ($input['herramientas'] as $tool) {
        $stmt_tool->bind_param("sd", $tool['name'], $tool['cost']);
        $stmt_tool->execute();

        // Obtenemos el ID de la herramienta para usarlo en los préstamos
        $id_herramienta_result = $conex->query("SELECT id_herramienta FROM Herramientas WHERE nombre = '" . $conex->real_escape_string($tool['name']) . "'");
        $id_herramienta_row = $id_herramienta_result->fetch_assoc();
        $tool_ids[$tool['name']] = $id_herramienta_row['id_herramienta'];
    }
    $stmt_tool->close();

    // 2. Insertar o actualizar empleados y sus préstamos
    $stmt_emp = $conex->prepare("INSERT INTO Empleados (id_nomina, nombre, departamento, fecha_ingreso) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), departamento = VALUES(departamento), fecha_ingreso = VALUES(fecha_ingreso)");
    $stmt_del_prestamos = $conex->prepare("DELETE FROM Prestamos WHERE id_nomina = ?");

    // --- CONSULTA DE INSERCIÓN ACTUALIZADA ---
    // Ahora acepta 4 parámetros (el último es la fecha_prestamo, tipo 's' string)
    $stmt_ins_prestamo = $conex->prepare("INSERT INTO Prestamos (id_nomina, id_herramienta, cantidad, fecha_prestamo) VALUES (?, ?, ?, ?)");

    foreach ($input['empleados'] as $employee) {
        // Insertar/Actualizar empleado
        $stmt_emp->bind_param("isss", $employee['nomina'], $employee['nombre'], $employee['departamento'], $employee['fecha_ingreso']);
        $stmt_emp->execute();

        // Borrar préstamos antiguos para empezar de cero
        $stmt_del_prestamos->bind_param("i", $employee['nomina']);
        $stmt_del_prestamos->execute();

        // Insertar los nuevos préstamos desde el archivo
        foreach ($employee['prestamos'] as $prestamo) {
            if (isset($tool_ids[$prestamo['herramienta']])) {
                $id_herramienta = $tool_ids[$prestamo['herramienta']];
                $fecha = $prestamo['fecha_prestamo']; // Obtenemos la fecha del JS

                // --- BINDING ACTUALIZADO ---
                // i: nomina, i: id_herramienta, i: cantidad, s: fecha_prestamo
                $stmt_ins_prestamo->bind_param("iiis", $employee['nomina'], $id_herramienta, $prestamo['cantidad'], $fecha);
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