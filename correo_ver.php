<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/models/Correo.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'ver_correos');
$usuario = usuarioActual();
$esAdministrador = ($usuario['rol_nombre'] ?? '') === 'Administrador';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit('Correo no encontrado.'); }

$flashOk = $_SESSION['flash_ok'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
$permiso = new Permiso($pdo);
$puedeClasificar = $permiso->usuarioTienePermiso((int) $usuario['id'], 'clasificar_correos');

$model = new Correo($pdo);
$correo = $model->obtenerPorId((int)$id, (int)$usuario['id'], $esAdministrador);
if (!$correo) { http_response_code(404); exit('Correo no encontrado o no autorizado.'); }

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function prioridadEtiqueta(?string $p): string { return match($p){'ALTA'=>'Alta','MEDIA'=>'Media','BAJA'=>'Baja',default=>'Sin clasificar'}; }
function estadoEtiqueta(string $s): string { return match($s){'NO_CLASIFICADO'=>'No clasificado','CLASIFICADO'=>'Clasificado','ATENDIDO'=>'Atendido','ARCHIVADO'=>'Archivado',default=>$s}; }
function contenidoCompacto(?string $contenido): string {
    $contenido = trim((string) $contenido);
    $contenido = preg_replace('/[ \t]+/u', ' ', $contenido) ?? $contenido;
    $contenido = preg_replace('/(?:\r?\n[ \t]*){3,}/u', "\n\n", $contenido) ?? $contenido;
    return trim($contenido);
}

$stmt = $pdo->prepare('INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent) VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)');
$stmt->execute([
 'usuario_id'=>(int)$usuario['id'], 'accion'=>'VER_CORREO', 'modulo'=>'correos',
 'descripcion'=>'Visualización del correo ID: '.(int)$correo['id'].'.',
 'ip'=>substr((string)($_SERVER['REMOTE_ADDR']??''),0,45),
 'user_agent'=>substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500)
]);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Correo - <?= e((string)config('APP_NAME')) ?></title><link rel="stylesheet" href="assets/css/estilos.css"></head>
<body><main class="contenedor panel"><section class="tarjeta tarjeta-ancha">
<div class="cabecera"><div><h1><?= e((string)$correo['asunto']) ?></h1><p class="nota">Detalle del correo importado.</p></div><a class="boton-secundario" href="correos.php">← Volver a correos</a></div>
<div class="detalle-correo">
<div class="detalle-fila"><strong>Cuenta:</strong><span><?= e((string)$correo['cuenta_email']) ?></span></div>
<div class="detalle-fila"><strong>Remitente:</strong><span><?= e((string)$correo['remitente']) ?></span></div>
<div class="detalle-fila"><strong>Destinatario:</strong><span><?= e((string)$correo['destinatario']) ?></span></div>
<div class="detalle-fila"><strong>Fecha:</strong><span><?= e((string)$correo['fecha_recepcion']) ?></span></div>
<div class="detalle-fila"><strong>Estado:</strong><span><?= e(estadoEtiqueta((string)$correo['estado'])) ?></span></div>
<div class="detalle-fila"><strong>Prioridad:</strong><span><?= e(prioridadEtiqueta($correo['prioridad'] ?? null)) ?></span></div>
<?php if (!empty($correo['categoria'])): ?><div class="detalle-fila"><strong>Categoría:</strong><span><?= e((string)$correo['categoria']) ?></span></div><?php endif; ?>
<?php if ($correo['confianza'] !== null): ?><div class="detalle-fila"><strong>Confianza IA:</strong><span><?= e((string)$correo['confianza']) ?></span></div><?php endif; ?>
</div>
<?php if ($flashOk): ?><div class="estado ok"><?= e((string)$flashOk) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="estado error"><?= e((string)$flashError) ?></div><?php endif; ?>
<?php if ($puedeClasificar && empty($correo['clasificacion_id'])): ?>
<form method="POST" action="clasificar_correo.php" class="form-inline">
<?= csrfInput() ?>
<input type="hidden" name="id" value="<?= (int)$correo['id'] ?>">
<button type="submit">Clasificar con IA</button>
</form>
<?php endif; ?>
<hr>
<div class="seccion-contenido">
<div class="seccion-titulo"><h2>Contenido del correo</h2><span class="nota">Vista compacta</span></div>
<div class="contenido-correo"><?= nl2br(e(contenidoCompacto((string)$correo['contenido']))) ?></div>
</div>
<?php if (!empty($correo['justificacion'])): ?>
<div class="seccion-contenido">
<div class="seccion-titulo"><h2>Justificación de la clasificación</h2><span class="nota">Generada por IA</span></div>
<div class="justificacion-correo"><?= nl2br(e(contenidoCompacto((string)$correo['justificacion']))) ?></div>
</div>
<?php endif; ?>
</section></main></body></html>
