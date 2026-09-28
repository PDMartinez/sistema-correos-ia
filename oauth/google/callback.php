<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/GoogleOAuthService.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'gestionar_cuentas_gmail');
$usuario = usuarioActual();

if (isset($_GET['error'])) {
    header('Location: ../../cuentas_gmail.php?error=' . rawurlencode('Google canceló o rechazó la autorización.'));
    exit;
}

$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));

if ($code === '' || $state === '') {
    header('Location: ../../cuentas_gmail.php?error=' . rawurlencode('La respuesta de Google no contiene los datos esperados.'));
    exit;
}

try {
    $service = new GoogleOAuthService($pdo);
    $resultado = $service->procesarCallback($code, $state, (int) $usuario['id']);

    header('Location: ../../cuentas_gmail.php?ok=' . rawurlencode('Cuenta Gmail conectada correctamente: ' . $resultado['email']));
    exit;
} catch (Throwable $e) {
    http_response_code(500);

    echo '<h1>Error en OAuth</h1>';
    echo '<pre>';
    echo htmlspecialchars(
        $e->getMessage() . "\n\n" . $e->getTraceAsString(),
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</pre>';

    exit;
}
