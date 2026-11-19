<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/inc/bootstrap.php';

$usuario_id = requiere_sesion();
$mensaje = "";

// Tipos de imagen permitidos para la foto de perfil (se valida el CONTENIDO, no solo la extensión).
$TIPOS_FOTO = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// 1. Procesar actualización de datos y foto
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_exigir();
    $errores = [];

    $nombre       = trim((string) ($_POST['nombre'] ?? ''));
    $apellidos    = trim((string) ($_POST['apellidos'] ?? ''));
    $departamento = trim((string) ($_POST['departamento'] ?? ''));
    $ciudad       = trim((string) ($_POST['ciudad'] ?? ''));
    $valor_kwh    = filter_var(str_replace(',', '.', (string) ($_POST['valor_kwh'] ?? '')), FILTER_VALIDATE_FLOAT);
    $tipo         = (string) ($_POST['tipo_usuario'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100 || $apellidos === '' || mb_strlen($apellidos) > 100) {
        $errores[] = 'Escribe tu nombre y tus apellidos (máximo 100 caracteres cada uno).';
    }
    if ($departamento === '' || mb_strlen($departamento) > 100 || $ciudad === '' || mb_strlen($ciudad) > 100) {
        $errores[] = 'Selecciona un departamento y una ciudad.';
    }
    if ($valor_kwh === false || $valor_kwh < 0 || $valor_kwh > 10000) {
        $errores[] = 'El valor del kWh no es válido.';
    }
    if (!in_array($tipo, ['hogar', 'microempresa'], true)) {
        $errores[] = 'Elige si eres un hogar o una microempresa.';
    }

    $estrato = null;
    $sector = null;
    if ($tipo === 'hogar') {
        $estrato = filter_var($_POST['estrato'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 6]]);
        if ($estrato === false) { $errores[] = 'Elige tu estrato (1 a 6).'; $estrato = null; }
    } elseif ($tipo === 'microempresa') {
        $sector = (string) ($_POST['sector'] ?? '');
        if (!isset($CFG['sectores'][$sector])) { $errores[] = 'Elige el sector de tu negocio.'; $sector = null; }
    }

    $limite = null;
    if (trim((string) ($_POST['limite_diario_kwh'] ?? '')) !== '') {
        $limite = filter_var(str_replace(',', '.', (string) $_POST['limite_diario_kwh']), FILTER_VALIDATE_FLOAT);
        if ($limite === false || $limite <= 0 || $limite > 100000) { $errores[] = 'El límite diario debe ser un número mayor que cero.'; $limite = null; }
    }

    // Foto: tamaño máximo 2 MB, tipo real (finfo + getimagesize) y nombre generado por el servidor.
    $nuevaFoto = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['foto_perfil'];
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
            $errores[] = 'La foto no se pudo subir. Debe pesar máximo 2 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            if (!isset($TIPOS_FOTO[$mime]) || @getimagesize($f['tmp_name']) === false) {
                $errores[] = 'La foto debe ser una imagen JPG, PNG o WEBP.';
            } else {
                $nuevaFoto = ['tmp' => $f['tmp_name'], 'ext' => $TIPOS_FOTO[$mime]];
            }
        }
    }

    if ($errores) {
        $mensaje = "<div class='alerta error'>" . e(implode(' ', $errores)) . "</div>";
    } else {
        if ($nuevaFoto !== null) {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            foreach (glob($dir . "perfil_" . $usuario_id . ".*") ?: [] as $v) { @unlink($v); }
            move_uploaded_file($nuevaFoto['tmp'], $dir . "perfil_" . $usuario_id . "." . $nuevaFoto['ext']);
        }

        $stmt = $conn->prepare(
            "UPDATE usuarios SET nombre=?, apellidos=?, departamento=?, ciudad=?, valor_kwh=?,
                    tipo_usuario=?, estrato=?, sector=?, limite_diario_kwh=? WHERE id=?"
        );
        $stmt->bind_param('ssssdsisdi', $nombre, $apellidos, $departamento, $ciudad, $valor_kwh, $tipo, $estrato, $sector, $limite, $usuario_id);
        if ($stmt->execute()) {
            $_SESSION['nombre'] = $nombre;
            $mensaje = "<div class='alerta exito'>¡Perfil actualizado correctamente!</div>";
        } else {
            error_log('Ahorro Eco: no se pudo actualizar el perfil: ' . $stmt->error);
            $mensaje = "<div class='alerta error'>No se pudo actualizar el perfil. Inténtalo de nuevo.</div>";
        }
        $stmt->close();
    }
}

