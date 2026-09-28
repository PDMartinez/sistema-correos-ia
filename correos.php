<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/models/Correo.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'ver_correos');
$usuario = usuarioActual();
$esAdministrador = ($usuario['rol_nombre'] ?? '') === 'Administrador';
$model = new Correo($pdo);

$buscar = trim((string) ($_GET['buscar'] ?? ''));
$cuentaId = (string) ($_GET['cuenta_id'] ?? '');
$estado = (string) ($_GET['estado'] ?? '');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$resultado = $model->listar((int) $usuario['id'], $esAdministrador, [
    'buscar' => $buscar,
    'cuenta_id' => $cuentaId,
    'estado' => $estado,
], $pagina, 20);
$cuentas = $model->listarCuentasDisponibles((int) $usuario['id'], $esAdministrador);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function estadoEtiqueta(string $estado): string {
    return match ($estado) {
        'NO_CLASIFICADO' => 'No clasificado',
        'CLASIFICADO' => 'Clasificado',
        'ATENDIDO' => 'Atendido',
        'ARCHIVADO' => 'Archivado',
        default => $estado,
    };
}
function prioridadEtiqueta(?string $prioridad): string {
    return match ($prioridad) {
        'ALTA' => 'Alta', 'MEDIA' => 'Media', 'BAJA' => 'Baja', default => '—'
    };
}
function urlPagina(int $pagina): string {
    $params = $_GET; $params['pagina'] = $pagina;
    return 'correos.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Correos - <?= e((string) config('APP_NAME')) ?></title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor panel">
<section class="tarjeta tarjeta-ancha">
<div class="cabecera">
<div><h1>Correos</h1><p class="nota">Consulta los correos importados desde las cuentas Gmail autorizadas.</p></div>
<div><a class="boton-secundario" href="dashboard.php">Volver al dashboard</a></div>
</div>

<form method="GET" class="filtros-correo">
<div><label for="buscar">Buscar</label><input id="buscar" name="buscar" value="<?= e($buscar) ?>" placeholder="Remitente, destinatario o asunto"></div>
<div><label for="cuenta_id">Cuenta Gmail</label><select id="cuenta_id" name="cuenta_id"><option value="">Todas</option><?php foreach ($cuentas as $cuenta): ?><option value="<?= (int)$cuenta['id'] ?>" <?= $cuentaId === (string)$cuenta['id'] ? 'selected' : '' ?>><?= e((string)$cuenta['email']) ?></option><?php endforeach; ?></select></div>
<div><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach (['NO_CLASIFICADO'=>'No clasificado','CLASIFICADO'=>'Clasificado','ATENDIDO'=>'Atendido','ARCHIVADO'=>'Archivado'] as $valor=>$texto): ?><option value="<?= $valor ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= $texto ?></option><?php endforeach; ?></select></div>
<div class="filtro-acciones"><button type="submit">Buscar</button><a class="boton-secundario boton-pequeno" href="correos.php">Limpiar</a></div>
</form>

<p class="resumen-listado"><?= (int)$resultado['total'] ?> correo(s) encontrado(s).</p>
<?php if (!$resultado['items']): ?>
<div class="estado ok">No se encontraron correos con los filtros seleccionados.</div>
<?php else: ?>
<div class="tabla-contenedor"><table>
<thead><tr><th>Fecha</th><th>Remitente</th><th>Asunto</th><th>Cuenta</th><th>Estado</th><th>Prioridad</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach ($resultado['items'] as $correo): ?>
<tr>
<td><?= e((string) $correo['fecha_recepcion']) ?></td>
<td><?= e((string) $correo['remitente']) ?></td>
<td><?= e((string) $correo['asunto']) ?></td>
<td><?= e((string) $correo['cuenta_email']) ?></td>
<td><span class="estado-correo estado-<?= strtolower((string)$correo['estado']) ?>"><?= e(estadoEtiqueta((string)$correo['estado'])) ?></span></td>
<td><?= e(prioridadEtiqueta($correo['prioridad'] ?? null)) ?></td>
<td><a class="boton-secundario boton-pequeno" href="correo_ver.php?id=<?= (int)$correo['id'] ?>">Ver</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<?php if ($resultado['total_paginas'] > 1): ?>
<div class="paginacion">
<?php if ($resultado['pagina'] > 1): ?><a class="boton-secundario boton-pequeno" href="<?= e(urlPagina($resultado['pagina']-1)) ?>">← Anterior</a><?php endif; ?>
<span>Página <?= (int)$resultado['pagina'] ?> de <?= (int)$resultado['total_paginas'] ?></span>
<?php if ($resultado['pagina'] < $resultado['total_paginas']): ?><a class="boton-secundario boton-pequeno" href="<?= e(urlPagina($resultado['pagina']+1)) ?>">Siguiente →</a><?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
</section></main>
</body></html>
