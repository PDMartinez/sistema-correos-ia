<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'clasificar_correos');
$usuario = usuarioActual();
$esAdministrador = ($usuario['rol_nombre'] ?? '') === 'Administrador';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: correos.php');
    exit;
}

if (!validarCsrf((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(419);
    exit('Token CSRF inválido.');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    $_SESSION['flash_error'] = 'Correo no válido.';
    header('Location: correos.php');
    exit;
}

try {
    $service = new ClasificacionService($pdo);
    $resultado = $service->clasificarCorreo((int) $id, (int) $usuario['id'], $esAdministrador);

    $stmt = $pdo->prepare(
        'INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent)
         VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)'
    );
    $stmt->execute([
        'usuario_id' => (int) $usuario['id'],
        'accion' => 'CLASIFICAR_CORREO_IA',
        'modulo' => 'clasificaciones',
        'descripcion' => 'Clasificación IA del correo ID ' . (int) $id . '. Prioridad: ' . $resultado['prioridad'] . ', categoría: ' . $resultado['categoria'] . '.',
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);

    $_SESSION['flash_ok'] = 'Correo clasificado correctamente mediante IA.';
} catch (Throwable $e) {
    error_log('Error clasificando correo ' . (int) $id . ': ' . $e->getMessage());
    $_SESSION['flash_error'] = 'No fue posible clasificar el correo en este momento. Revisa el registro del servidor.';
}

header('Location: correo_ver.php?id=' . (int) $id);
exit;
