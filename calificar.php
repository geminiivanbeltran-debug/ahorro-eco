<?php
declare(strict_types=1);

/**
 * Guarda la calificación (1 a 5) de una recomendación (RF-06, HU-04).
 * Solo el dueño de la recomendación puede calificarla.
 */
require __DIR__ . '/inc/bootstrap.php';

$uid = requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recomendaciones.php');
    exit;
}
csrf_exigir();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
$nota = filter_var($_POST['calificacion'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

if ($id === false || $nota === false) {
    $_SESSION['flash'] = ['error', 'La calificación debe ser un número de 1 a 5.'];
    header('Location: recomendaciones.php');
    exit;
}

$ahora = date('Y-m-d H:i:s');
$stmt = $conn->prepare('UPDATE recomendaciones SET calificacion = ?, fecha_calificacion = ? WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('isii', $nota, $ahora, $id, $uid);
$stmt->execute();
$cambio = $stmt->affected_rows;
$stmt->close();

$_SESSION['flash'] = $cambio >= 0 ? ['exito', 'Gracias, guardamos tu calificación.'] : ['error', 'No se pudo guardar la calificación.'];
header('Location: recomendaciones.php#rec-' . $id);
exit;
