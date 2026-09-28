<?php
declare(strict_types=1);

/**
 * Asistente LOCAL para crear el primer usuario administrador.
 *
 * IMPORTANTE:
 * - Ejecutar solamente durante la instalación inicial.
 * - Después de crear el administrador, ELIMINAR este archivo.
 * - No subir este archivo a producción.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/csrf.php';

iniciarSesionSegura();

$mensaje = null;
$tipo = 'error';

try {
    $pdo = Conexion::getInstance();

    $cantidad = (int) $pdo->query(
        "SELECT COUNT(*) FROM usuarios WHERE rol_id = (
            SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1
        )"
    )->fetchColumn();

    if ($cantidad > 0) {
        $mensaje = 'Ya existe al menos un usuario administrador. Elimina setup_admin.php y utiliza login.php.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarCsrf($_POST['csrf_token'] ?? null)) {
            $mensaje = 'Token CSRF inválido.';
        } else {
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $apellido = trim((string) ($_POST['apellido'] ?? ''));
            $email = trim(mb_strtolower((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $confirmacion = (string) ($_POST['password_confirm'] ?? '');

            if ($nombre === '' || $apellido === '') {
                $mensaje = 'Nombre y apellido son obligatorios.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mensaje = 'El correo electrónico no es válido.';
            } elseif (strlen($password) < 8) {
                $mensaje = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== $confirmacion) {
                $mensaje = 'Las contraseñas no coinciden.';
            } else {
                $rolStmt = $pdo->prepare(
                    "SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1"
                );
                $rolStmt->execute();
                $rolId = $rolStmt->fetchColumn();

                if (!$rolId) {
                    throw new RuntimeException('No existe el rol Administrador en la base de datos.');
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO usuarios
                        (rol_id, nombre, apellido, email, password, estado)
                     VALUES
                        (:rol_id, :nombre, :apellido, :email, :password, 1)'
                );

                $stmt->execute([
                    'rol_id' => $rolId,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $mensaje = 'Administrador creado correctamente. ELIMINA setup_admin.php antes de continuar.';
                $tipo = 'ok';
            }
        }
    }
} catch (Throwable $e) {
    $mensaje = config('APP_DEBUG') === 'true'
        ? 'Error técnico: ' . $e->getMessage()
        : 'No fue posible completar la operación.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración inicial</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
    <section class="tarjeta">
        <h1>Crear administrador inicial</h1>

        <?php if ($mensaje): ?>
            <div class="estado <?= $tipo === 'ok' ? 'ok' : 'error' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (!$mensaje || $tipo !== 'ok'): ?>
        <form method="POST" action="setup_admin.php" autocomplete="off">
            <?= csrfInput() ?>

            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" required>

            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" maxlength="100" required>

            <label for="email">Correo</label>
            <input type="email" id="email" name="email" maxlength="150" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" minlength="8" required>

            <label for="password_confirm">Confirmar contraseña</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>

            <button type="submit">Crear administrador</button>
        </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
