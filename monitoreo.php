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

// 1. Obtener la tarifa personalizada del usuario (valor_kwh)
$stmt_user =$conn->prepare("SELECT valor_kwh FROM usuarios WHERE id = ?");
$stmt_user->bind_param("i", $usuario_id);
$stmt_user->execute();$res_user = $stmt_user->get_result();$tarifa_kwh_pesos = 850.00; 
if ($row_user =$res_user->fetch_assoc()) {
    if (isset($row_user['valor_kwh']) && is_numeric($row_user['valor_kwh']) && floatval($row_user['valor_kwh']) > 0) {
        $tarifa_kwh_pesos = floatval($row_user['valor_kwh']);
    }
}
$stmt_user->close();

// 2. Consultar consumo actual sincronizado desde `dispositivos_iot`
$dispositivos_bd = [];$kwh_hora_total = 0.0;
$dispositivos_encendidos = 0;

$stmt_iot =$conn->prepare("SELECT nombre_dispositivo, estado, consumo_actual FROM dispositivos_iot WHERE usuario_id = ? ORDER BY id ASC");
if ($stmt_iot) {$stmt_iot->bind_param("i", $usuario_id);$stmt_iot->execute();
    $res_iot =$stmt_iot->get_result();
    while ($row =$res_iot->fetch_assoc()) {
        $dispositivos_bd[] =$row;
        if (strtolower($row['estado']) === 'encendido') {$dispositivos_encendidos++;
            $kwh_hora_total += floatval($row['consumo_actual']);
        }
    }
    $stmt_iot->close();
}

// Si aún no hay sincronización activa en dispositivos_iot, usar línea base de 0.120 kWh (120W)
$usando_estimacion = false;
if ($kwh_hora_total <= 0) {$kwh_hora_total = 0.120;
    $usando_estimacion = true;
}

// Proyección basada en 5 horas promedio de uso efectivo diario de los equipos activos
$horas_uso_diario = 5.0;
$kwh_dia    = $kwh_hora_total * $horas_uso_diario;
$kwh_semana =$kwh_dia * 7;
$kwh_mes    =$kwh_dia * 30;
$kwh_anio   =$kwh_dia * 365;

$costo_dia    = $kwh_dia * $tarifa_kwh_pesos;
$costo_semana = $kwh_semana * $tarifa_kwh_pesos;
$costo_mes    = $kwh_mes * $tarifa_kwh_pesos;
$costo_anio   = $kwh_anio * $tarifa_kwh_pesos;

