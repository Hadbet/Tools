<?php require_once 'dao/check_session.php'; ?>
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
        .input-main {
            background-color: rgba(10, 42, 67, 0.8);
            border-color: #1f7a8c;
        }
    </style>
</head>
<body class="min-h-screen p-4">

<div class="main-container rounded-2xl p-8 max-w-7xl w-full mx-auto shadow-2xl">
    <!-- Barra de Navegación -->
    <nav class="flex justify-center items-center gap-8 mb-8 border-b border-gray-700 pb-4">
        <a href="index.php" class="nav-link active font-semibold text-lg py-2">Inicio</a>
        <a href="gestion_usuarios.php" class="nav-link font-semibold text-lg py-2">Usuarios</a>
        <a href="dao/logout.php" class="nav-link font-semibold text-lg py-2 text-red-400 hover:text-red-300">Cerrar Sesión</a>
    </nav>

    <!-- Sección de Carga de Archivos -->
    <div class="text-center p-6 bg-gray-900 bg-opacity-30 rounded-lg mb-10">
        <h1 class="text-3xl font-bold text-cyan-300">Cargar Base de Datos</h1>
        <p class="text-gray-400 mt-2">Sube el archivo de Excel para actualizar el sistema.</p>
        <div class="mt-6 flex justify-center">
            <input type="file" id="fileInput" class="hidden" accept=".xlsx, .xls">
            <button id="uploadButton" class="btn-main text-white font-bold py-3 px-6 rounded-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Seleccionar Archivo
            </button>
        </div>
    </div>

    <!-- Sección de Búsqueda y Reporte -->
    <div class="mt-10">
        <h2 class="text-3xl font-bold text-cyan-300 text-center">Reporte de Empleado</h2>
        <p class="text-gray-400 mt-2 text-center">Busca un empleado por su número de nómina para generar su carta.</p>

        <div class="flex justify-center items-center gap-4 my-6">
            <input type="number" id="nominaInput" placeholder="Ingresa número de nómina" class="input-main text-white text-center text-lg w-full max-w-xs p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-400 transition">
            <button id="searchButton" class="btn-main text-white font-bold py-3 px-8 rounded-lg">Buscar</button>
        </div>

        <div id="reportResult" class="mt-6">
            <!-- El resultado de la búsqueda aparecerá aquí -->
        </div>
    </div>

</div>

