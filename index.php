<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Ingreso</title>
    <style>
        /* ESTILOS GENERALES Y VISTA PC */
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        
        .login-card { 
            width: 100%; 
            max-width: 450px; 
            background-color: #ffffff; 
            padding: 40px 35px; 
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            box-sizing: border-box; 
            text-align: center; 
        }

        .app-logo { 
            width: 140px; 
            height: auto; 
            display: block; 
            margin: 0 auto 15px; 
        }
        
        .app-title { 
            color: #2e7d32; 
            font-size: 28px; 
            font-weight: 900; 
            margin: 0 0 5px 0; 
        }
        
        .slogan { 
            color: #555555; 
            font-size: 15px; 
            margin-bottom: 35px; 
        }

        .input-group { 
            text-align: left; 
            margin-bottom: 20px; 
        }
        
        .input-group label { 
            font-size: 14px; 
            font-weight: bold; 
            color: #333; 
            display: block; 
            margin-bottom: 8px; 
        }
        
        .input-group input { 
            width: 100%; 
            padding: 14px; 
            border: 1px solid #cccccc; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 15px; 
            transition: border-color 0.3s;
        }
        
        .input-group input:focus {
            border-color: #2e7d32;
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 15px;
            transition: transform 0.1s, opacity 0.2s, background-color 0.2s;
        }
        .btn:active { transform: scale(0.97); }
        
        .btn-black { 
            background-color: #000000; 
            color: #ffffff; 
            border: none; 
        }
        .btn-black:hover { opacity: 0.85; }
        
        .btn-outline { 
            background-color: transparent; 
            color: #000000; 
            border: 2px solid #000000; 
            margin-bottom: 0; /* Al ser el último botón, se le quita el margen inferior */
        }
        .btn-outline:hover { background-color: #f8f9fa; }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #888;
            font-size: 13px;
            font-weight: bold;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider:not(:empty)::before { margin-right: 15px; }
        .divider:not(:empty)::after { margin-left: 15px; }

        /* DISEÑO RESPONSIVE (VISTA MÓVIL) */
        @media (max-width: 500px) {
            body {
                background-color: #ffffff; 
                align-items: flex-start; 
            }
            .login-card {
                padding: 30px 20px;
                border-radius: 0;
                box-shadow: none; 
                max-width: 100%;
            }
            .app-logo { width: 110px; }
            .app-title { font-size: 26px; }
            .slogan { font-size: 14px; margin-bottom: 25px; }
            .btn { padding: 14px; font-size: 15px; }
        }
    </style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2e7d32">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</head>
<body>
    <div class="login-card">
        
        <img src="image_2f3ead.png" alt="Logo Ahorro Eco" class="app-logo">
        <h1 class="app-title">¡Bienvenido a Ahorro Eco!</h1>
        <p class="slogan">Optimiza tu consumo de energía y ahorra dinero</p>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" placeholder="usuario@dominio.com" required>
            </div>
            
            <div class="input-group">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" placeholder="Ingrese su Clave" required>
            </div>
            
            <button type="submit" class="btn btn-black">Ingresar al Sistema</button>
        </form>

        <div class="divider">O</div>

        <button onclick="window.location.href='registro.php'" class="btn btn-outline">Crear Cuenta Nueva</button>
        
    </div>
</body>
</html>