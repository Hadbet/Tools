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
        <a href="#" class="nav-link active font-semibold text-lg py-2">Inicio</a>
        <a href="#" class="nav-link font-semibold text-lg py-2">Usuarios</a>
        <a href="#" class="nav-link font-semibold text-lg py-2">Perfil</a>
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
            const workbook = XLSX.read(data);

            // Busca la hoja que parece contener los datos principales
            let targetSheet = null;
            let sheetName = '';
            for (const name of workbook.SheetNames) {
                const sheet = workbook.Sheets[name];
                const json = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                const hasNomina = json.some(row => row.some(cell => typeof cell === 'string' && cell.trim().toLowerCase() === '# nomina'));
                if (hasNomina) {
                    targetSheet = sheet;
                    sheetName = name;
                    break;
                }
            }

            if (!targetSheet) {
                throw new Error("No se pudo encontrar una hoja con la columna '# Nomina'. Revisa el formato del archivo.");
            }

            const jsonData = XLSX.utils.sheet_to_json(targetSheet, { header: 1 });

            let headerRowIndex = -1;
            let headerRowData;
            for(let i = 0; i < jsonData.length; i++) {
                const row = jsonData[i];
                const cell = row.find(c => typeof c === 'string' && c.trim().toLowerCase() === '# nomina');
                if(cell){
                    headerRowIndex = i;
                    headerRowData = row.map(h => typeof h === 'string' ? h.trim() : h);
                    break;
                }
            }

            if (headerRowIndex === -1) {
                throw new Error("No se encontró la fila de encabezado con '# Nomina'.");
            }

            let costsRowIndex = -1;
            for(let i = headerRowIndex; i >= 0; i--){
                const row = jsonData[i];
                if(row.some(cell => typeof cell === 'number')){
                    costsRowIndex = i;
                    break;
                }
            }

            if (costsRowIndex === -1) {
                throw new Error("No se encontró la fila de costos de herramientas por encima de los encabezados.");
            }

            const toolNamesRow = jsonData[costsRowIndex-1].map(h => typeof h === 'string' ? h.trim() : h);
            const toolCostsRow = jsonData[costsRowIndex];

            const tools = [];
            for(let i = 0; i < toolNamesRow.length; i++){
                const name = toolNamesRow[i];
                const cost = toolCostsRow[i];
                if(name && typeof cost === 'number'){
                    tools.push({ name: name, cost: cost });
                }
            }

            if (tools.length === 0) {
                throw new Error("No se encontraron herramientas con costos válidos en el archivo.");
            }

            const nominaIndex = headerRowData.indexOf('# Nomina');
            const nombreIndex = headerRowData.indexOf('Nombre');
            const deptoIndex = headerRowData.indexOf('Departamento');
            const fechaIndex = headerRowData.indexOf('Fecha Ingreso');

            const employeeData = jsonData.slice(headerRowIndex + 1).map(row => {
                const nomina = row[nominaIndex];
                if (!nomina || isNaN(parseInt(nomina))) return null;

                const fechaIngresoRaw = row[fechaIndex];
                let fechaIngresoFormatted = null;
                if (fechaIngresoRaw) {
                    if (typeof fechaIngresoRaw === 'number') { // Formato de fecha de Excel (número de serie)
                        const date = XLSX.SSF.parse_date_code(fechaIngresoRaw);
                        fechaIngresoFormatted = `${date.y}-${String(date.m).padStart(2, '0')}-${String(date.d).padStart(2, '0')}`;
                    } else if (typeof fechaIngresoRaw === 'string') { // Formato de fecha como texto (MM/DD/YYYY)
                        const parts = fechaIngresoRaw.split('/');
                        if (parts.length === 3) {
                            fechaIngresoFormatted = `${parts[2]}-${String(parts[0]).padStart(2, '0')}-${String(parts[1]).padStart(2, '0')}`;
                        }
                    }
                }

                const prestamos = [];
                for(let i = fechaIndex + 1; i < headerRowData.length; i++) {
                    const toolName = headerRowData[i];
                    const quantity = row[i];
                    if(toolName && quantity && typeof quantity === 'number' && quantity > 0) {
                        prestamos.push({
                            herramienta: toolName,
                            cantidad: quantity
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

            const payload = {
                herramientas: tools,
                empleados: employeeData
            };

            const response = await fetch('api_cargar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message
                });
            } else {
                throw new Error(result.message || 'Ocurrió un error en el servidor.');
            }

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error al procesar',
                text: error.message
            });
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
            const response = await fetch(`https://grammermx.com/Mantenimiento/Tools/dao/api_get_single_employee.php?nomina=${nomina}`);
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor.');
            }
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

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

    const loadImage = (src) => new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = "Anonymous"; // Necesario si la imagen está en otro dominio
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

    // --- LÓGICA PARA GENERAR PDF ---
    async function generatePdf(nomina, nombre, estado) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // 1. Especifica la ruta a tu imagen en el servidor
        const imageUrl = 'images/logo.png'; // Cambia esto por la ruta correcta, ej: 'images/logo.png'

        try {
            const logoData = await loadImage(imageUrl);
            // 2. Agrega la imagen al PDF
            // doc.addImage(data, 'FORMATO', x, y, ancho, alto);
            doc.addImage(logoData, 'PNG', 85, 15, 40, 15);
        } catch (error) {
            console.error("Error al cargar la imagen:", error);
            // Opcional: puedes continuar sin el logo si falla
        }

        const responsable = "JAIR"; // Variable para el responsable
        const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });

        if (estado === 'Deudor') {
            try {
                const response = await fetch(`https://grammermx.com/Mantenimiento/Tools/dao/api_consulta.php?nomina=${nomina}`);
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
                        body.push([item.herramienta, item.cantidad, `$${parseFloat(item.costo).toFixed(2)}`, `$${subtotal.toFixed(2)}`]);
                    }
                });

                if (totalRow) {
                    totalAmount = parseFloat(totalRow.cantidad);
                }

                // --- Contenido de la Carta de Adeudo ---
                doc.setFontSize(14).setFont(undefined, 'bold');
                doc.text("Carta De Adeudo De Material y Herramienta", 105, 20, { align: 'center' });

                doc.setFontSize(11).setFont(undefined, 'normal');
                let text = `Por medio de la presente se hace constar que el Sr. ${nombre} con numero de nómina ${nomina} ha quedado a deber material que le fue asignado como préstamo por el área de almacén para el desarrollo de sus actividades; por lo cual me permito expedir el siguiente formato que hace constar el adeudo de material de la persona antes mencionada.\n\nTeniendo en cuenta que el material adeudado se descontará por vía nómina, se describen las cantidades y la descripción del material para los fines requeridos en el área de Recursos Humanos:`;
                const splitText = doc.splitTextToSize(text, 180);
                doc.text(splitText, 15, 35);

                doc.autoTable({
                    startY: doc.previousAutoTable ? doc.previousAutoTable.finalY + 10 : 75,
                    head: [['Herramienta', 'Cantidad', 'Costo Unitario', 'Subtotal']],
                    body: body,
                    theme: 'grid'
                });

                let finalY = doc.autoTable.previous.finalY;
                doc.setFontSize(12).setFont(undefined, 'bold');
                doc.text(`Total a Descontar: $${totalAmount.toFixed(2)} MXN`, 15, finalY + 15);

                finalY += 30;
                doc.setFontSize(11).setFont(undefined, 'normal');
                doc.text(`A continuación, con fecha de _______________________ se firma de conformidad el presente documento por parte de las personas involucradas en la liberación de este.`, 15, finalY);

                finalY += 30;
                doc.text("___________________________________", 15, finalY);
                doc.text(nombre.toUpperCase(), 15, finalY + 5);
                doc.text("NOMBRE Y FIRMA DE QUIEN ENTREGA MATERIAL", 15, finalY + 10);

                doc.text("___________________________________", 115, finalY);
                doc.text(responsable.toUpperCase(), 115, finalY + 5);
                doc.text("TOOLCRIB - RESPONSABLE DEL ÁREA", 115, finalY + 10);

                doc.save(`Carta_Adeudo_${nomina}.pdf`);

            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        } else {
            // --- Contenido de la Carta de No Adeudo ---
            doc.setFontSize(14).setFont(undefined, 'bold');
            doc.text("Carta De No Adeudo De Material y Herramienta", 105, 20, { align: 'center' });

            doc.setFontSize(11).setFont(undefined, 'normal');
            let text = `Por medio de la presente se hace constar que el Sr. ${nombre} con número de nómina ${nomina} ha entregado completamente el material que le fue asignado como préstamo por el área de almacén para el desarrollo de sus actividades, por lo cual me permito expedir el siguiente formato que hace constar el no adeudo de material de la persona antes mencionada.\n\nA continuación, con fecha de ______________________ se firma de conformidad el presente documento por parte de las personas involucradas en la liberación de este. .`;
            const splitText = doc.splitTextToSize(text, 180);
            doc.text(splitText, 15, 40);

            let finalY = 100;
            doc.text("___________________________________", 15, finalY);
            doc.text(nombre.toUpperCase(), 15, finalY + 5);
            doc.text("NOMBRE Y FIRMA DE QUIEN ENTREGA MATERIAL", 15, finalY + 10);

            doc.text("___________________________________", 115, finalY);
            doc.text(responsable.toUpperCase(), 115, finalY + 5);
            doc.text("TOOLCRIB - RESPONSABLE", 115, finalY + 10);

            doc.save(`Carta_No_Adeudo_${nomina}.pdf`);
        }
    }
</script>
</body>
</html>

