<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

iniciarSesionSegura();

if (usuarioAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
