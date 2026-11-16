<?php
header('Content-Type: text/html; charset=utf-8');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'conexion.php'; 

    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $departamento = $_POST['departamento'];
    $ciudad = $_POST['ciudad'];
    $direccion = $_POST['direccion'];
    $valor_kwh = $_POST['valor_kwh'];
    $correo = $conn->real_escape_string($_POST['correo']);
    $clave_plana = $_POST['clave']; // Contraseña sin encriptar para el correo
    $confirmar_clave = $_POST['confirmar_clave'];
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // 1. Validación de Google reCAPTCHA
    $recaptcha_secret = '6LcwMp8tAAAAAN5ElEVGZSSmlnH7qVISKl4OETSc'; 
    $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $response_data = json_decode($verify_response);

    if (!$response_data->success) {
        $mensaje = "<div class='alerta error'>Error: Por favor, verifica que no eres un robot (Captcha).</div>";
    }
    // 2. Validación de coincidencia de contraseñas
    elseif ($clave_plana !== $confirmar_clave) {
        $mensaje = "<div class='alerta error'>Error: Las contraseñas no coinciden.</div>";
    }
    // 3. Validación de seguridad de la contraseña
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $clave_plana)) {
        $mensaje = "<div class='alerta error'>Error: La clave debe tener al menos 8 caracteres, una mayúscula, una minúscula y un carácter especial.</div>";
    } else {
        // 4. Validación de correo existente
        $check_correo = "SELECT id FROM usuarios WHERE correo = '$correo'";
        $resultado_check = $conn->query($check_correo);

        if ($resultado_check->num_rows > 0) {
            $mensaje = "<div class='alerta error'>Atención: Este correo electrónico ya se encuentra registrado.</div>";
        } else {
            // Registro exitoso
            $clave = password_hash($clave_plana, PASSWORD_DEFAULT); 
            $sql = "INSERT INTO usuarios (nombre, apellidos, departamento, ciudad, direccion, valor_kwh, correo, clave) 
                    VALUES ('$nombre', '$apellidos', '$departamento', '$ciudad', '$direccion', '$valor_kwh', '$correo', '$clave')";

            if ($conn->query($sql) === TRUE) {
                require 'PHPMailer/src/Exception.php';
                require 'PHPMailer/src/PHPMailer.php';
                require 'PHPMailer/src/SMTP.php';

                $mail = new PHPMailer(true);
                try {
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();                                            
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;                                   
                    $mail->Username   = 'geminiivanbeltran@gmail.com'; 
                    $mail->Password   = 'nlluvwwakyerjeac'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                    $mail->Port       = 465;                                    

                    $mail->setFrom('geminiivanbeltran@gmail.com', 'Ahorro Eco');
                    $mail->addAddress($correo, $nombre);     

                    // Configuración del correo con URL y Credenciales
                    $mail->isHTML(true);                                  
                    $mail->Subject = '¡Bienvenido a Ahorro Eco! - Tus credenciales de acceso';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                            <!-- Cambio de fondo a negro (#000000) y texto a blanco (#ffffff) -->
                            <div style='background-color: #000000; padding: 20px; text-align: center;'>
                                <h1 style='color: #ffffff; margin: 0;'>Ahorro Eco</h1>
                            </div>
                            <div style='padding: 30px;'>
                                <h2 style='color: #000000;'>¡Hola $nombre!</h2>
                                <p>Gracias por registrarte en <b>Ahorro Eco</b>. Tu cuenta ha sido creada exitosamente.</p>
                                <p>A continuación, te compartimos tus credenciales para que puedas ingresar a la plataforma:</p>
                                
                                <div style='background-color: #f8f9fa; padding: 20px; border-left: 4px solid #000000; margin: 25px 0;'>
                                    <p style='margin: 5px 0;'><b>URL de acceso:</b> <a href='https://ahorroeco.lat/index.php' style='color: #000000; text-decoration: underline;'>https://ahorroeco.lat</a></p>
                                    <p style='margin: 5px 0;'><b>Usuario (Correo):</b> $correo</p>
                                    <p style='margin: 5px 0;'><b>Clave:</b> $clave_plana</p>
                                </div>
                                
                                <p>Te recomendamos guardar este correo en un lugar seguro.</p>
                                <p>¡Empieza a optimizar tu consumo de energía hoy mismo!</p>
                            </div>
                        </div>
                    ";

                    $mail->send();
                    $mensaje = "<div class='alerta exito'>Registro exitoso. Se han enviado las credenciales a tu correo. <br><br><button type='button' class='btn btn-black' onclick=\"window.location.href='index.php'\">Ir a Iniciar Sesión</button></div>";
                } catch (Exception $e) {
                    $mensaje = "<div class='alerta exito'>Registro exitoso (Sin correo). <br><br><button type='button' class='btn btn-black' onclick=\"window.location.href='index.php'\">Ir a Iniciar Sesión</button></div>";
                }
            } else {
                $mensaje = "<div class='alerta error'>Error al registrar: " . $conn->error . "</div>";
            }
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Registro de Usuario</title>
    
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
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
            padding: 40px 20px;
            box-sizing: border-box;
        }
        
        .register-card { 
            width: 100%; 
            max-width: 750px; 
            background-color: #ffffff; 
            padding: 40px 45px; 
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            box-sizing: border-box; 
        }

        .app-title-container { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .app-logo { width: 120px; height: auto; display: block; margin: 0 auto 10px; }
        .app-title { color: #2e7d32; font-size: 26px; font-weight: 900; margin-bottom: 5px; }
        .subtitle { font-size: 16px; font-weight: bold; color: #555; }

        /* CUADRÍCULA DE FORMULARIO PARA PC */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; 
            gap: 15px 20px; 
        }
        .full-width {
            grid-column: 1 / -1; 
        }

        .input-group label { font-size: 14px; font-weight: bold; color: #333; display: block; margin-bottom: 6px; }
        .input-group input, .input-group select { 
            width: 100%; padding: 12px; border: 1px solid #cccccc; border-radius: 8px; 
            box-sizing: border-box; font-size: 15px; color: #000; background-color: #fff;
            transition: border-color 0.3s;
        }
        .input-group input:focus, .input-group select:focus { border-color: #2e7d32; outline: none; }

        /* Formato Moneda COP */
        .currency-wrapper { position: relative; display: flex; align-items: center; }
        .currency-symbol { position: absolute; left: 12px; font-size: 16px; font-weight: bold; color: #555; }
        .currency-code { position: absolute; right: 12px; font-size: 13px; font-weight: bold; color: #777; }
        .currency-input { padding-left: 28px !important; padding-right: 50px !important; }

        .recaptcha-container { display: flex; justify-content: center; margin: 10px 0 20px; }

        /* Botones */
        .btn { padding: 15px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: transform 0.1s, opacity 0.2s; width: 100%; text-align: center; box-sizing: border-box; display: inline-block; }
        .btn:active { transform: scale(0.97); }
        .btn-black { background-color: #000000; color: #ffffff; border: none; }
        .btn-black:hover { opacity: 0.85; }
        .btn-outline { background-color: transparent; color: #000000; border: 2px solid #000000; margin-top: 10px;}
        .btn-outline:hover { background-color: #f8f9fa; }

        /* Alertas */
        .alerta { padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center; font-size: 15px; font-weight: bold; }
        .exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* DISEÑO RESPONSIVE (VISTA MÓVIL) */
        @media (max-width: 768px) {
            body { background-color: #ffffff; padding: 0; align-items: flex-start; }
            .register-card { padding: 30px 20px; border-radius: 0; box-shadow: none; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; } 
            .app-logo { width: 100px; }
            .app-title { font-size: 24px; }
            .g-recaptcha { transform: scale(0.90); transform-origin: center; } 
        }
    </style>
    
    <script>
        const ciudadesCol = {
            "Amazonas": ["Leticia", "Puerto Nariño"],
            "Antioquia": ["Medellín", "Abejorral", "Apartadó", "Bello", "Caucasia", "Envigado", "Itagüí", "Rionegro", "Santa Fe de Antioquia", "Turbo"],
            "Arauca": ["Arauca", "Arauquita", "Cravo Norte", "Fortul", "Puerto Rondón", "Saravena", "Tame"],
            "Atlántico": ["Barranquilla", "Baranoa", "Malambo", "Puerto Colombia", "Sabanalarga", "Soledad"],
            "Bolívar": ["Cartagena", "Carmen de Bolívar", "Magangué", "Mompós", "Turbaco"],
            "Boyacá": ["Tunja", "Chiquinquirá", "Duitama", "Sogamoso", "Villa de Leyva"],
            "Caldas": ["Manizales", "Chinchiná", "Dorada", "Riosucio", "Villamaría"],
            "Caquetá": ["Florencia", "El Doncello", "Puerto Rico", "San Vicente del Caguán"],
            "Casanare": ["Yopal", "Aguazul", "Paz de Ariporo", "Tauramena", "Villanueva"],
            "Cauca": ["Popayán", "El Tambo", "Patía", "Puerto Tejada", "Santander de Quilichao"],
            "Cesar": ["Valledupar", "Aguachica", "Agustín Codazzi", "Bosconia", "Curumaní"],
            "Chocó": ["Quibdó", "Istmina", "Tadó", "Condoto", "Bahía Solano"],
            "Córdoba": ["Montería", "Cereté", "Lorica", "Montelíbano", "Planeta Rica", "Sahagún"],
            "Cundinamarca": ["Bogotá D.C.", "Chía", "Cajicá", "Facatativá", "Fusagasugá", "Girardot", "Soacha", "Zipaquirá"],
            "Guainía": ["Inírida", "Barranco Minas"],
            "Guaviare": ["San José del Guaviare", "Calamar", "El Retorno", "Miraflores"],
            "Huila": ["Neiva", "Campoalegre", "Garzón", "La Plata", "Pitalito"],
            "La Guajira": ["Riohacha", "Fonseca", "Maicao", "San Juan del Cesar", "Uribia"],
            "Magdalena": ["Santa Marta", "Aracataca", "Ciénaga", "El Banco", "Fundación", "Plato"],
            "Meta": ["Villavicencio", "Acacías", "Granada", "Puerto Gaitán", "Puerto López"],
            "Nariño": ["Pasto", "Ipiales", "Tumaco", "Túquerres", "Sandoná"],
            "Norte de Santander": ["Cúcuta", "Los Patios", "Ocaña", "Pamplona", "Villa del Rosario"],
            "Putumayo": ["Mocoa", "Orito", "Puerto Asís", "Puerto Leguízamo", "Sibundoy"],
            "Quindío": ["Armenia", "Calarcá", "Circasia", "Montenegro", "Quimbaya"],
            "Risaralda": ["Pereira", "Dosquebradas", "La Virginia", "Santa Rosa de Cabal"],
            "San Andrés y Providencia": ["San Andrés", "Providencia"],
            "Santander": ["Bucaramanga", "Barrancabermeja", "Floridablanca", "Girón", "Piedecuesta", "San Gil", "Socorro"],
            "Sucre": ["Sincelejo", "Corozal", "San Marcos", "Tolú"],
            "Tolima": ["Ibagué", "Chaparral", "Espinal", "Honda", "Mariquita", "Melgar"],
            "Valle del Cauca": ["Cali", "Buenaventura", "Buga", "Cartago", "Jamundí", "Palmira", "Tuluá", "Yumbo"],
            "Vaupés": ["Mitú", "Carurú", "Taraira"],
            "Vichada": ["Puerto Carreño", "Cumaribo", "La Primavera", "Santa Rosalía"]
        };

        function cargarCiudades() {
            const depto = document.getElementById("departamento").value;
            const selectCiudad = document.getElementById("ciudad");
            selectCiudad.innerHTML = '<option value="">Seleccione una ciudad...</option>'; 

            if (ciudadesCol[depto]) {
                ciudadesCol[depto].sort().forEach(ciudad => {
                    let option = document.createElement("option");
                    option.value = ciudad;
                    option.text = ciudad;
                    selectCiudad.add(option);
                });
            }
        }
    </script>
</head>
<body>
    <div class="register-card">
        
        <div class="app-title-container">
            <img src="image_2f3ead.png" alt="Logo Ahorro Eco" class="app-logo">
            <h1 class="app-title">Ahorro Eco</h1>
            <div class="subtitle">Creación de Nueva Cuenta</div>
        </div>

        <?php if(isset($mensaje)) echo $mensaje; ?>

        <!-- Se oculta el formulario si el registro es exitoso -->
        <?php if(!isset($mensaje) || strpos($mensaje, 'exito') === false): ?>
        <form action="registro.php" method="POST">
            
            <div class="form-grid">
                <!-- Fila 1 -->
                <div class="input-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="input-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" required>
                </div>
                
                <!-- Fila 2 -->
                <div class="input-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento" onchange="cargarCiudades()" required>
                        <option value="">Seleccione un departamento...</option>
                        <script>
                            Object.keys(ciudadesCol).sort().forEach(depto => {
                                document.write(`<option value="${depto}">${depto}</option>`);
                            });
                        </script>
                    </select>
                </div>
                <div class="input-group">
                    <label for="ciudad">Ciudad / Municipio</label>
                    <select id="ciudad" name="ciudad" required>
                        <option value="">Seleccione un departamento primero...</option>
                    </select>
                </div>
                
                <!-- Fila 3 -->
                <div class="input-group full-width">
                    <label for="direccion">Dirección de Residencia</label>
                    <input type="text" id="direccion" name="direccion" required>
                </div>
                
                <!-- Fila 4 -->
                <div class="input-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" required>
                </div>
                <div class="input-group">
                    <label for="valor_kwh">Valor kWh (En Pesos Colombianos)</label>
                    <div class="currency-wrapper">
                        <span class="currency-symbol">$</span>
                        <input type="number" step="0.01" id="valor_kwh" name="valor_kwh" class="currency-input" required>
                        <span class="currency-code">COP</span>
                    </div>
                </div>

                <!-- Fila 5 -->
                <div class="input-group">
                    <label for="clave">Crear Clave</label>
                    <input type="password" id="clave" name="clave" placeholder="Min 8, mayúsculas, minúsculas y especial" required>
                </div>
                <div class="input-group">
                    <label for="confirmar_clave">Confirmar Clave</label>
                    <input type="password" id="confirmar_clave" name="confirmar_clave" placeholder="Repite tu contraseña" required>
                </div>

                <!-- Fila 6: reCAPTCHA -->
                <div class="full-width recaptcha-container">
                    <div class="g-recaptcha" data-sitekey="6LcwMp8tAAAAADOGjzS3AFeqG5LuVzXoVYcX0ugN"></div>
                </div>
                
                <!-- Botones -->
                <div class="full-width">
                    <button type="submit" class="btn btn-black">Registrar en el Sistema</button>
                    <button type="button" class="btn btn-outline" onclick="window.location.href='index.php'">Volver al Inicio</button>
                </div>
            </div>
            
        </form>
        <?php endif; ?>
        
    </div>
</body>
</html>