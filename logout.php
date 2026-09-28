<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/config/conexion.php';

iniciarSesionSegura();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
if (!validarCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Token CSRF inválido.');
}
$auth = new AuthController(new Usuario(Conexion::getInstance()));
$auth->cerrarSesion();
header('Location: login.php', true, 303);
exit;
