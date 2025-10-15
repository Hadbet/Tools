<?php require_once 'dao/check_session.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Control de Herramientas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #0a2a43 0%, #0e3e5f 100%); color: #e0e0e0; }
        .main-container { background-color: rgba(14, 62, 95, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-main { background-color: #1f7a8c; transition: all 0.3s; box-shadow: 0 4px 15px rgba(31, 122, 140, 0.4); }
        .btn-main:hover { background-color: #2c9ab7; transform: scale(1.05); }
        .nav-link { transition: all 0.2s; border-bottom: 2px solid transparent; }
        .nav-link:hover, .nav-link.active { color: #67e8f9; border-bottom-color: #67e8f9; }
        .input-main { background-color: rgba(10, 42, 67, 0.8); border-color: #1f7a8c; }
    </style>
</head>
<body class="min-h-screen p-4" onload="cargarUsuarios()">
<div class="main-container rounded-2xl p-8 max-w-7xl w-full mx-auto shadow-2xl">
    <!-- Barra de Navegación -->
    <nav class="flex justify-center items-center gap-8 mb-8 border-b border-gray-700 pb-4">
        <a href="cargar.php" class="nav-link font-semibold text-lg py-2">Inicio</a>
        <a href="gestion_usuarios.php" class="nav-link active font-semibold text-lg py-2">Usuarios</a>
        <a href="dao/logout.php" class="nav-link font-semibold text-lg py-2 text-red-400 hover:text-red-300">Cerrar Sesión</a>
    </nav>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Columna del Formulario -->
        <div class="md:col-span-1 bg-gray-900 bg-opacity-30 rounded-lg p-6">
            <h2 class="text-2xl font-bold text-cyan-300 mb-4">Registrar Usuario</h2>
            <form id="formUsuario" class="space-y-4">
                <div>
                    <label for="idUsuario" class="block text-sm font-medium text-gray-300">Nómina (ID Usuario)</label>
                    <input type="number" id="idUsuario" required class="mt-1 block w-full input-main rounded-md p-2 border-gray-600 focus:ring-cyan-500 focus:border-cyan-500 text-white">
                </div>
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-300">Nombre Completo</label>
                    <input type="text" id="nombre" required class="mt-1 block w-full input-main rounded-md p-2 border-gray-600 focus:ring-cyan-500 focus:border-cyan-500 text-white">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300">Contraseña</label>
                    <input type="password" id="password" required class="mt-1 block w-full input-main rounded-md p-2 border-gray-600 focus:ring-cyan-500 focus:border-cyan-500 text-white">
                </div>
                <button type="submit" class="w-full btn-main text-white font-bold py-2 px-4 rounded-lg">
                    Crear Usuario
                </button>
            </form>
        </div>

        <!-- Columna de la Tabla -->
        <div class="md:col-span-2">
            <h2 class="text-2xl font-bold text-cyan-300 mb-4">Usuarios Registrados</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-700">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-800 uppercase">
                    <tr>
                        <th class="px-4 py-2">ID Usuario</th>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Rol</th>
                        <th class="px-4 py-2 text-center">Estado</th>
                        <th class="px-4 py-2 text-center">Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="tablaUsuarios">
                    <!-- Los datos se cargarán aquí -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    async function cargarUsuarios() {
        try {
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_usuarios.php');
            const usuarios = await response.json();
            const tbody = document.getElementById('tablaUsuarios');
            tbody.innerHTML = '';
            usuarios.forEach(user => {
                const estadoClass = user.Estado == 1 ? 'text-green-400' : 'text-red-400';
                const estadoTexto = user.Estado == 1 ? 'Activo' : 'Inactivo';
                const toggleAction = user.Estado == 1 ? 'Desactivar' : 'Activar';
                const newStatus = user.Estado == 1 ? 0 : 1;

                tbody.innerHTML += `
                    <tr class="border-t border-gray-700">
                        <td class="px-4 py-2">${user.IdUsuario}</td>
                        <td class="px-4 py-2">${user.Nombre}</td>
                        <td class="px-4 py-2">${user.Rol}</td>
                        <td class="px-4 py-2 text-center font-semibold ${estadoClass}">${estadoTexto}</td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <button onclick="cambiarPassword('${user.IdUsuario}')" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1 px-2 rounded">Password</button>
                            <button onclick="toggleEstado('${user.IdUsuario}', ${newStatus})" class="bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-bold py-1 px-2 rounded">${toggleAction}</button>
                        </td>
                    </tr>
                `;
            });
        } catch (error) {
            Swal.fire('Error', 'No se pudieron cargar los usuarios.', 'error');
        }
    }

    document.getElementById('formUsuario').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            IdUsuario: document.getElementById('idUsuario').value,
            Nombre: document.getElementById('nombre').value,
            Password: document.getElementById('password').value,
            Rol: 1,
            Estado: 1
        };

        const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_usuarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if(result.success) {
            Swal.fire('Éxito', result.message, 'success');
            document.getElementById('formUsuario').reset();
            cargarUsuarios();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    });

    async function cambiarPassword(idUsuario) {
        const { value: newPassword } = await Swal.fire({
            title: `Cambiar contraseña para ${idUsuario}`,
            input: 'password',
            inputLabel: 'Nueva Contraseña',
            inputPlaceholder: 'Ingresa la nueva contraseña',
            showCancelButton: true,
            confirmButtonText: 'Cambiar',
            cancelButtonText: 'Cancelar'
        });

        if (newPassword) {
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_usuarios.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ IdUsuario: idUsuario, Password: newPassword })
            });
            const result = await response.json();
            if(result.success) Swal.fire('Éxito', result.message, 'success');
            else Swal.fire('Error', result.message, 'error');
        }
    }

    async function toggleEstado(idUsuario, newStatus) {
        const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_usuarios.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ IdUsuario: idUsuario, Estado: newStatus })
        });
        const result = await response.json();
        if(result.success) {
            Swal.fire('Éxito', result.message, 'success');
            cargarUsuarios();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
</script>
</body>
</html>
