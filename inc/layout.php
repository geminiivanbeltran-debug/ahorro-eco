<?php
declare(strict_types=1);

/**
 * Encabezado y navegación compartidos por las páginas nuevas.
 * La navegación conserva todos los enlaces de la demo y suma Equipos y Consumo.
 */

function pagina_inicio(string $titulo, string $activo): void
{
    $items = [
        'home'            => ['home.php',            '🏠', 'Home'],
        'iot'             => ['iot.php',             '📡', 'IOT'],
        'equipos'         => ['equipos.php',         '🔌', 'Equipos'],
        'consumo'         => ['consumo.php',         '⚡', 'Consumo'],
        'monitoreo'       => ['monitoreo.php',       '📊', 'Monitoreo'],
        'recomendaciones' => ['recomendaciones.php', '💡', 'Recomendación'],
        'simulacion'      => ['simulacion.php',      '🖥️', 'Simulación'],
        'educacion'       => ['educacion.php',       '🎓', 'Educación'],
        'perfil'          => ['perfil.php',          '👤', 'Perfil'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorro Eco - <?= e($titulo) ?></title>
    <link rel="stylesheet" href="nc_assets/css/app.css">
    <?php include __DIR__ . '/pwa_head.php'; ?>
</head>
<body>
<div class="app-container">
    <header class="app-header"><h2>Ahorro Eco</h2><img src="image_2f3ead.png" alt="Logo Ahorro Eco" class="app-logo-header"></header>
    <nav class="main-nav">
        <?php foreach ($items as $clave => [$url, $icono, $texto]): ?>
            <a href="<?= e($url) ?>"<?= $clave === $activo ? ' class="active"' : '' ?>><span class="icon"><?= $icono ?></span><?= e($texto) ?></a>
        <?php endforeach; ?>
        <a href="logout.php" class="nav-salir"><span class="icon">🚪</span>Salir</a>
    </nav>
    <main class="content">
<?php
}

function pagina_fin(): void
{
    ?>
    </main>
</div>
</body>
</html>
<?php
}
