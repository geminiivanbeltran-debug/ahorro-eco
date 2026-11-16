<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit(); }

include 'conexion.php';
$usuario_id = (int)$_SESSION['usuario_id'];

// Obtener tarifa real del usuario desde MySQL
$tarifa_kwh = 850.00;
$stmt_u = $conn->prepare("SELECT valor_kwh FROM usuarios WHERE id = ? LIMIT 1");
if ($stmt_u) {
    $stmt_u->bind_param("i", $usuario_id);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    if ($ru = $res_u->fetch_assoc()) {
        if (floatval($ru['valor_kwh']) > 0) {
            $tarifa_kwh = floatval($ru['valor_kwh']);
        }
    }
    $stmt_u->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Simulación</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; color: #222; }
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

        .content { padding: 35px 30px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin-bottom: 8px; text-align: center; }

        .sim-wrapper { width: 100%; max-width: 960px; display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px; margin-top: 20px; }
        .card-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .card-box h3 { margin: 0 0 15px 0; font-size: 18px; color: #1b5e20; }

        .form-group { margin-bottom: 13px; }
        .form-group label { display: block; font-size: 12.5px; font-weight: bold; color: #475569; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 9px 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .btn-add { width: 100%; background: #2e7d32; color: #fff; border: none; padding: 11px; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; margin-top: 5px; }
        .btn-add:hover { background: #1b5e20; }

        .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px; }
        .kpi-mini { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
        .kpi-mini span { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .kpi-mini strong { display: block; font-size: 18px; color: #0f172a; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8fafc; color: #333; }
        .btn-del { background: none; border: none; color: #d32f2f; font-weight: bold; cursor: pointer; font-size: 15px; }

        @media (max-width: 850px) { .sim-wrapper { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .app-header { padding: 15px 20px; } .app-header h2 { font-size: 22px; } .app-logo-header { width: 60px; }
            .main-nav { justify-content: flex-start; flex-wrap: nowrap; gap: 15px; padding: 10px 15px; overflow-x: auto; white-space: nowrap; border-bottom: 3px solid #000; }
            .main-nav a { font-size: 11px; min-width: 65px; } .main-nav a span.icon { font-size: 22px; }
            .content { padding: 25px 15px; } .section-title { font-size: 26px; }
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
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php" class="active"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <h1 class="section-title">Calculadora de Simulación</h1>
            <p style="color: #666; font-size: 16px; margin: 0 0 8px 0; text-align: center;">Proyecta el consumo mensual de tus electrodomésticos con tu tarifa real.</p>
            <div style="font-size: 13.5px; color: #2e7d32; font-weight: bold;">Tarifa de tu perfil: $<?php echo number_format($tarifa_kwh, 2); ?> COP / kWh</div>

            <div class="sim-wrapper">
                <!-- Formulario de carga -->
                <div class="card-box">
                    <h3>➕ Agregar Electrodoméstico</h3>
                    <div class="form-group">
                        <label>Equipo predefinido (opcional)</label>
                        <select id="preset-equipo" onchange="aplicarPreset()">
                            <option value="">-- Personalizado --</option>
                            <option value="Nevera No-Frost|150|12">Nevera No-Frost (150W - 12h/día efectivo)</option>
                            <option value="Aire Acondicionado Inverter|1100|6">Aire Acondicionado (1100W - 6h/día)</option>
                            <option value="Televisor Smart LED 50&quot;|110|5">Televisor Smart LED 50" (110W - 5h/día)</option>
                            <option value="Lavadora Automática|500|1">Lavadora Automática (500W - 1h/día)</option>
                            <option value="Computador de Escritorio|180|6">Computador de Escritorio (180W - 6h/día)</option>
                            <option value="Iluminación LED Hogar (8 bombillos)|72|6">Iluminación LED Hogar (72W - 6h/día)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre del equipo</label>
                        <input type="text" id="sim-nombre" placeholder="Ej: Televisor Sala" value="Televisor Smart LED">
                    </div>
                    <div class="form-group">
                        <label>Potencia (Watts)</label>
                        <input type="number" id="sim-watts" value="110" min="1">
                    </div>
                    <div class="form-group">
                        <label>Horas de uso al día</label>
                        <input type="number" id="sim-horas" value="5" step="0.5" min="0.5" max="24">
                    </div>
                    <div class="form-group">
                        <label>Días al mes</label>
                        <input type="number" id="sim-dias" value="30" min="1" max="31">
                    </div>
                    <button type="button" class="btn-add" onclick="agregarEquipo()">Añadir a la Simulación</button>
                </div>

                <!-- Resultados y Gráfica -->
                <div class="card-box">
                    <h3>📊 Resultado de la Simulación Mensual</h3>
                    <div class="kpi-row">
                        <div class="kpi-mini">
                            <span>Consumo Mes</span>
                            <strong id="tot-kwh" style="color:#2e7d32;">0 kWh</strong>
                        </div>
                        <div class="kpi-mini">
                            <span>Costo Estimado</span>
                            <strong id="tot-cop" style="color:#d32f2f;">$0</strong>
                        </div>
                        <div class="kpi-mini">
                            <span>Huella CO₂</span>
                            <strong id="tot-co2">0 kg</strong>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Uso</th>
                                <th>kWh/Mes</th>
                                <th>Costo/Mes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tabla-sim"></tbody>
                    </table>

                    <div style="margin-top: 20px;">
                        <canvas id="chartSimulacion" height="140"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const TARIFA = <?php echo json_encode($tarifa_kwh); ?>;
        let listaEquipos = [
            { nombre: 'Nevera No-Frost', watts: 150, horas: 12, dias: 30 },
            { nombre: 'Televisor Smart LED', watts: 110, horas: 5, dias: 30 },
            { nombre: 'Iluminación LED Hogar', watts: 72, horas: 6, dias: 30 }
        ];

        const ctxSim = document.getElementById('chartSimulacion').getContext('2d');
        const chartSim = new Chart(ctxSim, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#2e7d32', '#0288d1', '#f57c00', '#7b1fa2', '#c62828', '#00838f']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        function aplicarPreset() {
            const val = document.getElementById('preset-equipo').value;
            if (!val) return;
            const [nom, w, h] = val.split('|');
            document.getElementById('sim-nombre').value = nom;
            document.getElementById('sim-watts').value = w;
            document.getElementById('sim-horas').value = h;
        }

        function agregarEquipo() {
            const nombre = document.getElementById('sim-nombre').value.trim();
            const watts  = parseFloat(document.getElementById('sim-watts').value);
            const horas  = parseFloat(document.getElementById('sim-horas').value);
            const dias   = parseInt(document.getElementById('sim-dias').value);

            if (!nombre || isNaN(watts) || watts <= 0 || isNaN(horas) || horas <= 0) return;
            listaEquipos.push({ nombre, watts, horas, dias: dias || 30 });
            renderSimulacion();
        }

        function eliminarEquipo(idx) {
            listaEquipos.splice(idx, 1);
            renderSimulacion();
        }

        function renderSimulacion() {
            const tbody = document.getElementById('tabla-sim');
            tbody.innerHTML = '';

            let totalKwh = 0;
            let totalCop = 0;

            listaEquipos.forEach((eq, i) => {
                const kwh = (eq.watts * eq.horas * eq.dias) / 1000;
                const cop = kwh * TARIFA;
                totalKwh += kwh;
                totalCop += cop;

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${eq.nombre}</strong><br><small style="color:#666;">${eq.watts} W</small></td>
                        <td>${eq.horas}h × ${eq.dias}d</td>
                        <td>${kwh.toFixed(1)} kWh</td>
                        <td style="color:#d32f2f; font-weight:bold;">$${Math.round(cop).toLocaleString('es-CO')}</td>
                        <td><button class="btn-del" onclick="eliminarEquipo(${i})" title="Quitar">✖</button></td>
                    </tr>
                `;
            });

            document.getElementById('tot-kwh').textContent = totalKwh.toFixed(1) + ' kWh';
            document.getElementById('tot-cop').textContent = '$' + Math.round(totalCop).toLocaleString('es-CO');
            document.getElementById('tot-co2').textContent = (totalKwh * 0.18).toFixed(1) + ' kg';

            chartSim.data.labels = listaEquipos.map(e => e.nombre);
            chartSim.data.datasets[0].data = listaEquipos.map(e => Number(((e.watts * e.horas * e.dias) / 1000).toFixed(2)));
            chartSim.update();
        }

        renderSimulacion();
    </script>
</body>
</html>