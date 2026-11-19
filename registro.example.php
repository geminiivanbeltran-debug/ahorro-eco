<?php
declare(strict_types=1);

/**
 * Plantilla de registro de usuarios de Ahorro Eco (RF-01).
 * Renómbrala a "registro.php" y completa las llaves de reCAPTCHA y las credenciales SMTP.
 * (registro.php está en .gitignore para que las llaves no se publiquen.)
 *
 * Cambios frente a la demo: el usuario elige su perfil (hogar o microempresa) y su estrato o
 * sector; todas las consultas son preparadas; el correo de bienvenida ya NO incluye la
 * contraseña; se pide el consentimiento de tratamiento de datos (Ley 1581 de 2012) y se
 * recogen solo los datos necesarios (sin dirección).
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/inc/bootstrap.php';
$CFG = require __DIR__ . '/inc/config.php';

const RECAPTCHA_SECRET = 'TU_RECAPTCHA_SECRET_KEY';
const RECAPTCHA_SITE   = 'TU_RECAPTCHA_SITE_KEY';
const SMTP_USUARIO     = 'TU_CORREO_SMTP@gmail.com';
const SMTP_CLAVE       = 'TU_CONTRASENA_DE_APLICACION';
const URL_ACCESO       = 'https://TU_DOMINIO/index.php';

$mensaje = '';
$registrado = false;
$v = ['nombre' => '', 'apellidos' => '', 'correo' => '', 'tipo' => 'hogar', 'estrato' => '', 'sector' => ''];

function verificar_captcha(string $respuesta): bool
{
    if (RECAPTCHA_SECRET === 'TU_RECAPTCHA_SECRET_KEY') {
        return true; // modo desarrollo: aún no se configuró reCAPTCHA. Configúralo antes de publicar.
    }
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => RECAPTCHA_SECRET, 'response' => $respuesta]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    $salida = curl_exec($ch);
    curl_close($ch);
    $datos = is_string($salida) ? json_decode($salida, true) : null;
    return is_array($datos) && !empty($datos['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();

    $v['nombre']    = trim((string) ($_POST['nombre'] ?? ''));
    $v['apellidos'] = trim((string) ($_POST['apellidos'] ?? ''));
    $v['correo']    = trim((string) ($_POST['correo'] ?? ''));
    $v['tipo']      = (string) ($_POST['tipo_usuario'] ?? 'hogar');
    $v['estrato']   = (string) ($_POST['estrato'] ?? '');
    $v['sector']    = (string) ($_POST['sector'] ?? '');
    $clave          = (string) ($_POST['clave'] ?? '');
    $confirmar      = (string) ($_POST['confirmar_clave'] ?? '');

    $error = null;
    if (!verificar_captcha((string) ($_POST['g-recaptcha-response'] ?? ''))) {
        $error = 'Por favor, verifica que no eres un robot (captcha).';
    } elseif ($v['nombre'] === '' || mb_strlen($v['nombre']) > 100 || $v['apellidos'] === '' || mb_strlen($v['apellidos']) > 100) {
        $error = 'Escribe tu nombre y tus apellidos (máximo 100 caracteres cada uno).';
    } elseif (!filter_var($v['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['correo']) > 100) {
        $error = 'El correo electrónico no es válido.';
    } elseif (!in_array($v['tipo'], ['hogar', 'microempresa'], true)) {
        $error = 'Elige si eres un hogar o una microempresa.';
    } elseif ($v['tipo'] === 'hogar' && !preg_match('/^[1-6]$/', $v['estrato'])) {
        $error = 'Elige tu estrato (1 a 6).';
    } elseif ($v['tipo'] === 'microempresa' && !isset($CFG['sectores'][$v['sector']])) {
        $error = 'Elige el sector de tu negocio.';
    } elseif (empty($_POST['acepto_datos'])) {
        $error = 'Debes aceptar el tratamiento de tus datos personales para registrarte.';
    } elseif ($clave !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $clave)) {
        $error = 'La clave debe tener al menos 8 caracteres, una mayúscula, una minúscula y un carácter especial.';
    }

    if ($error === null) {
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->bind_param('s', $v['correo']);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($existe) {
            $error = 'Este correo electrónico ya se encuentra registrado.';
        }
    }

    if ($error !== null) {
        $mensaje = "<div class='alerta error'>" . e($error) . "</div>";
    } else {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $depto = 'Valle del Cauca';
        $ciudad = 'Cali';
        $estrato = $v['tipo'] === 'hogar' ? (int) $v['estrato'] : null;
        $sector = $v['tipo'] === 'microempresa' ? $v['sector'] : null;

        $stmt = $conn->prepare(
            'INSERT INTO usuarios (nombre, apellidos, departamento, ciudad, correo, clave, tipo_usuario, estrato, sector)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('sssssssis', $v['nombre'], $v['apellidos'], $depto, $ciudad, $v['correo'], $hash, $v['tipo'], $estrato, $sector);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Ahorro Eco: error al registrar usuario: ' . $stmt->error);
        }
        $stmt->close();

        if (!$ok) {
            $mensaje = "<div class='alerta error'>No se pudo completar el registro. Inténtalo de nuevo más tarde.</div>";
        } else {
            $registrado = true;
            $mensaje = "<div class='alerta exito'>Registro exitoso. Ya puedes iniciar sesión.</div>";

            // Correo de bienvenida (opcional): nunca incluye la contraseña.
            if (SMTP_USUARIO !== 'TU_CORREO_SMTP@gmail.com') {
                try {
                    require_once __DIR__ . '/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
                    $mail = new PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USUARIO;
                    $mail->Password = SMTP_CLAVE;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->setFrom(SMTP_USUARIO, 'Ahorro Eco');
                    $mail->addAddress($v['correo'], $v['nombre']);
                    $mail->isHTML(true);
                    $mail->Subject = '¡Bienvenido a Ahorro Eco!';
                    $mail->Body = '<div style="font-family:Arial,sans-serif;color:#333;max-width:560px;margin:0 auto">'
                        . '<h2>¡Hola ' . e($v['nombre']) . '!</h2>'
                        . '<p>Tu cuenta de <b>Ahorro Eco</b> fue creada. Ingresa con tu correo <b>' . e($v['correo']) . '</b> y la contraseña que elegiste.</p>'
                        . '<p><a href="' . e(URL_ACCESO) . '">Entrar a Ahorro Eco</a></p>'
                        . '<p style="font-size:12px;color:#777">Por tu seguridad, nunca enviamos tu contraseña por correo.</p></div>';
                    $mail->send();
                } catch (Exception $ex) {
                    error_log('Ahorro Eco: no se pudo enviar el correo de bienvenida: ' . $ex->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Registro de Usuario</title>
    <?php if (RECAPTCHA_SECRET !== 'TU_RECAPTCHA_SECRET_KEY'): ?><script src="https://www.google.com/recaptcha/api.js" async defer></script><?php endif; ?>
    <?php include __DIR__ . '/inc/pwa_head.php'; ?>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 30px 16px; box-sizing: border-box; }
        .card { width: 100%; max-width: 720px; background: #fff; padding: 36px 40px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,.1); box-sizing: border-box; }
        .titulo { text-align: center; margin-bottom: 24px; }
        .titulo img { width: 110px; display: block; margin: 0 auto 8px; }
        .titulo h1 { color: #2e7d32; font-size: 26px; font-weight: 900; margin: 0 0 4px; }
        .titulo p { margin: 0; color: #555; font-weight: bold; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
        .full { grid-column: 1 / -1; }
        label { font-size: 14px; font-weight: bold; color: #333; display: block; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password], select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fff; }
        input:focus, select:focus { border-color: #2e7d32; outline: none; }
        .opciones { display: flex; gap: 12px; }
        .opcion { flex: 1; border: 2px solid #ccc; border-radius: 8px; padding: 12px; font-weight: bold; cursor: pointer; display: flex; gap: 8px; align-items: center; margin: 0; }
        .opcion input { width: auto; }
        .check { display: flex; gap: 8px; align-items: flex-start; font-weight: normal; font-size: 13.5px; color: #444; }
        .check input { width: auto; margin-top: 3px; }
        .btn { padding: 14px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; box-sizing: border-box; border: none; text-align: center; display: block; text-decoration: none; }
        .btn-negro { background: #000; color: #fff; } .btn-borde { background: transparent; color: #000; border: 2px solid #000; margin-top: 10px; }
        .alerta { padding: 14px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alerta.exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alerta.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .ayuda { font-size: 12px; color: #777; margin-top: 4px; }
        @media (max-width: 700px) { .card { padding: 26px 18px; } .grid { grid-template-columns: 1fr; } .opciones { flex-direction: column; } }
    </style>
</head>
<body>
<div class="card">
    <div class="titulo">
        <img src="image_2f3ead.png" alt="Logo Ahorro Eco">
        <h1>Ahorro Eco</h1>
        <p>Creación de nueva cuenta</p>
    </div>
    <?= $mensaje ?>

    <?php if ($registrado): ?>
        <a href="index.php" class="btn btn-negro">Ir a iniciar sesión</a>
    <?php else: ?>
    <form action="registro.php" method="POST">
        <?= csrf_campo() ?>
        <div class="grid">
            <div><label for="nombre">Nombre</label><input type="text" id="nombre" name="nombre" maxlength="100" required value="<?= e($v['nombre']) ?>"></div>
            <div><label for="apellidos">Apellidos</label><input type="text" id="apellidos" name="apellidos" maxlength="100" required value="<?= e($v['apellidos']) ?>"></div>
            <div class="full"><label for="correo">Correo electrónico</label><input type="email" id="correo" name="correo" maxlength="100" required value="<?= e($v['correo']) ?>"></div>

            <div class="full">
                <label>¿Quién usará Ahorro Eco?</label>
                <div class="opciones">
                    <label class="opcion"><input type="radio" name="tipo_usuario" value="hogar" onchange="alternar()" <?= $v['tipo'] === 'hogar' ? 'checked' : '' ?>> 🏠 Un hogar</label>
                    <label class="opcion"><input type="radio" name="tipo_usuario" value="microempresa" onchange="alternar()" <?= $v['tipo'] === 'microempresa' ? 'checked' : '' ?>> 🏪 Una microempresa</label>
                </div>
            </div>

            <div id="grp_estrato">
                <label for="estrato">Estrato</label>
                <select id="estrato" name="estrato">
                    <?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>" <?= $v['estrato'] === (string) $i ? 'selected' : '' ?>>Estrato <?= $i ?></option><?php endfor; ?>
                </select>
                <div class="ayuda">Define la tarifa de EMCALI que se usa en tus cálculos.</div>
            </div>
            <div id="grp_sector" style="display:none">
                <label for="sector">Sector del negocio</label>
                <select id="sector" name="sector">
                    <?php foreach ($CFG['sectores'] as $clave => $texto): ?><option value="<?= e($clave) ?>" <?= $v['sector'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option><?php endforeach; ?>
                </select>
                <div class="ayuda">Las microempresas pagan la tarifa comercial.</div>
            </div>
            <div><label>Ciudad</label><input type="text" value="Santiago de Cali, Valle del Cauca" readonly><div class="ayuda">El prototipo usa las tarifas de EMCALI.</div></div>

            <div><label for="clave">Contraseña</label><input type="password" id="clave" name="clave" required placeholder="Mín. 8, mayúscula, minúscula y especial" autocomplete="new-password"></div>
            <div><label for="confirmar_clave">Confirmar contraseña</label><input type="password" id="confirmar_clave" name="confirmar_clave" required autocomplete="new-password"></div>

            <div class="full">
                <label class="check"><input type="checkbox" name="acepto_datos" value="1" required>
                    <span>Autorizo el tratamiento de mis datos personales (correo, tipo de usuario, estrato o sector, equipos y lecturas) con fines académicos del proyecto Ahorro Eco, conforme a la Ley 1581 de 2012. Mi participación es voluntaria y puedo pedir la eliminación de mis datos en cualquier momento desde mi perfil.</span></label>
            </div>

            <?php if (RECAPTCHA_SECRET !== 'TU_RECAPTCHA_SECRET_KEY'): ?>
            <div class="full" style="display:flex;justify-content:center"><div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE) ?>"></div></div>
            <?php endif; ?>

            <div class="full">
                <button type="submit" class="btn btn-negro">Registrar en el sistema</button>
                <a class="btn btn-borde" href="index.php">Volver al inicio</a>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
<script>
function alternar() {
    var micro = document.querySelector('input[name=tipo_usuario]:checked').value === 'microempresa';
    document.getElementById('grp_estrato').style.display = micro ? 'none' : '';
    document.getElementById('grp_sector').style.display = micro ? '' : 'none';
}
alternar();
</script>
</body>
</html>
