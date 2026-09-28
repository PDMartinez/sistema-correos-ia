<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
exigirPermiso(Conexion::getInstance(), 'gestionar_usuarios');

$model = new Usuario(Conexion::getInstance());
$buscar = trim((string) ($_GET['buscar'] ?? ''));
$buscar = mb_substr($buscar, 0, 100);
$usuarios = $model->listar($buscar);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$actual = usuarioActual();
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuarios - Sistema Correos IA</title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor panel">
<section class="tarjeta tarjeta-ancha">
<div class="cabecera">
<h1>Gestión de usuarios</h1>
<div><a class="boton-secundario" href="dashboard.php">Dashboard</a>
<a class="boton-secundario" href="usuario_form.php">+ Nuevo usuario</a></div>
</div>
<?php if ($flash): ?>
<div class="estado <?= $flash['ok'] ? 'ok' : 'error' ?>"><?= e($flash['mensaje']) ?></div>
<?php endif; ?>
<form class="buscar" method="GET">
<label for="buscar">Buscar usuario</label>
<input id="buscar" name="buscar" value="<?= e($buscar) ?>" maxlength="100"
placeholder="Nombre, apellido o correo">
<button type="submit">Buscar</button>
</form>
<div class="tabla-contenedor">
<table>
<thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($usuarios as $u): ?>
<tr>
<td><?= e($u['nombre'] . ' ' . $u['apellido']) ?></td>
<td><?= e($u['email']) ?></td>
<td><?= e($u['rol_nombre']) ?></td>
<td><?= (int) $u['estado'] === 1 ? 'Activo' : 'Inactivo' ?></td>
<td class="acciones">
<a href="usuario_form.php?id=<?= (int) $u['id'] ?>">Editar</a>
<?php if ((int) $u['id'] !== (int) $actual['id']): ?>
<form method="POST" action="usuario_estado.php"
onsubmit="return confirm('¿Confirmas el cambio de estado de este usuario?');">
<?= csrfInput() ?>
<input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
<input type="hidden" name="estado" value="<?= (int) $u['estado'] === 1 ? 0 : 1 ?>">
<button type="submit" class="boton-pequeno"><?= (int) $u['estado'] === 1 ? 'Desactivar' : 'Activar' ?></button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$usuarios): ?>
<tr><td colspan="5">No se encontraron usuarios.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
</main>
</body>
</html>
