<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/GoogleOAuthService.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'gestionar_cuentas_gmail');

try {
    $service = new GoogleOAuthService($pdo);
    header('Location: ' . $service->crearUrlAutorizacion());
    exit;
} catch (Throwable $e) {
    error_log('Error al iniciar Google OAuth: ' . $e->getMessage());
    http_response_code(500);
    echo 'No se pudo iniciar la conexión con Google.';
}
