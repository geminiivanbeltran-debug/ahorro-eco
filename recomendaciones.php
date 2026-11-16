<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit(); }

include 'conexion.php';
$usuario_id = (int)$_SESSION['usuario_id'];

// Obtener datos y tarifa del usuario
$nombre_usuario = "Usuario";
$tarifa_kwh = 850.00;
$stmt_u = $conn->prepare("SELECT nombre, ciudad, valor_kwh FROM usuarios WHERE id = ? LIMIT 1");
if ($stmt_u) {
    $stmt_u->bind_param("i", $usuario_id);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    if ($ru = $res_u->fetch_assoc()) {
        $nombre_usuario = $ru['nombre'];
        if (floatval($ru['valor_kwh']) > 0) {
            $tarifa_kwh = floatval($ru['valor_kwh']);
        }
    }
    $stmt_u->close();
}

// Consultar dispositivos almacenados en `dispositivos_iot`
$dispositivos_activos = [];
$kwh_hora_activo = 0.0;
$stmt_d = $conn->prepare("SELECT nombre_dispositivo, estado, consumo_actual FROM dispositivos_iot WHERE usuario_id = ?");
if ($stmt_d) {
    $stmt_d->bind_param("i", $usuario_id);
    $stmt_d->execute();
    $res_d = $stmt_d->get_result();
    while ($rd = $res_d->fetch_assoc()) {
        if (strtolower($rd['estado']) === 'encendido') {
            $dispositivos_activos[] = $rd;
            $kwh_hora_activo += floatval($rd['consumo_actual']);
        }
    }
    $stmt_d->close();
}

// Cálculo de ahorro mensual proyectado si se reduce un 25% el tiempo de encendido (sobre 5h/día)
$gasto_mensual_est = $kwh_hora_activo * 5 * 30 * $tarifa_kwh;
$ahorro_mensual_25 = $gasto_mensual_est * 0.25;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Recomendaciones</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .app-container { width: 100%; max-width: 1200px; margin: 0 auto; background-color: #ffffff; min-height: 100vh; box-sizing: border-box; box-shadow: 0 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .app-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-bottom: 1px solid #eaeaea; }
        .app-header h2 { margin: 0; color: #000; font-size: 28px; }
        .app-logo-header { width: 80px; height: auto; }
        .main-nav { display: flex; justify-content: center; gap: 35px; padding: 15px 40px; border-bottom: 2px solid #000; background-color: #ffffff; flex-wrap: wrap; }
        .main-nav::-webkit-scrollbar { display: none; } 
        .main-nav a { color: #555; text-decoration: none; font-size: 14px; display: flex; flex-direction: column; align-items: center; font-weight: bold; transition: transform 0.2s; }
        .main-nav a:hover, .main-nav a.active { color: #000; transform: scale(1.05); }
        .main-nav a span.icon { font-size: 26px; margin-bottom: 5px; }
        .nav-salir { color: #d32f2f !important; }
        .content { padding: 40px 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin-bottom: 10px; }

        .saving-banner { background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 12px; padding: 20px 25px; width: 100%; max-width: 820px; margin: 15px 0 25px 0; box-sizing: border-box; }
        .saving-banner h3 { margin: 0 0 6px 0; color: #1b5e20; font-size: 18px; }
        .saving-banner p { margin: 0; color: #333; font-size: 14.5px; }

        .rec-list { width: 100%; max-width: 820px; display: flex; flex-direction: column; gap: 16px; }
        .module-card { background: #fff; border: 1px solid #eaeaea; border-radius: 12px; padding: 22px 26px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: left; border-left: 6px solid #ffc107; }
        .module-card.critica { border-left-color: #d32f2f; }
        .module-card.optima { border-left-color: #2e7d32; }
        .module-card h3 { margin: 0 0 8px 0; font-size: 18px; color: #111; }
        .module-card p { margin: 0; color: #555; font-size: 14.5px; line-height: 1.5; }
        .badge-impact { display: inline-block; margin-top: 10px; background: #f1f5f9; color: #0f172a; font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 6px; }

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
            <a href="iot.php"><span class="icon">📡</span>IOT</a>
            <a href="monitoreo.php"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php" class="active"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <h1 class="section-title">Recomendaciones Inteligentes</h1>
            <p style="color: #666; font-size: 17px; margin: 0;">Diagnóstico personalizado para <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> basado en tus dispositivos IoT.</p>

            <div class="saving-banner">
                <?php if (count($dispositivos_activos) > 0): ?>
                    <h3>💰 Potencial de Ahorro Mensual Detectado: $<?php echo number_format($ahorro_mensual_25, 0, ',', '.'); ?> COP</h3>
                    <p>Actualmente tienes <strong><?php echo count($dispositivos_activos); ?> dispositivo(s) encendido(s)</strong> consumiendo <strong><?php echo number_format($kwh_hora_activo, 3); ?> kWh/h</strong>. Reducir 1.2 horas diarias de uso inactivo generaría este ahorro directo en tu factura.</p>
                <?php else: ?>
                    <h3>✅ ¡Excelente gestión energética!</h3>
                    <p>Todos tus dispositivos IoT sincronizados se encuentran actualmente apagados (0.000 kWh en consumo activo).</p>
                <?php endif; ?>
            </div>
            
            <div class="rec-list">
                <?php foreach ($dispositivos_activos as $disp): 
                    $consumo_d = floatval($disp['consumo_actual']);
                    $costo_mes_d = $consumo_d * 5 * 30 * $tarifa_kwh;
                ?>
                <div class="module-card critica">
                    <h3>⚡ Dispositivo Activo: <?php echo htmlspecialchars($disp['nombre_dispositivo']); ?></h3>
                    <p>Este canal registra un consumo activo de <strong><?php echo number_format($consumo_d, 3); ?> kWh/h</strong>. Si no lo estás utilizando en este momento, apágalo remotamente desde el módulo <em>IOT</em> para evitar gasto innecesario.</p>
                    <span class="badge-impact">Impacto estimado: $<?php echo number_format($costo_mes_d, 0, ',', '.'); ?> COP / mes (uso 5h/día)</span>
                </div>
                <?php endforeach; ?>

                <div class="module-card">
                    <h3>💡 Elimina el Consumo Vampiro (Standby)</h3>
                    <p>Los equipos conectados en regletas múltiples (televisores, consolas y decodificadores) siguen demandando energía aunque estén apagados. Programa un corte nocturno en tus tomas Tuya para ahorrar hasta un 12% mensual.</p>
                    <span class="badge-impact">Ahorro estimado: 10% - 12% en factura</span>
                </div>

                <div class="module-card optima">
                    <h3>🌿 Optimización de Horarios de Mayor Demanda</h3>
                    <p>Evita operar electrodomésticos de alto consumo (lavadora, plancha, horno) simultáneamente durante las horas pico nocturnas (6:00 p. m. a 9:00 p. m.) para mantener una carga estable en tu red doméstica.</p>
                    <span class="badge-impact">Beneficio: Mayor vida útil de tus equipos</span>
                </div>
            </div>
        </main>
    </div>
</body>
</html>