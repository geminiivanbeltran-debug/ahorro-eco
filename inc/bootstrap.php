<?php
declare(strict_types=1);

/**
 * Arranque común de las páginas nuevas del prototipo:
 * sesión segura, conexión, configuración y utilidades (CSRF, JSON, escape).
 */

// Nunca mostrar errores de PHP al usuario (corrección 3 del Anexo E); se registran en el log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('America/Bogota');

if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../conexion.php';   // define $conn (mysqli)
require_once __DIR__ . '/MotorReglas.php';

/** @var array $CFG */
$CFG = require __DIR__ . '/config.php';

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function cop($valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}

function num($valor, int $dec = 2): string
{
    return number_format((float) $valor, $dec, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Detiene la solicitud si el token CSRF no coincide. */
function csrf_exigir(bool $json = false): void
{
    $enviado = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($enviado) || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        if ($json) {
            json_salida(['ok' => false, 'error' => 'token_invalido'], 400);
        }
        http_response_code(400);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

function json_salida(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Exige sesión iniciada; devuelve el id del usuario. */
function requiere_sesion(bool $json = false): int
{
    if (empty($_SESSION['usuario_id'])) {
        if ($json) {
            json_salida(['ok' => false, 'error' => 'sesion_expirada'], 401);
        }
        header('Location: index.php');
        exit;
    }
    return (int) $_SESSION['usuario_id'];
}

/** Datos del usuario necesarios para tarifas y reglas. */
function cargar_usuario(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare(
        'SELECT id, nombre, apellidos, correo, tipo_usuario, estrato, sector, limite_diario_kwh
           FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$u) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    $u['estrato'] = $u['estrato'] !== null ? (int) $u['estrato'] : null;
    $u['limite_diario_kwh'] = $u['limite_diario_kwh'] !== null ? (float) $u['limite_diario_kwh'] : null;
    return $u;
}

function cargar_equipos(mysqli $conn, int $usuarioId): array
{
    $stmt = $conn->prepare(
        'SELECT id, nombre, categoria, potencia_w, cantidad, horas_dia, franja, deja_reposo
           FROM equipos WHERE usuario_id = ? ORDER BY id'
    );
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $f['id'] = (int) $f['id'];
        $f['potencia_w'] = (float) $f['potencia_w'];
        $f['cantidad'] = (int) $f['cantidad'];
        $f['horas_dia'] = (float) $f['horas_dia'];
        $f['deja_reposo'] = (int) $f['deja_reposo'];
        $out[] = $f;
    }
    $stmt->close();
    return $out;
}
