<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'ver_estadisticas');
$usuario = usuarioActual();
$permiso = new Permiso($pdo);
$esAdministrador = $permiso->usuarioTienePermiso((int) $usuario['id'], 'gestionar_usuarios');

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function n(int|float|null $v): string { return number_format((float) ($v ?? 0), 0, ',', '.'); }
function pct(float|null $v): string { return $v === null ? '—' : number_format($v * 100, 1, ',', '.') . '%'; }

$filtros = [
    'cuenta_id' => $_GET['cuenta_id'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'prioridad' => $_GET['prioridad'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
];

$stats = new Estadisticas($pdo);
$resumen = $stats->resumen((int) $usuario['id'], $esAdministrador, $filtros);
$prioridades = $stats->porPrioridad((int) $usuario['id'], $esAdministrador, $filtros);
$categorias = $stats->porCategoria((int) $usuario['id'], $esAdministrador, $filtros);
$confianza = $stats->porConfianza((int) $usuario['id'], $esAdministrador, $filtros);
$evolucion = $stats->evolucion((int) $usuario['id'], $esAdministrador, $filtros);
$cuentas = $stats->listarCuentasDisponibles((int) $usuario['id'], $esAdministrador);

$prioridadMap = ['ALTA'=>0,'MEDIA'=>0,'BAJA'=>0];
foreach ($prioridades as $row) { $prioridadMap[$row['prioridad']] = (int) $row['cantidad']; }

$maxCategoria = 0;
foreach ($categorias as $row) { $maxCategoria = max($maxCategoria, (int) $row['cantidad']); }
$maxEvolucion = 0;
foreach ($evolucion as $row) { $maxEvolucion = max($maxEvolucion, (int) $row['total']); }

$querySinFiltros = 'estadisticas.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Estadísticas - <?= e((string) config('APP_NAME')) ?></title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="module-page dashboard-page">
<?php require __DIR__ . '/views/layout/app_nav.php'; ?>
<main class="contenedor panel">
<section class="tarjeta tarjeta-ancha dashboard-shell">

<div class="dashboard-titulo-seccion">
  <div>
    <h2>Resumen del sistema</h2>
    <p class="nota">Indicadores de correos, clasificación IA y revisión humana.</p>
  </div>
</div>

<form class="filtros-estadisticas" method="GET" action="estadisticas.php">
  <div><label for="cuenta_id">Cuenta Gmail</label><select name="cuenta_id" id="cuenta_id"><option value="">Todas</option><?php foreach ($cuentas as $cuenta): ?><option value="<?= (int)$cuenta['id'] ?>" <?= (string)$filtros['cuenta_id'] === (string)$cuenta['id'] ? 'selected' : '' ?>><?= e($cuenta['email']) ?></option><?php endforeach; ?></select></div>
  <div><label for="fecha_desde">Desde</label><input type="date" name="fecha_desde" id="fecha_desde" value="<?= e((string)$filtros['fecha_desde']) ?>"></div>
  <div><label for="fecha_hasta">Hasta</label><input type="date" name="fecha_hasta" id="fecha_hasta" value="<?= e((string)$filtros['fecha_hasta']) ?>"></div>
  <div><label for="prioridad">Prioridad IA</label><select name="prioridad" id="prioridad"><option value="">Todas</option><?php foreach (['ALTA','MEDIA','BAJA'] as $op): ?><option value="<?= $op ?>" <?= $filtros['prioridad'] === $op ? 'selected' : '' ?>><?= ucfirst(strtolower($op)) ?></option><?php endforeach; ?></select></div>
  <div><label for="categoria">Categoría</label><select name="categoria" id="categoria"><option value="">Todas</option><?php foreach (['URGENTE','FINANCIERO','ADMINISTRATIVO','OPERATIVO','INFORMACION','OTRO'] as $op): ?><option value="<?= $op ?>" <?= $filtros['categoria'] === $op ? 'selected' : '' ?>><?= e($op) ?></option><?php endforeach; ?></select></div>
  <div class="filtro-acciones"><button type="submit">Aplicar</button><a class="boton-secundario" href="<?= e($querySinFiltros) ?>">Limpiar</a></div>
</form>

<div class="kpi-grid">
  <div class="kpi"><span>Total correos</span><strong><?= n($resumen['total']) ?></strong></div>
  <div class="kpi"><span>No clasificados</span><strong><?= n($resumen['no_clasificados']) ?></strong></div>
  <div class="kpi"><span>Clasificados IA</span><strong><?= n($resumen['clasificados']) ?></strong></div>
  <div class="kpi"><span>Pendientes de revisión</span><strong><?= n($resumen['pendientes_revision']) ?></strong></div>
  <div class="kpi"><span>Revisados</span><strong><?= n($resumen['revisados']) ?></strong></div>
  <div class="kpi"><span>Atendidos</span><strong><?= n($resumen['atendidos']) ?></strong></div>
</div>

<div class="estadisticas-grid">
<section class="panel-estadistica">
  <h2>Distribución por prioridad</h2>
  <?php foreach ($prioridadMap as $prioridad => $cantidad): ?><div class="barra-fila"><span><?= e(ucfirst(strtolower($prioridad))) ?></span><div class="barra"><i class="barra-<?= strtolower($prioridad) ?>" style="width:<?= $resumen['clasificados'] > 0 ? round($cantidad * 100 / $resumen['clasificados'], 1) : 0 ?>%"></i></div><strong><?= n($cantidad) ?></strong></div><?php endforeach; ?>
</section>
<section class="panel-estadistica">
  <h2>Confianza de la IA</h2>
  <div class="indicador-grande"><?= pct($resumen['confianza_promedio']) ?></div>
  <p class="nota">Promedio de confianza de las clasificaciones disponibles.</p>
  <div class="barra-fila"><span>≥ 80%</span><div class="barra"><i class="barra-neutra" style="width:<?= $resumen['clasificados'] > 0 ? round($confianza['alta'] * 100 / max(1,$resumen['clasificados']),1) : 0 ?>%"></i></div><strong><?= n($confianza['alta']) ?></strong></div>
  <div class="barra-fila"><span>50–79%</span><div class="barra"><i class="barra-neutra" style="width:<?= $resumen['clasificados'] > 0 ? round($confianza['media'] * 100 / max(1,$resumen['clasificados']),1) : 0 ?>%"></i></div><strong><?= n($confianza['media']) ?></strong></div>
  <div class="barra-fila"><span>&lt; 50%</span><div class="barra"><i class="barra-neutra" style="width:<?= $resumen['clasificados'] > 0 ? round($confianza['baja'] * 100 / max(1,$resumen['clasificados']),1) : 0 ?>%"></i></div><strong><?= n($confianza['baja']) ?></strong></div>
</section>
<section class="panel-estadistica">
  <h2>Concordancia IA / revisión humana</h2>
  <div class="indicador-grande"><?= pct($resumen['concordancia']) ?></div>
  <p class="nota"><?= n($resumen['coincidencias']) ?> coincidencias de <?= n($resumen['revisados']) ?> revisiones actuales.</p>
  <div class="mini-metricas"><div><strong><?= n($resumen['coincidencias']) ?></strong><span>Coinciden</span></div><div><strong><?= n($resumen['modificaciones']) ?></strong><span>Modificadas</span></div></div>
</section>
<section class="panel-estadistica">
  <h2>Distribución por categoría</h2>
  <?php if (!$categorias): ?><p class="nota">No hay clasificaciones para los filtros seleccionados.</p><?php endif; ?>
  <?php foreach ($categorias as $row): ?><div class="barra-fila"><span><?= e((string)$row['categoria']) ?></span><div class="barra"><i class="barra-neutra" style="width:<?= $maxCategoria > 0 ? round((int)$row['cantidad'] * 100 / $maxCategoria, 1) : 0 ?>%"></i></div><strong><?= n((int)$row['cantidad']) ?></strong></div><?php endforeach; ?>
</section>
</div>

<section class="panel-estadistica tabla-estadistica">
<h2>Evolución por fecha de recepción</h2>
<?php if (!$evolucion): ?><p class="nota">No hay datos para los filtros seleccionados.</p><?php else: ?><div class="tabla-contenedor"><table><thead><tr><th>Fecha</th><th>Total</th><th>Clasificados</th><th>Revisados</th><th>Visualización</th></tr></thead><tbody><?php foreach ($evolucion as $row): ?><tr><td><?= e((string)$row['fecha']) ?></td><td><?= n((int)$row['total']) ?></td><td><?= n((int)$row['clasificados']) ?></td><td><?= n((int)$row['revisados']) ?></td><td><div class="barra"><i class="barra-neutra" style="width:<?= $maxEvolucion > 0 ? round((int)$row['total'] * 100 / $maxEvolucion,1) : 0 ?>%"></i></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>

<p class="nota">La concordancia mostrada compara la prioridad actual propuesta por la IA con la prioridad final de la revisión humana vigente. No representa por sí sola una medida completa de precisión del modelo.</p>
</section>
</main>
</body>
</html>
