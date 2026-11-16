<?php
// Plantilla de conexión a la base de datos MySQL para Ahorro Eco
// Renombra este archivo a "conexion.php" y completa tus credenciales locales o de hosting.

$host     = "localhost";
$usuario  = "TU_USUARIO_MYSQL";
$password = "TU_CONTRASENA_MYSQL";
$basedatos = "ahorvonr_1";

// Crear conexión
$conn = new mysqli($host, $usuario, $password, $basedatos);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión con la base de datos: " . $conn->connect_error);
}

// Establecer codificación UTF-8 para tildes y caracteres especiales
$conn->set_charset("utf8mb4");
?>
