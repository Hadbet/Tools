<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Control de Herramientas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Librerías para leer Excel y generar PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
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
        .btn-main {
            background-color: #1f7a8c;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(31, 122, 140, 0.4);
        }
        .btn-main:hover {
            background-color: #2c9ab7;
            transform: scale(1.05);
        }
        .nav-link {
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }
        .nav-link:hover, .nav-link.active {
            color: #67e8f9; /* cyan-300 */
            border-bottom-color: #67e8f9;
        }
    </style>
</head>
<body class="min-h-screen p-4" onload="loadEmployeeReport()">

<div class="main-container rounded-2xl p-8 max-w-7xl w-full mx-auto shadow-2xl">
    <!-- Barra de Navegación -->
    <nav class="flex justify-center items-center gap-8 mb-8 border-b border-gray-700 pb-4">
        <a href="#" class="nav-link active font-semibold text-lg py-2">Inicio</a>
        <a href="#" class="nav-link font-semibold text-lg py-2">Usuarios</a>
        <a href="#" class="nav-link font-semibold text-lg py-2">Perfil</a>
    </nav>

    <!-- Sección de Carga de Archivos -->
    <div class="text-center p-6 bg-gray-900 bg-opacity-30 rounded-lg mb-10">
        <h1 class="text-3xl font-bold text-cyan-300">Cargar Base de Datos de Herramientas</h1>
        <p class="text-gray-400 mt-2">Selecciona el archivo Excel (.xlsx) para actualizar la información.</p>
        <button id="uploadButton" class="btn-main text-white font-bold py-3 px-6 rounded-lg mt-4 inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Seleccionar y Cargar Archivo
        </button>
        <input type="file" id="fileInput" class="hidden" accept=".xlsx, .xls">
    </div>

    <!-- Sección de Reporte General -->
    <div>
        <h2 class="text-3xl font-bold text-cyan-300 text-center mb-6">Reporte General de Empleados</h2>
        <div class="overflow-x-auto rounded-lg border border-gray-700 bg-gray-900 bg-opacity-30">
            <table class="min-w-full text-left text-sm">
                <thead class="uppercase bg-gray-900 bg-opacity-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nómina</th>
                    <th scope="col" class="px-6 py-3">Nombre</th>
                    <th scope="col" class="px-6 py-3 text-center">Estado</th>
                    <th scope="col" class="px-6 py-3 text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="employee-table-body">
                <!-- Las filas se insertarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // --- LÓGICA DE CARGA DE ARCHIVO EXCEL ---
    document.getElementById('uploadButton').addEventListener('click', () => {
        document.getElementById('fileInput').click();
    });

    document.getElementById('fileInput').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            processAndSendData(file);
        }
        // Resetea el input para permitir cargar el mismo archivo de nuevo
        event.target.value = '';
    });

    async function processAndSendData(file) {
        Swal.fire({
            title: 'Procesando archivo...',
            text: 'Por favor, espera mientras leemos el Excel.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });

            // Busca la hoja que contiene los datos de los usuarios, usualmente llamada "Usuarios"
            const sheetName = workbook.SheetNames.find(name => name.toLowerCase().includes('usuarios'));
            if (!sheetName) {
                throw new Error('No se encontró la hoja de cálculo "Usuarios" en el archivo Excel.');
            }
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: "" });

            let headerRowIndex = -1;
            let toolRowIndex = -1;
            let startDataRowIndex = -1;

            // Búsqueda dinámica de las filas de encabezado
            for (let i = 0; i < jsonData.length; i++) {
                const row = jsonData[i].map(cell => String(cell).trim().toLowerCase());
                if (row.includes('# nomina')) {
                    headerRowIndex = i;
                    startDataRowIndex = i + 1; // Los datos empiezan en la siguiente fila
                }
                if (row.includes('flexometro')) {
                    toolRowIndex = i;
                }
                if (headerRowIndex !== -1 && toolRowIndex !== -1) break;
            }

            if (headerRowIndex === -1) throw new Error('No se encontró la fila de encabezado con "# Nomina".');
            if (toolRowIndex === -1) throw new Error('No se encontró la fila de encabezado con "Flexometro".');

            const toolHeaders = jsonData[toolRowIndex].map(cell => String(cell).trim());
            const dataHeaders = jsonData[headerRowIndex].map(cell => String(cell).trim());

            // Encuentra la columna inicial de las herramientas y de los datos de empleado
            const toolStartIndex = toolHeaders.findIndex(h => h.toLowerCase() === 'flexometro');
            const nominaIndex = dataHeaders.findIndex(h => h.toLowerCase() === '# nomina');
            const nombreIndex = dataHeaders.findIndex(h => h.toLowerCase() === 'nombre');
            const deptoIndex = dataHeaders.findIndex(h => h.toLowerCase() === 'departamento');
            const fechaIndex = dataHeaders.findIndex(h => h.toLowerCase() === 'fecha ingreso');

            if (toolStartIndex === -1 || nominaIndex === -1 || nombreIndex === -1) {
                throw new Error('No se pudieron encontrar todas las columnas necesarias (Flexometro, # Nomina, Nombre).');
            }

            const herramientas = [];
            const prestamos = [];

            // Extraer las herramientas y sus costos
            const costRow = jsonData[startDataRowIndex];
            for (let i = toolStartIndex; i < toolHeaders.length; i++) {
                const nombreHerramienta = toolHeaders[i];
                const costoHerramienta = parseFloat(costRow[i]);
                if (nombreHerramienta && !isNaN(costoHerramienta) && nombreHerramienta.toLowerCase() !== 'total' && nombreHerramienta.toLowerCase() !== 'folio t.c') {
                    herramientas.push({
                        nombre: nombreHerramienta,
                        costo: costoHerramienta
                    });
                }
            }

            // Extraer los préstamos de cada empleado
            for (let i = startDataRowIndex + 1; i < jsonData.length; i++) {
                const row = jsonData[i];
                const nomina = row[nominaIndex];
                if (!nomina) continue;

                const empleado = {
                    nomina: String(nomina).trim(),
                    nombre: String(row[nombreIndex]).trim(),
                    departamento: String(row[deptoIndex]).trim(),
                    fechaIngreso: String(row[fechaIndex]).trim()
                };

                for (let j = 0; j < herramientas.length; j++) {
                    const toolColumnIndex = toolStartIndex + j;
                    const cantidad = parseFloat(row[toolColumnIndex]);
                    if (!isNaN(cantidad) && cantidad > 0) {
                        prestamos.push({
                            empleado: empleado,
                            herramienta: herramientas[j],
                            cantidad: cantidad
                        });
                    }
                }
                // Capturar la columna "Total" si existe para el empleado
                const totalHeaderIndex = toolHeaders.findIndex(h => h.toLowerCase() === 'total');
                if (totalHeaderIndex !== -1) {
                    const totalValue = parseFloat(row[totalHeaderIndex]);
                    if (!isNaN(totalValue) && totalValue > 0) {
                        prestamos.push({
                            empleado: empleado,
                            herramienta: { nombre: 'Total', costo: 0 },
                            cantidad: totalValue
                        });
                    }
                }
            }
            if (prestamos.length === 0 && herramientas.length === 0) {
                throw new Error("No se encontraron datos de herramientas o préstamos para procesar en el archivo.");
            }

            // Enviar datos al servidor
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_cargar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ herramientas, prestamos })
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message
                });
                loadEmployeeReport(); // Recargar la tabla
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error al procesar',
                text: error.message || 'Ocurrió un error inesperado.'
            });
        }
    }

    // --- LÓGICA DE REPORTE GENERAL Y GENERACIÓN DE PDF ---
    async function loadEmployeeReport() {
        const tableBody = document.getElementById('employee-table-body');
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center p-6">Cargando reporte...</td></tr>';

        try {
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_get_all_employees.php');
            const employees = await response.json();

            if (employees.error) {
                throw new Error(employees.error);
            }

            tableBody.innerHTML = '';
            if (employees.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center p-6">No hay empleados en la base de datos.</td></tr>';
                return;
            }

            employees.forEach(emp => {
                const isDeudor = emp.estado === 'Deudor';
                const statusClass = isDeudor ? 'bg-red-500 text-red-100' : 'bg-green-500 text-green-100';
                const buttonText = isDeudor ? 'Descargar Carta de Adeudo' : 'Descargar Carta de No Adeudo';

                const row = `
                    <tr class="border-b border-gray-700 hover:bg-gray-800">
                        <td class="px-6 py-4">${emp.nomina}</td>
                        <td class="px-6 py-4 font-medium">${emp.nombre}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full ${statusClass}">
                                ${emp.estado}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="generatePdf('${emp.nomina}', ${isDeudor})" class="btn-main text-white text-xs font-bold py-2 px-3 rounded">
                                ${buttonText}
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center p-6 text-red-400">Error al cargar el reporte: ${error.message}</td></tr>`;
        }
    }

    async function generatePdf(nomina, isDeudor) {
        Swal.fire({
            title: 'Generando PDF...',
            text: 'Por favor, espera.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            // Obtener los datos más frescos del empleado
            const response = await fetch(`https://grammermx.com/Mantenimiento/Tools/dao/api_consulta.php?nomina=${nomina}`);
            const data = await response.json();

            if (data.error) throw new Error(data.error);

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const nombreEmpleado = data.empleado.nombre;
            const nominaEmpleado = data.empleado.nomina;
            const responsable = "JAIR"; // Variable para el responsable

            doc.setFontSize(14);
            doc.setFont("helvetica", "bold");

            if (isDeudor) {
                // --- CARTA DE ADEUDO ---
                doc.text("Carta De Adeudo De Material y Herramienta", 105, 20, { align: 'center' });
                doc.setFontSize(11);
                doc.setFont("helvetica", "normal");

                const textoAdeudo = `Por medio de la presente se hace constar que el Sr. ${nombreEmpleado} con numero de nómina ${nominaEmpleado} ha quedado a deber material que le fue asignado como préstamo por el área de almacén para el desarrollo de sus actividades; por lo cual me permito expedir el siguiente formato que hace constar el adeudo de material de la persona antes mencionada adjuntando los formatos que hacen constar la entrega de dicho material firmado por las tres partes involucradas en la asignación de este.\n\nTeniendo en cuenta que el material adeudado se descontará por vía nómina, se describen las cantidades y la descripción del material para los fines requeridos en el área de Recursos Humanos:`;
                doc.text(textoAdeudo, 20, 35, { maxWidth: 170 });

                const tableBody = [];
                let totalAdeudo = 0;
                let totalDirecto = null;

                data.prestamos.forEach(p => {
                    if (p.herramienta.toLowerCase() === 'total') {
                        totalDirecto = parseFloat(p.cantidad);
                    } else {
                        const subtotal = p.cantidad * p.costo;
                        tableBody.push([p.herramienta, p.cantidad, `$${parseFloat(p.costo).toFixed(2)}`, `$${subtotal.toFixed(2)}`]);
                        totalAdeudo += subtotal;
                    }
                });

                if (totalDirecto !== null) {
                    totalAdeudo = totalDirecto;
                }

                if (tableBody.length > 0) {
                    doc.autoTable({
                        startY: 80,
                        head: [['Herramienta', 'Cantidad', 'Costo Unitario', 'Subtotal']],
                        body: tableBody,
                        theme: 'grid'
                    });
                }

                const finalY = doc.autoTable.previous.finalY || 80;
                doc.setFontSize(12);
                doc.setFont("helvetica", "bold");
                doc.text(`Total a descontar: $${totalAdeudo.toFixed(2)} MXN`, 20, finalY + 15);

                doc.setFontSize(11);
                doc.setFont("helvetica", "normal");
                doc.text("A continuación, con fecha de _______________ se firma de conformidad el presente documento.", 20, finalY + 30);

                doc.text("___________________________________", 20, finalY + 55);
                doc.text(nombreEmpleado, 20, finalY + 60);
                doc.text("NOMBRE Y FIRMA DE QUIEN ADEUDA", 20, finalY + 65);

                doc.text("___________________________________", 110, finalY + 55);
                doc.text(responsable, 110, finalY + 60);
                doc.text("TOOLCRIB - RESPONSABLE", 110, finalY + 65);

            } else {
                // --- CARTA DE NO ADEUDO ---
                doc.text("Carta De No Adeudo De Material y Herramienta", 105, 20, { align: 'center' });
                doc.setFontSize(11);
                doc.setFont("helvetica", "normal");

                const textoNoAdeudo = `Por medio de la presente se hace constar que el Sr. ${nombreEmpleado} con número de nómina ${nominaEmpleado} ha entregado completamente el material que le fue asignado como préstamo por el área de almacén para el desarrollo de sus actividades, por lo cual me permito expedir el siguiente formato que hace constar el no adeudo de material de la persona antes mencionada.\n\nA continuación, con fecha de _______________ se firma de conformidad el presente documento por parte de las personas involucradas en la liberación de este.`;
                doc.text(textoNoAdeudo, 20, 35, { maxWidth: 170 });

                doc.text("___________________________________", 20, 100);
                doc.text(nombreEmpleado, 20, 105);
                doc.text("NOMBRE Y FIRMA DE QUIEN ENTREGA", 20, 110);

                doc.text("___________________________________", 110, 100);
                doc.text(responsable, 110, 105);
                doc.text("TOOLCRIB - RESPONSABLE", 110, 110);
            }

            Swal.close();
            doc.save(`Carta_${isDeudor ? 'Adeudo' : 'NoAdeudo'}_${nomina}.pdf`);

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error al generar PDF',
                text: error.message
            });
        }
    }
</script>
</body>
</html>

