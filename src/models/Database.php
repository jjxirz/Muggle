<?php

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'biblioteca_digital';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $hostsToTry = array_unique([$host, '127.0.0.1', 'localhost']);
        $lastException = null;

        foreach ($hostsToTry as $candidateHost) {
            $dsn = "mysql:host={$candidateHost};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                return self::$connection;
            } catch (PDOException $exception) {
                $lastException = $exception;
            }
        }

        $message = 'No se pudo conectar a MySQL. Verifica DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS.';
        if ($lastException instanceof PDOException) {
            $message .= ' Motivo: ' . $lastException->getMessage();
        }

        throw new RuntimeException($message);
    }
}
