<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

try {
    $pdo = Conexion::getInstance();

    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM roles');
    $resultado = $stmt->fetch();

    $estadoBD = 'Conexión correcta';
    $totalRoles = (int) ($resultado['total'] ?? 0);
} catch (Throwable $e) {
    $estadoBD = 'Error de conexión';
    $totalRoles = 0;
    $errorBD = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(config('APP_NAME')) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <main class="contenedor">
        <section class="tarjeta">
            <h1><?= htmlspecialchars(config('APP_NAME')) ?></h1>
            <p>Etapa 3 — Configuración PHP + PDO</p>

            <div class="estado <?= $estadoBD === 'Conexión correcta' ? 'ok' : 'error' ?>">
                <strong>Base de datos:</strong>
                <?= htmlspecialchars($estadoBD) ?>
            </div>

            <?php if ($estadoBD === 'Conexión correcta'): ?>
                <p>Roles registrados en MySQL: <strong><?= $totalRoles ?></strong></p>
                <p class="exito">La aplicación PHP puede comunicarse correctamente con MySQL.</p>
            <?php else: ?>
                <p class="error-text">
                    No fue posible conectar con MySQL.
                    Revisa el archivo <code>.env</code> y que MySQL esté iniciado en XAMPP.
                </p>
                <?php if (config('APP_DEBUG') === 'true'): ?>
                    <details>
                        <summary>Detalle técnico</summary>
                        <pre><?= htmlspecialchars($errorBD ?? '') ?></pre>
                    </details>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