// 2. Obtener datos actuales del usuario
$stmt = $conn->prepare("SELECT nombre, apellidos, correo, departamento, ciudad, valor_kwh, tipo_usuario, estrato, sector, limite_diario_kwh FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Buscar si el usuario tiene foto subida
$foto_perfil = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
$archivos_foto = glob(__DIR__ . "/uploads/perfil_" . $usuario_id . ".*");
if (!empty($archivos_foto)) {
    $foto_perfil = 'uploads/' . basename($archivos_foto[0]) . "?v=" . time();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Mi Perfil</title>
    <style>
        /* ESTILOS GENERALES Y PC */
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .app-container { 
            width: 100%; max-width: 1200px; margin: 0 auto; background-color: #ffffff; 
            min-height: 100vh; box-sizing: border-box; box-shadow: 0 0 15px rgba(0,0,0,0.1);
            display: flex; flex-direction: column;
        }

        /* Encabezado y Navegación */
        .app-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-bottom: 1px solid #eaeaea; }
        .app-header h2 { margin: 0; color: #000; font-size: 28px; }
        .app-logo-header { width: 80px; height: auto; }

        .main-nav { 
            display: flex; justify-content: center; gap: 35px; padding: 15px 40px; 
            border-bottom: 2px solid #000; background-color: #ffffff; flex-wrap: wrap;
        }
        .main-nav::-webkit-scrollbar { display: none; } 
        .main-nav a { color: #555; text-decoration: none; font-size: 14px; display: flex; flex-direction: column; align-items: center; font-weight: bold; transition: transform 0.2s; }
        .main-nav a:hover, .main-nav a.active { color: #000; transform: scale(1.05); }
        .main-nav a span.icon { font-size: 26px; margin-bottom: 5px; }
        .nav-salir { color: #d32f2f !important; }
        .nav-salir:hover { color: #b71c1c !important; }

        /* Contenido Central */
        .content { padding: 40px 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin-bottom: 30px; text-align: center; }
        
        /* Contenedor del Formulario (Ancho para PC) */
        .form-container { 
            width: 100%; max-width: 850px; background-color: #ffffff; padding: 30px; 
            border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eaeaea;
        }

        /* Maquetación a 2 grandes bloques (Foto a la izquierda, Formulario a la derecha) */
        .form-layout { display: flex; gap: 40px; align-items: flex-start; }
        .foto-col { flex: 0 0 220px; text-align: center; }
        .fields-col { flex: 1; }
        
        /* Estilos de la Foto */
        .foto-img { width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 5px solid #2e7d32; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .foto-input { display: block; width: 100%; font-size: 12px; color: #555; margin-top: 10px; cursor: pointer; }

        /* Cuadrícula interna para los campos del formulario en PC */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 20px; }
        .full-width { grid-column: 1 / -1; } /* Hace que un campo ocupe las 2 columnas */

        .input-group { text-align: left; }
        .input-group label { font-size: 14px; font-weight: bold; color: #333; display: block; margin-bottom: 6px; }
        .input-group input, .input-group select { width: 100%; padding: 12px; border: 1px solid #cccccc; border-radius: 8px; box-sizing: border-box; font-size: 15px; color: #000; background-color: #fff; }
        .input-group input[readonly] { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; border: 1px dashed #ccc; }
        
        /* Formato especial de Moneda (COP) */
        .currency-wrapper { position: relative; display: flex; align-items: center; }
        .currency-symbol { position: absolute; left: 12px; font-size: 16px; font-weight: bold; color: #555; }
        .currency-code { position: absolute; right: 12px; font-size: 13px; font-weight: bold; color: #777; }
        .currency-input { padding-left: 28px !important; padding-right: 50px !important; }

        /* Botón de Guardar */
        .btn-black { background-color: #000; color: #fff; border: none; padding: 15px; width: 100%; max-width: 300px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: transform 0.1s; margin: 25px auto 0; display: block; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .btn-black:active { transform: scale(0.95); }

        .alerta { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; width: 100%; box-sizing: border-box; }
        .exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }

        /* DISEÑO MÓVIL (Se apilan los elementos) */
        @media (max-width: 768px) {
            .app-container { box-shadow: none; }
            .app-header { padding: 15px 20px; }
            .app-header h2 { font-size: 22px; }
            .app-logo-header { width: 60px; }
            .main-nav { justify-content: flex-start; flex-wrap: nowrap; gap: 15px; padding: 10px 15px; overflow-x: auto; white-space: nowrap; border-bottom: 3px solid #000; }
            .main-nav a { font-size: 11px; min-width: 65px; }
            .main-nav a span.icon { font-size: 22px; }
            .content { padding: 20px 15px; }
            .section-title { font-size: 26px; margin-bottom: 20px; }
            
            .form-container { padding: 20px; border: none; box-shadow: none; border-radius: 0; }
            .form-layout { flex-direction: column; align-items: center; gap: 20px; }
            .foto-col { flex: none; width: 100%; }
            .foto-img { width: 130px; height: 130px; }
            .form-grid { grid-template-columns: 1fr; /* Pasa de 2 columnas a 1 columna */ }
            .btn-black { max-width: 100%; }
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

        function cargarCiudades(ciudadActual = "") {
            const depto = document.getElementById("departamento").value;
            const selectCiudad = document.getElementById("ciudad");
            selectCiudad.innerHTML = '<option value="">Seleccione una ciudad...</option>'; 

            if (ciudadesCol[depto]) {
                ciudadesCol[depto].sort().forEach(ciudad => {
                    let option = document.createElement("option");
                    option.value = ciudad;
                    option.text = ciudad;
                    if (ciudad === ciudadActual) {
                        option.selected = true;
                    }
                    selectCiudad.add(option);
                });
            }
        }

        window.onload = function() {
            const deptoGuardado = "<?php echo addslashes($usuario['departamento']); ?>";
            const ciudadGuardada = "<?php echo addslashes($usuario['ciudad']); ?>";
            
            if (deptoGuardado) {
                document.getElementById("departamento").value = deptoGuardado;
                cargarCiudades(ciudadGuardada);
            }
        };
    </script>
</head>
<body>
    <div class="app-container">
        
        <header class="app-header">
            <h2>Ahorro Eco</h2>
            <img src="image_2f3ead.png" alt="Logo Ahorro Eco" class="app-logo-header">
        </header>

        <nav class="main-nav">
            <a href="home.php"><span class="icon">🏠</span>Home</a>
            <a href="iot.php"><span class="icon">📡</span>IOT</a>
            <a href="monitoreo.php"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php" class="active"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <h1 class="section-title">Mi Perfil</h1>
            
            <div class="form-container">
                <?php echo $mensaje; ?>

                <form action="perfil.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_campo(); ?>
                    <div class="form-layout">
                        
                        <!-- Columna Izquierda: Foto de Perfil -->
                        <div class="foto-col">
                            <img src="<?php echo $foto_perfil; ?>" alt="Foto de perfil" class="foto-img">
                            <label style="font-size: 14px; font-weight: bold; color: #333;">Actualizar Foto</label>
                            <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp" class="foto-input">
                        </div>

                        <!-- Columna Derecha: Campos de Formulario en Grid -->
                        <div class="fields-col">
                            <div class="form-grid">
                                
                                <div class="input-group full-width">
                                    <label>Correo Electrónico (No editable)</label>
                                    <input type="email" value="<?php echo htmlspecialchars($usuario['correo']); ?>" readonly>
                                </div>
                                
                                <div class="input-group">
                                    <label>Departamento</label>
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
                                    <label>Ciudad / Municipio</label>
                                    <select id="ciudad" name="ciudad" required>
                                        <option value="">Seleccione...</option>
                                    </select>
                                </div>

                                <div class="input-group">
                                    <label>Nombre</label>
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                </div>
                                
                                <div class="input-group">
                                    <label>Apellidos</label>
                                    <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos']); ?>" required>
                                </div>
                                <div class="input-group">
                                    <label>Tipo de usuario</label>
                                    <select name="tipo_usuario" id="tipo_usuario" onchange="alternarPerfil()" required>
                                        <option value="hogar" <?php echo $usuario['tipo_usuario'] === 'hogar' ? 'selected' : ''; ?>>Hogar</option>
                                        <option value="microempresa" <?php echo $usuario['tipo_usuario'] === 'microempresa' ? 'selected' : ''; ?>>Microempresa</option>
                                    </select>
                                </div>

                                <div class="input-group" id="grp_estrato">
                                    <label>Estrato</label>
                                    <select name="estrato">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo (int) $usuario['estrato'] === $i ? 'selected' : ''; ?>>Estrato <?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="input-group" id="grp_sector">
                                    <label>Sector del negocio</label>
                                    <select name="sector">
                                        <?php foreach ($CFG['sectores'] as $clave => $texto): ?>
                                            <option value="<?php echo e($clave); ?>" <?php echo $usuario['sector'] === $clave ? 'selected' : ''; ?>><?php echo e($texto); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="input-group full-width">
                                    <label>Límite diario de consumo en kWh (opcional)</label>
                                    <input type="number" step="0.1" min="0" name="limite_diario_kwh" value="<?php echo e($usuario['limite_diario_kwh']); ?>" placeholder="Ej.: 8">
                                    <small style="color:#777">Si lo superas, Ahorro Eco te muestra una alerta.</small>
                                </div>

                                <!-- Campo de Moneda con símbolo $ y COP -->
                                <div class="input-group full-width">
                                    <label>Valor kWh (módulos IOT y Monitoreo)</label>
                                    <div class="currency-wrapper">
                                        <span class="currency-symbol">$</span>
                                        <input type="number" step="0.01" name="valor_kwh" class="currency-input" value="<?php echo htmlspecialchars($usuario['valor_kwh']); ?>" required>
                                        <span class="currency-code">COP</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-black">Guardar Cambios</button>
                </form>

                <div style="margin-top:28px; padding-top:18px; border-top:1px solid #eee; text-align:center;">
                    <a href="eliminar_cuenta.php" style="color:#d32f2f; font-weight:bold; text-decoration:none;">Eliminar mi cuenta y mis datos</a>
                </div>
            </div>
        </main>

    </div>

    <script>
        function alternarPerfil() {
            var micro = document.getElementById('tipo_usuario').value === 'microempresa';
            document.getElementById('grp_estrato').style.display = micro ? 'none' : '';
            document.getElementById('grp_sector').style.display = micro ? '' : 'none';
        }
        alternarPerfil();
    </script>
</body>
</html>