<?php
declare(strict_types=1);

/**
 * M1 - Registro de equipos (RF-02, HU-01).
 * El usuario crea, edita y borra sus equipos con potencia, cantidad y horas de uso.
 */
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/consultas.php';

header('Content-Type: text/html; charset=utf-8');

$uid = requiere_sesion();
$u = cargar_usuario($conn, $uid);
$modelo = new Modelo($CFG);
$tarifa = new Tarifa($CFG['tarifas']);

function volver(string $tipo, string $texto): void
{
    $_SESSION['flash'] = [$tipo, $texto];
    header('Location: equipos.php');
    exit;
}

/** Valida los campos del formulario. Devuelve el arreglo limpio o un texto de error. */
function leer_equipo(array $cfg)
{
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    if ($nombre === '' || mb_strlen($nombre) > 80) {
        return 'El nombre es obligatorio y debe tener máximo 80 caracteres.';
    }
    $categoria = (string) ($_POST['categoria'] ?? '');
    if (!isset($cfg['categorias'][$categoria])) {
        return 'Elige una categoría válida.';
    }
    $potencia = filter_var(str_replace(',', '.', (string) ($_POST['potencia_w'] ?? '')), FILTER_VALIDATE_FLOAT);
    if ($potencia === false || $potencia <= 0 || $potencia > 20000) {
        return 'La potencia debe ser un número entre 1 y 20.000 W (por unidad).';
    }
    $cantidad = filter_var($_POST['cantidad'] ?? '', FILTER_VALIDATE_INT);
    if ($cantidad === false || $cantidad < 1 || $cantidad > 999) {
        return 'La cantidad debe ser un entero entre 1 y 999.';
    }
    $horas = filter_var(str_replace(',', '.', (string) ($_POST['horas_dia'] ?? '')), FILTER_VALIDATE_FLOAT);
    if ($horas === false || $horas < 0 || $horas > 24) {
        return 'Las horas de uso al día deben estar entre 0 y 24.';
    }
    $franja = (string) ($_POST['franja'] ?? '');
    if (!isset($cfg['franjas'][$franja])) {
        return 'Elige una franja válida.';
    }
    return [
        'nombre' => $nombre, 'categoria' => $categoria, 'potencia' => (float) $potencia,
        'cantidad' => (int) $cantidad, 'horas' => round((float) $horas, 1), 'franja' => $franja,
        'reposo' => isset($_POST['deja_reposo']) ? 1 : 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'guardar') {
        $d = leer_equipo($CFG);
        if (is_string($d)) {
            volver('error', $d);
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare(
                'UPDATE equipos SET nombre=?, categoria=?, potencia_w=?, cantidad=?, horas_dia=?, franja=?, deja_reposo=?
                  WHERE id=? AND usuario_id=?'
            );
            $stmt->bind_param('ssdidsiii', $d['nombre'], $d['categoria'], $d['potencia'], $d['cantidad'], $d['horas'], $d['franja'], $d['reposo'], $id, $uid);
            $stmt->execute();
            $stmt->close();
            volver('exito', 'Equipo actualizado.');
        }
        $ahora = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            'INSERT INTO equipos (usuario_id, nombre, categoria, potencia_w, cantidad, horas_dia, franja, deja_reposo, creado)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('issdidsis', $uid, $d['nombre'], $d['categoria'], $d['potencia'], $d['cantidad'], $d['horas'], $d['franja'], $d['reposo'], $ahora);
        $stmt->execute();
        $stmt->close();
        volver('exito', 'Equipo registrado.');
    }

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM equipos WHERE id=? AND usuario_id=?');
        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $stmt->close();
        volver('exito', 'Equipo eliminado.');
    }

    if ($accion === 'plantilla') {
        if (count(cargar_equipos($conn, $uid)) > 0) {
            volver('error', 'Ya tienes equipos registrados; la plantilla solo se carga cuando la lista está vacía.');
        }
        $ahora = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            'INSERT INTO equipos (usuario_id, nombre, categoria, potencia_w, cantidad, horas_dia, franja, deja_reposo, creado)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        foreach ($CFG['plantillas'][$u['tipo_usuario']] as $p) {
            $pot = (float) $p['potencia'];
            $cant = (int) $p['cantidad'];
            $hor = (float) $p['horas'];
            $rep = (int) $p['reposo'];
            $stmt->bind_param('issdidsis', $uid, $p['nombre'], $p['categoria'], $pot, $cant, $hor, $p['franja'], $rep, $ahora);
            $stmt->execute();
        }
        $stmt->close();
        volver('exito', 'Se cargaron los equipos de ejemplo. Ajusta sus valores a los de tu hogar o negocio.');
    }

    volver('error', 'Acción no reconocida.');
}

