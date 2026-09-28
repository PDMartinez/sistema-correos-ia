<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
exigirPermiso(Conexion::getInstance(), 'gestionar_usuarios');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
if (!validarCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Token CSRF inválido.');
}
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$estado = filter_var($_POST['estado'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1 || !in_array($estado, [0, 1], true)) {
    $_SESSION['flash'] = ['ok' => false, 'mensaje' => 'Solicitud inválida.'];
} else {
    $pdo = Conexion::getInstance();
    $controller = new UsuarioController($pdo, new Usuario($pdo));
    $_SESSION['flash'] = $controller->cambiarEstado($id, $estado, (int) usuarioActual()['id']);
}
header('Location: usuarios.php', true, 303);
exit;
