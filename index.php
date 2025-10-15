<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Control de Herramientas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #video-bg {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: -100;
            background-size: cover;
            filter: brightness(0.4);
        }
    </style>
</head>
<body>
<video autoplay muted loop id="video-bg">
    <!-- IMPORTANTE: Crea una carpeta 'videos' y pon un video llamado 'background.mp4' dentro -->
    <source src="https://grammermx.com/AleTest/ATS/videos/Video.mp4" type="video/mp4">
    Tu navegador no soporta videos de fondo.
</video>

<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-gray-900 bg-opacity-70 rounded-2xl shadow-lg p-8 space-y-8 backdrop-blur-sm border border-gray-700">
        <div>
            <img class="mx-auto h-16 w-auto" src="images/logo.png" alt="Logo">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                Control de Herramientas
            </h2>
        </div>
        <form id="loginForm" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <input id="idUsuario" name="idUsuario" type="number" required class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-700 bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm rounded-t-md" placeholder="Número de Nómina (Usuario)">
                </div>
                <div>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-700 bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm rounded-b-md" placeholder="Contraseña">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-cyan-600 hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 focus:ring-offset-gray-900">
                    Iniciar Sesión
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const idUsuario = document.getElementById('idUsuario').value;
        const password = document.getElementById('password').value;

        const formData = new FormData();
        formData.append('IdUsuario', idUsuario);
        formData.append('Password', password);

        try {
            const response = await fetch('https://grammermx.com/Mantenimiento/Tools/dao/api_login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = 'cargar.php';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de acceso',
                    text: result.message,
                    background: '#1f2937',
                    color: '#e5e7eb'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'No se pudo conectar con el servidor. Inténtalo más tarde.',
                background: '#1f2937',
                color: '#e5e7eb'
            });
        }
    });
</script>
</body>
</html>
