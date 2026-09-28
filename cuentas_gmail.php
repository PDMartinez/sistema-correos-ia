<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/models/CuentaGmail.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'gestionar_cuentas_gmail');

$model = new CuentaGmail($pdo);
$cuentas = $model->listar();

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$ok = trim((string) ($_GET['ok'] ?? ''));
$error = trim((string) ($_GET['error'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestionar cuentas Gmail - <?= e((string) config('APP_NAME')) ?></title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
<section class="tarjeta">
<h1>Gestionar cuentas Gmail</h1>
<p>Conecta las cuentas Gmail que serán utilizadas para importar y clasificar correos.</p>

<?php if ($ok !== ''): ?>
<div class="estado ok"><?= e($ok) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div class="estado error"><?= e($error) ?></div>
<?php endif; ?>

<p>
    <a class="boton-secundario" href="oauth/google/connect.php">+ Conectar cuenta Gmail</a>
    <a class="boton-secundario" href="dashboard.php">Volver al dashboard</a>
</p>

<?php if (!$cuentas): ?>
<p>No hay cuentas Gmail conectadas.</p>
<?php else: ?>
<table>
<thead>
<tr>
<th>Cuenta</th>
<th>Estado</th>
<th>Última sincronización</th>
<th>Registrada</th>
</tr>
</thead>
<tbody>
<?php foreach ($cuentas as $cuenta): ?>
<tr>
<td><?= e((string) $cuenta['email']) ?></td>
<td><?= e((string) $cuenta['estado']) ?></td>
<td><?= e((string) ($cuenta['ultima_sincronizacion'] ?? 'Nunca')) ?></td>
<td><?= e((string) $cuenta['fecha_conexion']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</section>
</main>
</body>
</html>
