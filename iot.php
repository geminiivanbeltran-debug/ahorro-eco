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
$usuario_id = (int)$_SESSION['usuario_id'];

// 1. OBTENER VALOR_KWH DESDE EL PERFIL DEL USUARIO
$stmt_user = $conn->prepare("SELECT valor_kwh FROM usuarios WHERE id = ?");
$stmt_user->bind_param("i", $usuario_id);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$tarifa_kwh_pesos = 850.00; 
if ($row_user = $res_user->fetch_assoc()) {
    if (isset($row_user['valor_kwh']) && is_numeric($row_user['valor_kwh']) && floatval($row_user['valor_kwh']) > 0) {
        $tarifa_kwh_pesos = floatval($row_user['valor_kwh']);
    }
}
$stmt_user->close();

$client_id = "hnhueeku99u39st3fhth"; 
$secret    = "6d8fa8df399d4ee5a18d4767cf3bda77"; 
$host      = "https://openapi.tuyaus.com"; 

function obtenerTokenTuya($client_id, $secret, $host) {
    $t = round(microtime(true) * 1000);
    $httpMethod = "GET";
    $contentSha256 = hash('sha256', "");
    $stringToSignToken = $httpMethod . "\n" . $contentSha256 . "\n\n" . "/v1.0/token?grant_type=1";
    $strForSignToken = $client_id . $t . $stringToSignToken;
    $sign_token = strtoupper(hash_hmac('sha256', $strForSignToken, $secret));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host . "/v1.0/token?grant_type=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "client_id: " . $client_id, "sign: " . $sign_token, "t: " . $t, "sign_method: HMAC-SHA256"
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return (isset($data['success']) && $data['success']) ? $data['result']['access_token'] : null;
}

function consultarEstadoTuya($device_id, $access_token, $client_id, $secret, $host) {
    $t2 = round(microtime(true) * 1000);
    $url = "/v1.0/iot-03/devices/" . $device_id . "/status";
    $stringToSignDevice = "GET" . "\n" . hash('sha256', "") . "\n\n" . $url;
    $strForSignDevice = $client_id . $access_token . $t2 . "" . $stringToSignDevice;
    $sign_device = strtoupper(hash_hmac('sha256', $strForSignDevice, $secret));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "client_id: " . $client_id, "access_token: " . $access_token, "sign: " . $sign_device, "t: " . $t2, "sign_method: HMAC-SHA256"
    ));
    $resp_dev = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp_dev, true);
}

$mensaje_alerta = "";
$tipo_alerta = "";

// 2. VINCULAR NUEVO DISPOSITIVO CON VALIDACIÓN EN TUYA API
if (isset($_POST['crear_dispositivo'])) {
    $nombre_nuevo   = trim($_POST['nombre_dispositivo'] ?? '');
    $dev_id_nuevo   = trim($_POST['nuevo_device_id'] ?? '');
    $switch_nuevo   = trim($_POST['nuevo_switch_code'] ?? 'switch_1');
    $potencia_nueva = floatval($_POST['potencia_watts'] ?? 120.0);

    if ($nombre_nuevo === '' || $dev_id_nuevo === '') {
        $mensaje_alerta = "Debes ingresar el nombre y el Device ID de Tuya.";
        $tipo_alerta = "error";
    } else {
        $token_val = obtenerTokenTuya($client_id, $secret, $host);
        if (!$token_val) {
            $mensaje_alerta = "No se pudo autenticar con Tuya Cloud.";
            $tipo_alerta = "error";
        } else {
            $verificacion = consultarEstadoTuya($dev_id_nuevo, $token_val, $client_id, $secret, $host);
            if (isset($verificacion['success']) && $verificacion['success'] === true) {
                $stmt_ins = $conn->prepare("INSERT INTO dispositivos_usuario (usuario_id, nombre_dispositivo, device_id, tarifa_kwh, potencia_watts, switch_code) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt_ins) {
                    $stmt_ins->bind_param("issdds", $usuario_id, $nombre_nuevo, $dev_id_nuevo, $tarifa_kwh_pesos, $potencia_nueva, $switch_nuevo);
                    if ($stmt_ins->execute()) {
                        $mensaje_alerta = "Dispositivo '" . htmlspecialchars($nombre_nuevo) . "' verificado y guardado.";
                        $tipo_alerta = "success";
                    } else {
                        $mensaje_alerta = ($conn->errno === 1062)
                            ? "Este Device ID ya existe (ejecuta ALTER TABLE dispositivos_usuario DROP INDEX device_id si deseas agregar otro canal del mismo enchufe)."
                            : "Error BD: " . $conn->error;
                        $tipo_alerta = "error";
                    }
                    $stmt_ins->close();
                }
            } else {
                $err_tuya = $verificacion['msg'] ?? 'Device ID no válido en Tuya Cloud.';
                $mensaje_alerta = "Error Tuya API: " . htmlspecialchars($err_tuya);
                $tipo_alerta = "error";
            }
        }
    }
}

