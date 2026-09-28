<?php
declare(strict_types=1);

class Usuario
{
    public function __construct(private PDO $pdo) {}

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.rol_id, u.nombre, u.apellido, u.email,
                    u.estado, u.ultimo_acceso, u.fecha_creacion,
                    r.nombre AS rol_nombre
             FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function actualizarUltimoAcceso(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function listar(string $buscar = ''): array
    {
        $sql = 'SELECT u.id, u.nombre, u.apellido, u.email, u.estado,
                       u.ultimo_acceso, r.nombre AS rol_nombre
                FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id';
        $params = [];
        if ($buscar !== '') {
            $sql .= ' WHERE u.nombre LIKE :nombre OR u.apellido LIKE :apellido
                      OR u.email LIKE :email';
            $like = '%' . $buscar . '%';
            $params = ['nombre' => $like, 'apellido' => $like, 'email' => $like];
        }
        $sql .= ' ORDER BY u.id DESC LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function rolesActivos(): array
    {
        return $this->pdo->query(
            'SELECT id, nombre FROM roles WHERE estado = 1 ORDER BY nombre'
        )->fetchAll();
    }

    public function emailExiste(string $email, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT 1 FROM usuarios WHERE email = :email';
        $params = ['email' => $email];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptoId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function rolActivo(int $rolId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id AND estado = 1');
        $stmt->execute(['id' => $rolId]);
        return (bool) $stmt->fetchColumn();
    }

    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (rol_id, nombre, apellido, email, password, estado)
             VALUES (:rol_id, :nombre, :apellido, :email, :password, 1)'
        );
        $stmt->execute($datos);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE usuarios SET rol_id = :rol_id, nombre = :nombre,
                apellido = :apellido, email = :email';
        if (isset($datos['password'])) {
            $sql .= ', password = :password';
        }
        $sql .= ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id] + $datos);
    }

    public function cambiarEstado(int $id, int $estado): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET estado = :estado WHERE id = :id');
        $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public function contarAdministradoresActivos(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE r.nombre = 'Administrador' AND u.estado = 1"
        )->fetchColumn();
    }

    public function bloquearAdministradoresActivos(): void
    {
        // Serializa cambios de administradores durante la transacción.
        $this->pdo->query(
            "SELECT u.id FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE r.nombre = 'Administrador' AND u.estado = 1
             FOR UPDATE"
        )->fetchAll();
    }

    public function esAdministrador(array $usuario): bool
    {
        return $usuario['rol_nombre'] === 'Administrador';
    }
}
