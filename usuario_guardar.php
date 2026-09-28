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
$pdo = Conexion::getInstance();
$controller = new UsuarioController($pdo, new Usuario($pdo));
$resultado = $controller->guardar($_POST, (int) usuarioActual()['id']);
$_SESSION['flash'] = $resultado;
if (!$resultado['ok']) {
    $_SESSION['form_old'] = [
        'nombre' => (string) ($_POST['nombre'] ?? ''),
        'apellido' => (string) ($_POST['apellido'] ?? ''),
        'email' => (string) ($_POST['email'] ?? ''),
        'rol_id' => (string) ($_POST['rol_id'] ?? ''),
    ];
}
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$destino = $resultado['ok'] ? 'usuarios.php'
    : 'usuario_form.php' . ($id && $id > 0 ? '?id=' . $id : '');
header('Location: ' . $destino, true, 303);
exit;
