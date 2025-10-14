<?php
include_once('dao/db/db.php');

$uploadMessage = '';
$error = false;

// Esta sección procesa el archivo cuando se envía el formulario.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['dataFile'])) {
    $fileName = $_FILES['dataFile']['tmp_name'];

    if ($_FILES['dataFile']['size'] > 0) {
        $file = fopen($fileName, "r");

        $con = new ToolcribDB();
        $conex = $con->conectar();
        $conex->begin_transaction(); // Iniciamos una transacción para asegurar la integridad de los datos.

        try {
            // Leemos el archivo línea por línea (CSV).
            $headerTools = fgetcsv($file); // Fila 1 (vacía)
            $toolNames = fgetcsv($file);   // Fila 2 (Nombres de herramientas)
            // Saltamos las 5 filas siguientes que no contienen datos relevantes para las herramientas.
            for($i = 0; $i < 5; $i++) { fgetcsv($file); }
            $toolCosts = fgetcsv($file);   // Fila 8 (Costos de herramientas)

            // 1. Procesar herramientas y costos
            $toolMap = []; // Para mapear el índice de la columna con el ID de la herramienta
            for ($i = 4; $i < count($toolNames) - 2; $i++) { // Iteramos desde la columna de la primera herramienta
                $name = trim($toolNames[$i]);
                $cost = isset($toolCosts[$i]) ? floatval(trim($toolCosts[$i])) : 0;

                if (!empty($name) && $cost > 0) {
                    // Usamos INSERT ... ON DUPLICATE KEY UPDATE para insertar o actualizar la herramienta.
                    $stmt = $conex->prepare("INSERT INTO Herramientas (nombre, costo) VALUES (?, ?) ON DUPLICATE KEY UPDATE costo = ?");
                    $stmt->bind_param("sdd", $name, $cost, $cost);
                    $stmt->execute();
                    $id_herramienta = $conex->insert_id > 0 ? $conex->insert_id : null;

                    // Si se actualizó, necesitamos obtener el ID
                    if(!$id_herramienta) {
                        $stmt_get_id = $conex->prepare("SELECT id_herramienta FROM Herramientas WHERE nombre = ?");
                        $stmt_get_id->bind_param("s", $name);
                        $stmt_get_id->execute();
                        $result_id = $stmt_get_id->get_result();
                        if($row_id = $result_id->fetch_assoc()) {
                            $id_herramienta = $row_id['id_herramienta'];
                        }
                        $stmt_get_id->close();
                    }
                    $toolMap[$i] = $id_herramienta;
                    $stmt->close();
                }
            }

            // 2. Procesar empleados y préstamos
            while (($column = fgetcsv($file)) !== FALSE) {
                $id_nomina = intval(trim($column[0]));
                $nombre = trim($column[1]);
                $depto = trim($column[2]);
                $fecha_ingreso_str = trim($column[3]);
                $fecha_ingreso = date('Y-m-d', strtotime($fecha_ingreso_str));

                if ($id_nomina > 0 && !empty($nombre)) {
                    // Insertar o actualizar empleado
                    $stmt = $conex->prepare("INSERT INTO Empleados (id_nomina, nombre, departamento, fecha_ingreso) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombre = ?, departamento = ?, fecha_ingreso = ?");
                    $stmt->bind_param("issssss", $id_nomina, $nombre, $depto, $fecha_ingreso, $nombre, $depto, $fecha_ingreso);
                    $stmt->execute();
                    $stmt->close();

                    // Borramos préstamos anteriores para este empleado para no duplicar datos en cada carga.
                    $stmt_delete = $conex->prepare("DELETE FROM Prestamos WHERE id_nomina = ?");
                    $stmt_delete->bind_param("i", $id_nomina);
                    $stmt_delete->execute();
                    $stmt_delete->close();

                    // Insertar nuevos préstamos
                    foreach($toolMap as $colIndex => $id_herramienta) {
                        $cantidad = isset($column[$colIndex]) ? intval(trim($column[$colIndex])) : 0;
                        if($cantidad > 0) {
                            $stmt = $conex->prepare("INSERT INTO Prestamos (id_nomina, id_herramienta, cantidad, fecha_prestamo) VALUES (?, ?, ?, CURDATE())");
                            $stmt->bind_param("iii", $id_nomina, $id_herramienta, $cantidad);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }

            $conex->commit(); // Si todo fue bien, confirmamos los cambios.
            $uploadMessage = '¡Base de datos actualizada correctamente!';
            $error = false;
        } catch (Exception $e) {
            $conex->rollback(); // Si algo falla, revertimos todos los cambios.
            $uploadMessage = 'Error al procesar el archivo: ' . $e->getMessage();
            $error = true;
        } finally {
            if (isset($conex)) {
                $conex->close();
            }
            fclose($file);
        }
    } else {
        $uploadMessage = 'Por favor, selecciona un archivo CSV válido.';
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Datos - Toolcrib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0a2a43 0%, #0e3e5f 100%);
        }
        .upload-container {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .upload-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }
        .btn-upload {
            background-color: #1f7a8c;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-upload:hover {
            background-color: #165a68;
            transform: scale(1.05);
        }
        .file-input-label {
            border: 2px dashed #1f7a8c;
            transition: border-color 0.3s, background-color 0.3s;
        }
        .file-input-label:hover {
            background-color: rgba(31, 122, 140, 0.1);
            border-color: #2c9ab7;
        }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-4">

<div class="upload-container bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-8 max-w-lg w-full shadow-lg border border-gray-700">
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-cyan-300">Control de Herramientas</h1>
        <p class="text-gray-300 mt-2">Carga de Datos del Toolcrib</p>
    </div>

    <?php if (!empty($uploadMessage)): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?php echo $error ? 'bg-red-800 text-red-200' : 'bg-green-800 text-green-200'; ?>" role="alert">
            <span class="font-medium"><?php echo $uploadMessage; ?></span>
        </div>
    <?php endif; ?>

    <form action="cargar.php" method="post" enctype="multipart/form-data" class="space-y-6">
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-300" for="dataFile">Archivo de datos (.csv)</label>
            <label for="dataFile" class="file-input-label flex flex-col items-center justify-center w-full h-48 rounded-lg cursor-pointer">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-10 h-10 mb-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    <p class="mb-2 text-sm text-gray-400"><span class="font-semibold">Click para subir</span> o arrastra el archivo</p>
                    <p class="text-xs text-gray-500">CSV (codificado en UTF-8)</p>
                </div>
                <input id="dataFile" name="dataFile" type="file" class="hidden" accept=".csv" />
            </label>
            <p id="file-name" class="text-center text-sm text-gray-400 mt-2"></p>
        </div>
        <p class="text-xs text-gray-400">
            <strong>Importante:</strong> Guarda el archivo de Excel como <strong>CSV (delimitado por comas)</strong> antes de subirlo. El script está diseñado para leer la estructura específica de tu archivo.
        </p>
        <button type="submit" class="w-full btn-upload text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline">
            Actualizar Base de Datos
        </button>
    </form>
</div>

<script>
    document.getElementById('dataFile').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
        document.getElementById('file-name').textContent = 'Archivo: ' + fileName;
    });
</script>
</body>
</html>
