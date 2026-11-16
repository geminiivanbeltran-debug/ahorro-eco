<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

include 'conexion.php';
$usuario_id = $_SESSION['usuario_id'];

// Obtener correo del usuario
$stmt = $conn->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$email_usuario = ($row = $res->fetch_assoc()) ? $row['correo'] : "Desconocido";
$stmt->close();

$enviado = false;
$error_correo = "";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_encuesta'])) {
    $experiencia = $_POST['experiencia'] ?? 'No especificado';
    $facil_usar = $_POST['facil_usar'] ?? 'No especificado';
    $funcionalidades = isset($_POST['funcionalidades']) ? implode(", ", $_POST['funcionalidades']) : 'Ninguna';
    $diseno = $_POST['diseno'] ?? 'No especificado';
    $comentarios = trim($_POST['comentarios'] ?? 'Sin comentarios');

    $mail = new PHPMailer(true);

    try {
        // CONFIGURACIÓN DEL SERVIDOR SMTP (Reemplazar con tus datos reales en producción)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'TU_CORREO_SMTP@gmail.com';
        $mail->Password   = 'TU_CONTRASENA_DE_APLICACION';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Remitente y Destinatario
        $mail->setFrom('TU_CORREO_SMTP@gmail.com', 'Ahorro Eco');
        $mail->addAddress('TU_CORREO_DESTINO@gmail.com');
        $mail->addReplyTo($email_usuario, 'Usuario Ahorro Eco');

        // Contenido del correo
        $mail->isHTML(false);
        $mail->Subject = 'Nueva Respuesta de Encuesta - Ahorro Eco';
        $mail->Body    = "Se ha recibido una nueva respuesta en la encuesta de Ahorro Eco:\n\n" .
                         "Usuario (Correo): $email_usuario\n" .
                         "1. Experiencia general: $experiencia\n" .
                         "2. Es fácil de usar: $facil_usar\n" .
                         "3. Funcionalidades útiles: $funcionalidades\n" .
                         "4. Opinión de diseño: $diseno\n" .
                         "5. Comentarios adicionales:\n$comentarios\n";

        $mail->send();
        $enviado = true;
    } catch (Exception $e) {
        $error_correo = "No se pudo enviar el mensaje. Error de PHPMailer: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Encuesta</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .app-container { width: 100%; max-width: 1200px; margin: 0 auto; background-color: #ffffff; min-height: 100vh; box-sizing: border-box; box-shadow: 0 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .app-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-bottom: 1px solid #eaeaea; }
        .app-header h2 { margin: 0; color: #000; font-size: 28px; }
        .app-logo-header { width: 80px; height: auto; }
        .main-nav { display: flex; justify-content: center; gap: 35px; padding: 15px 40px; border-bottom: 2px solid #000; background-color: #ffffff; flex-wrap: wrap; }
        .main-nav a { color: #555; text-decoration: none; font-size: 14px; display: flex; flex-direction: column; align-items: center; font-weight: bold; }
        .main-nav a:hover, .main-nav a.active { color: #000; transform: scale(1.05); }
        .main-nav a span.icon { font-size: 26px; margin-bottom: 5px; }
        .nav-salir { color: #d32f2f !important; }
        
        .content { padding: 40px 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin-bottom: 20px; }
        
        .survey-box { background: #fff; border: 1px solid #eaeaea; border-radius: 12px; padding: 30px; width: 100%; max-width: 650px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: left; box-sizing: border-box; }
        .question { font-weight: bold; font-size: 15px; color: #222; margin-top: 20px; margin-bottom: 10px; }
        .options-group { margin-bottom: 15px; font-size: 14px; color: #444; }
        .options-group label { display: block; margin-bottom: 6px; cursor: pointer; }
        
        textarea { width: 100%; height: 90px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; resize: vertical; margin-top: 5px; }
        
        .btn-enviar { display: block; width: 100%; background-color: #000; color: white; border: none; padding: 14px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; margin-top: 30px; text-align: center; }
        .btn-enviar:hover { background-color: #333; }

        .gracias-box { text-align: center; padding: 60px 20px; }
        .gracias-box h2 { font-size: 28px; font-weight: 900; color: #2e7d32; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="app-header"><h2>Ahorro Eco</h2><img src="image_2f3ead.png" alt="Logo" class="app-logo-header"></header>
        
        <nav class="main-nav">
            <a href="home.php"><span class="icon">🏠</span>Home</a>
            <a href="iot.php"><span class="icon">📡</span>IOT</a>
            <a href="monitoreo.php"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <?php if ($enviado): ?>
                <div class="gracias-box">
                    <h1 class="section-title">Encuesta</h1>
                    <div style="font-size: 50px; margin-bottom: 20px;">🎉</div>
                    <h2>¡Muchas gracias por su tiempo y sus comentarios!</h2>
                    <p style="color: #666; font-size: 16px; margin-top: 15px;">Tu respuesta ha sido enviada correctamente por correo.</p>
                    <a href="home.php" class="btn-enviar" style="max-width: 250px; margin: 30px auto 0; text-decoration: none;">Volver al Inicio</a>
                </div>
            <?php else: ?>
                <h1 class="section-title">Encuesta</h1>
                
                <?php if (!empty($error_correo)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; max-width: 650px; width: 100%; box-sizing: border-box;">
                        <?php echo htmlspecialchars($error_correo); ?>
                    </div>
                <?php endif; ?>

                <div class="survey-box">
                    <form method="POST">
                        <div class="question">¿Cómo calificarías tu experiencia general con la aplicación de gestión de energía?</div>
                        <div class="options-group">
                            <label><input type="radio" name="experiencia" value="Excelente" required> Excelente</label>
                            <label><input type="radio" name="experiencia" value="Buena"> Buena</label>
                            <label><input type="radio" name="experiencia" value="Regular"> Regular</label>
                            <label><input type="radio" name="experiencia" value="Mala"> Mala</label>
                        </div>

                        <div class="question">¿Consideras que la aplicación es fácil de usar?</div>
                        <div class="options-group">
                            <label><input type="radio" name="facil_usar" value="Si" required> Sí</label>
                            <label><input type="radio" name="facil_usar" value="No"> No</label>
                        </div>

                        <div class="question">¿Qué funcionalidades de la aplicación encuentras más útiles?</div>
                        <div class="options-group">
                            <label><input type="checkbox" name="funcionalidades[]" value="Monitorización"> Monitorización</label>
                            <label><input type="checkbox" name="funcionalidades[]" value="Recomendaciones"> Recomendaciones</label>
                            <label><input type="checkbox" name="funcionalidades[]" value="Simulación"> Simulación</label>
                        </div>

                        <div class="question">¿Qué opinas sobre el diseño y la navegación de la aplicación?</div>
                        <div class="options-group">
                            <label><input type="radio" name="diseno" value="Muy bueno" required> Muy bueno</label>
                            <label><input type="radio" name="diseno" value="Bueno"> Bueno</label>
                            <label><input type="radio" name="diseno" value="Regular"> Regular</label>
                            <label><input type="radio" name="diseno" value="Malo"> Malo</label>
                        </div>

                        <div class="question">Comentarios Adicionales:</div>
                        <textarea name="comentarios" placeholder="Escribe tus sugerencias o comentarios aquí..."></textarea>

                        <button type="submit" name="enviar_encuesta" class="btn-enviar">Enviar</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
