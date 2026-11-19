<?php
declare(strict_types=1);

/**
 * M3 - Panel de consumo y costo (RF-04, HU-02).
 * Muestra el consumo por equipo y por día y estima la factura en pesos con la
 * tarifa de EMCALI según el estrato o el sector. Se actualiza con cada lectura simulada.
 */
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/consultas.php';

header('Content-Type: text/html; charset=utf-8');

$uid = requiere_sesion();
$u = cargar_usuario($conn, $uid);
$equipos = cargar_equipos($conn, $uid);

pagina_inicio('Consumo', 'consumo');
?>
<h1 class="section-title">Consumo y costo</h1>
<p class="lead">Los sensores de «Ahorro Eco» se simulan por software a partir de tus equipos. Cada lectura se guarda, el panel se actualiza y las recomendaciones se calculan de nuevo.</p>

<?php if (!$equipos): ?>
    <div class="alerta info">Primero registra tus equipos para poder simular su consumo.</div>
    <a class="btn btn-negro" href="equipos.php">Ir a Mis equipos</a>
<?php else: ?>

<div class="card">
    <h3>Simulador</h3>
    <div class="form-grid">
        <div class="campo">
            <label for="seg">Leer cada…</label>
            <select id="seg">
                <option value="5">5 segundos</option>
                <option value="10" selected>10 segundos</option>
                <option value="30">30 segundos</option>
            </select>
        </div>
        <div class="campo">
            <label for="min">Cada lectura equivale a…</label>
            <select id="min">
                <option value="5">5 minutos</option>
                <option value="15" selected>15 minutos</option>
                <option value="30">30 minutos</option>
                <option value="60">1 hora</option>
            </select>
        </div>
        <div class="campo">
            <label for="hora">Hora que se simula</label>
            <select id="hora">
                <option value="">Hora actual</option>
                <?php for ($h = 0; $h < 24; $h++): ?>
                    <option value="<?= $h ?>"><?= sprintf('%02d:00', $h) ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <div class="acciones" style="margin-top:14px">
        <button type="button" class="btn btn-negro" id="btnUna">Una lectura</button>
        <button type="button" class="btn btn-verde" id="btnIniciar">▶ Iniciar simulación</button>
        <button type="button" class="btn btn-borde" id="btnDetener" disabled>■ Detener</button>
        <button type="button" class="btn btn-rojo" id="btnReiniciar">Borrar lecturas</button>
        <span class="estado" id="estado">Simulación detenida.</span>
    </div>
</div>

<div class="grid-kpi">
    <div class="kpi"><div class="etq">Consumo de hoy</div><div class="val" id="kHoyKwh">—</div><div class="sub" id="kHoyCop">—</div></div>
    <div class="kpi"><div class="etq">Acumulado del mes</div><div class="val" id="kMesCop">—</div><div class="sub" id="kMesKwh">—</div></div>
    <div class="kpi"><div class="etq">Factura estimada del mes</div><div class="val" id="kProyCop">—</div><div class="sub" id="kProyKwh">—</div></div>
    <div class="kpi"><div class="etq">Recomendaciones</div><div class="val" id="kRecs">—</div><div class="sub" id="kTiempo">Se actualizan con cada lectura</div></div>
</div>

<div class="alerta info" id="alertaLimite" style="display:none"></div>

<div class="card">
    <h3>Consumo de hoy por equipo</h3>
    <div class="tabla-scroll">
    <table class="tabla">
        <thead><tr><th>Equipo</th><th class="num">Potencia última lectura</th><th class="num">kWh hoy</th><th class="num">Costo hoy</th></tr></thead>
        <tbody id="tbEquipos"><tr><td colspan="4" class="estado">Cargando…</td></tr></tbody>
    </table>
    </div>
</div>

<div class="card">
    <h3>Consumo de los últimos 7 días (kWh)</h3>
    <div class="barras" id="barras"></div>
</div>

<p class="estado" id="tarifa"></p>
<p><a class="btn btn-negro" href="recomendaciones.php">Ver recomendaciones de ahorro →</a></p>

