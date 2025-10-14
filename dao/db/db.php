<?php
// db.php
// Clase para la conexión a la base de datos del Toolcrib.
// Asegúrate de que los datos de conexión sean correctos.

class ToolcribDB {
    private $host = "127.0.0.1:3306";
    private $usuario = "u909553968_calidadUser"; // Recomiendo crear un usuario específico para esta app
    private $clave = "Grammer2025";
    private $db = "u909553968_Calidad"; // Deberías crear una base de datos nueva, por ejemplo: u909553968_Toolcrib
    public $conexion;

    // Método para establecer y devolver la conexión a la base de datos.
    public function conectar() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->clave, $this->db);
        if ($this->conexion->connect_error) {
            die("Conexión fallida: " . $this->conexion->connect_error);
        }
        // Asegura que la conexión maneje caracteres UTF-8 para evitar problemas con acentos y caracteres especiales.
        $this->conexion->set_charset("utf8");
        return $this->conexion;
    }
}
?>