// 3. ELIMINAR DISPOSITIVO VINCULADO
if (isset($_POST['eliminar_dispositivo']) && isset($_POST['id_registro_bd'])) {
    $id_reg = (int)$_POST['id_registro_bd'];
    $stmt_del = $conn->prepare("DELETE FROM dispositivos_usuario WHERE id = ? AND usuario_id = ?");
    if ($stmt_del) {
        $stmt_del->bind_param("ii", $id_reg, $usuario_id);
        $stmt_del->execute();
        $stmt_del->close();
        $mensaje_alerta = "Dispositivo eliminado.";
        $tipo_alerta = "success";
    }
}

// 4. ENCENDER / APAGAR EN TUYA CLOUD
if (isset($_POST['accion_toggle']) && isset($_POST['device_id']) && isset($_POST['switch_code'])) {
    $dev_id_objetivo = $_POST['device_id'];
    $switch_code = $_POST['switch_code'];
    $nuevo_estado = ($_POST['accion_toggle'] === 'encender') ? true : false;
    $access_token = obtenerTokenTuya($client_id, $secret, $host);
    
    if ($access_token) {
        $t = round(microtime(true) * 1000);
        $url = "/v1.0/iot-03/devices/" . $dev_id_objetivo . "/commands";
        
        $payload = array("commands" => array(array("code" => $switch_code, "value" => $nuevo_estado)));
        $json_payload = json_encode($payload);
        
        $stringToSign = "POST" . "\n" . hash('sha256', $json_payload) . "\n\n" . $url;
        $strForSign = $client_id . $access_token . $t . "" . $stringToSign;
        $sign = strtoupper(hash_hmac('sha256', $strForSign, $secret));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "client_id: " . $client_id, "access_token: " . $access_token, "sign: " . $sign, "t: " . $t, "sign_method: HMAC-SHA256", "Content-Type: application/json"
        ));
        curl_exec($ch);
        curl_close($ch);
        usleep(400000); // Esperar 0.4s para que Tuya actualice su estado antes de recargar
    }
    header("Location: iot.php");
    exit();
}

// 5. CONSTRUIR LISTA UNIFICADA DE DISPOSITIVOS
$lista_dispositivos = array(
    array(
        "bd_id" => null,
        "nombre" => "Toma TV", 
        "id" => "eba43172697800f9e1pgri", 
        "tipo" => "simple", 
        "switch" => "switch_1",
        "potencia_base" => 120.0
    ),
    array(
        "bd_id" => null,
        "nombre" => "Toma Múltiple - Canal 1", 
        "id" => "ebb9ce311b9f23189eli7h", 
        "tipo" => "doble", 
        "switch" => "switch_1",
        "potencia_base" => 60.0
    ),
    array(
        "bd_id" => null,
        "nombre" => "Toma Múltiple - Canal 2", 
        "id" => "ebb9ce311b9f23189eli7h", 
        "tipo" => "doble", 
        "switch" => "switch_2",
        "potencia_base" => 60.0
    )
);

