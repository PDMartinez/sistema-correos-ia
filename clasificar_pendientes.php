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
$limite = filter_input(INPUT_POST, 'limite', FILTER_VALIDATE_INT) ?: 10;
$limite = max(1, min($limite, 20));

try {
    $service = new ClasificacionService($pdo);
    $resultado = $service->clasificarPendientes((int) $usuario['id'], $esAdministrador, $limite);

    $stmt = $pdo->prepare(
        'INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent)
         VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)'
    );
    $stmt->execute([
        'usuario_id' => (int) $usuario['id'],
        'accion' => 'CLASIFICAR_PENDIENTES_IA',
        'modulo' => 'clasificaciones',
        'descripcion' => 'Clasificación por lote. Procesados: ' . $resultado['procesados'] . ', clasificados: ' . $resultado['clasificados'] . ', errores: ' . count($resultado['errores']) . '.',
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);

    $_SESSION['flash_ok'] = 'Lote procesado: ' . $resultado['clasificados'] . ' clasificados de ' . $resultado['procesados'] . '.';
    if (count($resultado['errores']) > 0) {
        $_SESSION['flash_error'] = 'Se produjeron ' . count($resultado['errores']) . ' errores. Revise el registro del servidor.';
    }
} catch (Throwable $e) {
    error_log('Error clasificando pendientes: ' . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage();
}

header('Location: correos.php?estado=NO_CLASIFICADO');
exit;
