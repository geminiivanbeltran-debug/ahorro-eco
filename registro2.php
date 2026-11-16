<?php
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
    $clave_plana = $_POST['clave'];
    $confirmar_clave = $_POST['confirmar_clave'];
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // 1. Validación de Google reCAPTCHA
    $recaptcha_secret = '6LcwMp8tAAAAAN5ElEVGZSSmlnH7qVISKl4OETSc'; // Reemplaza con tu clave secreta
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
            $mensaje = "<div class='alerta error'>Atención: Este correo electrónico ya se encuentra registrado en el sistema.</div>";
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
                    $mail->isSMTP();                                            
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;                                   
                    $mail->Username   = 'geminiivanbeltran@gmail.com'; 
                    $mail->Password   = 'dmjepvvrdlzctldk'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                    $mail->Port       = 465;                                    

                    $mail->setFrom('tucorreo@gmail.com', 'Ahorro Eco');
                    $mail->addAddress($correo, $nombre);     

                    $mail->isHTML(true);                                  
                    $mail->Subject = '¡Bienvenido a Ahorro Eco!';
                    $mail->Body = "<h2>¡Hola $nombre!</h2><p>Gracias por registrarte en Ahorro Eco.</p>";

                    $mail->send();
                    $mensaje = "<div class='alerta exito'>Registro exitoso. Correo enviado. <br><a href='index.php'>Ingresa aquí</a></div>";
                } catch (Exception $e) {
                    $mensaje = "<div class='alerta exito'>Registro exitoso (Sin correo). <br><a href='index.php'>Ingresa aquí</a></div>";
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
    
    <!-- Script de Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; display: flex; justify-content: center; align-items: center; margin: 0; padding: 20px 0; }
        .app-container { width: 100%; max-width: 360px; padding: 20px; box-sizing: border-box; }
        .app-title-container { text-align: center; margin-bottom: 20px; }
        .app-logo { width: 120px; height: auto; display: block; margin: 0 auto 10px; }
        .app-title { color: #2e7d32; font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { font-size: 18px; font-weight: bold; color: #333; }
        .input-group { margin-bottom: 12px; }
        .input-group label { font-size: 13px; font-weight: bold; color: #333; display: block; margin-bottom: 4px; }
        .input-group input, .input-group select { width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 8px; box-sizing: border-box; font-size: 14px; background-color: #fff; }
        .btn-black { background-color: #000000; color: #ffffff; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-bottom: 10px; }
        .btn-outline { background-color: transparent; color: #000000; border: 1px solid #000000; }
        .alerta { padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 14px; }
        .exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .g-recaptcha { margin-bottom: 15px; transform: scale(0.95); transform-origin: 0 0; } /* Ajuste para móviles */
    </style>
    
    <script>
        // Diccionario con los 32 departamentos y principales municipios (reducido para eficiencia web)
        // Para integrar los 1,122 municipios exactos en producción, se recomienda cargar un archivo ciudades.json vía Fetch API.
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
    <div class="app-container">
        <div class="app-title-container">
            <img src="image_2f3ead.png" alt="Logo Ahorro Eco" class="app-logo">
            <h1 class="app-title">Ahorro Eco</h1>
            <div class="subtitle">Registro de Usuario</div>
        </div>

        <?php if(isset($mensaje)) echo $mensaje; ?>

        <form action="registro.php" method="POST">
            <!-- Campos Personales -->
            <div class="input-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="input-group">
                <label for="apellidos">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" required>
            </div>
            
            <!-- Ubicación -->
            <div class="input-group">
                <label for="departamento">Departamento</label>
                <select id="departamento" name="departamento" onchange="cargarCiudades()" required>
                    <option value="">Seleccione un departamento...</option>
                    <script>
                        // Cargar departamentos dinámicamente
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
            <div class="input-group">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" required>
            </div>
            <div class="input-group">
                <label for="valor_kwh">Valor kWh (Ver Recibo)</label>
                <input type="number" step="0.01" id="valor_kwh" name="valor_kwh" required>
            </div>

            <!-- Credenciales -->
            <div class="input-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" required>
            </div>
            <div class="input-group">
                <label for="clave">Crear Clave</label>
                <input type="password" id="clave" name="clave" placeholder="Min 8, mayúsculas, minúsculas y especial" required>
            </div>
            <div class="input-group">
                <label for="confirmar_clave">Confirmar Clave</label>
                <input type="password" id="confirmar_clave" name="confirmar_clave" placeholder="Repite tu contraseña" required>
            </div>

            <!-- Google reCAPTCHA v2 -->
            <!-- IMPORTANTE: Reemplaza TU_CLAVE_DE_SITIO_DE_GOOGLE con tu Site Key pública -->
            <div class="g-recaptcha" data-sitekey="6LcwMp8tAAAAADOGjzS3AFeqG5LuVzXoVYcX0ugN"></div>
            
            <button type="submit" class="btn-black">Registrar en el Sistema</button>
            <button type="button" class="btn-black btn-outline" onclick="window.location.href='index.php'">Volver</button>
        </form>
    </div>
</body>
</html>