// 3. Consultar últimas lecturas guardadas en `historial_consumo_iot` (si existe)
$labels_hist = [];$valores_hist = [];
$check_hist =$conn->query("SHOW TABLES LIKE 'historial_consumo_iot'");
if ($check_hist &&$check_hist->num_rows > 0) {
    $stmt_h =$conn->prepare("SELECT DATE_FORMAT(fecha_lectura, '%H:%i:%s') AS hora, SUM(potencia_watts) AS watts_totales FROM historial_consumo_iot WHERE usuario_id = ? GROUP BY fecha_lectura ORDER BY id DESC LIMIT 10");
    if ($stmt_h) {
        $stmt_h->bind_param("i", $usuario_id);
        $stmt_h->execute();$res_h = $stmt_h->get_result();$temp_hist = [];
        while ($rh =$res_h->fetch_assoc()) {
            $temp_hist[] =$rh;
        }
        $temp_hist = array_reverse($temp_hist);
        foreach ($temp_hist as $item) {$labels_hist[] = $item['hora'];$valores_hist[] = round(floatval($item['watts_totales']), 1);
        }$stmt_h->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Monitoreo</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .app-container { width: 100%; max-width: 1200px; margin: 0 auto; background-color: #ffffff; min-height: 100vh; box-sizing: border-box; box-shadow: 0 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .app-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-bottom: 1px solid #eaeaea; }
        .app-header h2 { margin: 0; color: #000; font-size: 28px; }
        .app-logo-header { width: 80px; height: auto; }
        .main-nav { display: flex; justify-content: center; gap: 35px; padding: 15px 40px; border-bottom: 2px solid #000; background-color: #ffffff; flex-wrap: wrap; }
        .main-nav a { color: #555; text-decoration: none; font-size: 14px; display: flex; flex-direction: column; align-items: center; font-weight: bold; transition: transform 0.2s; }
        .main-nav a:hover, .main-nav a.active { color: #000; transform: scale(1.05); }
        .main-nav a span.icon { font-size: 26px; margin-bottom: 5px; }
        .nav-salir { color: #d32f2f !important; }
        
        .content { padding: 40px 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin-bottom: 10px; text-align: center; }
        
        .tabs-container { display: flex; gap: 10px; margin-bottom: 25px; justify-content: center; flex-wrap: wrap; }
        .tab-btn { background: #e0e0e0; border: none; padding: 10px 20px; font-size: 15px; font-weight: bold; border-radius: 20px; cursor: pointer; transition: background 0.2s, color 0.2s; }
        .tab-btn.active { background: #2e7d32; color: white; }

        .stats-grid { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; width: 100%; max-width: 900px; display: none; margin-bottom: 30px; }
        .stats-grid.active-tab { display: grid; }
        
        .stat-card { background: #fff; border: 1px solid #eaeaea; border-radius: 12px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; }
        .stat-title { font-size: 16px; color: #555; font-weight: bold; margin-bottom: 10px; }
        .stat-value { font-size: 32px; font-weight: 900; color: #2e7d32; margin: 12px 0; }
        .stat-cost { font-size: 24px; font-weight: bold; color: #d32f2f; margin-top: 5px; }
        .info-detalle { font-size: 13px; color: #777; margin-top: 10px; }

        .tarifa-banner { background: #e8f5e9; border: 1px solid #c8e6c9; padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; color: #2e7d32; text-align: center; font-weight: bold; max-width: 900px; width: 100%; box-sizing: border-box; }

        .chart-container { background: #fff; border: 1px solid #eaeaea; border-radius: 12px; padding: 20px; width: 100%; max-width: 900px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); box-sizing: border-box; margin-bottom: 25px; }

        .device-table { width: 100%; max-width: 900px; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .device-table th, .device-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .device-table th { background: #f8fafc; color: #333; font-weight: bold; }
        .badge-on { background: #d4edda; color: #155724; padding: 3px 9px; border-radius: 10px; font-size: 12px; font-weight: bold; }
        .badge-off { background: #f8d7da; color: #721c24; padding: 3px 9px; border-radius: 10px; font-size: 12px; font-weight: bold; }

        @media (max-width: 768px) {
            .app-header { padding: 15px 20px; } .app-header h2 { font-size: 22px; } .app-logo-header { width: 60px; }
            .main-nav { justify-content: flex-start; flex-wrap: nowrap; gap: 15px; padding: 10px 15px; overflow-x: auto; white-space: nowrap; border-bottom: 3px solid #000; }
            .main-nav a { font-size: 11px; min-width: 65px; } .main-nav a span.icon { font-size: 22px; }
            .content { padding: 30px 15px; } .section-title { font-size: 26px; }
        }
    </style>
    <script>
        function cambiarTab(periodo) {
            document.querySelectorAll('.stats-grid').forEach(el => el.classList.remove('active-tab'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById('grid-' + periodo).classList.add('active-tab');
            document.getElementById('btn-' + periodo).classList.add('active');
        }
    </script>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2e7d32">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</head>
<body>
    <div class="app-container">
        <header class="app-header"><h2>Ahorro Eco</h2><img src="image_2f3ead.png" alt="Logo" class="app-logo-header"></header>
        
        <nav class="main-nav">
            <a href="home.php"><span class="icon">🏠</span>Home</a>
            <a href="iot.php"><span class="icon">📡</span>IOT</a>
            <a href="equipos.php"><span class="icon">🔌</span>Equipos</a>
            <a href="consumo.php"><span class="icon">⚡</span>Consumo</a>
            <a href="monitoreo.php" class="active"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <h1 class="section-title">Monitoreo de Consumo</h1>
            <p style="color: #666; font-size: 16px; margin-bottom: 20px; text-align: center;">Consolidado energético sincronizado con tus dispositivos IoT registrados.</p>
            
            <div class="tarifa-banner">
                Tarifa aplicada: <b>$<?php echo number_format($tarifa_kwh_pesos, 2); ?> COP / kWh</b> • 
                Dispositivos encendidos: <b><?php echo $dispositivos_encendidos; ?> de <?php echo count($dispositivos_bd); ?></b>
                <?php echo $usando_estimacion ? " (Modo línea base 120W — abre IOT para sincronizar cargas activas)" : ""; ?>
            </div>

            <div class="tabs-container">
                <button id="btn-dia" class="tab-btn active" onclick="cambiarTab('dia')">Día</button>
                <button id="btn-semana" class="tab-btn" onclick="cambiarTab('semana')">Semana</button>
                <button id="btn-mes" class="tab-btn" onclick="cambiarTab('mes')">Mes</button>
                <button id="btn-anio" class="tab-btn" onclick="cambiarTab('anio')">Año</button>
            </div>

            <!-- TARJETAS DE DATOS -->
            <div id="grid-dia" class="stats-grid active-tab">
                <div class="stat-card">
                    <div class="stat-title">Consumo Proyectado (Día)</div>
                    <div class="stat-value"><?php echo number_format($kwh_dia, 2); ?> kWh</div>
                    <div class="stat-cost">$<?php echo number_format($costo_dia, 2); ?> COP</div>
                    <div class="info-detalle">Base: <?php echo number_format($kwh_hora_total, 3); ?> kWh/h × 5 h/día</div>
                </div>
            </div>

            <div id="grid-semana" class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Consumo Proyectado (Semana)</div>
                    <div class="stat-value"><?php echo number_format($kwh_semana, 2); ?> kWh</div>
                    <div class="stat-cost">$<?php echo number_format($costo_semana, 2); ?> COP</div>
                    <div class="info-detalle">Proyección a 7 días continuos</div>
                </div>
            </div>

            <div id="grid-mes" class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Consumo Proyectado (Mes)</div>
                    <div class="stat-value"><?php echo number_format($kwh_mes, 2); ?> kWh</div>
                    <div class="stat-cost">$<?php echo number_format($costo_mes, 2); ?> COP</div>
                    <div class="info-detalle">Ciclo de facturación estándar (30 días)</div>
                </div>
            </div>

            <div id="grid-anio" class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Consumo Proyectado (Año)</div>
                    <div class="stat-value"><?php echo number_format($kwh_anio, 2); ?> kWh</div>
                    <div class="stat-cost">$<?php echo number_format($costo_anio, 2); ?> COP</div>
                    <div class="info-detalle">Proyección anual (365 días)</div>
                </div>
            </div>

            <!-- DESGLOSE POR DISPOSITIVO ALMACENADO EN BD -->
            <?php if (!empty($dispositivos_bd)): ?>
            <table class="device-table">
                <thead>
                    <tr>
                        <th>Dispositivo Sincronizado</th>
                        <th>Estado</th>
                        <th>Consumo / Hora</th>
                        <th>Costo / Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dispositivos_bd as $d):$enc = (strtolower($d['estado']) === 'encendido');$c_kwh = $enc ? floatval($d['consumo_actual']) : 0.0;
                        $c_cop = $c_kwh * $tarifa_kwh_pesos;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($d['nombre_dispositivo']); ?></strong></td>
                        <td><span class="<?php echo $enc ? 'badge-on' : 'badge-off'; ?>"><?php echo htmlspecialchars($d['estado']); ?></span></td>
                        <td><?php echo number_format($c_kwh, 3); ?> kWh</td>
                        <td style="color:#d32f2f; font-weight:bold;">$<?php echo number_format($c_cop, 2); ?> COP</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- GRÁFICA DE PROYECCIÓN -->
            <div class="chart-container">
                <h3 style="margin-top: 0; color: #333; text-align: center;">Proyección de Consumo Energético (kWh)</h3>
                <canvas id="graficoConsumo" style="width:100%; max-height: 320px;"></canvas>
            </div>

            <!-- GRÁFICA HISTÓRICA EN TIEMPO REAL (SI HAY LECTURAS EN `historial_consumo_iot`) -->
            <?php if (!empty($labels_hist)): ?>
            <div class="chart-container">
                <h3 style="margin-top: 0; color: #333; text-align: center;">Historial de Potencia Registrada en MySQL (Watts)</h3>
                <canvas id="graficoHistorial" style="width:100%; max-height: 300px;"></canvas>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('graficoConsumo').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Día', 'Semana', 'Mes', 'Año'],
                datasets: [{
                    label: 'Consumo Proyectado (kWh)',
                    data: [
                        <?php echo round($kwh_dia, 2); ?>, 
                        <?php echo round($kwh_semana, 2); ?>, 
                        <?php echo round($kwh_mes, 2); ?>, 
                        <?php echo round($kwh_anio, 2); ?>
                    ],
                    backgroundColor: ['#66bb6a', '#43a047', '#2e7d32', '#1b5e20'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        <?php if (!empty($labels_hist)): ?>
        const ctxHist = document.getElementById('graficoHistorial').getContext('2d');
        new Chart(ctxHist, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_hist); ?>,
                datasets: [{
                    label: 'Potencia Activa Total (Watts)',
                    data: <?php echo json_encode($valores_hist); ?>,
                    borderColor: '#0288d1',
                    backgroundColor: 'rgba(2, 136, 209, 0.12)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>