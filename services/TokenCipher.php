<?php
declare(strict_types=1);

final class TokenCipher
{
    private const VERSION = 'v1';

    public function __construct(private string $keyBase64)
    {
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('La extensión Sodium de PHP no está disponible.');
        }

        $key = base64_decode($keyBase64, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY no es válida. Debe ser una clave Base64 de 32 bytes.');
        }
    }

    public function encrypt(string $plainText): string
    {
        $key = $this->key();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($plainText, $nonce, $key);

        return self::VERSION . ':' . base64_encode($nonce . $cipherText);
    }

    public function decrypt(string $encrypted): string
    {
        [$version, $payload] = array_pad(explode(':', $encrypted, 2), 2, null);

        if ($version !== self::VERSION || !is_string($payload)) {
            throw new RuntimeException('Token cifrado inválido o versión no soportada.');
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Token cifrado corrupto.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plainText = sodium_crypto_secretbox_open($cipherText, $nonce, $this->key());

        if ($plainText === false) {
            throw new RuntimeException('No se pudo descifrar el token.');
        }

        return $plainText;
    }

    private function key(): string
    {
        $key = base64_decode($this->keyBase64, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY no es válida.');
        }
        return $key;
    }
}
