<?php

require_once __DIR__ . '/Database.php';

class InteractionAdminModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function summary(int $days = 30): array
    {
        $days = max(1, $days);

        $favoritesTotal = (int) $this->db->query('SELECT COUNT(*) FROM favoritos')->fetchColumn();

        $readingListTotal = 0;
        $readingListActiveUsers = 0;

        if ($this->tableExists('lista_lectura')) {
            $readingListTotal = (int) $this->db->query('SELECT COUNT(*) FROM lista_lectura')->fetchColumn();
            $readingListActiveUsers = (int) $this->db->query('SELECT COUNT(DISTINCT id_usuario) FROM lista_lectura')->fetchColumn();
        }

        $favoriteActiveUsers = (int) $this->db->query('SELECT COUNT(DISTINCT id_usuario) FROM favoritos')->fetchColumn();

        $favoriteAddsRecent = $this->countRecentAdds('favoritos', 'fecha_agregado', $days);
        $readingListAddsRecent = $this->tableExists('lista_lectura')
            ? $this->countRecentAdds('lista_lectura', 'fecha_agregado', $days)
            : 0;

        return [
            'favorites_total' => $favoritesTotal,
            'reading_list_total' => $readingListTotal,
            'favorite_users' => $favoriteActiveUsers,
            'reading_list_users' => $readingListActiveUsers,
            'favorite_adds_recent' => $favoriteAddsRecent,
            'reading_list_adds_recent' => $readingListAddsRecent,
        ];
    }

    public function topBooksByFavorites(int $limit = 10, int $days = 30): array
    {
        $limit = max(1, min(50, $limit));
        $days = max(1, $days);

        $sql = 'SELECT l.titulo, l.autor, COUNT(f.id_favorito) AS total, MAX(f.fecha_agregado) AS ultima_fecha
                FROM favoritos f
                INNER JOIN libros l ON l.id_libro = f.id_libro
                WHERE f.fecha_agregado >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY l.id_libro, l.titulo, l.autor
                ORDER BY total DESC, ultima_fecha DESC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function topBooksByReadingList(int $limit = 10, int $days = 30): array
    {
        if (!$this->tableExists('lista_lectura')) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $days = max(1, $days);

        $sql = 'SELECT l.titulo, l.autor, COUNT(ll.id_lista_lectura) AS total, MAX(ll.fecha_agregado) AS ultima_fecha
                FROM lista_lectura ll
                INNER JOIN libros l ON l.id_libro = ll.id_libro
                WHERE ll.fecha_agregado >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY l.id_libro, l.titulo, l.autor
                ORDER BY total DESC, ultima_fecha DESC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function recentFavoriteEvents(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $sql = 'SELECT u.nombre AS usuario, u.email, l.titulo, l.autor, f.fecha_agregado
                FROM favoritos f
                INNER JOIN usuarios u ON u.id_usuario = f.id_usuario
                INNER JOIN libros l ON l.id_libro = f.id_libro
                ORDER BY f.fecha_agregado DESC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function recentReadingListEvents(int $limit = 20): array
    {
        if (!$this->tableExists('lista_lectura')) {
            return [];
        }

        $limit = max(1, min(100, $limit));

        $sql = 'SELECT u.nombre AS usuario, u.email, l.titulo, l.autor, ll.fecha_agregado
                FROM lista_lectura ll
                INNER JOIN usuarios u ON u.id_usuario = ll.id_usuario
                INNER JOIN libros l ON l.id_libro = ll.id_libro
                ORDER BY ll.fecha_agregado DESC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => $tableName]);

        return (bool) $stmt->fetchColumn();
    }

    private function countRecentAdds(string $tableName, string $dateColumn, int $days): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s >= DATE_SUB(NOW(), INTERVAL :days DAY)',
            $tableName,
            $dateColumn
        );

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
