<?php
// tuya_api.example.php - Puente de comunicación segura entre Ahorro Eco y Tuya Cloud
// Renombra a "tuya_api.php" y reemplaza con tus credenciales de Tuya IoT Platform

function obtenerConsumoTuya($device_id) {
    // 1. CREDENCIALES DE EJEMPLO (Reemplazar con tus claves de Tuya IoT Platform)
    $client_id = "TU_TUYA_ACCESS_ID"; 
    $secret    = "TU_TUYA_ACCESS_SECRET";
    
    // Servidor Western America
    $host = "https://openapi.tuyaus.com"; 
    
    // 2. GENERAR TIMESTAMP Y FIRMA PARA EL TOKEN
    $t = time() * 1000;
    $sign_method = "HMAC-SHA256";
    
    $string_to_sign_token = $client_id . $t;
    $sign_token = strtoupper(hash_hmac('sha256', $string_to_sign_token, $secret));

    // 3. PETICIÓN cURL PARA OBTENER EL TOKEN DE ACCESO
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host . "/v1.0/token?grant_type=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "client_id: " . $client_id,
        "sign: " . $sign_token,
        "t: " . $t,
        "sign_method: " . $sign_method
    ));
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    if (!isset($data['success']) || !$data['success']) {
        return array('error' => true, 'mensaje' => "Error de Conexión: " . ($data['msg'] ?? 'Token inválido'));
    }
    
    $access_token = $data['result']['access_token'];

    // 4. USAR EL TOKEN PARA LEER EL ESTADO DEL DISPOSITIVO FÍSICO
    $t2 = time() * 1000;
    
    $nonce = ""; 
    $httpMethod = "GET";
    $contentSha256 = hash('sha256', ""); 
    $headersStr = ""; 
    $url = "/v1.0/iot-03/devices/" . $device_id . "/status";
    
    $stringToSign = $httpMethod . "\n" . $contentSha256 . "\n" . $headersStr . "\n" . $url;
    $strForSign = $client_id . $access_token . $t2 . $nonce . $stringToSign;
    $sign_device = strtoupper(hash_hmac('sha256', $strForSign, $secret));

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $host . $url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
        "client_id: " . $client_id,
        "access_token: " . $access_token,
        "sign: " . $sign_device,
        "t: " . $t2,
        "sign_method: " . $sign_method
    ));
    
    $response_device = curl_exec($ch2);
    curl_close($ch2);
    
    $device_data = json_decode($response_device, true);
    
    if (!isset($device_data['success']) || !$device_data['success']) {
        return array('error' => true, 'mensaje' => "Dispositivo no encontrado o apagado: " . ($device_data['msg'] ?? 'Error de lectura'));
    }

    // 5. FILTRAR EL DATO DE CONSUMO (Watts / Potencia actual)
    $consumo_w = 0;
    foreach ($device_data['result'] as $parametro) {
        if ($parametro['code'] == 'cur_power') {
            $consumo_w = $parametro['value'] / 10; 
        }
    }
    
    return array('error' => false, 'watts' => $consumo_w);
}
?>