// ---------------------------------------------------------------- Vista
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$equipos = cargar_equipos($conn, $uid);
$editando = null;
if (isset($_GET['editar'])) {
    foreach ($equipos as $eq) {
        if ($eq['id'] === (int) $_GET['editar']) { $editando = $eq; }
    }
}

$totalKwh = 0.0;
foreach ($equipos as $eq) { $totalKwh += $modelo->kwhMes($eq); }
$totalCop = $tarifa->costoMes($u['tipo_usuario'], $u['estrato'], $totalKwh);

$val = fn(string $k, $def = '') => $editando[$k] ?? $def;

pagina_inicio('Mis equipos', 'equipos');
?>
<h1 class="section-title">Mis equipos</h1>
<p class="lead">Registra los equipos de tu <?= $u['tipo_usuario'] === 'hogar' ? 'hogar' : 'negocio' ?> con su potencia, cantidad y horas de uso. Con esos datos se simula el consumo y se generan las recomendaciones.</p>

<?php if ($flash): ?><div class="alerta <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="card">
    <h3><?= $editando ? 'Editar equipo' : 'Agregar equipo' ?></h3>
    <form method="post" action="equipos.php" autocomplete="off">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : 0 ?>">
        <div class="form-grid">
            <div class="campo">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria" required>
                    <?php foreach ($CFG['categorias'] as $clave => $c):
                        if ($c['perfil'] !== 'ambos' && $c['perfil'] !== $u['tipo_usuario']) { continue; } ?>
                        <option value="<?= e($clave) ?>" data-potencia="<?= e($c['potencia']) ?>" data-horas="<?= e($c['horas']) ?>" data-nombre="<?= e($c['nombre']) ?>"
                            <?= $val('categoria') === $clave ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80" required value="<?= e($val('nombre')) ?>" placeholder="Ej.: Nevera de la cocina">
            </div>
            <div class="campo">
                <label for="franja">Franja principal de uso</label>
                <select id="franja" name="franja" required>
                    <?php foreach ($CFG['franjas'] as $clave => $f): ?>
                        <option value="<?= e($clave) ?>" <?= $val('franja', 'noche') === $clave ? 'selected' : '' ?>><?= e($f['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="potencia_w">Potencia por unidad (W)</label>
                <input type="number" id="potencia_w" name="potencia_w" min="1" max="20000" step="any" required value="<?= e($val('potencia_w')) ?>">
                <div class="ayuda">Está en la etiqueta del equipo.</div>
            </div>
            <div class="campo">
                <label for="cantidad">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" min="1" max="999" step="1" required value="<?= e($val('cantidad', 1)) ?>">
            </div>
            <div class="campo">
                <label for="horas_dia">Horas de uso al día</label>
                <input type="number" id="horas_dia" name="horas_dia" min="0" max="24" step="0.1" required value="<?= e($val('horas_dia')) ?>">
            </div>
            <div class="campo full">
                <label class="check"><input type="checkbox" name="deja_reposo" value="1" <?= $val('deja_reposo') ? 'checked' : '' ?>>
                    Queda conectado sin usarse (consumo en reposo o «standby»)</label>
            </div>
        </div>
        <div class="acciones" style="margin-top:16px">
            <button type="submit" class="btn btn-negro"><?= $editando ? 'Guardar cambios' : 'Agregar equipo' ?></button>
            <?php if ($editando): ?><a href="equipos.php" class="btn btn-borde">Cancelar</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3>Equipos registrados (<?= count($equipos) ?>)</h3>
    <?php if (!$equipos): ?>
        <div class="vacio">
            Aún no has registrado equipos.
            <form method="post" action="equipos.php" style="margin-top:14px">
                <?= csrf_campo() ?><input type="hidden" name="accion" value="plantilla">
                <button type="submit" class="btn btn-verde">Cargar equipos de ejemplo (<?= $u['tipo_usuario'] === 'hogar' ? 'hogar' : 'microempresa' ?>)</button>
            </form>
        </div>
    <?php else: ?>
        <div class="tabla-scroll">
        <table class="tabla">
            <thead><tr>
                <th>Equipo</th><th class="num">Potencia</th><th class="num">Cant.</th><th class="num">Horas/día</th>
                <th>Franja</th><th>Reposo</th><th class="num">kWh/mes</th><th class="num">% del total</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($equipos as $eq): $k = $modelo->kwhMes($eq); ?>
                <tr>
                    <td><strong><?= e($eq['nombre']) ?></strong><br><span class="estado"><?= e($modelo->categoria($eq['categoria'])['nombre']) ?></span></td>
                    <td class="num"><?= num($eq['potencia_w'], 0) ?> W</td>
                    <td class="num"><?= (int) $eq['cantidad'] ?></td>
                    <td class="num"><?= num($eq['horas_dia'], 1) ?></td>
                    <td><?= e(explode(' (', $CFG['franjas'][$eq['franja']]['nombre'])[0]) ?></td>
                    <td><?= $eq['deja_reposo'] ? 'Sí' : 'No' ?></td>
                    <td class="num"><?= num($k, 1) ?></td>
                    <td class="num"><?= $totalKwh > 0 ? num($k * 100 / $totalKwh, 0) : 0 ?> %</td>
                    <td class="num">
                        <a class="btn btn-borde btn-chico" href="equipos.php?editar=<?= (int) $eq['id'] ?>">Editar</a>
                        <form method="post" action="equipos.php" style="display:inline" onsubmit="return confirm('¿Eliminar este equipo y sus lecturas?');">
                            <?= csrf_campo() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int) $eq['id'] ?>">
                            <button type="submit" class="btn btn-rojo btn-chico">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="6"><strong>Consumo mensual estimado</strong></td>
                <td class="num"><strong><?= num($totalKwh, 1) ?></strong></td><td class="num">100 %</td><td></td>
            </tr></tfoot>
        </table>
        </div>
        <p class="estado" style="margin-top:12px">
            Factura estimada de energía: <strong><?= cop($totalCop) ?></strong> al mes · <?= e($tarifa->descripcion($u['tipo_usuario'], $u['estrato'])) ?>
        </p>
        <div class="acciones" style="margin-top:14px">
            <a class="btn btn-verde" href="consumo.php">Simular consumo →</a>
            <a class="btn btn-borde" href="recomendaciones.php">Ver recomendaciones</a>
        </div>
    <?php endif; ?>
</div>

<script>
// Al elegir una categoría nueva se sugieren potencia, horas y nombre típicos (solo al crear, y sin pisar lo que ya escribiste).
(function () {
    var cat = document.getElementById('categoria');
    var editando = <?= $editando ? 'true' : 'false' ?>;
    function sugerir() {
        if (editando) { return; }
        var o = cat.options[cat.selectedIndex];
        var p = document.getElementById('potencia_w'), h = document.getElementById('horas_dia'), n = document.getElementById('nombre');
        if (!p.dataset.tocado) { p.value = o.dataset.potencia; }
        if (!h.dataset.tocado) { h.value = o.dataset.horas; }
        if (!n.dataset.tocado) { n.value = o.dataset.nombre; }
    }
    ['potencia_w', 'horas_dia', 'nombre'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () { this.dataset.tocado = '1'; });
    });
    cat.addEventListener('change', sugerir);
    sugerir();
})();
</script>
<?php pagina_fin();
