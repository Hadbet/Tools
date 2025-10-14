<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Datos - Toolcrib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Librería para leer Excel en el navegador -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Librería para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <div>
        <!-- Input de archivo oculto -->
        <input id="fileInput" type="file" class="hidden" accept=".xlsx, .xls" />

        <!-- Botón visible para el usuario -->
        <button id="btnExcelUpload" class="w-full btn-upload text-white font-bold py-4 px-4 rounded-lg focus:outline-none focus:shadow-outline flex items-center justify-center gap-3 text-lg">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            Seleccionar Archivo Excel
        </button>
        <p id="file-name" class="text-center text-sm text-gray-400 mt-2"></p>
    </div>
    <div class="text-center mt-6">
        <a href="consulta.html" class="text-cyan-400 hover:text-cyan-200 transition">Ir al Portal de Consulta &rarr;</a>
    </div>
</div>

<script>
    document.getElementById('btnExcelUpload').addEventListener('click', () => {
        document.getElementById('fileInput').click();
    });

    document.getElementById('fileInput').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            document.getElementById('file-name').textContent = `Archivo: ${file.name}`;
            procesarExcel(file);
        }
    });

    async function procesarExcel(file) {
        try {
            Swal.fire({
                title: 'Procesando archivo...',
                text: 'Por favor, espera mientras leemos los datos.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });
            const worksheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: "" });

            // --- 1. Extraer Herramientas y Costos ---
            const toolNames = jsonData[1] || []; // Fila 2
            const toolCosts = jsonData[7] || []; // Fila 8
            const tools = [];
            // Empezamos en la columna 4 que corresponde al índice 4 (Flexometro)
            for (let i = 4; i < toolNames.length; i++) {
                const name = toolNames[i] ? String(toolNames[i]).trim() : '';
                const cost = toolCosts[i] ? parseFloat(String(toolCosts[i]).replace(',', '.')) : 0;
                if (name && cost > 0) {
                    tools.push({ name: name, cost: cost, columnIndex: i });
                }
            }

            // --- 2. Extraer Empleados y sus Préstamos ---
            const employeeData = [];
            // Los datos de empleados empiezan en la fila 9 (índice 8)
            for (let i = 8; i < jsonData.length; i++) {
                const row = jsonData[i];
                const nomina = row[0] ? parseInt(row[0], 10) : 0;

                if (nomina > 0) {
                    // Función para convertir fecha de Excel (número o texto) a YYYY-MM-DD
                    const formatDate = (excelDate) => {
                        if (typeof excelDate === 'number') {
                            const date = new Date(Math.round((excelDate - 25569) * 864e5));
                            return date.toISOString().split('T')[0];
                        }
                        if (typeof excelDate === 'string') {
                            const parts = excelDate.split('/');
                            if (parts.length === 3) {
                                // Asumiendo formato DD/MM/YYYY o MM/DD/YYYY
                                const year = parts[2].length === 2 ? `20${parts[2]}` : parts[2];
                                return `${year}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                            }
                        }
                        return null;
                    };

                    const employee = {
                        nomina: nomina,
                        nombre: row[1] ? String(row[1]).trim() : '',
                        departamento: row[2] ? String(row[2]).trim() : '',
                        fecha_ingreso: formatDate(row[3]),
                        prestamos: []
                    };

                    tools.forEach(tool => {
                        const quantity = row[tool.columnIndex] ? parseInt(row[tool.columnIndex], 10) : 0;
                        if (quantity > 0) {
                            employee.prestamos.push({
                                toolName: tool.name,
                                quantity: quantity
                            });
                        }
                    });
                    employeeData.push(employee);
                }
            }

            // --- 3. Enviar datos al Servidor ---
            // Cambia la URL a la ruta correcta de tu API en el servidor.
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_cargar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tools: tools, employeeData: employeeData })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message
                });
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            console.error("Error al procesar el Excel:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Ocurrió un error al procesar el archivo. Revisa que el formato sea correcto.'
            });
        } finally {
            // Resetea el input para poder subir el mismo archivo otra vez si es necesario.
            document.getElementById('fileInput').value = '';
            document.getElementById('file-name').textContent = '';
        }
    }
</script>
</body>
</html>
