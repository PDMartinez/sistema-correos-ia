<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'revisar_clasificaciones');
$usuario = usuarioActual();
$esAdministrador = ($usuario['rol_nombre'] ?? '') === 'Administrador';
$model = new RevisionClasificacion($pdo);
$correoModel = new Correo($pdo);

$flashOk = $_SESSION['flash_ok'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

$buscar = trim((string) ($_GET['buscar'] ?? ''));
$cuentaId = (string) ($_GET['cuenta_id'] ?? '');
$prioridad = (string) ($_GET['prioridad'] ?? '');
$categoria = (string) ($_GET['categoria'] ?? '');
$confianzaMin = (string) ($_GET['confianza_min'] ?? '');
$revision = (string) ($_GET['revision'] ?? 'PENDIENTE');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$resultado = $model->listar((int) $usuario['id'], $esAdministrador, [
    'buscar' => $buscar,
    'cuenta_id' => $cuentaId,
    'prioridad' => $prioridad,
    'categoria' => $categoria,
    'confianza_min' => $confianzaMin,
    'revision' => $revision,
], $pagina, 20);
$cuentas = $correoModel->listarCuentasDisponibles((int) $usuario['id'], $esAdministrador);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function prioridadEtiqueta(?string $p): string { return match ($p) { 'ALTA' => 'Alta', 'MEDIA' => 'Media', 'BAJA' => 'Baja', default => '—' }; }
function urlPagina(int $pagina): string { $params = $_GET; $params['pagina'] = $pagina; return 'revisiones.php?' . http_build_query($params); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Revisiones - <?= e((string) config('APP_NAME')) ?></title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="module-page">
<?php require __DIR__ . '/views/layout/app_nav.php'; ?>
<main class="contenedor panel">
<section class="tarjeta tarjeta-ancha">
<div class="cabecera">
<div><h1>Revisión de clasificaciones</h1><p class="nota">Revise la prioridad propuesta por la IA y establezca la prioridad final.</p></div>
<div><a class="boton-secundario" href="dashboard.php">Volver al dashboard</a></div>
</div>

<form method="GET" class="filtros-revision">
<div><label for="buscar">Buscar</label><input id="buscar" name="buscar" value="<?= e($buscar) ?>" placeholder="Remitente, destinatario o asunto"></div>
<div><label for="cuenta_id">Cuenta Gmail</label><select id="cuenta_id" name="cuenta_id"><option value="">Todas</option><?php foreach ($cuentas as $cuenta): ?><option value="<?= (int)$cuenta['id'] ?>" <?= $cuentaId === (string)$cuenta['id'] ? 'selected' : '' ?>><?= e((string)$cuenta['email']) ?></option><?php endforeach; ?></select></div>
<div><label for="prioridad">Prioridad IA</label><select id="prioridad" name="prioridad"><option value="">Todas</option><?php foreach (['ALTA'=>'Alta','MEDIA'=>'Media','BAJA'=>'Baja'] as $v=>$t): ?><option value="<?= $v ?>" <?= $prioridad === $v ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
<div><label for="categoria">Categoría</label><select id="categoria" name="categoria"><option value="">Todas</option><?php foreach (['URGENTE','FINANCIERO','ADMINISTRATIVO','OPERATIVO','INFORMACION','OTRO'] as $v): ?><option value="<?= $v ?>" <?= $categoria === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
<div><label for="confianza_min">Confianza mínima</label><select id="confianza_min" name="confianza_min"><option value="">Todas</option><option value="0.50" <?= $confianzaMin==='0.50'?'selected':'' ?>>50 %</option><option value="0.70" <?= $confianzaMin==='0.70'?'selected':'' ?>>70 %</option><option value="0.80" <?= $confianzaMin==='0.80'?'selected':'' ?>>80 %</option><option value="0.90" <?= $confianzaMin==='0.90'?'selected':'' ?>>90 %</option></select></div>
<div><label for="revision">Estado de revisión</label><select id="revision" name="revision"><option value="PENDIENTE" <?= $revision==='PENDIENTE'?'selected':'' ?>>Pendientes</option><option value="REVISADO" <?= $revision==='REVISADO'?'selected':'' ?>>Revisados</option><option value="TODOS" <?= $revision==='TODOS'?'selected':'' ?>>Todos</option></select></div>
<div class="filtro-acciones"><button type="submit">Filtrar</button><a class="boton-secundario boton-pequeno" href="revisiones.php">Limpiar</a></div>
</form>

<?php if ($flashOk): ?><div class="estado ok"><?= e((string)$flashOk) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="estado error"><?= e((string)$flashError) ?></div><?php endif; ?>
<p class="resumen-listado"><?= (int)$resultado['total'] ?> clasificación(es) encontrada(s).</p>

<?php if (!$resultado['items']): ?>
<div class="estado ok">No se encontraron clasificaciones con los filtros seleccionados.</div>
<?php else: ?>
<div class="tabla-contenedor"><table>
<thead><tr><th>Fecha</th><th>Remitente</th><th>Asunto</th><th>Prioridad IA</th><th>Categoría</th><th>Confianza</th><th>Revisión</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach ($resultado['items'] as $item): ?>
<tr>
<td><?= e((string)$item['fecha_clasificacion']) ?></td>
<td><?= e((string)$item['remitente']) ?></td>
<td><?= e((string)$item['asunto']) ?></td>
<td><span class="badge-prioridad prioridad-<?= strtolower((string)$item['prioridad']) ?>"><?= e(prioridadEtiqueta($item['prioridad'])) ?></span></td>
<td><?= e((string)($item['categoria'] ?? '—')) ?></td>
<td><?= $item['confianza'] !== null ? e(number_format((float)$item['confianza'] * 100, 1, ',', '.')) . ' %' : '—' ?></td>
<td><?php if (!empty($item['revision_id'])): ?><span class="estado-correo estado-atendido">Revisado</span><?php else: ?><span class="estado-correo estado-no_clasificado">Pendiente</span><?php endif; ?></td>
<td><a class="boton-secundario boton-pequeno" href="revision_ver.php?id=<?= (int)$item['id'] ?>"><?= !empty($item['revision_id']) ? 'Ver' : 'Revisar' ?></a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if ($resultado['total_paginas'] > 1): ?><div class="paginacion"><?php if ($resultado['pagina'] > 1): ?><a class="boton-secundario boton-pequeno" href="<?= e(urlPagina($resultado['pagina']-1)) ?>">← Anterior</a><?php endif; ?><span>Página <?= (int)$resultado['pagina'] ?> de <?= (int)$resultado['total_paginas'] ?></span><?php if ($resultado['pagina'] < $resultado['total_paginas']): ?><a class="boton-secundario boton-pequeno" href="<?= e(urlPagina($resultado['pagina']+1)) ?>">Siguiente →</a><?php endif; ?></div><?php endif; ?>
<?php endif; ?>
</section></main>
</body></html>
