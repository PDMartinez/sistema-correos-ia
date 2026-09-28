<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/services/GmailApiService.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'gestionar_cuentas_gmail');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (!validarCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Token CSRF inválido.');
}

$cuentaId = filter_input(INPUT_POST, 'cuenta_id', FILTER_VALIDATE_INT);
if (!$cuentaId || $cuentaId < 1) {
    header('Location: cuentas_gmail.php?error=' . rawurlencode('La cuenta Gmail seleccionada no es válida.'));
    exit;
}

$usuario = usuarioActual();

try {
    $service = new GmailApiService($pdo);
    $resultado = $service->sincronizarCuenta((int) $cuentaId, (int) $usuario['id']);

    $mensaje = sprintf(
        'Sincronización completada: %d importados, %d ya existentes y %d con error.',
        $resultado['importados'],
        $resultado['omitidos'],
        $resultado['errores']
    );

    header('Location: cuentas_gmail.php?ok=' . rawurlencode($mensaje));
    exit;
} catch (Throwable $e) {
    error_log('Error sincronizando Gmail: ' . $e->getMessage());
    header('Location: cuentas_gmail.php?error=' . rawurlencode('No se pudo sincronizar la cuenta Gmail. Revisa el registro de PHP para más detalles.'));
    exit;
}
