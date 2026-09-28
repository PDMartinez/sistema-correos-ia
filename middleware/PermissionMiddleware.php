<?php
declare(strict_types=1);

function exigirPermiso(PDO $pdo, string $permiso): void
{
    exigirAutenticacion();

    $usuario = usuarioActual();
    $permisoModel = new Permiso($pdo);

    if (!$usuario || !$permisoModel->usuarioTienePermiso((int) $usuario['id'], $permiso)) {
        http_response_code(403);
        require __DIR__ . '/../views/errors/403.php';
        exit;
    }
}
