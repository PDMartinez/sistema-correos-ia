<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
exigirPermiso(Conexion::getInstance(), 'gestionar_usuarios');

$model = new Usuario(Conexion::getInstance());
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$usuario = null;
if ($id && $id > 0) {
    $usuario = $model->obtenerPorId($id);
    if (!$usuario) {
        http_response_code(404);
        exit('Usuario no encontrado.');
    }
}
$roles = $model->rolesActivos();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function campo(array $old, ?array $u, string $key): string {
    return e((string) ($old[$key] ?? $u[$key] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $usuario ? 'Editar' : 'Nuevo' ?> usuario - Sistema Correos IA</title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
<section class="tarjeta">
<h1><?= $usuario ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
<?php if ($flash): ?>
<div class="estado <?= $flash['ok'] ? 'ok' : 'error' ?>"><?= e($flash['mensaje']) ?></div>
<?php endif; ?>
<form method="POST" action="usuario_guardar.php" autocomplete="off">
<?= csrfInput() ?>
<input type="hidden" name="id" value="<?= (int) ($usuario['id'] ?? 0) ?>">
<label for="nombre">Nombre</label>
<input id="nombre" name="nombre" required maxlength="100" value="<?= campo($old, $usuario, 'nombre') ?>">
<label for="apellido">Apellido</label>
<input id="apellido" name="apellido" required maxlength="100" value="<?= campo($old, $usuario, 'apellido') ?>">
<label for="email">Correo electrónico</label>
<input id="email" type="email" name="email" required maxlength="150" value="<?= campo($old, $usuario, 'email') ?>">
<label for="rol_id">Rol</label>
<select id="rol_id" name="rol_id" required>
<option value="">Seleccionar</option>
<?php foreach ($roles as $rol): ?>
<option value="<?= (int) $rol['id'] ?>"
<?= (int) ($old['rol_id'] ?? $usuario['rol_id'] ?? 0) === (int) $rol['id'] ? 'selected' : '' ?>>
<?= e($rol['nombre']) ?>
</option>
<?php endforeach; ?>
</select>
<label for="password">Contraseña <?= $usuario ? '(dejar en blanco para mantener)' : '' ?></label>
<input id="password" type="password" name="password"
<?= $usuario ? '' : 'required' ?> minlength="12" maxlength="72" autocomplete="new-password">
<label for="password_confirm">Confirmar contraseña</label>
<input id="password_confirm" type="password" name="password_confirm"
<?= $usuario ? '' : 'required' ?> minlength="12" maxlength="72" autocomplete="new-password">
<button type="submit">Guardar usuario</button>
<a class="boton-secundario" href="usuarios.php">Cancelar</a>
</form>
</section>
</main>
</body>
</html>
