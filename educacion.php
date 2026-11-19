<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit(); }

// Guías educativas con fuentes oficiales verificables
$articulos = [
    [
        'categoria'     => 'habitos',
        'etiqueta'      => 'Hábitos en el Hogar',
        'titulo'        => 'El impacto del Consumo Vampiro (Standby) en el hogar',
        'resumen'       => 'Los electrodomésticos apagados pero enchufados pueden representar hasta el 12% del consumo mensual.',
        'contenido'     => 'Equipos como decodificadores de TV, consolas, microondas con reloj digital y cargadores siguen demandando energía las 24 horas. Agruparlos en multitomas con interruptor o enchufes inteligentes reduce el desperdicio continuo.',
        'fuente_nombre' => 'UPME - Guía de Eficiencia Energética',
        'fuente_url'    => 'https://www1.upme.gov.co/'
    ],
    [
        'categoria'     => 'equipos',
        'etiqueta'      => 'Electrodomésticos',
        'titulo'        => 'Etiquetado energético RETIQ y eficiencia en refrigeración',
        'resumen'       => 'La nevera opera las 24 horas y representa cerca del 30% de la factura eléctrica residencial.',
        'contenido'     => 'Mantén 10 cm de separación respecto a la pared para ventilar el condensador, regula el termostato entre 3 °C y 5 °C, revisa el sellado magnético de las puertas y prioriza equipos con etiqueta RETIQ clase A.',
        'fuente_nombre' => 'MinEnergía - Reglamento Técnico RETIQ',
        'fuente_url'    => 'https://www.minenergia.gov.co/'
    ],
    [
        'categoria'     => 'equipos',
        'etiqueta'      => 'Climatización',
        'titulo'        => 'Uso óptimo del aire acondicionado en climas cálidos',
        'resumen'       => 'Ajustar el termostato entre 23 °C y 25 °C evita sobrecargas innecesarias en el compresor.',
        'contenido'     => 'Cada grado por debajo de 23 °C incrementa el gasto eléctrico entre un 7% y un 10%. Limpiar los filtros mensualmente y mantener puertas y ventanas cerradas maximiza la eficiencia.',
        'fuente_nombre' => 'CREG - Uso Racional de Energía',
        'fuente_url'    => 'https://creg.gov.co/'
    ],
    [
        'categoria'     => 'ambiente',
        'etiqueta'      => 'Sostenibilidad e IoT',
        'titulo'        => 'Monitoreo inteligente (IoT) y reducción de huella de carbono',
        'resumen'       => 'Medir el consumo en tiempo real permite detectar anomalías y reducir emisiones de CO₂.',
        'contenido'     => 'En Colombia, cada kWh ahorrado evita aproximadamente 0.18 kg de CO₂ equivalente y reduce la presión sobre el sistema interconectado nacional en épocas de sequía.',
        'fuente_nombre' => 'IDEAM / UPME - Factores de Emisión',
        'fuente_url'    => 'https://www1.upme.gov.co/'
    ]
];

// Obtener noticias ambientales/energéticas desde Feed RSS externo (con caché de 6 horas)
function obtenerNoticiasExternasRSS() {
    $cacheFile = sys_get_temp_dir() . '/ahorroeco_rss_cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 21600) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached)) return $cached;
    }

    $rssUrl = 'https://news.google.com/rss/search?q=ahorro+energia+sostenibilidad+colombia&hl=es-419&gl=CO&ceid=CO:es-419';
    $noticias = [];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $rssUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'AhorroEco-TRL5/1.0'
        ]);
        $xmlStr = curl_exec($ch);
        curl_close($ch);

        if ($xmlStr) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlStr);
            if ($xml && isset($xml->channel->item)) {
                $contador = 0;
                foreach ($xml->channel->item as $item) {
                    if ($contador >= 3) break;
                    $tituloCompleto = (string)$item->title;
                    $partes = explode(' - ', $tituloCompleto);
                    $medio = count($partes) > 1 ? array_pop($partes) : 'Actualidad Energética';
                    $tituloLimpio = implode(' - ', $partes);

                    $noticias[] = [
                        'titulo' => $tituloLimpio,
                        'medio'  => $medio,
                        'fecha'  => date('d/m/Y', strtotime((string)$item->pubDate)),
                        'link'   => (string)$item->link
                    ];
                    $contador++;
                }
            }
        }
    }

    if (!empty($noticias)) {
        file_put_contents($cacheFile, json_encode($noticias));
    }
    return $noticias;
}

