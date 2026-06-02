<?php

require_once __DIR__ . '/Database.php';

class AdminLogModel
{
    private PDO $db;
    private string $detailColumn;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->detailColumn = $this->resolveDetailColumn();
    }

    public function getTodayMetrics(): array
    {
        if (!$this->tableExists('logs_sistema')) {
            return [
                'total' => 0,
                'warnings' => 0,
                'errors' => 0,
            ];
        }

        $sql = 'SELECT
                    COUNT(*) AS total,
                    SUM(CASE
                        WHEN LOWER(COALESCE(accion, "")) LIKE "%error%"
                          OR LOWER(COALESCE(accion, "")) LIKE "%fallo%"
                          OR LOWER(COALESCE(accion, "")) LIKE "%denegad%"
                          OR LOWER(COALESCE(accion, "")) LIKE "%inval%"
                        THEN 1 ELSE 0
                    END) AS total_errors,
                    SUM(CASE
                        WHEN LOWER(COALESCE(accion, "")) LIKE "%warn%"
                          OR LOWER(COALESCE(accion, "")) LIKE "%advert%"
                          OR LOWER(COALESCE(accion, "")) LIKE "%intento%"
                          OR LOWER(COALESCE(accion, "")) LIKE "%expir%"
                        THEN 1 ELSE 0
                    END) AS total_warnings
                FROM logs_sistema
                WHERE DATE(fecha) = CURDATE()';

        $row = $this->db->query($sql)->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'warnings' => (int) ($row['total_warnings'] ?? 0),
            'errors' => (int) ($row['total_errors'] ?? 0),
        ];
    }

    public function getRecentLogs(int $limit = 300): array
    {
        if (!$this->tableExists('logs_sistema')) {
            return [];
        }

        $limit = max(1, min(1000, $limit));

        $detailExpr = $this->detailColumn !== ''
            ? 'l.' . $this->detailColumn . ' AS detalle'
            : 'NULL AS detalle';

        $sql = 'SELECT
                    l.id_log,
                    DATE_FORMAT(l.fecha, "%H:%i") AS hora,
                    COALESCE(NULLIF(l.accion, ""), "Evento del sistema") AS mensaje,
                    COALESCE(NULLIF(u.email, ""), NULLIF(u.nombre, ""), "Sistema") AS usuario,
                    COALESCE(NULLIF(l.ip, ""), "-") AS ip,
                    ' . $detailExpr . ',
                    CASE
                        WHEN LOWER(COALESCE(l.accion, "")) LIKE "%error%"
                          OR LOWER(COALESCE(l.accion, "")) LIKE "%fallo%"
                          OR LOWER(COALESCE(l.accion, "")) LIKE "%denegad%"
                          OR LOWER(COALESCE(l.accion, "")) LIKE "%inval%"
                        THEN "error"
                        WHEN LOWER(COALESCE(l.accion, "")) LIKE "%warn%"
                          OR LOWER(COALESCE(l.accion, "")) LIKE "%advert%"
                          OR LOWER(COALESCE(l.accion, "")) LIKE "%intento%"
                          OR LOWER(COALESCE(l.accion, "")) LIKE "%expir%"
                        THEN "warning"
                        ELSE "info"
                    END AS tipo
                FROM logs_sistema l
                LEFT JOIN usuarios u ON u.id_usuario = l.id_usuario
                ORDER BY l.fecha DESC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function resolveDetailColumn(): string
    {
        if ($this->columnExists('logs_sistema', 'detalle')) {
            return 'detalle';
        }

        if ($this->columnExists('logs_sistema', 'descripcion')) {
            return 'descripcion';
        }

        return '';
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table_name'
        );
        $stmt->execute(['table_name' => $tableName]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND column_name = :column_name'
        );
        $stmt->execute([
            'table_name' => $tableName,
            'column_name' => $columnName,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