$stmt_disp = $conn->prepare("SELECT id, nombre_dispositivo, device_id, potencia_watts, switch_code FROM dispositivos_usuario WHERE usuario_id = ? ORDER BY id ASC");
if ($stmt_disp) {
    $stmt_disp->bind_param("i", $usuario_id);
    $stmt_disp->execute();
    $res_disp = $stmt_disp->get_result();
    while ($row_d = $res_disp->fetch_assoc()) {
        $lista_dispositivos[] = array(
            "bd_id" => $row_d['id'],
            "nombre" => $row_d['nombre_dispositivo'],
            "id" => $row_d['device_id'],
            "tipo" => "simple",
            "switch" => $row_d['switch_code'],
            "potencia_base" => floatval($row_d['potencia_watts'])
        );
    }
    $stmt_disp->close();
}

// 6. CONSULTAR ESTADOS EN TUYA Y CALCULAR CANALES ACTIVOS POR DISPOSITIVO FÍSICO
$access_token_global = obtenerTokenTuya($client_id, $secret, $host);
$cache_estados = array();
$canales_activos_por_id = array();

foreach ($lista_dispositivos as $dev) {
    $did = $dev['id'];
    if (!isset($cache_estados[$did]) && $access_token_global) {
        $cache_estados[$did] = consultarEstadoTuya($did, $access_token_global, $client_id, $secret, $host);
    }
    if (!isset($canales_activos_por_id[$did])) {
        $canales_activos_por_id[$did] = 0;
    }
    $dev_data = $cache_estados[$did] ?? null;
    if ($dev_data && isset($dev_data['result']) && is_array($dev_data['result'])) {
        foreach ($dev_data['result'] as $p) {
            if ($p['code'] === $dev['switch'] && ($p['value'] === true || $p['value'] === 1 || $p['value'] === "true")) {
                $canales_activos_por_id[$did]++;
            }
        }
    }
}