$noticias_externas = obtenerNoticiasExternasRSS();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - Centro de Educación</title>
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

        .content { padding: 35px 40px; flex-grow: 1; }
        .hero-edu { text-align: center; max-width: 760px; margin: 0 auto 25px auto; }
        .section-title { color: #000; font-size: 32px; font-weight: 900; margin: 0 0 10px 0; }
        .section-subtitle { color: #666; font-size: 17px; margin: 0; line-height: 1.5; }

        .filter-bar { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }
        .filter-btn { background: #f4f6f8; border: 1px solid #dcdcdc; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; color: #444; transition: all 0.2s; }
        .filter-btn:hover { background: #e8f5e9; border-color: #2e7d32; color: #2e7d32; }
        .filter-btn.active { background: #2e7d32; color: #fff; border-color: #2e7d32; }

        .articles-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 22px; margin-bottom: 40px; }
        .article-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; }
        .tag-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .badge-cat { background: #e8f5e9; color: #1b5e20; font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; }
        .badge-source { background: #f1f5f9; color: #334155; font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 6px; }
        .article-card h3 { margin: 0 0 10px 0; font-size: 18px; color: #111; line-height: 1.3; }
        .article-card p { margin: 0 0 16px 0; font-size: 14px; color: #555; line-height: 1.5; }
        .article-detail { display: none; background: #f8fafc; border-left: 3px solid #2e7d32; padding: 13px; margin-bottom: 15px; font-size: 13.5px; color: #333; border-radius: 0 6px 6px 0; line-height: 1.55; }
        .source-link { display: inline-block; margin-top: 8px; color: #1b5e20; font-weight: bold; text-decoration: none; font-size: 12.5px; }
        .source-link:hover { text-decoration: underline; }
        .btn-read { background: #111; color: #fff; border: none; padding: 9px 15px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; align-self: flex-start; }
        .btn-read:hover { background: #2e7d32; }

        /* Noticias RSS en vivo */
        .rss-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        .rss-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
        .rss-header h3 { margin: 0; font-size: 19px; color: #0f172a; }
        .live-pill { background: #dcfce7; color: #166534; font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; }
        .rss-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; }
        .rss-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; justify-content: space-between; }
        .rss-item a { color: #0f172a; font-weight: bold; text-decoration: none; font-size: 14px; line-height: 1.4; margin-bottom: 10px; display: block; }
        .rss-item a:hover { color: #2e7d32; }
        .rss-meta { font-size: 12px; color: #64748b; }

        @media (max-width: 768px) {
            .app-header { padding: 15px 20px; } .app-header h2 { font-size: 22px; } .app-logo-header { width: 60px; }
            .main-nav { justify-content: flex-start; flex-wrap: nowrap; gap: 15px; padding: 10px 15px; overflow-x: auto; white-space: nowrap; border-bottom: 3px solid #000; }
            .main-nav a { font-size: 11px; min-width: 65px; } .main-nav a span.icon { font-size: 22px; }
            .content { padding: 25px 15px; } .section-title { font-size: 26px; }
        }
    </style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2e7d32">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <h2>Ahorro Eco</h2>
            <img src="image_2f3ead.png" alt="Logo" class="app-logo-header">
        </header>

        <nav class="main-nav">
            <a href="home.php"><span class="icon">🏠</span>Home</a>
            <a href="iot.php"><span class="icon">📡</span>IOT</a>
            <a href="equipos.php"><span class="icon">🔌</span>Equipos</a>
            <a href="consumo.php"><span class="icon">⚡</span>Consumo</a>
            <a href="monitoreo.php"><span class="icon">📊</span>Monitoreo</a>
            <a href="recomendaciones.php"><span class="icon">💡</span>Recomendación</a>
            <a href="simulacion.php"><span class="icon">🖥️</span>Simulación</a>
            <a href="educacion.php" class="active"><span class="icon">🎓</span>Educación</a>
            <a href="perfil.php"><span class="icon">👤</span>Perfil</a>
            <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
        </nav>

        <main class="content">
            <div class="hero-edu">
                <h1 class="section-title">Centro de Educación</h1>
                <p class="section-subtitle">Guías técnicas respaldadas por entidades energéticas y noticias ambientales actualizadas.</p>
            </div>

            <!-- Filtros -->
            <div class="filter-bar">
                <button class="filter-btn active" onclick="filtrarArticulos('todos', this)">Todos los artículos</button>
                <button class="filter-btn" onclick="filtrarArticulos('habitos', this)">Hábitos en el Hogar</button>
                <button class="filter-btn" onclick="filtrarArticulos('equipos', this)">Electrodomésticos</button>
                <button class="filter-btn" onclick="filtrarArticulos('ambiente', this)">Sostenibilidad e IoT</button>
            </div>

            <!-- Guías educativas -->
            <div class="articles-grid">
                <?php foreach ($articulos as $art): ?>
                <article class="article-card" data-cat="<?= htmlspecialchars($art['categoria']) ?>">
                    <div>
                        <div class="tag-row">
                            <span class="badge-cat"><?= htmlspecialchars($art['etiqueta']) ?></span>
                            <span class="badge-source">Guía Oficial</span>
                        </div>
                        <h3><?= htmlspecialchars($art['titulo']) ?></h3>
                        <p><?= htmlspecialchars($art['resumen']) ?></p>
                        <div class="article-detail">
                            <?= nl2br(htmlspecialchars($art['contenido'])) ?><br>
                            <a href="<?= htmlspecialchars($art['fuente_url']) ?>" target="_blank" rel="noopener" class="source-link">
                                🔗 Fuente oficial: <?= htmlspecialchars($art['fuente_nombre']) ?> ↗
                            </a>
                        </div>
                    </div>
                    <button class="btn-read" onclick="toggleDetalle(this)">Leer guía y fuente</button>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- Noticias en tiempo real desde Feed RSS externo -->
            <?php if (!empty($noticias_externas)): ?>
            <section class="rss-box">
                <div class="rss-header">
                    <h3>🌐 Actualidad Externa: Energía y Medio Ambiente</h3>
                    <span class="live-pill">Sincronizado vía RSS Externo</span>
                </div>
                <div class="rss-list">
                    <?php foreach ($noticias_externas as $noticia): ?>
                    <div class="rss-item">
                        <a href="<?= htmlspecialchars($noticia['link']) ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($noticia['titulo']) ?> ↗
                        </a>
                        <div class="rss-meta">
                            Fuente: <strong><?= htmlspecialchars($noticia['medio']) ?></strong> • <?= htmlspecialchars($noticia['fecha']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleDetalle(btn) {
            const detail = btn.parentElement.querySelector('.article-detail');
            const visible = detail.style.display === 'block';
            detail.style.display = visible ? 'none' : 'block';
            btn.textContent = visible ? 'Leer guía y fuente' : 'Cerrar guía';
        }

        function filtrarArticulos(categoria, boton) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            boton.classList.add('active');

            document.querySelectorAll('.article-card').forEach(card => {
                card.style.display = (categoria === 'todos' || card.dataset.cat === categoria) ? 'flex' : 'none';
            });
        }
    </script>
</body>
</html>