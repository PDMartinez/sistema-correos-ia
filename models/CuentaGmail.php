<?php
declare(strict_types=1);

final class CuentaGmail
{
    public function __construct(private PDO $pdo)
    {
    }

    public function obtenerPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cuentas_gmail WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cuentas_gmail
             (email, google_user_id, access_token, refresh_token, token_expira_en, estado)
             VALUES (:email, :google_user_id, :access_token, :refresh_token, :token_expira_en, :estado)'
        );
        $stmt->execute($datos);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizarTokens(
        int $id,
        string $accessToken,
        ?string $refreshToken,
        string $tokenExpiraEn,
        string $estado
    ): void {
        if ($refreshToken !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE cuentas_gmail
                 SET access_token = :access_token,
                     refresh_token = :refresh_token,
                     token_expira_en = :token_expira_en,
                     estado = :estado,
                     fecha_actualizacion = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expira_en' => $tokenExpiraEn,
                'estado' => $estado,
                'id' => $id,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE cuentas_gmail
             SET access_token = :access_token,
                 token_expira_en = :token_expira_en,
                 estado = :estado,
                 fecha_actualizacion = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'access_token' => $accessToken,
            'token_expira_en' => $tokenExpiraEn,
            'estado' => $estado,
            'id' => $id,
        ]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cuentas_gmail WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function actualizarUltimaSincronizacion(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cuentas_gmail
             SET ultima_sincronizacion = CURRENT_TIMESTAMP,
                 fecha_actualizacion = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function marcarError(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE cuentas_gmail
             SET estado = 'ERROR', fecha_actualizacion = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public function listar(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, email, estado, ultima_sincronizacion, fecha_conexion, fecha_actualizacion
             FROM cuentas_gmail ORDER BY email ASC'
        );
        return $stmt->fetchAll();
    }
}
