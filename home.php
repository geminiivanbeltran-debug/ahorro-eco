<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// Validación de sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Inicio</title>
    <style>
        /* ESTILOS GENERALES Y PC */
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f0f2f5; 
            margin: 0; 
            padding: 0; 
        }
        .app-container { 
            width: 100%; 
            max-width: 1200px; 
            margin: 0 auto; 
            background-color: #ffffff; 
            min-height: 100vh; 
            box-sizing: border-box; 
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }

        /* Encabezado: Título y Logo */
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            border-bottom: 1px solid #eaeaea;
        }
        .app-header h2 {
            margin: 0;
            color: #000;
            font-size: 28px;
        }
        .app-logo-header {
            width: 80px;
            height: auto;
        }

        /* Menú de navegación centrado y a color */
        .main-nav { 
            display: flex; 
            justify-content: center; /* Centra perfectamente todos los botones */
            gap: 35px; /* Espaciado uniforme */
            padding: 15px 40px; 
            border-bottom: 2px solid #000;
            background-color: #ffffff;
            flex-wrap: wrap;
        }
        .main-nav::-webkit-scrollbar { display: none; } 
        .main-nav a { 
            color: #555; 
            text-decoration: none; 
            font-size: 14px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            font-weight: bold; 
            transition: color 0.2s, transform 0.2s;
        }
        .main-nav a:hover, .main-nav a.active { 
            color: #000; 
            transform: scale(1.05);
        }
        /* Iconos a color (se eliminó el filtro de escala de grises) */
        .main-nav a span.icon { 
            font-size: 26px; 
            margin-bottom: 5px; 
        }
        
        /* Estilo especial y atractivo para el botón Salir */
        .nav-salir {
            color: #d32f2f !important; /* Rojo elegante para destacar la salida */
        }
        .nav-salir:hover {
            color: #b71c1c !important;
        }

        /* Contenido Central */
        .content { 
            padding: 60px 20px; 
            text-align: center; 
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .welcome-title { 
            color: #000; 
            font-size: 36px; 
            font-weight: 900; 
            margin-bottom: 15px; 
            max-width: 800px;
        }
        .slogan { 
            color: #000; 
            font-size: 22px; 
            font-weight: bold; 
            margin-bottom: 40px; 
            max-width: 600px;
        }
        /* Contenedor para tu imagen de las hojas */
        .leaf-graphic {
            font-size: 100px; 
            margin-bottom: 40px;
        }
        .btn-black { 
            background-color: #000000; 
            color: #ffffff; 
            border: none; 
            padding: 15px 40px; 
            border-radius: 8px; 
            font-size: 18px; 
            font-weight: bold; 
            cursor: pointer; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            transition: transform 0.1s;
        }
        .btn-black:active { transform: scale(0.95); }

        /* DISEÑO RESPONSIVE (MÓVILES) */
        @media (max-width: 768px) {
            .app-container { box-shadow: none; }
            .app-header { padding: 15px 20px; }
            .app-header h2 { font-size: 22px; }
            .app-logo-header { width: 60px; }
            .main-nav {
                justify-content: flex-start;
                flex-wrap: nowrap;
                gap: 15px;
                padding: 10px 15px;
                overflow-x: auto;
                white-space: nowrap;
                border-bottom: 3px solid #000;
            }
            .main-nav a { font-size: 11px; min-width: 65px; }
            .main-nav a span.icon { font-size: 22px; }
            .content { padding: 30px 15px; }
            .welcome-title { font-size: 28px; }
            .slogan { font-size: 18px; }
            .leaf-graphic { font-size: 80px; }
            .btn-black { width: 100%; max-width: 250px; }
        }
    </style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2e7d32">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</head>
<body>
    <div class="app-container">
        
        <!-- Encabezado superior -->
        <header class="app-header">
            <h2>Ahorro Eco</h2>
            <img src="image_2f3ead.png" alt="Logo Ahorro Eco" class="app-logo-header">
        </header>

        <!-- Barra de navegación (Centrada, a color y con nuevo botón Salir) -->
        <nav class="main-nav">
            <a href="home.php" class="active"><span class="icon">🏠</span>Home</a>
            <a href="iot.php"><span class="icon">📡</span>IOT</a>
            <a href="equipos.php"><span class="icon">🔌</span>Equipos</a>
            <a href="consumo.php"><span class="icon">⚡</span>Consumo</a>
            <a href="monitoreo.php"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <!-- Contenido principal -->
        <main class="content">
            <h1 class="welcome-title">¡Bienvenido a Ahorro Eco!</h1>
            <div class="slogan">Optimiza tu consumo de energía y ahorra dinero</div>
            
            <!-- Aquí puedes colocar la etiqueta <img> de tu hoja 3D -->
            <div class="leaf-graphic">🍃</div>
            
            <button onclick="window.location.href='encuesta.php'" class="btn-black">Encuesta</button>
        </main>

    </div>
</body>
</html>