<script>
(function () {
    var CSRF = <?= json_encode(csrf_token()) ?>;
    var temporizador = null;
    var ultimaPotencia = {};   // equipo_id -> W de la última lectura

    var $ = function (id) { return document.getElementById(id); };
    var fmt = function (n, d) { return Number(n).toLocaleString('es-CO', { minimumFractionDigits: d, maximumFractionDigits: d }); };
    var cop = function (n) { return '$' + fmt(n, 0); };

    function celda(texto, clase) {
        var td = document.createElement('td');
        td.textContent = texto;
        if (clase) { td.className = clase; }
        return td;
    }

    function pintar(r) {
        $('kHoyKwh').textContent = fmt(r.hoy.kwh, 2) + ' kWh';
        $('kHoyCop').textContent = cop(r.hoy.cop);
        $('kMesCop').textContent = cop(r.mes.cop);
        $('kMesKwh').textContent = fmt(r.mes.kwh, 1) + ' kWh en lo que va del mes';
        $('kProyCop').textContent = cop(r.proyeccion_mes.cop);
        $('kProyKwh').textContent = fmt(r.proyeccion_mes.kwh, 1) + ' kWh según tus equipos';
        $('tarifa').textContent = r.tarifa;

        var cuerpo = $('tbEquipos');
        cuerpo.textContent = '';
        r.por_equipo.forEach(function (e) {
            var tr = document.createElement('tr');
            tr.appendChild(celda(e.nombre));
            tr.appendChild(celda(ultimaPotencia[e.id] !== undefined ? fmt(ultimaPotencia[e.id], 0) + ' W' : '—', 'num'));
            tr.appendChild(celda(fmt(e.kwh, 3), 'num'));
            tr.appendChild(celda(cop(e.cop), 'num'));
            cuerpo.appendChild(tr);
        });

        var max = Math.max.apply(null, r.dias.map(function (d) { return d.kwh; }).concat([0.001]));
        var cont = $('barras');
        cont.textContent = '';
        r.dias.forEach(function (d, i) {
            var col = document.createElement('div'); col.className = 'barra-col';
            var v = document.createElement('div'); v.className = 'barra-val'; v.textContent = d.kwh > 0 ? fmt(d.kwh, 1) : '';
            var b = document.createElement('div'); b.className = 'barra' + (i === r.dias.length - 1 ? ' hoy' : '');
            b.style.height = Math.max(2, Math.round(d.kwh / max * 110)) + 'px';
            var l = document.createElement('div'); l.className = 'barra-etq'; l.textContent = d.etiqueta;
            col.appendChild(v); col.appendChild(b); col.appendChild(l);
            cont.appendChild(col);
        });

        var aviso = $('alertaLimite');
        if (r.limite_diario && r.hoy.kwh > r.limite_diario) {
            aviso.style.display = 'block';
            aviso.textContent = 'Superaste tu límite diario: llevas ' + fmt(r.hoy.kwh, 2) + ' kWh y tu límite es ' + fmt(r.limite_diario, 2) + ' kWh.';
        } else {
            aviso.style.display = 'none';
        }
    }

    function llamar(cuerpo) {
        var datos = new URLSearchParams(cuerpo);
        datos.append('csrf', CSRF);
        return fetch('api_simulador.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF },
            body: datos
        }).then(function (resp) { return resp.json().then(function (j) { j._http = resp.status; return j; }); });
    }

    function leer() {
        return llamar({ accion: 'simular', intervalo_min: $('min').value, hora: $('hora').value }).then(function (j) {
            if (j._http === 401) { location.href = 'index.php'; return; }
            if (!j.ok) {
                if (j.error !== 'demasiado_rapido') { $('estado').textContent = j.mensaje || ('No se pudo leer (' + j.error + ').'); }
                return;
            }
            j.lecturas.forEach(function (l) { ultimaPotencia[l.equipo_id] = l.potencia_w; });
            pintar(j.resumen);
            $('kRecs').textContent = j.recomendaciones_total;
            $('kTiempo').textContent = 'Calculadas en ' + fmt(j.tiempo_reglas_ms, 1) + ' ms';
            var hh = ('0' + j.hora_simulada).slice(-2);
            $('estado').textContent = 'Última lectura a las ' + new Date().toLocaleTimeString('es-CO') + ' (hora simulada ' + hh + ':00, franja ' + j.franja + ').';
        }).catch(function () { $('estado').textContent = 'Sin conexión con el servidor.'; });
    }

    function iniciar() {
        detener();
        leer();
        temporizador = setInterval(leer, Number($('seg').value) * 1000);
        $('btnIniciar').disabled = true; $('btnDetener').disabled = false;
        $('estado').textContent = 'Simulación en marcha…';
    }
    function detener() {
        if (temporizador) { clearInterval(temporizador); temporizador = null; }
        $('btnIniciar').disabled = false; $('btnDetener').disabled = true;
    }

    $('btnUna').addEventListener('click', leer);
    $('btnIniciar').addEventListener('click', iniciar);
    $('btnDetener').addEventListener('click', function () { detener(); $('estado').textContent = 'Simulación detenida.'; });
    $('seg').addEventListener('change', function () { if (temporizador) { iniciar(); } });
    $('btnReiniciar').addEventListener('click', function () {
        if (!confirm('¿Borrar todas las lecturas simuladas?')) { return; }
        detener();
        llamar({ accion: 'reiniciar' }).then(function (j) { if (j.ok) { ultimaPotencia = {}; pintar(j.resumen); $('estado').textContent = 'Lecturas borradas.'; } });
    });

    fetch('api_simulador.php?accion=resumen', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) { if (j.ok) { pintar(j.resumen); } });
})();
</script>
<?php endif; ?>
<?php pagina_fin();
