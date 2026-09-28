<?php
declare(strict_types=1);

class Permiso
{
    public function __construct(private PDO $pdo)
    {
    }

    public function usuarioTienePermiso(int $usuarioId, string $permiso): bool
    {
        $sql = "
            SELECT 1
            FROM usuarios u
            INNER JOIN rol_permisos rp ON rp.rol_id = u.rol_id
            INNER JOIN permisos p ON p.id = rp.permiso_id
            WHERE u.id = :usuario_id
              AND u.estado = 1
              AND p.nombre = :permiso
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'permiso' => $permiso,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