<script>
    // --- LÓGICA PARA CARGA DE EXCEL ---
    document.getElementById('uploadButton').addEventListener('click', () => {
        document.getElementById('fileInput').click();
    });

    document.getElementById('fileInput').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            processExcelFile(file);
        }
    });

    // Función auxiliar para formatear fechas de Excel a YYYY-MM-DD
    const formatDate = (excelDate) => {
        if (!excelDate || (typeof excelDate === 'string' && excelDate.toUpperCase() === 'M-D-A')) return null;

        let date;
        if (typeof excelDate === 'number') { // Formato de fecha de Excel (número de serie)
            date = XLSX.SSF.parse_date_code(excelDate);
        } else if (typeof excelDate === 'string') { // Formato de fecha como texto (MM/DD/YYYY)
            const parts = excelDate.split('/');
            if (parts.length === 3) {
                // Asumiendo formato M/D/YYYY
                date = { y: parseInt(parts[2]), m: parseInt(parts[0]), d: parseInt(parts[1]) };
            } else {
                return null; // Formato de string no reconocido
            }
        } else if (excelDate instanceof Date) {
            return excelDate.toISOString().split('T')[0];
        } else {
            return null; // Tipo no reconocido
        }

        if (!date || isNaN(date.y) || isNaN(date.m) || isNaN(date.d)) return null;

        return `${date.y}-${String(date.m).padStart(2, '0')}-${String(date.d).padStart(2, '0')}`;
    };


    async function processExcelFile(file) {
        Swal.fire({
            title: 'Procesando archivo',
            text: 'Por favor, espera...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { cellDates: true });

            let targetSheet = null;
            for (const name of workbook.SheetNames) {
                const sheet = workbook.Sheets[name];
                const json = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                if (json.some(row => row.some(cell => typeof cell === 'string' && cell.trim().toLowerCase() === '# nomina'))) {
                    targetSheet = sheet;
                    break;
                }
            }

            if (!targetSheet) {
                throw new Error("No se pudo encontrar una hoja con la columna '# Nomina'. Revisa el formato.");
            }

            const jsonData = XLSX.utils.sheet_to_json(targetSheet, { header: 1 });

            let headerRowIndex = jsonData.findIndex(row => row.some(cell => typeof cell === 'string' && cell.trim().toLowerCase() === '# nomina'));
            if (headerRowIndex === -1) throw new Error("No se encontró la fila de encabezado con '# Nomina'.");

            let headerRowData = jsonData[headerRowIndex].map(h => typeof h === 'string' ? h.trim() : h);

            // --- LÓGICA DE BÚSQUEDA DE FILAS MEJORADA ---

            // 1. La fila de COSTOS es la misma que la de "# Nomina"
            const toolCostsRow = headerRowData; // headerRowData es jsonData[headerRowIndex]

            // 2. Buscamos la fila de NOMBRES de herramientas (ej. "Flexometro") ARRIBA de la fila de cabecera
            let toolNameRowIndex = -1;
            for(let i = headerRowIndex - 1; i >= 0; i--){ // Empezamos a buscar hacia arriba
                const row = jsonData[i];
                if(row && row.some(cell => typeof cell === 'string' && String(cell).trim().toLowerCase() === 'flexometro')){
                    toolNameRowIndex = i;
                    break;
                }
            }

            if (toolNameRowIndex === -1) {
                throw new Error("No se pudo encontrar la fila con nombres de herramientas (ej. 'Flexometro') encima de la fila de '# Nomina'.");
            }

            const toolNamesRow = jsonData[toolNameRowIndex].map(h => typeof h === 'string' ? h.trim() : h);
            // --- FIN LÓGICA DE BÚSQUEDA ---

            // --- LÓGICA DE HERRAMIENTAS ACTUALIZADA ---
            const tools = [];
            let headerToolIndex = -1;

            // Buscamos el inicio de las herramientas (ej. "Flexometro") en la fila de nombres
            const flexometroIndex = toolNamesRow.findIndex(name => name && name.toLowerCase() === 'flexometro');

            // Buscamos el índice de la columna "Fecha Ingreso" para saber dónde empezar a buscar costos
            const fechaIngresoIndex = headerRowData.indexOf('Fecha Ingreso');

            // Buscamos el primer costo numérico *después* de "Fecha Ingreso"
            let firstCostIndex = -1;
            if (fechaIngresoIndex !== -1) {
                firstCostIndex = toolCostsRow.findIndex((cost, i) => i > fechaIngresoIndex && typeof cost === 'number');
            } else {
                // Fallback si no encontramos "Fecha Ingreso", buscamos el primer número
                firstCostIndex = toolCostsRow.findIndex(cost => typeof cost === 'number');
            }

            // El índice de inicio de la herramienta es el que sea que hayamos encontrado
            // Damos prioridad al índice de 'Flexometro' si existe y coincide con el primer costo
            if (flexometroIndex !== -1 && flexometroIndex === firstCostIndex) {
                headerToolIndex = flexometroIndex;
            } else if (flexometroIndex !== -1) {
                // Si 'Flexometro' está en una columna diferente al primer costo (raro), confiamos en 'Flexometro'
                headerToolIndex = flexometroIndex;
            } else if (firstCostIndex !== -1) {
                // Si no encontramos 'Flexometro', confiamos en el primer costo
                headerToolIndex = firstCostIndex;
            } else {
                throw new Error("No se encontró la columna 'Flexometro' ni ninguna columna de costo válida después de 'Fecha Ingreso'.");
            }

            // Iteramos de 2 en 2, ya que ahora es Herramienta | Fecha
            for(let i = headerToolIndex; i < toolNamesRow.length; i += 2) {
                const name = toolNamesRow[i];
                const cost = toolCostsRow[i];

                if(!name || (name && name.toLowerCase().includes('total'))) break;

                if(name && typeof cost === 'number') {
                    tools.push({
                        name: name,
                        cost: cost,
                        quantityColumnIndex: i, // Columna de la cantidad
                        dateColumnIndex: i + 1   // Columna de la fecha
                    });
                }
            }
            if (tools.length === 0) throw new Error("No se encontraron herramientas con costos válidos.");
            // --- FIN LÓGICA DE HERRAMIENTAS ---

            const nominaIndex = headerRowData.indexOf('# Nomina');
            const nombreIndex = headerRowData.indexOf('Nombre');
            const deptoIndex = headerRowData.indexOf('Departamento');
            const fechaIndex = headerRowData.indexOf('Fecha Ingreso');

            const employeeData = jsonData.slice(headerRowIndex + 1).map(row => {
                const nomina = row[nominaIndex];
                if (!nomina || isNaN(parseInt(nomina))) return null;

                const fechaIngresoFormatted = formatDate(row[fechaIndex]);

                const prestamos = [];
                tools.forEach(tool => {
                    const quantity = row[tool.quantityColumnIndex];
                    if(quantity && typeof quantity === 'number' && quantity > 0) {
                        const fechaPrestamoRaw = row[tool.dateColumnIndex];
                        prestamos.push({
                            herramienta: tool.name,
                            cantidad: quantity,
                            fecha_prestamo: formatDate(fechaPrestamoRaw)
                        });
                    }
                });

                const totalColIndex = toolNamesRow.findIndex(name => name && name.toLowerCase().includes('total'));
                if (totalColIndex !== -1) {
                    // El total está en la fila de datos, pero en la columna de 'Total'
                    const totalValue = row[totalColIndex];
                    if (totalValue && typeof totalValue === 'number' && totalValue > 0) {
                        prestamos.push({
                            herramienta: 'Total',
                            cantidad: totalValue,
                            fecha_prestamo: null
                        });
                    }
                }

                return {
                    nomina: nomina,
                    nombre: row[nombreIndex] || 'N/A',
                    departamento: row[deptoIndex] || 'N/A',
                    fecha_ingreso: fechaIngresoFormatted,
                    prestamos: prestamos
                };
            }).filter(Boolean);

            const payload = { herramientas: tools, empleados: employeeData };

            const response = await fetch('dao/api_cargar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire({ icon: 'success', title: '¡Éxito!', text: result.message });
            } else {
                throw new Error(result.message || 'Ocurrió un error en el servidor.');
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error al procesar', text: error.message });
            console.error(error);
        }
    }

    // --- LÓGICA PARA BÚSQUEDA Y REPORTE ---
    const searchButton = document.getElementById('searchButton');
    const nominaInput = document.getElementById('nominaInput');
    const reportResultDiv = document.getElementById('reportResult');

    const searchEmployee = async () => {
        const nomina = nominaInput.value;
        if (!nomina) {
            Swal.fire('Atención', 'Por favor, ingresa un número de nómina.', 'warning');
            return;
        }
        reportResultDiv.innerHTML = `<p class="text-center">Buscando...</p>`;
        try {
            const response = await fetch(`dao/api_get_single_employee.php?nomina=${nomina}`);
            if (!response.ok) throw new Error('Error en la respuesta del servidor.');
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            displayEmployeeResult(data);
        } catch (error) {
            reportResultDiv.innerHTML = '';
            Swal.fire('Error', error.message, 'error');
        }
    };

    searchButton.addEventListener('click', searchEmployee);
    nominaInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') searchEmployee();
    });

    function displayEmployeeResult(employee) {
        const estadoClass = employee.estado === 'Deudor' ? 'text-red-400 bg-red-900 bg-opacity-50' : 'text-green-400 bg-green-900 bg-opacity-50';
        const buttonText = employee.estado === 'Deudor' ? 'Descargar Carta de Adeudo' : 'Descargar Carta de No Adeudo';
        reportResultDiv.innerHTML = `
            <div class="overflow-x-auto rounded-lg border border-gray-700">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-800 uppercase">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nómina</th>
                            <th scope="col" class="px-6 py-3">Nombre</th>
                            <th scope="col" class="px-6 py-3 text-center">Estado</th>
                            <th scope="col" class="px-6 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-gray-700">
                            <td class="px-6 py-4 font-medium">${employee.nomina}</td>
                            <td class="px-6 py-4">${employee.nombre}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full font-semibold ${estadoClass}">${employee.estado}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="generatePdf('${employee.nomina}', '${employee.nombre}', '${employee.estado}')" class="btn-main text-white text-xs font-bold py-2 px-3 rounded">
                                    ${buttonText}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    // --- LÓGICA PARA GENERAR PDF ---
    const loadImage = (src) => new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = (err) => reject(err);
        img.src = src;
    });

    async function generatePdf(nomina, nombre, estado) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const imageUrl = 'images/logo.png';
        try {
            const logoData = await loadImage(imageUrl);
            const imgWidth = 50;
            const imgHeight = (400 / 650) * imgWidth;
            doc.addImage(logoData, 'PNG', 15, 10, imgWidth, imgHeight);
        } catch (error) {
            console.error("Error al cargar la imagen:", error);
        }

        const responsable = "<?php echo addslashes($_SESSION['Nombre']);?>";
        const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });

        if (estado === 'Deudor') {
            try {
                const response = await fetch(`dao/api_consulta.php?nomina=${nomina}`);
                const data = await response.json();
                if (data.error || !data.prestamos) throw new Error('No se pudo obtener el detalle del adeudo.');

                let totalAmount = 0;
                const body = [];
                let totalRow = null;

                data.prestamos.forEach(item => {
                    if (item.herramienta.toLowerCase() === 'total') {
                        totalRow = item;
                    } else {
                        const subtotal = item.cantidad * item.costo;
                        let fechaFormateada = 'Indefinida';
                        if (item.fecha_prestamo) {
                            try {
                                const parts = item.fecha_prestamo.split('-');
                                if (parts.length === 3) fechaFormateada = `${parts[2]}/${parts[1]}/${parts[0]}`;
                            } catch (e) {}
                        }
                        body.push([item.herramienta, fechaFormateada, item.cantidad, `$${parseFloat(item.costo).toFixed(2)}`, `$${subtotal.toFixed(2)}`]);
                    }
                });

                if (totalRow) { totalAmount = parseFloat(totalRow.cantidad); }

                doc.setFontSize(14).setFont(undefined, 'bold');
                doc.text("Carta De Adeudo De Material y Herramienta", 105, 58, { align: 'center' });

                doc.setFontSize(11).setFont(undefined, 'normal');
                let textAdeudo = `Por medio de la presente se hace constar que el Sr. ${nombre} con numero de nómina ${nomina} ha quedado a deber material que le fue asignado como préstamo por el área de almacén para el desarrollo de sus actividades; por lo cual me permito expedir el siguiente formato que hace constar el adeudo de material de la persona antes mencionada.\n\nTeniendo en cuenta que el material adeudado se descontará por vía nómina, se describen las cantidades y la descripción del material para los fines requeridos en el área de Recursos Humanos:`;
                const splitTextAdeudo = doc.splitTextToSize(textAdeudo, 180);
                doc.text(splitTextAdeudo, 15, 75);

                doc.autoTable({
                    startY: 115,
                    head: [['Herramienta', 'Fecha Préstamo', 'Cantidad', 'Costo Unitario', 'Subtotal']],
                    body: body,
                    theme: 'grid'
                });

                let finalY = doc.autoTable.previous.finalY;
                doc.setFontSize(12).setFont(undefined, 'bold');
                doc.text(`Total a Descontar: $${totalAmount.toFixed(2)} MXN`, 15, finalY + 15);

                finalY += 30;
                doc.setFontSize(11).setFont(undefined, 'normal');
                doc.text(`A continuación, con fecha de ${hoy} se firma de conformidad el presente documento.`, 15, finalY);

                finalY += 30;
                doc.text("___________________________________", 15, finalY);
                doc.text(nombre.toUpperCase(), 15, finalY + 5);
                doc.text("NOMBRE Y FIRMA DE QUIEN ADEUDA", 15, finalY + 10);

                doc.text("___________________________________", 115, finalY);
                doc.text(responsable.toUpperCase(), 115, finalY + 5);
                doc.text("TOOLCRIB - RESPONSABLE", 115, finalY + 10);

                doc.save(`Carta_Adeudo_${nomina}.pdf`);

            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        } else {
            // --- Contenido de la Carta de No Adeudo ---
            doc.setFontSize(14).setFont(undefined, 'bold');
            doc.text("Carta De No Adeudo De Material y Herramienta", 105, 70, { align: 'center' });

            doc.setFontSize(11).setFont(undefined, 'normal');
            let textNoAdeudo = `Por medio de la presente se hace constar que el Sr. ${nombre} con número de nómina ${nomina} ha entregado completamente el material que le fue asignado como préstamo por el área de almacén para el desarrollo de sus actividades, por lo cual me permito expedir el siguiente formato que hace constar el no adeudo de material de la persona antes mencionada.\n\nA continuación, con fecha de ${hoy} se firma de conformidad el presente documento.`;
            const splitTextNoAdeudo = doc.splitTextToSize(textNoAdeudo, 180);
            doc.text(splitTextNoAdeudo, 15, 90);

            let finalY = 150;
            doc.text("___________________________________", 15, finalY);
            doc.text(nombre.toUpperCase(), 15, finalY + 10);
            doc.text("NOMBRE Y FIRMA DE QUIEN ENTREGA", 15, finalY + 15);

            doc.text("___________________________________", 115, finalY);
            doc.text(responsable.toUpperCase(), 115, finalY + 10);
            doc.text("TOOLCRIB - RESPONSABLE DEL ÁREA", 115, finalY + 15);

            doc.text("___________________________________", 65, 200);
            doc.text("METROLOGÍA - RESPONSABLE DEL ÁREA", 65, 200 + 10);

            doc.save(`Carta_No_Adeudo_${nomina}.pdf`);
        }
    }
</script>
</body>
</html>