<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Datos - Toolcrib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Librería para leer archivos Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Librería para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0a2a43 0%, #0e3e5f 100%);
            color: #e0e0e0;
        }
        .main-container {
            background-color: rgba(14, 62, 95, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-upload {
            background-color: #1f7a8c;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(31, 122, 140, 0.4);
        }
        .btn-upload:hover {
            background-color: #2c9ab7;
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(44, 154, 183, 0.5);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="main-container rounded-2xl p-8 max-w-lg w-full shadow-2xl text-center">
    <div class="mb-8">
        <h1 class="text-5xl font-bold text-cyan-300 tracking-wider">Carga de Datos</h1>
        <p class="text-gray-300 mt-2 text-lg">Control de Herramientas</p>
    </div>

    <p class="mb-6 text-gray-400">
        Selecciona el archivo de Excel (.xlsx, .xls) para actualizar la base de datos de herramientas y préstamos.
    </p>

    <button id="uploadBtn" class="btn-upload text-white font-bold py-4 px-8 rounded-lg inline-flex items-center justify-center w-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        <span>Seleccionar Archivo Excel</span>
    </button>

    <input type="file" id="fileInput" class="hidden" accept=".xlsx, .xls">
</div>

<script>
    document.getElementById('uploadBtn').addEventListener('click', () => {
        document.getElementById('fileInput').click();
    });

    document.getElementById('fileInput').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            processExcelFile(file);
        }
        // Reset file input para permitir subir el mismo archivo de nuevo
        event.target.value = '';
    });

    async function processExcelFile(file) {
        try {
            Swal.fire({
                title: 'Procesando archivo...',
                text: 'Por favor, espera un momento.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array', cellDates: true });
            const worksheet = workbook.Sheets[workbook.SheetNames[0]];
            // Usamos `defval: null` para que las celdas vacías se representen como null
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: null });

            if (!jsonData || jsonData.length < 9) {
                throw new Error('El archivo Excel no tiene el formato o las filas esperadas.');
            }

            // --- Lógica de extracción de datos ---

            // --- 1. Extraer Herramientas y Costos ---
            const toolNames = jsonData[1] || []; // Fila 2
            const toolCosts = jsonData[7] || []; // Fila 8
            const tools = [];
            // Empezamos en la columna 'F' que corresponde al índice 5.
            for (let i = 5; i < toolNames.length; i++) {
                const name = toolNames[i] ? String(toolNames[i]).trim() : '';

                // CORRECCIÓN: Omitir las columnas que no son herramientas, como 'Total' o 'Folio T.C'.
                if (!name || name.toLowerCase().includes('total') || name.toLowerCase().includes('folio')) {
                    continue; // Saltar a la siguiente iteración
                }

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
                // Se leen los datos a partir de la columna B (índice 1).
                const nomina = row[1] ? parseInt(row[1], 10) : 0;

                if (nomina > 0) {
                    const formatDate = (excelDate) => {
                        if (!excelDate) return null;
                        if (excelDate instanceof Date) {
                            return excelDate.toISOString().split('T')[0];
                        }
                        return new Date(Math.round((excelDate - 25569) * 864e5)).toISOString().split('T')[0];
                    };

                    const employee = {
                        nomina: nomina,
                        nombre: row[2] ? String(row[2]).trim() : '',
                        departamento: row[3] ? String(row[3]).trim() : '',
                        fecha_ingreso: formatDate(row[4]),
                        prestamos: []
                    };

                    tools.forEach(tool => {
                        const quantity = row[tool.columnIndex] ? parseInt(row[tool.columnIndex], 10) : 0;
                        if (quantity > 0) {
                            employee.prestamos.push({
                                herramienta: tool.name,
                                cantidad: quantity
                            });
                        }
                    });
                    employeeData.push(employee);
                }
            }

            // --- 3. Enviar datos al servidor ---
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_cargar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tools, employees: employeeData })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Error del servidor: ${response.status} - ${errorText}`);
            }

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
            console.error('Error al procesar el archivo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Ocurrió un problema al procesar el archivo.'
            });
        }
    }
</script>
</body>
</html>

