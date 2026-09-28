<?php
declare(strict_types=1);

class Usuario
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.rol_id,
                u.nombre,
                u.apellido,
                u.email,
                u.password,
                u.estado,
                r.nombre AS rol_nombre
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.email = :email
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function actualizarUltimoAcceso(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $usuarioId]);
    }

    public function obtenerPorId(int $usuarioId): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.rol_id,
                u.nombre,
                u.apellido,
                u.email,
                u.estado,
                r.nombre AS rol_nombre
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $usuarioId]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }
}