// Verificar si existe la tabla de historial para guardar trazabilidad
$check_hist = $conn->query("SHOW TABLES LIKE 'historial_consumo_iot'");
$existe_tabla_hist = ($check_hist && $check_hist->num_rows > 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - IOT</title>
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
        .content { padding: 40px 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin-bottom: 10px; text-align: center; }

        .alert-box { width: 100%; max-width: 900px; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: bold; box-sizing: border-box; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .add-device-panel { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px 25px; width: 100%; max-width: 900px; margin-bottom: 25px; box-sizing: border-box; }
        .add-device-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .add-device-header h3 { margin: 0; font-size: 17px; color: #1e293b; }
        .btn-toggle-form { background: #2e7d32; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .add-device-form { display: none; margin-top: 18px; padding-top: 18px; border-top: 1px solid #e2e8f0; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 5px; }
        .form-group input, .form-group select { padding: 9px 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; }
        .btn-save-device { background: #0f172a; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 14px; }
        .btn-save-device:hover { background: #2e7d32; }

        .device-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; width: 100%; max-width: 900px; margin-top: 10px;}
        .device-card { background: #fff; border: 1px solid #eaeaea; border-radius: 12px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: left; position: relative; }
        .device-name { font-weight: bold; font-size: 20px; margin-bottom: 5px; color: #2e7d32; }
        
        .status-online { font-size: 12px; font-weight: bold; padding: 3px 8px; border-radius: 12px; display: inline-block; margin-bottom: 15px; background-color: #d4edda; color: #155724; }
        .status-offline { font-size: 12px; font-weight: bold; padding: 3px 8px; border-radius: 12px; display: inline-block; margin-bottom: 15px; background-color: #f8d7da; color: #721c24; }
        
        .metric-row { font-size: 13px; color: #555; margin-bottom: 5px; }
        .metric-val { font-weight: bold; color: #000; }
        .cost-val { font-weight: bold; color: #d32f2f; }

        .global-summary { background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 12px; padding: 20px; width: 100%; max-width: 900px; margin-top: 30px; box-sizing: border-box; text-align: center; }
        
        .btn-control { display: block; width: 100%; padding: 10px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; color: white; text-align: center; margin-top: 15px; }
        .btn-on { background-color: #d32f2f; }
        .btn-off { background-color: #2e7d32; }
        .btn-delete-dev { position: absolute; top: 15px; right: 15px; background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer; }
        .btn-delete-dev:hover { color: #d32f2f; }

        @media (max-width: 768px) {
            .app-header { padding: 15px 20px; } .app-header h2 { font-size: 22px; } .app-logo-header { width: 60px; }
            .main-nav { justify-content: flex-start; flex-wrap: nowrap; gap: 15px; padding: 10px 15px; overflow-x: auto; white-space: nowrap; border-bottom: 3px solid #000; }
            .main-nav a { font-size: 11px; min-width: 65px; } .main-nav a span.icon { font-size: 22px; }
            .content { padding: 30px 15px; } .section-title { font-size: 26px; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="app-header"><h2>Ahorro Eco</h2><img src="image_2f3ead.png" alt="Logo" class="app-logo-header"></header>
        
        <nav class="main-nav">
            <a href="home.php"><span class="icon">🏠</span>Home</a>
            <a href="iot.php" class="active"><span class="icon">📡</span>IOT</a>
            <a href="monitoreo.php"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <h1 class="section-title">Dispositivos y Consumo IoT</h1>
            <p style="color: #666; font-size: 16px; margin-bottom: 10px; text-align: center;">Control, cálculo exacto y sincronización automática con MySQL.</p>
            <div style="font-size: 14px; color: #444; margin-bottom: 20px;">Tarifa de tu perfil: <b>$<?php echo number_format($tarifa_kwh_pesos, 2); ?> COP / kWh</b></div>

            <?php if ($mensaje_alerta !== ""): ?>
                <div class="alert-box <?php echo ($tipo_alerta === 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $mensaje_alerta; ?>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO PARA VINCULAR NUEVO DISPOSITIVO CON API DE TUYA -->
            <div class="add-device-panel">
                <div class="add-device-header" onclick="toggleFormTuya()">
                    <h3>➕ Vincular Nuevo Dispositivo Tuya Cloud</h3>
                    <button type="button" class="btn-toggle-form" id="btn-toggle-txt">Agregar Dispositivo</button>
                </div>
                <form method="POST" class="add-device-form" id="form-nuevo-tuya">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre del Dispositivo</label>
                            <input type="text" name="nombre_dispositivo" placeholder="Ej: Toma Nevera / Aire" required>
                        </div>
                        <div class="form-group">
                            <label>Device ID (Tuya IoT Platform)</label>
                            <input type="text" name="nuevo_device_id" placeholder="Ej: eba43172697800f9..." required>
                        </div>
                        <div class="form-group">
                            <label>Código de Switch (Canal)</label>
                            <select name="nuevo_switch_code">
                                <option value="switch_1">switch_1 (Principal)</option>
                                <option value="switch_2">switch_2 (Canal 2)</option>
                                <option value="switch_3">switch_3 (Canal 3)</option>
                                <option value="switch">switch (Genérico)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Potencia Nominal (Watts)</label>
                            <input type="number" step="0.1" name="potencia_watts" value="120" required>
                        </div>
                    </div>
                    <button type="submit" name="crear_dispositivo" class="btn-save-device">Validar en Tuya API y Guardar</button>
                </form>
            </div>
            
            <div class="device-grid">
                <?php 
                $suma_total_watts = 0.0;
                $suma_total_kwh = 0.0;
                $suma_total_pesos = 0.0;

                foreach ($lista_dispositivos as $dev): 
                    $dev_id = $dev['id'];
                    $switch_code = $dev['switch'];
                    $esta_encendido = false;
                    $cur_power_watts = 0.0;
                    $voltaje_v = 0.0;
                    $dev_data = $cache_estados[$dev_id] ?? null;

                    if ($dev_data && isset($dev_data['result']) && is_array($dev_data['result'])) {
                        foreach ($dev_data['result'] as $p) {
                            if ($p['code'] === $switch_code) {
                                $esta_encendido = ($p['value'] === true || $p['value'] === 1 || $p['value'] === "true");
                            }
                            if ($p['code'] === 'cur_power') {
                                $cur_power_watts = floatval($p['value']) / 10.0; // Tuya reporta décimas de Watt
                            }
                            if ($p['code'] === 'cur_voltage') {
                                $voltaje_v = floatval($p['value']) / 10.0; // Tuya reporta décimas de Voltio
                            }
                        }
                    }

                    // CÁLCULO UNIFICADO POR CANAL:
                    // Si está apagado, potencia y consumo = 0.
                    // Si está encendido, se reparte la potencia activa únicamente entre los canales encendidos.
                    $activos_en_este_id = max(1, $canales_activos_por_id[$dev_id] ?? 1);

                    if ($esta_encendido) {
                        $watts_canal = ($cur_power_watts > 0)
                            ? ($cur_power_watts / $activos_en_este_id)
                            : floatval($dev['potencia_base']);

                        // Conversión directa Watts -> kWh/h para coherencia exacta con Monitoreo y Recomendaciones
                        $kwh_canal = $watts_canal / 1000.0;
                    } else {
                        $watts_canal = 0.0;
                        $kwh_canal = 0.0;
                    }

                    $costo_canal = $kwh_canal * $tarifa_kwh_pesos;
                    $estado_texto = $esta_encendido ? 'Encendido' : 'Apagado';

                    // Acumular totales reales
                    $suma_total_watts += $watts_canal;
                    $suma_total_kwh += $kwh_canal;
                    $suma_total_pesos += $costo_canal;

                    // PERSISTENCIA EN BASE DE DATOS (`dispositivos_iot`)
                    $nombre_db = $dev['nombre'];
                    $stmt_chk = $conn->prepare("SELECT id FROM dispositivos_iot WHERE usuario_id = ? AND nombre_dispositivo = ? LIMIT 1");
                    if ($stmt_chk) {
                        $stmt_chk->bind_param("is", $usuario_id, $nombre_db);
                        $stmt_chk->execute();
                        $res_chk = $stmt_chk->get_result();
                        if ($row_iot = $res_chk->fetch_assoc()) {
                            $id_iot_row = (int)$row_iot['id'];
                            $stmt_upd = $conn->prepare("UPDATE dispositivos_iot SET estado = ?, consumo_actual = ? WHERE id = ?");
                            $stmt_upd->bind_param("sdi", $estado_texto, $kwh_canal, $id_iot_row);
                            $stmt_upd->execute();
                            $stmt_upd->close();
                        } else {
                            $stmt_ins_iot = $conn->prepare("INSERT INTO dispositivos_iot (usuario_id, nombre_dispositivo, estado, consumo_actual) VALUES (?, ?, ?, ?)");
                            $stmt_ins_iot->bind_param("issd", $usuario_id, $nombre_db, $estado_texto, $kwh_canal);
                            $stmt_ins_iot->execute();
                            $stmt_ins_iot->close();
                        }
                        $stmt_chk->close();
                    }

                    // GUARDAR EN HISTORIAL DE LECTURAS (`historial_consumo_iot`) SI EXISTE LA TABLA
                    if ($existe_tabla_hist) {
                        $stmt_h = $conn->prepare("INSERT INTO historial_consumo_iot (usuario_id, device_id, nombre_dispositivo, switch_code, estado, potencia_watts, consumo_kwh, costo_cop) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt_h) {
                            $stmt_h->bind_param("issssddd", $usuario_id, $dev_id, $nombre_db, $switch_code, $estado_texto, $watts_canal, $kwh_canal, $costo_canal);
                            $stmt_h->execute();
                            $stmt_h->close();
                        }
                    }
                ?>
                    <div class="device-card">
                        <?php if ($dev['bd_id'] !== null): ?>
                            <form method="POST" onsubmit="return confirm('¿Eliminar este dispositivo vinculado?');">
                                <input type="hidden" name="id_registro_bd" value="<?php echo $dev['bd_id']; ?>">
                                <button type="submit" name="eliminar_dispositivo" class="btn-delete-dev" title="Eliminar dispositivo">✖</button>
                            </form>
                        <?php endif; ?>

                        <div style="font-size: 28px; margin-bottom: 5px;">🔌</div>
                        <div class="device-name"><?php echo htmlspecialchars($dev['nombre']); ?></div>
                        
                        <?php if ($esta_encendido): ?>
                            <span class="status-online">● Encendido (Activo)</span>
                        <?php else: ?>
                            <span class="status-offline">● Apagado (Inactivo)</span>
                        <?php endif; ?>

                        <div style="font-size: 12px; color: #777; margin-bottom: 4px;">ID: <?php echo htmlspecialchars($dev_id); ?></div>
                        <div style="font-size: 12px; color: #777; margin-bottom: 10px;">Canal: <?php echo htmlspecialchars($switch_code); ?> <?php echo ($voltaje_v > 0) ? " • " . number_format($voltaje_v, 1) . " V" : ""; ?></div>
                        
                        <div class="metric-row">Potencia Activa: <span class="metric-val"><?php echo number_format($watts_canal, 1); ?> W</span></div>
                        <div class="metric-row">Consumo Estimado/h: <span class="metric-val"><?php echo number_format($kwh_canal, 3); ?> kWh</span></div>
                        <div class="metric-row" style="margin-bottom: 15px;">Costo/h: <span class="cost-val">$<?php echo number_format($costo_canal, 2); ?> COP</span></div>

                        <form method="POST">
                            <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($dev_id); ?>">
                            <input type="hidden" name="switch_code" value="<?php echo htmlspecialchars($switch_code); ?>">
                            <?php if ($esta_encendido): ?>
                                <button type="submit" name="accion_toggle" value="apagar" class="btn-control btn-on">Apagar</button>
                            <?php else: ?>
                                <button type="submit" name="accion_toggle" value="encender" class="btn-control btn-off">Encender</button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- RESUMEN TOTAL SUMADO DE TODOS LOS DISPOSITIVOS -->
            <div class="global-summary">
                <div style="font-size: 14px; color: #333; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Consumo Total Activo (Sincronizado en MySQL)</div>
                <div style="font-size: 18px; font-weight: bold; color: #1b5e20; margin-bottom: 4px;">Carga Instantánea: <?php echo number_format($suma_total_watts, 1); ?> W</div>
                <div style="font-size: 26px; font-weight: 900; color: #2e7d32; margin-bottom: 5px;"><?php echo number_format($suma_total_kwh, 3); ?> kWh / hora</div>
                <div style="font-size: 22px; font-weight: 900; color: #d32f2f;">$<?php echo number_format($suma_total_pesos, 2); ?> COP / hora</div>
            </div>

        </main>
    </div>

    <script>
        function toggleFormTuya() {
            const form = document.getElementById('form-nuevo-tuya');
            const btn = document.getElementById('btn-toggle-txt');
            const abierto = form.style.display === 'block';
            form.style.display = abierto ? 'none' : 'block';
            btn.textContent = abierto ? 'Agregar Dispositivo' : 'Cerrar Formulario';
        }
    </script>
</body>
</html>