<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/models/Permiso.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/PermissionMiddleware.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'ver_correos');

$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars(config('APP_NAME')) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
    <section class="tarjeta">
        <h1>Dashboard</h1>

        <p>
            Bienvenido,
            <strong><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></strong>.
        </p>

        <p>
            Rol:
            <strong><?= htmlspecialchars($usuario['rol_nombre']) ?></strong>
        </p>

        <div class="estado ok">
            Autenticación y autorización funcionando correctamente.
        </div>

        <p>La gestión de correos se implementará en las siguientes etapas.</p>

        <a class="boton-secundario" href="logout.php">Cerrar sesión</a>
    </section>
</main>
</body>
</html>
