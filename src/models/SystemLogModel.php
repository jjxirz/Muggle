<?php

require_once __DIR__ . '/Database.php';

class SystemLogModel
{
    public static function record(?int $userId, string $accion, ?string $descripcion = null): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                'INSERT INTO logs_sistema (id_usuario, accion, descripcion, ip)
                 VALUES (:id_usuario, :accion, :descripcion, :ip)'
            );
            $stmt->execute([
                'id_usuario' => $userId,
                'accion' => trim($accion) !== '' ? $accion : 'Evento del sistema',
                'descripcion' => $descripcion,
                'ip' => self::detectClientIp(),
            ]);
        } catch (Throwable $exception) {
            // Never block business logic because of log persistence failures.
        }
    }

    private static function detectClientIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            $raw = trim((string) ($_SERVER[$key] ?? ''));
            if ($raw === '') {
                continue;
            }

            $parts = array_map('trim', explode(',', $raw));
            foreach ($parts as $part) {
                if (filter_var($part, FILTER_VALIDATE_IP) !== false) {
                    return $part;
                }
            }
        }

        return '0.0.0.0';
    }
}
