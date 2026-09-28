<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/TokenCipher.php';
require_once __DIR__ . '/../models/CuentaGmail.php';

final class GoogleOAuthService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function crearUrlAutorizacion(): string
    {
        $client = googleClient();
        $state = bin2hex(random_bytes(32));

        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_state_expires'] = time() + 600;

        $client->setState($state);
        return $client->createAuthUrl();
    }

    public function procesarCallback(string $code, string $state, int $usuarioId): array
    {
        $this->validarState($state);

        $client = googleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException('Google rechazó la autorización: ' . (string) ($token['error_description'] ?? $token['error']));
        }

        $accessToken = (string) ($token['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Google no devolvió un access token válido.');
        }

        $client->setAccessToken($token);
        $gmail = new Google\Service\Gmail($client);
        $profile = $gmail->users->getProfile('me');
        $email = mb_strtolower(trim((string) $profile->getEmailAddress()));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('No se pudo obtener un correo Gmail válido.');
        }

        $expiresIn = (int) ($token['expires_in'] ?? 3600);
        $expiresAt = (new DateTimeImmutable('now'))
            ->modify('+' . max(60, $expiresIn) . ' seconds')
            ->format('Y-m-d H:i:s');

        $cipher = new TokenCipher((string) config('APP_KEY', ''));
        $accessTokenEncrypted = $cipher->encrypt($accessToken);

        $refreshToken = isset($token['refresh_token']) ? trim((string) $token['refresh_token']) : '';
        $refreshTokenEncrypted = $refreshToken !== '' ? $cipher->encrypt($refreshToken) : null;

        $cuentaModel = new CuentaGmail($this->pdo);
        $existente = $cuentaModel->obtenerPorEmail($email);

        if ($existente) {
            $cuentaModel->actualizarTokens(
                (int) $existente['id'],
                $accessTokenEncrypted,
                $refreshTokenEncrypted,
                $expiresAt,
                'ACTIVA'
            );
            $cuentaId = (int) $existente['id'];
            $accion = 'ACTUALIZAR_CUENTA_GMAIL';
        } else {
            $cuentaId = $cuentaModel->crear([
                'email' => $email,
                'google_user_id' => null,
                'access_token' => $accessTokenEncrypted,
                'refresh_token' => $refreshTokenEncrypted,
                'token_expira_en' => $expiresAt,
                'estado' => 'ACTIVA',
            ]);
            $accion = 'CONECTAR_CUENTA_GMAIL';
        }

        $this->auditar($usuarioId, $accion, $cuentaId, $email);

        unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expires']);

        return [
            'id' => $cuentaId,
            'email' => $email,
            'accion' => $accion,
        ];
    }

    private function validarState(string $state): void
    {
        $esperado = (string) ($_SESSION['google_oauth_state'] ?? '');
        $expira = (int) ($_SESSION['google_oauth_state_expires'] ?? 0);

        unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expires']);

        if ($esperado === '' || $expira < time() || !hash_equals($esperado, $state)) {
            throw new RuntimeException('La validación de seguridad de Google OAuth falló. Vuelve a iniciar la conexión.');
        }
    }

    private function auditar(int $usuarioId, string $accion, int $cuentaId, string $email): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent)
             VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'modulo' => 'cuentas_gmail',
            'descripcion' => 'Cuenta Gmail ID: ' . $cuentaId . ' (' . $email . ')',
            'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
        ]);
    }
}
