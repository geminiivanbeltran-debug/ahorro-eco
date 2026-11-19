<?php
declare(strict_types=1);

/**
 * Eliminación de la cuenta y de todos los datos del usuario (Ley 1581 de 2012, RNF-04).
 * Pide la contraseña para confirmar. Las tablas hijas (equipos, lecturas, recomendaciones y las
 * de la demo IoT) se borran en cascada por sus claves foráneas.
 */
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';

header('Content-Type: text/html; charset=utf-8');

$uid = requiere_sesion();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $clave = (string) ($_POST['clave'] ?? '');

    $stmt = $conn->prepare('SELECT clave FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fila || !password_verify($clave, $fila['clave'])) {
        usleep(400000);
        $error = 'La contraseña no es correcta.';
    } else {
        $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $borrado = $stmt->affected_rows > 0;
        $stmt->close();

        if ($borrado) {
            foreach (glob(__DIR__ . '/uploads/perfil_' . $uid . '.*') ?: [] as $foto) {
                @unlink($foto);
            }
            $_SESSION = [];
            session_destroy();
            echo "<script>alert('Tu cuenta y tus datos fueron eliminados.'); window.location.href='index.php';</script>";
            exit;
        }
        $error = 'No se pudo eliminar la cuenta. Inténtalo de nuevo.';
    }
}

pagina_inicio('Eliminar cuenta', 'perfil');
?>
<h1 class="section-title">Eliminar mi cuenta</h1>
<p class="lead">Se borrarán de forma definitiva tu perfil, tus equipos, las lecturas simuladas, tus recomendaciones y calificaciones, y tu foto. Esta acción no se puede deshacer.</p>

<?php if ($error): ?><div class="alerta error"><?= e($error) ?></div><?php endif; ?>

<div class="card" style="max-width:520px">
    <form method="post" action="eliminar_cuenta.php">
        <?= csrf_campo() ?>
        <div class="campo">
            <label for="clave">Escribe tu contraseña para confirmar</label>
            <input type="password" id="clave" name="clave" required autocomplete="current-password">
        </div>
        <div class="acciones" style="margin-top:16px">
            <button type="submit" class="btn btn-rojo" onclick="return confirm('¿Seguro que quieres eliminar tu cuenta y todos tus datos?');">Eliminar definitivamente</button>
            <a class="btn btn-borde" href="perfil.php">Cancelar</a>
        </div>
    </form>
</div>
<?php pagina_fin();
