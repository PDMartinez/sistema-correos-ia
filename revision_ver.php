<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'revisar_clasificaciones');
$usuario = usuarioActual();
$esAdministrador = ($usuario['rol_nombre'] ?? '') === 'Administrador';
$permiso = new Permiso($pdo);
$puedeModificar = $permiso->usuarioTienePermiso((int)$usuario['id'], 'modificar_clasificacion');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit('Correo no encontrado.'); }

$model = new RevisionClasificacion($pdo);
$item = $model->obtenerParaRevision((int)$id, (int)$usuario['id'], $esAdministrador);
if (!$item) { http_response_code(404); exit('Correo no encontrado o no autorizado.'); }
$historial = $model->historial((int)$item['clasificacion_id']);

$flashOk = $_SESSION['flash_ok'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function prioridadEtiqueta(?string $p): string { return match ($p) { 'ALTA' => 'Alta', 'MEDIA' => 'Media', 'BAJA' => 'Baja', default => '—' }; }
function estadoEtiqueta(string $s): string { return match ($s) { 'NO_CLASIFICADO'=>'No clasificado','CLASIFICADO'=>'Clasificado','ATENDIDO'=>'Atendido','ARCHIVADO'=>'Archivado',default=>$s }; }
function contenidoCompacto(?string $contenido): string {
    $contenido = trim((string)$contenido);
    $contenido = preg_replace('/[ \t]+/u', ' ', $contenido) ?? $contenido;
    $contenido = preg_replace('/(?:\r?\n[ \t]*){3,}/u', "\n\n", $contenido) ?? $contenido;
    return trim($contenido);
}

$stmt = $pdo->prepare('INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent) VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)');
$stmt->execute([
    'usuario_id'=>(int)$usuario['id'], 'accion'=>'VER_REVISION_CLASIFICACION', 'modulo'=>'revisiones_clasificacion',
    'descripcion'=>'Consulta de revisión del correo ID: '.(int)$item['id'].'.',
    'ip'=>substr((string)($_SERVER['REMOTE_ADDR']??''),0,45),
    'user_agent'=>substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500)
]);
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Revisión - <?= e((string)config('APP_NAME')) ?></title><link rel="stylesheet" href="assets/css/estilos.css"></head>
<body class="module-page"><?php require __DIR__ . '/views/layout/app_nav.php'; ?>
<main class="contenedor panel"><section class="tarjeta tarjeta-ancha">
<div class="cabecera"><div><h1><?= e((string)$item['asunto']) ?></h1><p class="nota">Revisión humana de la clasificación generada por IA.</p></div><a class="boton-secundario" href="revisiones.php">← Volver a revisiones</a></div>
<?php if ($flashOk): ?><div class="estado ok"><?= e((string)$flashOk) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="estado error"><?= e((string)$flashError) ?></div><?php endif; ?>
<div class="detalle-correo">
<div class="detalle-fila"><strong>Cuenta:</strong><span><?= e((string)$item['cuenta_email']) ?></span></div>
<div class="detalle-fila"><strong>Remitente:</strong><span><?= e((string)$item['remitente']) ?></span></div>
<div class="detalle-fila"><strong>Destinatario:</strong><span><?= e((string)$item['destinatario']) ?></span></div>
<div class="detalle-fila"><strong>Fecha:</strong><span><?= e((string)$item['fecha_recepcion']) ?></span></div>
<div class="detalle-fila"><strong>Estado:</strong><span><?= e(estadoEtiqueta((string)$item['estado'])) ?></span></div>
<div class="detalle-fila"><strong>Prioridad IA:</strong><span class="badge-prioridad prioridad-<?= strtolower((string)$item['prioridad']) ?>"><?= e(prioridadEtiqueta($item['prioridad'])) ?></span></div>
<div class="detalle-fila"><strong>Categoría:</strong><span><?= e((string)($item['categoria'] ?? '—')) ?></span></div>
<div class="detalle-fila"><strong>Confianza:</strong><span><?= $item['confianza'] !== null ? e(number_format((float)$item['confianza']*100, 1, ',', '.')).' %' : '—' ?></span></div>
<div class="detalle-fila"><strong>Modelo IA:</strong><span><?= e((string)($item['modelo_ia'] ?? '—')) ?></span></div>
</div>
<div class="seccion-contenido"><div class="seccion-titulo"><h2>Contenido del correo</h2><span class="nota">Vista compacta</span></div><div class="contenido-correo"><?= nl2br(e(contenidoCompacto((string)$item['contenido']))) ?></div></div>
<div class="seccion-contenido"><div class="seccion-titulo"><h2>Justificación de la IA</h2></div><div class="justificacion-correo"><?= nl2br(e(contenidoCompacto((string)($item['justificacion'] ?? 'Sin justificación.')))) ?></div></div>

