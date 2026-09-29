<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'ver_correos');
$usuario = usuarioActual();
$permiso = new Permiso($pdo);

// El dashboard principal es ahora el panel de estadísticas para los usuarios
// que cuentan con el permiso correspondiente.
if ($permiso->usuarioTienePermiso((int) $usuario['id'], 'ver_estadisticas')) {
    header('Location: estadisticas.php');
    exit;
}

// Los usuarios sin acceso a estadísticas conservan un panel operativo básico.
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
<title>Inicio - <?= e((string) config('APP_NAME')) ?></title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="dashboard-page">
<main class="contenedor">
<section class="tarjeta dashboard-shell">
<h1>Inicio</h1>
<p>Bienvenido, <strong><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></strong>.</p>
<p>Rol: <strong><?= e($usuario['rol_nombre']) ?></strong></p>
<div class="estado ok">Sesión y permisos activos.</div>
<nav class="dashboard-enlaces">
<?php if ($gestionaGmail): ?><a class="boton-secundario" href="cuentas_gmail.php">Importar / gestionar Gmail</a><?php endif; ?>
<a class="boton-secundario" href="correos.php">Correos y clasificación</a>
<?php if ($revisaClasificaciones): ?><a class="boton-secundario" href="revisiones.php">Revisar clasificaciones</a><?php endif; ?>
<?php if ($gestionaUsuarios): ?><a class="boton-secundario" href="usuarios.php">Gestionar usuarios</a><?php endif; ?>
</nav>
<form method="POST" action="logout.php">
<?= csrfInput() ?>
<button type="submit">Cerrar sesión</button>
</form>
</section>
</main>
</body>
</html>
