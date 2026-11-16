<?php
// Forzar a PHP a mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Iniciando prueba del sistema...</h2>";

// 1. Verificar si los archivos físicos existen antes de llamarlos
$rutas = [
    'PHPMailer/src/Exception.php',
    'PHPMailer/src/PHPMailer.php',
    'PHPMailer/src/SMTP.php'
];

foreach ($rutas as $ruta) {
    if (!file_exists($ruta)) {
        die("<div style='color: red; border: 2px solid red; padding: 15px;'>
             <h3>ERROR: Archivo no encontrado</h3>
             <p>No se puede encontrar el archivo: <b>$ruta</b></p>
             <p>Solución: Entra al administrador de archivos de tu hosting gratuito. Asegúrate de que subiste la carpeta llamada <b>PHPMailer</b> (respetando las mayúsculas) y que dentro de ella está la carpeta <b>src</b>.</p>
             </div>");
    }
}

// Si pasa la prueba, cargamos la librería
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

echo "<p style='color:blue;'>Librería PHPMailer cargada correctamente. Intentando conectar con Google...</p>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Mostrará todo el proceso técnico en pantalla
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    
    // =========================================================================
    // MODIFICA ESTAS TRES LÍNEAS
    // =========================================================================
    $mi_correo     = 'geminiivanbeltran@gmail.com'; 
    $mi_contrasena = 'dmjepvvrdlzctldk'; 
    $correo_destino = 'ivandariobeltran@gmail.com'; 
    // =========================================================================

    $mail->Username   = $mi_correo; 
    $mail->Password   = $mi_contrasena; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
    $mail->Port       = 465;

    $mail->setFrom($mi_correo, 'Ahorro Eco');
    $mail->addAddress($correo_destino);

    $mail->isHTML(true);
    $mail->Subject = 'Prueba Ahorro Eco';
    $mail->Body    = 'El correo funciona perfectamente en el hosting.';

    $mail->send();
    echo "<br><div style='color: green; border: 2px solid green; padding: 15px;'>¡ÉXITO! Correo enviado.</div>";
    
} catch (Exception $e) {
    echo "<br><div style='color: red; border: 2px solid red; padding: 15px;'>ERROR de conexión: {$mail->ErrorInfo}</div>";
}
?>