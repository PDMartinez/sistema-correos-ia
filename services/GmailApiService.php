<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/TokenCipher.php';
require_once __DIR__ . '/../models/CuentaGmail.php';
require_once __DIR__ . '/../models/Correo.php';

final class GmailApiService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Sincroniza mensajes de una cuenta Gmail y evita duplicados mediante
     * la combinación cuenta_gmail_id + gmail_message_id.
     */
    public function sincronizarCuenta(int $cuentaId, ?int $usuarioId = null): array
    {
        $cuentaModel = new CuentaGmail($this->pdo);
        $cuenta = $cuentaModel->obtenerPorId($cuentaId);

        if (!$cuenta) {
            throw new RuntimeException('La cuenta Gmail no existe.');
        }

        if ((string) $cuenta['estado'] !== 'ACTIVA') {
            throw new RuntimeException('La cuenta Gmail no está activa.');
        }

        try {
            $gmail = $this->crearClienteGmail($cuenta);
            $correoModel = new Correo($this->pdo);

            $maxMessages = max(1, min(500, (int) config('GMAIL_SYNC_MAX_MESSAGES', 50)));
            $query = trim((string) config('GMAIL_SYNC_QUERY', 'newer_than:30d -in:spam -in:trash'));

            $importados = 0;
            $omitidos = 0;
            $errores = 0;
            $procesados = 0;
            $pageToken = null;

            do {
                $pageSize = min(100, $maxMessages - $procesados);
                if ($pageSize <= 0) {
                    break;
                }

                $params = [
                    'maxResults' => $pageSize,
                    'includeSpamTrash' => false,
                ];

                if ($query !== '') {
                    $params['q'] = $query;
                }

                if ($pageToken !== null) {
                    $params['pageToken'] = $pageToken;
                }

                $response = $gmail->users_messages->listUsersMessages('me', $params);
                $messages = $response->getMessages() ?? [];

                foreach ($messages as $messageRef) {
                    if ($procesados >= $maxMessages) {
                        break;
                    }

                    $procesados++;
                    $messageId = (string) $messageRef->getId();

                    try {
                        if ($correoModel->existePorMensaje($cuentaId, $messageId)) {
                            $omitidos++;
                            continue;
                        }

                        $message = $gmail->users_messages->get('me', $messageId, [
                            'format' => 'full',
                        ]);

                        $datos = $this->extraerDatosMensaje($message);

                        $correoModel->crear([
                            'cuenta_gmail_id' => $cuentaId,
                            'gmail_message_id' => $messageId,
                            'thread_id' => (string) $message->getThreadId(),
                            'remitente' => $datos['remitente'],
                            'destinatario' => $datos['destinatario'],
                            'asunto' => $datos['asunto'],
                            'contenido' => $datos['contenido'],
                            'fecha_recepcion' => $datos['fecha_recepcion'],
                            'estado' => 'NO_CLASIFICADO',
                        ]);

                        $importados++;
                    } catch (Throwable $e) {
                        $errores++;
                        error_log('Error importando Gmail message ' . $messageId . ': ' . $e->getMessage());
                    }
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken !== null && $procesados < $maxMessages);

            $cuentaModel->actualizarUltimaSincronizacion($cuentaId);

            $this->auditar(
                $usuarioId,
                'SINCRONIZAR_CUENTA_GMAIL',
                'Cuenta Gmail ID: ' . $cuentaId . ' (' . $cuenta['email'] . '). ' .
                'Importados: ' . $importados . ', omitidos: ' . $omitidos . ', errores: ' . $errores . '.'
            );

            return [
                'cuenta_id' => $cuentaId,
                'email' => (string) $cuenta['email'],
                'procesados' => $procesados,
                'importados' => $importados,
                'omitidos' => $omitidos,
                'errores' => $errores,
                'consulta' => $query,
            ];
        } catch (Throwable $e) {
            $cuentaModel->marcarError($cuentaId);

            $this->auditar(
                $usuarioId,
                'ERROR_SINCRONIZAR_CUENTA_GMAIL',
                'Cuenta Gmail ID: ' . $cuentaId . '. Error: ' . $e->getMessage()
            );

            throw $e;
        }
    }

    private function crearClienteGmail(array $cuenta): Google\Service\Gmail
    {
        $cipher = new TokenCipher((string) config('APP_KEY', ''));
        $accessToken = $cipher->decrypt((string) $cuenta['access_token']);

        $client = googleClient();
        $client->setAccessToken([
            'access_token' => $accessToken,
            'expires_in' => max(0, strtotime((string) $cuenta['token_expira_en']) - time()),
        ]);

        if (!$client->isAccessTokenExpired()) {
            return new Google\Service\Gmail($client);
        }

        $encryptedRefreshToken = (string) ($cuenta['refresh_token'] ?? '');
        if ($encryptedRefreshToken === '') {
            throw new RuntimeException('La cuenta Gmail no tiene refresh token disponible. Es necesario volver a autorizarla.');
        }

        $refreshToken = $cipher->decrypt($encryptedRefreshToken);
        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($newToken['error'])) {
            throw new RuntimeException(
                'Google no pudo renovar el acceso: ' .
                (string) ($newToken['error_description'] ?? $newToken['error'])
            );
        }

        $newAccessToken = (string) ($newToken['access_token'] ?? '');
        if ($newAccessToken === '') {
            throw new RuntimeException('Google no devolvió un nuevo access token.');
        }

        $expiresIn = max(60, (int) ($newToken['expires_in'] ?? 3600));
        $expiresAt = (new DateTimeImmutable('now'))
            ->modify('+' . $expiresIn . ' seconds')
            ->format('Y-m-d H:i:s');

        $cuentaModel = new CuentaGmail($this->pdo);
        $cuentaModel->actualizarTokens(
            (int) $cuenta['id'],
            $cipher->encrypt($newAccessToken),
            null,
            $expiresAt,
            'ACTIVA'
        );

        $client->setAccessToken($newToken);

        return new Google\Service\Gmail($client);
    }

    private function extraerDatosMensaje(Google\Service\Gmail\Message $message): array
    {
        $payload = $message->getPayload();
        $headers = [];

        if ($payload !== null && $payload->getHeaders()) {
            foreach ($payload->getHeaders() as $header) {
                $name = strtolower((string) $header->getName());
                if (!isset($headers[$name])) {
                    $headers[$name] = trim((string) $header->getValue());
                }
            }
        }

        $contenido = $this->extraerContenidoPayload($payload);
        if ($contenido === '') {
            $contenido = trim((string) $message->getSnippet());
        }

        $internalDate = (string) $message->getInternalDate();
        $fechaRecepcion = $internalDate !== ''
            ? (new DateTimeImmutable('@' . ((int) $internalDate / 1000)))->setTimezone(new DateTimeZone((string) config('APP_TIMEZONE', 'America/Asuncion')))->format('Y-m-d H:i:s')
            : ($headers['date'] ?? null);

        return [
            'remitente' => $this->limitarTexto($headers['from'] ?? 'Sin remitente', 255),
            'destinatario' => $this->limitarTexto($headers['to'] ?? 'Sin destinatario', 255),
            'asunto' => $this->limitarTexto($headers['subject'] ?? '(Sin asunto)', 500),
            'contenido' => $contenido,
            'fecha_recepcion' => $fechaRecepcion ?: null,
        ];
    }

    private function extraerContenidoPayload(?Google\Service\Gmail\MessagePart $part): string
    {
        if ($part === null) {
            return '';
        }

        $mimeType = strtolower((string) $part->getMimeType());
        $body = $part->getBody();

        if ($body !== null && $body->getData() && in_array($mimeType, ['text/plain', 'text/html'], true)) {
            $texto = $this->decodificarBase64Url((string) $body->getData());
            if ($mimeType === 'text/html') {
                $texto = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $texto) ?? $texto;
                $texto = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $texto) ?? $texto;
                $texto = strip_tags($texto);
                $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $texto = preg_replace("/\\r\\n|\\r/", "\\n", $texto) ?? $texto;
            $texto = preg_replace("/\\n{3,}/", "\\n\\n", $texto) ?? $texto;
            return trim($texto);
        }

        $partes = $part->getParts() ?? [];
        $htmlFallback = '';

        foreach ($partes as $subpart) {
            $subMime = strtolower((string) $subpart->getMimeType());
            $contenido = $this->extraerContenidoPayload($subpart);

            if ($contenido === '') {
                continue;
            }

            if ($subMime === 'text/plain') {
                return $contenido;
            }

            if ($subMime === 'text/html' && $htmlFallback === '') {
                $htmlFallback = $contenido;
            }
        }

        return $htmlFallback;
    }

    private function decodificarBase64Url(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($data, true);
        return $decoded === false ? '' : $decoded;
    }

    private function limitarTexto(string $texto, int $maximo): string
    {
        return function_exists('mb_substr') ? mb_substr($texto, 0, $maximo) : substr($texto, 0, $maximo);
    }

    private function auditar(?int $usuarioId, string $accion, string $descripcion): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent)
             VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip, :user_agent)'
        );

        $stmt->execute([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'modulo' => 'gmail',
            'descripcion' => $descripcion,
            'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
        ]);
    }
}
