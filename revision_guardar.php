<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$pdo = Conexion::getInstance();
exigirPermiso($pdo, 'modificar_clasificacion');
$usuario = usuarioActual();
$esAdministrador = ($usuario['rol_nombre'] ?? '') === 'Administrador';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: revisiones.php');
    exit;
}

if (!validarCsrf((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(419);
    exit('Token CSRF inválido.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$prioridadFinal = strtoupper(trim((string)($_POST['prioridad_final'] ?? '')));
$comentario = trim((string)($_POST['comentario'] ?? ''));
if ($comentario === '') { $comentario = null; }
if ($comentario !== null && mb_strlen($comentario) > 1000) { $comentario = mb_substr($comentario, 0, 1000); }

if (!$id || $id < 1) {
    $_SESSION['flash_error'] = 'Correo no válido.';
    header('Location: revisiones.php');
    exit;
}

if (!in_array($prioridadFinal, ['ALTA', 'MEDIA', 'BAJA'], true)) {
    $_SESSION['flash_error'] = 'Debe seleccionar una prioridad final válida.';
    header('Location: revision_ver.php?id=' . (int)$id);
    exit;
}

try {
    $model = new RevisionClasificacion($pdo);
    $item = $model->obtenerParaRevision((int)$id, (int)$usuario['id'], $esAdministrador);
    if (!$item) {
        throw new RuntimeException('Correo no encontrado o no autorizado.');
    }

    $pdo->beginTransaction();
    $resultado = $model->guardar((int)$item['clasificacion_id'], (int)$usuario['id'], $prioridadFinal, $comentario);
    $revisionId = (int)$resultado['id'];
    $accionRevision = (string)$resultado['accion'];

    $stmt = $pdo->prepare(
        'INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent)
         VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)'
    );
    $stmt->execute([
        'usuario_id' => (int)$usuario['id'],
        'accion' => $accionRevision === 'MODIFICAR' ? 'MODIFICAR_REVISION_CLASIFICACION' : 'REVISAR_CLASIFICACION',
        'modulo' => 'revisiones_clasificacion',
        'descripcion' => ($accionRevision === 'MODIFICAR' ? 'Modificación de revisión ID ' : 'Revisión ID ') . $revisionId . ' del correo ID ' . (int)$id . '. Prioridad IA: ' . $item['prioridad'] . '; prioridad final: ' . $prioridadFinal . '.',
        'ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);
    $pdo->commit();

    $_SESSION['flash_ok'] = $accionRevision === 'MODIFICAR'
        ? 'Revisión modificada correctamente. El correo continúa marcado como atendido.'
        : 'Revisión registrada correctamente. El correo quedó marcado como atendido.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Error registrando revisión del correo ' . (int)$id . ': ' . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage();
}

header('Location: revision_ver.php?id=' . (int)$id);
exit;
