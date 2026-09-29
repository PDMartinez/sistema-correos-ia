<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

iniciarSesionSegura();

if (usuarioAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = null;
$tipoMensaje = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $mensaje = 'La solicitud no es válida. Actualiza la página e inténtalo nuevamente.';
    } else {
        try {
            $pdo = Conexion::getInstance();
            $usuarioModel = new Usuario($pdo);
            $auth = new AuthController($usuarioModel);

            $resultado = $auth->autenticar(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );

            if ($resultado['success']) {
                header('Location: dashboard.php');
                exit;
            }

            $mensaje = $resultado['message'];
        } catch (Throwable $e) {
            error_log('Error durante el inicio de sesión: ' . $e->getMessage());
            $mensaje = 'No fue posible iniciar sesión en este momento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - <?= htmlspecialchars(config('APP_NAME')) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
    <section class="tarjeta login-card">
        <h1>Iniciar sesión</h1>
        <p><?= htmlspecialchars(config('APP_NAME')) ?></p>

        <?php if ($mensaje): ?>
            <div class="estado error">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <?= csrfInput() ?>

            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                maxlength="150"
                autocomplete="username"
            >

            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">Ingresar</button>
        </form>

        <p class="nota">
            Acceso restringido a usuarios autorizados.
        </p>
    </section>
</main>
</body>
</html>
