<?php
declare(strict_types=1);

/**
 * Conexión centralizada a MySQL mediante PDO.
 */
class Conexion
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            $host = config('DB_HOST', '127.0.0.1');
            $port = config('DB_PORT', '3306');
            $dbname = config('DB_NAME', 'sistema_correos_ia');
            $user = config('DB_USER', 'root');
            $password = config('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            self::$instancia = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instancia;
    }
}
