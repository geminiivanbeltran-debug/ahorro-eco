<?php
declare(strict_types=1);

/**
 * Inicio de sesión (RF-01).
 * Consulta preparada, verificación con password_verify, sesión regenerada al autenticar
 * y un único mensaje de error para no revelar qué correos están registrados.
 */
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $clave_ingresada = (string) ($_POST['clave'] ?? '');

    $usuario = null;
    if ($correo !== '' && mb_strlen($correo) <= 100) {
        $stmt = $conn->prepare('SELECT id, nombre, clave FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if ($usuario && password_verify($clave_ingresada, $usuario['clave'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];

        // Si PHP trae un algoritmo de hash más fuerte, se actualiza el hash guardado.
        if (password_needs_rehash($usuario['clave'], PASSWORD_DEFAULT)) {
            $nuevo = password_hash($clave_ingresada, PASSWORD_DEFAULT);
            $up = $conn->prepare('UPDATE usuarios SET clave = ? WHERE id = ?');
            $up->bind_param('si', $nuevo, $usuario['id']);
            $up->execute();
            $up->close();
        }
        header("Location: home.php");
        exit();
    }

    usleep(400000); // frena un poco los intentos de adivinar contraseñas
    echo "<script>alert('Correo o contraseña incorrectos'); window.location.href='index.php';</script>";
    exit();
}

header("Location: index.php");
exit();
