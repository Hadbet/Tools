<?php
session_start();
if (!isset($_SESSION['IdUsuario'])) {
    // Si no hay sesión activa, redirige al login
    header("Location: login.html");
    exit();
}
// Si hay sesión, el script que lo incluyó puede continuar.
?>
