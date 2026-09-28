<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'ver_correos');
$usuario = usuarioActual();
$permiso = new Permiso($pdo);
$gestionaUsuarios = $permiso->usuarioTienePermiso((int) $usuario['id'], 'gestionar_usuarios');
$gestionaGmail = $permiso->usuarioTienePermiso((int) $usuario['id'], 'gestionar_cuentas_gmail');
$revisaClasificaciones = $permiso->usuarioTienePermiso((int) $usuario['id'], 'revisar_clasificaciones');
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - <?= e((string) config('APP_NAME')) ?></title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
<section class="tarjeta">
<h1>Dashboard</h1>
<p>Bienvenido, <strong><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></strong>.</p>
<p>Rol: <strong><?= e($usuario['rol_nombre']) ?></strong></p>
<div class="estado ok">Autenticación y permisos activos.</div>
<?php if ($gestionaUsuarios): ?>
<p><a class="boton-secundario" href="usuarios.php">Gestionar usuarios</a></p>
<?php endif; ?>
<?php if ($gestionaGmail): ?>
<p><a class="boton-secundario" href="cuentas_gmail.php">Gestionar cuentas Gmail</a></p>
<?php endif; ?>
<p><a class="boton-secundario" href="correos.php">Ver correos</a></p>
<?php if ($revisaClasificaciones): ?>
<p><a class="boton-secundario" href="revisiones.php">Revisar clasificaciones</a></p>
<?php endif; ?>
<form method="POST" action="logout.php">
<?= csrfInput() ?>
<button type="submit">Cerrar sesión</button>
</form>
</section>
</main>
</body>
</html>
