<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
$sesion_activa = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco — Gestión Inteligente de Energía IoT</title>
    <style>
        /* PALETA Y ESTILOS BASE DE AHORRO ECO */
        :root {
            --verde-eco: #2e7d32;
            --verde-oscuro: #1b5e20;
            --verde-suave: #e8f5e9;
            --verde-borde: #c8e6c9;
            --negro: #000000;
            --rojo-alerta: #d32f2f;
            --fondo-gris: #f0f2f5;
            --texto-principal: #111111;
            --texto-secundario: #555555;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--fondo-gris);
            color: var(--texto-principal);
            line-height: 1.6;
        }

        /* BARRA DE NAVEGACIÓN SUPERIOR */
        .navbar {
            background-color: #ffffff;
            border-bottom: 2px solid var(--negro);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 14px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--negro);
        }

        .brand img {
            width: 56px;
            height: auto;
        }

        .brand-text {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }

        .brand-text span {
            color: var(--verde-eco);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 26px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--texto-secundario);
            font-size: 14.5px;
            font-weight: bold;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--verde-eco);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* BOTONES COHERENTES CON INDEX Y REGISTRO */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 14.5px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.2s, background-color 0.2s, color 0.2s;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-black {
            background-color: var(--negro);
            color: #ffffff;
            border: 2px solid var(--negro);
        }

        .btn-black:hover {
            opacity: 0.85;
        }

        .btn-green {
            background-color: var(--verde-eco);
            color: #ffffff;
            border: 2px solid var(--verde-eco);
        }

        .btn-green:hover {
            background-color: var(--verde-oscuro);
            border-color: var(--verde-oscuro);
        }

        .btn-outline {
            background-color: #ffffff;
            color: var(--negro);
            border: 2px solid var(--negro);
        }

        .btn-outline:hover {
            background-color: #f8f9fa;
        }

        /* SECCIÓN HERO PRINCIPAL */
        .hero {
            background-color: #ffffff;
            border-bottom: 1px solid #eaeaea;
            padding: 60px 30px;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1.05fr;
            gap: 40px;
            align-items: center;
        }

        .badge-trl {
            display: inline-block;
            background-color: var(--verde-suave);
            color: var(--verde-eco);
            border: 1px solid var(--verde-borde);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: 42px;
            font-weight: 900;
            line-height: 1.15;
            color: var(--negro);
            margin-bottom: 18px;
        }

        .hero h1 span {
            color: var(--verde-eco);
        }

        .hero p {
            font-size: 17px;
            color: var(--texto-secundario);
            margin-bottom: 26px;
            max-width: 560px;
        }

        .hero-cta-group {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 30px;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }

        .metric-item strong {
            display: block;
            font-size: 21px;
            font-weight: 900;
            color: var(--verde-eco);
        }

        .metric-item span {
            font-size: 12.5px;
            color: var(--texto-secundario);
            font-weight: bold;
        }

        /* CONTENEDOR DE VISTA PREVIA EN CÓDIGO PURO (SIMULA LA VENTANA DE LA APP) */
        .app-mockup {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.09);
        }

        .mockup-topbar {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .window-dots {
            display: flex;
            gap: 6px;
        }

        .window-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-red { background: #ef4444; }
        .dot-yellow { background: #f59e0b; }
        .dot-green { background: #22c55e; }

        .mockup-url {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 3px 12px;
            font-size: 12px;
            color: #475569;
            font-weight: bold;
        }

        .mockup-nav {
            display: flex;
            justify-content: space-around;
            padding: 8px 10px;
            border-bottom: 2px solid #000000;
            background: #ffffff;
            font-size: 11px;
            font-weight: bold;
            color: #666666;
        }

        .mockup-nav span.active-nav {
            color: #000000;
            text-decoration: underline;
        }

        .mockup-body {
            padding: 20px;
            background-color: #ffffff;
        }

        .mockup-title {
            text-align: center;
            font-size: 20px;
            font-weight: 900;
            color: #000000;
            margin-bottom: 4px;
        }

        .mockup-sub {
            text-align: center;
            font-size: 12.5px;
            color: #666666;
            margin-bottom: 14px;
        }

        /* TARJETAS DEL MÓDULO IOT EN CÓDIGO */
        .iot-mini-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }

        .iot-mini-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 10px;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .iot-mini-card h4 {
            font-size: 13px;
            color: var(--verde-eco);
            margin: 4px 0;
            line-height: 1.25;
        }

        .pill-on {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            font-size: 10.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 10px;
            margin-bottom: 8px;
            width: fit-content;
        }

        .pill-off {
            display: inline-block;
            background: #f8d7da;
            color: #721c24;
            font-size: 10.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 10px;
            margin-bottom: 8px;
            width: fit-content;
        }

        .iot-stat {
            font-size: 11px;
            color: #555;
            margin-bottom: 3px;
        }

        .iot-stat b {
            color: #000;
        }

        .iot-stat .red {
            color: var(--rojo-alerta);
            font-weight: bold;
        }

        .btn-mini-toggle {
            width: 100%;
            margin-top: 8px;
            padding: 7px 6px;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-weight: bold;
            font-size: 11.5px;
            cursor: pointer;
        }

        .btn-mini-on { background-color: var(--rojo-alerta); }
        .btn-mini-off { background-color: var(--verde-eco); }

        .iot-total-banner {
            background: var(--verde-suave);
            border: 1px solid var(--verde-borde);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .iot-total-banner .lbl {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
        }

        .iot-total-banner .watts-line {
            font-size: 13.5px;
            font-weight: bold;
            color: var(--verde-oscuro);
            margin: 2px 0;
        }

        .iot-total-banner .kwh-line {
            font-size: 19px;
            font-weight: 900;
            color: var(--verde-eco);
        }

        .iot-total-banner .cop-line {
            font-size: 15px;
            font-weight: 900;
            color: var(--rojo-alerta);
        }

        /* MÓDULO MONITOREO EN CÓDIGO */
        .mon-tarifa-bar {
            background: var(--verde-suave);
            border: 1px solid var(--verde-borde);
            color: var(--verde-eco);
            font-size: 11.5px;
            font-weight: bold;
            text-align: center;
            padding: 7px 10px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .mon-tabs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .mon-tab {
            background: #e0e0e0;
            border: none;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            color: #222;
        }

        .mon-tab.active {
            background: var(--verde-eco);
            color: #ffffff;
        }

        .mon-kpi-card {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 12px;
        }

        .mon-kpi-card .k-title {
            font-size: 12.5px;
            font-weight: bold;
            color: #555;
        }

        .mon-kpi-card .k-val {
            font-size: 24px;
            font-weight: 900;
            color: var(--verde-eco);
            margin: 3px 0;
        }

        .mon-kpi-card .k-cost {
            font-size: 17px;
            font-weight: bold;
            color: var(--rojo-alerta);
        }

        .mon-mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-bottom: 12px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }

        .mon-mini-table th, .mon-mini-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .mon-mini-table th {
            background: #f8fafc;
            color: #333;
        }

        .mon-chart-box {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 10px 14px;
            background: #fafafa;
        }

        .mon-chart-title {
            font-size: 11.5px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 8px;
        }

        .mon-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 75px;
            gap: 10px;
        }

        .bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            height: 100%;
            justify-content: flex-end;
        }

        .bar-fill {
            width: 32px;
            border-radius: 5px 5px 0 0;
            transition: height 0.3s;
        }

        .bar-lbl {
            font-size: 10.5px;
            font-weight: bold;
            color: #555;
            margin-top: 4px;
        }

        /* SECCIÓN MÓDULOS DEL ECOSISTEMA */
        .section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 65px 30px;
        }

        .section-header {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 42px;
        }

        .section-header h2 {
            font-size: 32px;
            font-weight: 900;
            color: var(--negro);
            margin-bottom: 10px;
        }

        .section-header p {
            font-size: 16px;
            color: var(--texto-secundario);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
            gap: 22px;
        }

        .module-card {
            background-color: #ffffff;
            border: 1px solid #eaeaea;
            border-radius: 12px;
            padding: 26px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .module-icon {
            font-size: 34px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .module-card h3 {
            font-size: 19px;
            font-weight: bold;
            color: var(--negro);
            margin-bottom: 8px;
        }

        .module-card p {
            font-size: 14.5px;
            color: var(--texto-secundario);
        }

        /* SECCIÓN CÓMO FUNCIONA */
        .workflow-wrapper {
            background-color: #ffffff;
            border-top: 1px solid #eaeaea;
            border-bottom: 1px solid #eaeaea;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
        }

        .step-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }

        .step-num {
            width: 34px;
            height: 34px;
            background-color: var(--verde-eco);
            color: #ffffff;
            font-weight: 900;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 15px;
        }

        .step-card h4 {
            font-size: 17px;
            color: var(--negro);
            margin-bottom: 8px;
        }

        .step-card p {
            font-size: 14px;
            color: var(--texto-secundario);
        }

        /* CTA FINAL */
        .cta-banner {
            background-color: #ffffff;
            border: 2px solid var(--negro);
            border-radius: 14px;
            padding: 45px 35px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .cta-banner h2 {
            font-size: 30px;
            font-weight: 900;
            margin-bottom: 12px;
            color: var(--negro);
        }

        .cta-banner p {
            font-size: 16px;
            color: var(--texto-secundario);
            max-width: 650px;
            margin: 0 auto 26px;
        }

        footer {
            background-color: var(--negro);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }

        footer p {
            color: #cccccc;
            margin-top: 6px;
            font-size: 13px;
        }

        @media (max-width: 960px) {
            .hero-container {
                grid-template-columns: 1fr;
            }
            .nav-links {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .nav-container {
                padding: 12px 16px;
            }
            .brand-text {
                font-size: 20px;
            }
            .hero {
                padding: 40px 16px;
            }
            .hero h1 {
                font-size: 30px;
            }
            .iot-mini-grid {
                grid-template-columns: 1fr;
            }
            .hero-metrics {
                grid-template-columns: 1fr;
            }
            .section {
                padding: 45px 18px;
            }
        }
    </style>
</head>
<body>

    <!-- NAVEGACIÓN -->
    <header class="navbar">
        <div class="nav-container">
            <a href="landing.php" class="brand">
                <img src="image_2f3ead.png" alt="Logo Ahorro Eco">
                <span class="brand-text">Ahorro <span>Eco</span></span>
            </a>

            <nav class="nav-links">
                <a href="#modulos">Módulos</a>
                <a href="#como-funciona">Arquitectura IoT</a>
                <a href="#demo-interactiva">Vista Previa</a>
            </nav>

            <div class="nav-actions">
                <?php if ($sesion_activa): ?>
                    <a href="home.php" target="_blank" rel="noopener noreferrer" class="btn btn-green">Ir al Panel</a>
                <?php else: ?>
                    <a href="index.php" target="_blank" rel="noopener noreferrer" class="btn btn-outline">Iniciar Sesión</a>
                    <a href="registro.php" target="_blank" rel="noopener noreferrer" class="btn btn-black">Crear Cuenta</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-container">
            <div>
                <span class="badge-trl">🌱 Plataforma Web + IoT Tuya Cloud • Prototipo Funcional</span>
                <h1>Optimiza tu consumo de energía y <span>ahorra dinero</span> en tiempo real</h1>
                <p>
                    Monitorea tus electrodomésticos desde cualquier lugar, controla tomas inteligentes en vivo, proyecta el impacto financiero en pesos colombianos (COP) y recibe recomendaciones personalizadas.
                </p>

                <div class="hero-cta-group">
                    <button type="button" id="btn-prev-iot" class="btn btn-green" onclick="cambiarVistaModulo('iot')">📡 Abrir Control IoT en Vivo</button>
                    <button type="button" id="btn-prev-mon" class="btn btn-outline" onclick="cambiarVistaModulo('monitoreo')">📊 Ver Monitoreo</button>
                </div>

                <div class="hero-metrics">
                    <div class="metric-item">
                        <strong>100% Tiempo Real</strong>
                        <span>Telemetría API Tuya Cloud</span>
                    </div>
                    <div class="metric-item">
                        <strong>Tarifa Local COP</strong>
                        <span>Cálculo exacto por kWh</span>
                    </div>
                    <div class="metric-item">
                        <strong>Hasta 15% Ahorro</strong>
                        <span>Control de consumo standby</span>
                    </div>
                </div>
            </div>

            <!-- VISTA PREVIA EN CÓDIGO PURO (SIN IMÁGENES) -->
            <div class="app-mockup" id="demo-interactiva">
                <div class="mockup-topbar">
                    <div class="window-dots">
                        <span class="dot-red"></span>
                        <span class="dot-yellow"></span>
                        <span class="dot-green"></span>
                    </div>
                    <div class="mockup-url" id="mockup-url-bar">ahorroeco.lat/iot.php</div>
                    <span style="font-size:11px; color:#2e7d32; font-weight:bold;">● Vista Previa</span>
                </div>

                <div class="mockup-nav">
                    <span>🏠 Home</span>
                    <span id="mnav-iot" class="active-nav">📡 IOT</span>
                    <span id="mnav-mon">📊 Monitoreo</span>
                    <span>💡 Recomendación</span>
                    <span>🖥️ Simulación</span>
                </div>

                <!-- APARTADO 1: CONTROL IOT EN VIVO (EN CÓDIGO) -->
                <div class="mockup-body" id="panel-codigo-iot">
                    <div class="mockup-title">Dispositivos y Consumo IoT</div>
                    <div class="mockup-sub">Tarifa de perfil demo: <b>$850.00 COP / kWh</b> (Haz clic en Apagar/Encender para probar)</div>

                    <div class="iot-mini-grid">
                        <!-- Dispositivo 1 -->
                        <div class="iot-mini-card">
                            <div>
                                <div style="font-size:18px;">🔌</div>
                                <h4>Toma TV</h4>
                                <span id="badge-dev-1" class="pill-on">● Encendido</span>
                                <div class="iot-stat">Potencia: <b id="w-dev-1">120.0 W</b></div>
                                <div class="iot-stat">Consumo: <b id="k-dev-1">0.120 kWh</b></div>
                                <div class="iot-stat">Costo/h: <span class="red" id="c-dev-1">$102.00</span></div>
                            </div>
                            <button type="button" id="btn-dev-1" class="btn-mini-toggle btn-mini-on" onclick="toggleDispositivo(1)">Apagar</button>
                        </div>

                        <!-- Dispositivo 2 -->
                        <div class="iot-mini-card">
                            <div>
                                <div style="font-size:18px;">🔌</div>
                                <h4>Toma Múltiple 1</h4>
                                <span id="badge-dev-2" class="pill-on">● Encendido</span>
                                <div class="iot-stat">Potencia: <b id="w-dev-2">28.6 W</b></div>
                                <div class="iot-stat">Consumo: <b id="k-dev-2">0.029 kWh</b></div>
                                <div class="iot-stat">Costo/h: <span class="red" id="c-dev-2">$24.31</span></div>
                            </div>
                            <button type="button" id="btn-dev-2" class="btn-mini-toggle btn-mini-on" onclick="toggleDispositivo(2)">Apagar</button>
                        </div>

                        <!-- Dispositivo 3 -->
                        <div class="iot-mini-card">
                            <div>
                                <div style="font-size:18px;">🔌</div>
                                <h4>Toma Múltiple 2</h4>
                                <span id="badge-dev-3" class="pill-off">● Apagado</span>
                                <div class="iot-stat">Potencia: <b id="w-dev-3">0.0 W</b></div>
                                <div class="iot-stat">Consumo: <b id="k-dev-3">0.000 kWh</b></div>
                                <div class="iot-stat">Costo/h: <span class="red" id="c-dev-3">$0.00</span></div>
                            </div>
                            <button type="button" id="btn-dev-3" class="btn-mini-toggle btn-mini-off" onclick="toggleDispositivo(3)">Encender</button>
                        </div>
                    </div>

                    <div class="iot-total-banner">
                        <div class="lbl">Consumo Total Activo (Sincronizado en MySQL)</div>
                        <div class="watts-line" id="iot-tot-watts">Carga Instantánea: 148.6 W</div>
                        <div class="kwh-line" id="iot-tot-kwh">0.149 kWh / hora</div>
                        <div class="cop-line" id="iot-tot-cop">$126.31 COP / hora</div>
                    </div>
                </div>

                <!-- APARTADO 2: MONITOREO DE CONSUMO (EN CÓDIGO) -->
                <div class="mockup-body" id="panel-codigo-monitoreo" style="display: none;">
                    <div class="mockup-title">Monitoreo de Consumo</div>
                    <div class="mon-tarifa-bar" id="mon-banner-info">
                        Tarifa aplicada: $850.00 COP / kWh • Dispositivos encendidos: 2 de 3
                    </div>

                    <div class="mon-tabs">
                        <button type="button" id="mtab-dia" class="mon-tab active" onclick="cambiarPeriodoMon('dia')">Día</button>
                        <button type="button" id="mtab-semana" class="mon-tab" onclick="cambiarPeriodoMon('semana')">Semana</button>
                        <button type="button" id="mtab-mes" class="mon-tab" onclick="cambiarPeriodoMon('mes')">Mes</button>
                        <button type="button" id="mtab-anio" class="mon-tab" onclick="cambiarPeriodoMon('anio')">Año</button>
                    </div>

                    <div class="mon-kpi-card">
                        <div class="k-title" id="mon-kpi-titulo">Consumo Proyectado (Día — 5 h/día)</div>
                        <div class="k-val" id="mon-kpi-kwh">0.74 kWh</div>
                        <div class="k-cost" id="mon-kpi-cop">$631.55 COP</div>
                    </div>

                    <table class="mon-mini-table">
                        <thead>
                            <tr>
                                <th>Dispositivo Sincronizado</th>
                                <th>Estado</th>
                                <th>Consumo/h</th>
                                <th>Costo/h</th>
                            </tr>
                        </thead>
                        <tbody id="mon-tabla-body"></tbody>
                    </table>

                    <div class="mon-chart-box">
                        <div class="mon-chart-title">Proyección de Consumo Energético (kWh)</div>
                        <div class="mon-bars">
                            <div class="bar-col">
                                <div class="bar-fill" style="height: 20%; background:#66bb6a;"></div>
                                <span class="bar-lbl">Día</span>
                            </div>
                            <div class="bar-col">
                                <div class="bar-fill" style="height: 45%; background:#43a047;"></div>
                                <span class="bar-lbl">Semana</span>
                            </div>
                            <div class="bar-col">
                                <div class="bar-fill" style="height: 72%; background:#2e7d32;"></div>
                                <span class="bar-lbl">Mes</span>
                            </div>
                            <div class="bar-col">
                                <div class="bar-fill" style="height: 100%; background:#1b5e20;"></div>
                                <span class="bar-lbl">Año</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MÓDULOS DE LA APLICACIÓN -->
    <section class="section" id="modulos">
        <div class="section-header">
            <h2>Ecosistema Integral de Gestión Energética</h2>
            <p>Seis módulos sincronizados con base de datos MySQL y dispositivos inteligentes para transformar tus hábitos de consumo.</p>
        </div>

        <div class="modules-grid">
            <div class="module-card">
                <span class="module-icon">📡</span>
                <h3>Control IoT en Tiempo Real</h3>
                <p>Enciende, apaga y vincula enchufes inteligentes mediante integración directa con Tuya IoT Platform y lectura de voltaje y potencia.</p>
            </div>

            <div class="module-card">
                <span class="module-icon">📊</span>
                <h3>Monitoreo y Analítica</h3>
                <p>Visualiza proyecciones de consumo diario, semanal, mensual y anual junto con el historial real de potencia almacenado en MySQL.</p>
            </div>

            <div class="module-card">
                <span class="module-icon">💡</span>
                <h3>Recomendaciones Inteligentes</h3>
                <p>Diagnóstico automático basado en tus equipos encendidos para detectar cargas fantasma y estimar tu ahorro mensual en pesos.</p>
            </div>

            <div class="module-card">
                <span class="module-icon">🖥️</span>
                <h3>Calculadora de Simulación</h3>
                <p>Simula el costo mensual y la huella de CO₂ de cualquier electrodoméstico ajustando potencia en Watts, horas de uso y tu tarifa local.</p>
            </div>

            <div class="module-card">
                <span class="module-icon">🎓</span>
                <h3>Centro de Educación</h3>
                <p>Guías técnicas sobre etiquetado RETIQ, eficiencia energética y noticias ambientales sincronizadas automáticamente.</p>
            </div>

            <div class="module-card">
                <span class="module-icon">👤</span>
                <h3>Tarifa Personalizada por Perfil</h3>
                <p>Configura el valor exacto del kWh de tu factura eléctrica por departamento y ciudad para obtener cálculos financieros reales.</p>
            </div>
        </div>
    </section>

    <!-- ARQUITECTURA / CÓMO FUNCIONA -->
    <div class="workflow-wrapper" id="como-funciona">
        <section class="section">
            <div class="section-header">
                <h2>¿Cómo funciona Ahorro Eco?</h2>
                <p>Flujo completo desde la captura de telemetría física hasta la toma de decisiones de ahorro.</p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">1</div>
                    <h4>Configura tu Tarifa</h4>
                    <p>Registra tu cuenta con ubicación y el valor exacto del kWh de tu recibo de energía en pesos colombianos.</p>
                </div>

                <div class="step-card">
                    <div class="step-num">2</div>
                    <h4>Vincula tus Tomas IoT</h4>
                    <p>Conecta tus dispositivos inteligentes con su Device ID y controla cada canal de forma remota y segura.</p>
                </div>

                <div class="step-card">
                    <div class="step-num">3</div>
                    <h4>Analiza tus Gráficas</h4>
                    <p>Consulta el histórico de potencia activa y compara el gasto proyectado por día, semana, mes y año.</p>
                </div>

                <div class="step-card">
                    <div class="step-num">4</div>
                    <h4>Optimiza y Ahorra</h4>
                    <p>Aplica las recomendaciones automáticas, simula nuevos escenarios y valida tu experiencia en la encuesta.</p>
                </div>
            </div>
        </section>
    </div>

    <!-- CALL TO ACTION FINAL -->
    <section class="section">
        <div class="cta-banner">
            <h2>Comienza a gestionar tu consumo energético hoy</h2>
            <p>Accede al prototipo funcional desplegado en la nube y prueba el monitoreo IoT, la calculadora de simulación y los reportes en tiempo real.</p>
            <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
                <?php if ($sesion_activa): ?>
                    <a href="home.php" target="_blank" rel="noopener noreferrer" class="btn btn-green">Ir al Panel</a>
                <?php else: ?>
                    <a href="index.php" target="_blank" rel="noopener noreferrer" class="btn btn-black">Iniciar Sesión en Ahorro Eco</a>
                    <a href="registro.php" target="_blank" rel="noopener noreferrer" class="btn btn-green">Crear Cuenta Nueva</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <strong>Ahorro Eco</strong> — Plataforma Web e IoT para la Gestión y Optimización del Consumo Energético Residencial
        <p>© <?php echo date('Y'); ?> Ahorro Eco • Todos los derechos reservados</p>
    </footer>

    <script>
        const TARIFA_DEMO = 850;
        let periodoMon = 'dia';

        const dispositivos = {
            1: { nombre: 'Toma TV', wattsNominal: 120.0, activo: true },
            2: { nombre: 'Toma Múltiple - Canal 1', wattsNominal: 28.6, activo: true },
            3: { nombre: 'Toma Múltiple - Canal 2', wattsNominal: 28.6, activo: false }
        };

        function cambiarVistaModulo(vista) {
            const panelIot = document.getElementById('panel-codigo-iot');
            const panelMon = document.getElementById('panel-codigo-monitoreo');
            const btnIot   = document.getElementById('btn-prev-iot');
            const btnMon   = document.getElementById('btn-prev-mon');
            const urlBar   = document.getElementById('mockup-url-bar');
            const navIot   = document.getElementById('mnav-iot');
            const navMon   = document.getElementById('mnav-mon');

            if (vista === 'monitoreo') {
                panelIot.style.display = 'none';
                panelMon.style.display = 'block';
                btnMon.className = 'btn btn-green';
                btnIot.className = 'btn btn-outline';
                urlBar.textContent = 'ahorroeco.lat/monitoreo.php';
                navMon.classList.add('active-nav');
                navIot.classList.remove('active-nav');
                actualizarMonitoreoCodigo();
            } else {
                panelMon.style.display = 'none';
                panelIot.style.display = 'block';
                btnIot.className = 'btn btn-green';
                btnMon.className = 'btn btn-outline';
                urlBar.textContent = 'ahorroeco.lat/iot.php';
                navIot.classList.add('active-nav');
                navMon.classList.remove('active-nav');
            }

            document.getElementById('demo-interactiva').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function toggleDispositivo(id) {
            dispositivos[id].activo = !dispositivos[id].activo;
            renderTodo();
        }

        function cambiarPeriodoMon(periodo) {
            periodoMon = periodo;
            document.querySelectorAll('.mon-tab').forEach(b => b.classList.remove('active'));
            document.getElementById('mtab-' + periodo).classList.add('active');
            actualizarMonitoreoCodigo();
        }

        function renderTodo() {
            let totalWatts = 0;

            Object.keys(dispositivos).forEach(key => {
                const d = dispositivos[key];
                const w = d.activo ? d.wattsNominal : 0;
                const kwh = w / 1000;
                const cop = kwh * TARIFA_DEMO;
                totalWatts += w;

                const badge = document.getElementById('badge-dev-' + key);
                const btn   = document.getElementById('btn-dev-' + key);

                badge.textContent = d.activo ? '● Encendido' : '● Apagado';
                badge.className   = d.activo ? 'pill-on' : 'pill-off';

                btn.textContent   = d.activo ? 'Apagar' : 'Encender';
                btn.className     = d.activo ? 'btn-mini-toggle btn-mini-on' : 'btn-mini-toggle btn-mini-off';

                document.getElementById('w-dev-' + key).textContent = w.toFixed(1) + ' W';
                document.getElementById('k-dev-' + key).textContent = kwh.toFixed(3) + ' kWh';
                document.getElementById('c-dev-' + key).textContent = '$' + cop.toFixed(2);
            });

            const totalKwh = totalWatts / 1000;
            const totalCop = totalKwh * TARIFA_DEMO;

            document.getElementById('iot-tot-watts').textContent = `Carga Instantánea: ${totalWatts.toFixed(1)} W`;
            document.getElementById('iot-tot-kwh').textContent   = `${totalKwh.toFixed(3)} kWh / hora`;
            document.getElementById('iot-tot-cop').textContent   = `$${totalCop.toFixed(2)} COP / hora`;

            actualizarMonitoreoCodigo();
        }

        function actualizarMonitoreoCodigo() {
            let totalWatts = 0;
            let encendidos = 0;
            const tbody = document.getElementById('mon-tabla-body');
            tbody.innerHTML = '';

            Object.values(dispositivos).forEach(d => {
                if (d.activo) {
                    totalWatts += d.wattsNominal;
                    encendidos++;
                }
                const w = d.activo ? d.wattsNominal : 0;
                const kwh = w / 1000;
                const cop = kwh * TARIFA_DEMO;

                tbody.innerHTML += `
                    <tr>
                        <td><b>${d.nombre}</b></td>
                        <td><span class="${d.activo ? 'pill-on' : 'pill-off'}" style="margin:0;">${d.activo ? 'Encendido' : 'Apagado'}</span></td>
                        <td>${kwh.toFixed(3)} kWh</td>
                        <td style="color:#d32f2f; font-weight:bold;">$${cop.toFixed(2)}</td>
                    </tr>
                `;
            });

            document.getElementById('mon-banner-info').textContent =
                `Tarifa aplicada: $850.00 COP / kWh • Dispositivos encendidos: ${encendidos} de 3`;

            const kwhHora = totalWatts / 1000;
            const kwhDia  = kwhHora * 5;
            const factores = {
                dia:    { mult: 1,   titulo: 'Consumo Proyectado (Día — 5 h/día)' },
                semana: { mult: 7,   titulo: 'Consumo Proyectado (Semana — 7 días)' },
                mes:    { mult: 30,  titulo: 'Consumo Proyectado (Mes — 30 días)' },
                anio:   { mult: 365, titulo: 'Consumo Proyectado (Año — 365 días)' }
            };

            const f = factores[periodoMon];
            const kwhCalc = kwhDia * f.mult;
            const copCalc = kwhCalc * TARIFA_DEMO;

            document.getElementById('mon-kpi-titulo').textContent = f.titulo;
            document.getElementById('mon-kpi-kwh').textContent    = `${kwhCalc.toFixed(2)} kWh`;
            document.getElementById('mon-kpi-cop').textContent    = `$${copCalc.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} COP`;
        }

        // Inicializar valores al cargar
        renderTodo();
    </script>
</body>
</html>