<div class="bloque-revision">
<h2>Decisión de revisión</h2>
<?php if (!empty($item['revision_id'])): ?>
<div class="estado ok">Esta clasificación ya fue revisada. La prioridad final registrada es <strong><?= e(prioridadEtiqueta($item['prioridad_final'])) ?></strong>.</div>
<div class="detalle-revision"><div><strong>Revisor:</strong> <?= e(trim((string)$item['revisor_nombre'].' '.(string)$item['revisor_apellido'])) ?></div><div><strong>Fecha:</strong> <?= e((string)$item['fecha_revision']) ?></div><div><strong>Prioridad anterior:</strong> <?= e(prioridadEtiqueta($item['prioridad_anterior'])) ?></div><div><strong>Prioridad final:</strong> <?= e(prioridadEtiqueta($item['prioridad_final'])) ?></div><?php if (!empty($item['revision_comentario'])): ?><div><strong>Comentario:</strong><br><?= nl2br(e((string)$item['revision_comentario'])) ?></div><?php endif; ?></div>
<?php if ($puedeModificar): ?><p class="nota">La revisión ya fue realizada. Si necesita corregir la decisión, puede modificar esta misma revisión.</p><form method="POST" action="revision_guardar.php" class="form-revision"><?= csrfInput() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><label>Modificar prioridad final</label><div class="opciones-prioridad"><?php foreach (['ALTA'=>'Alta','MEDIA'=>'Media','BAJA'=>'Baja'] as $v=>$t): ?><label><input type="radio" name="prioridad_final" value="<?= $v ?>" <?= $item['prioridad_final']===$v?'checked':'' ?>> <?= $t ?></label><?php endforeach; ?></div><label for="comentario">Comentario (opcional)</label><textarea id="comentario" name="comentario" rows="4" maxlength="1000" placeholder="Indique el motivo de la modificación..." ><?= e((string)($item['revision_comentario'] ?? '')) ?></textarea><button type="submit">Modificar revisión</button></form><?php endif; ?>
<?php else: ?>
<?php if ($puedeModificar): ?><form method="POST" action="revision_guardar.php" class="form-revision"><?= csrfInput() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><p>Seleccione la prioridad que quedará como decisión final. Puede coincidir con la propuesta de la IA.</p><label>Prioridad final</label><div class="opciones-prioridad"><?php foreach (['ALTA'=>'Alta','MEDIA'=>'Media','BAJA'=>'Baja'] as $v=>$t): ?><label><input type="radio" name="prioridad_final" value="<?= $v ?>" <?= $item['prioridad']===$v?'checked':'' ?>> <?= $t ?></label><?php endforeach; ?></div><label for="comentario">Comentario (opcional)</label><textarea id="comentario" name="comentario" rows="4" maxlength="1000" placeholder="Explique brevemente la decisión si es necesario."></textarea><button type="submit">Confirmar revisión</button></form><?php else: ?><div class="estado error">Su usuario puede revisar clasificaciones, pero no tiene permiso para modificar la prioridad final.</div><?php endif; ?>
<?php endif; ?>
</div>

<?php if ($historial): ?><div class="seccion-contenido"><div class="seccion-titulo"><h2>Historial de revisiones</h2></div><div class="tabla-contenedor"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Anterior</th><th>Final</th><th>Comentario</th></tr></thead><tbody><?php foreach ($historial as $revision): ?><tr><td><?= e((string)$revision['fecha_revision']) ?></td><td><?= e(trim((string)$revision['nombre'].' '.(string)$revision['apellido'])) ?></td><td><?= e(prioridadEtiqueta($revision['prioridad_anterior'])) ?></td><td><?= e(prioridadEtiqueta($revision['prioridad_final'])) ?></td><td><?= nl2br(e((string)($revision['comentario'] ?? ''))) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
</section></main></body></html>
