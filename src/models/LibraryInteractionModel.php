<?php

require_once __DIR__ . '/Database.php';

class LibraryInteractionModel
{
    private PDO $db;
    private ?bool $favoriteTableExists = null;
    private ?bool $readingListTableExists = null;
    private ?bool $progressTableExists = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureInteractionTables();
    }

    public function toggleFavorite(int $userId, array $bookData): array
    {
        if (!$this->hasFavoriteTable()) {
            return ['is_favorite' => false, 'book_id' => null];
        }

        $bookId = $this->ensureBook($bookData);

        $stmt = $this->db->prepare(
            'SELECT id_favorito FROM favoritos WHERE id_usuario = :user_id AND id_libro = :book_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'book_id' => $bookId]);
        $favoriteId = $stmt->fetchColumn();

        if ($favoriteId) {
            $delete = $this->db->prepare('DELETE FROM favoritos WHERE id_favorito = :id');
            $delete->execute(['id' => $favoriteId]);
            return ['is_favorite' => false, 'book_id' => $bookId];
        }

        $insert = $this->db->prepare(
            'INSERT INTO favoritos (id_usuario, id_libro) VALUES (:user_id, :book_id)'
        );
        $insert->execute(['user_id' => $userId, 'book_id' => $bookId]);

        return ['is_favorite' => true, 'book_id' => $bookId];
    }

    public function toggleReadingList(int $userId, array $bookData): array
    {
        if (!$this->hasReadingListTable()) {
            return ['in_reading_list' => false, 'book_id' => null];
        }

        $bookId = $this->ensureBook($bookData);

        $stmt = $this->db->prepare(
            'SELECT id_lista_lectura FROM lista_lectura WHERE id_usuario = :user_id AND id_libro = :book_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'book_id' => $bookId]);
        $readingListId = $stmt->fetchColumn();

        if ($readingListId) {
            $delete = $this->db->prepare('DELETE FROM lista_lectura WHERE id_lista_lectura = :id');
            $delete->execute(['id' => $readingListId]);
            return ['in_reading_list' => false, 'book_id' => $bookId];
        }

        $insert = $this->db->prepare(
            'INSERT INTO lista_lectura (id_usuario, id_libro) VALUES (:user_id, :book_id)'
        );
        $insert->execute(['user_id' => $userId, 'book_id' => $bookId]);

        return ['in_reading_list' => true, 'book_id' => $bookId];
    }

    public function isFavorite(int $userId, array $bookData): bool
    {
        if (!$this->hasFavoriteTable()) {
            return false;
        }

        $bookId = $this->findBookId($bookData);
        if ($bookId === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM favoritos WHERE id_usuario = :user_id AND id_libro = :book_id'
        );
        $stmt->execute(['user_id' => $userId, 'book_id' => $bookId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function isInReadingList(int $userId, array $bookData): bool
    {
        if (!$this->hasReadingListTable()) {
            return false;
        }

        $bookId = $this->findBookId($bookData);
        if ($bookId === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM lista_lectura WHERE id_usuario = :user_id AND id_libro = :book_id'
        );
        $stmt->execute(['user_id' => $userId, 'book_id' => $bookId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function saveProgress(int $userId, array $bookData, int $page, int $totalPages): bool
    {
        if (!$this->hasProgressTable()) {
            return false;
        }

        $bookId = $this->ensureBook($bookData);
        $page = max(0, $page);
        $totalPages = max(1, $totalPages);
        $percentage = min(100, max(0, round(($page / $totalPages) * 100, 2)));

        $select = $this->db->prepare(
            'SELECT id_progreso
             FROM progreso_lectura
             WHERE id_usuario = :user_id AND id_libro = :book_id
             ORDER BY id_progreso DESC
             LIMIT 1'
        );
        $select->execute(['user_id' => $userId, 'book_id' => $bookId]);
        $progressId = $select->fetchColumn();

        if ($progressId) {
            $update = $this->db->prepare(
                'UPDATE progreso_lectura
                 SET pagina_actual = :page, porcentaje = :percentage
                 WHERE id_progreso = :id'
            );
            return $update->execute([
                'page' => $page,
                'percentage' => $percentage,
                'id' => $progressId,
            ]);
        }

        $insert = $this->db->prepare(
            'INSERT INTO progreso_lectura (id_usuario, id_libro, pagina_actual, porcentaje)
             VALUES (:user_id, :book_id, :page, :percentage)'
        );

        return $insert->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'page' => $page,
            'percentage' => $percentage,
        ]);
    }

    public function getFavoritesByUser(int $userId): array
    {
        if (!$this->hasFavoriteTable()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT l.titulo, l.autor, l.descripcion, l.portada, l.archivo, l.tipo, l.fecha_publicado,
                    c.nombre AS categoria, p.nombre AS plan_nombre
             FROM favoritos f
             INNER JOIN libros l ON l.id_libro = f.id_libro
             LEFT JOIN categorias c ON c.id_categoria = l.id_categoria
             LEFT JOIN suscripciones s ON s.id_usuario = :user_id AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             WHERE f.id_usuario = :user_id
             ORDER BY f.fecha_agregado DESC
             LIMIT 12'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getReadingListByUser(int $userId): array
    {
        if (!$this->hasReadingListTable()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT l.titulo, l.autor, l.descripcion, l.portada, l.archivo, l.tipo, l.fecha_publicado,
                    c.nombre AS categoria, p.nombre AS plan_nombre
             FROM lista_lectura ll
             INNER JOIN libros l ON l.id_libro = ll.id_libro
             LEFT JOIN categorias c ON c.id_categoria = l.id_categoria
             LEFT JOIN suscripciones s ON s.id_usuario = :user_id AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             WHERE ll.id_usuario = :user_id
             ORDER BY ll.fecha_agregado DESC
             LIMIT 12'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getRecentProgressByUser(int $userId): array
    {
        if (!$this->hasProgressTable()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT l.titulo, l.autor, l.descripcion, l.portada, l.archivo, l.tipo, l.fecha_publicado,
                    c.nombre AS categoria, p.porcentaje, p.pagina_actual, p.fecha_actualizacion,
                    pl.nombre AS plan_nombre
             FROM progreso_lectura p
             INNER JOIN libros l ON l.id_libro = p.id_libro
             LEFT JOIN categorias c ON c.id_categoria = l.id_categoria
             LEFT JOIN suscripciones s ON s.id_usuario = :user_id AND s.estado = "activa"
             LEFT JOIN planes pl ON pl.id_plan = s.id_plan
             WHERE p.id_usuario = :user_id
             ORDER BY p.fecha_actualizacion DESC
             LIMIT 12'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getProgressStatus(int $userId, array $bookData): array
    {
        if (!$this->hasProgressTable()) {
            return ['has_progress' => false, 'percentage' => 0.0];
        }

        $bookId = $this->findBookId($bookData);
        if ($bookId === null) {
            return ['has_progress' => false, 'percentage' => 0.0];
        }

        $stmt = $this->db->prepare(
            'SELECT porcentaje
             FROM progreso_lectura
             WHERE id_usuario = :user_id AND id_libro = :book_id
             ORDER BY id_progreso DESC
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
        ]);

        $percentage = (float) ($stmt->fetchColumn() ?: 0);

        return [
            'has_progress' => $percentage > 0,
            'percentage' => $percentage,
        ];
    }

    private function ensureBook(array $bookData): int
    {
        $existing = $this->findBookId($bookData);
        if ($existing !== null) {
            return $existing;
        }

        $categoriaId = $this->getDefaultCategoryId();

        $stmt = $this->db->prepare(
            'INSERT INTO libros (titulo, autor, descripcion, archivo, tipo, id_categoria)
             VALUES (:titulo, :autor, :descripcion, :archivo, :tipo, :id_categoria)'
        );

        $title = trim((string) ($bookData['title'] ?? 'Libro sin titulo'));
        $author = trim((string) ($bookData['author'] ?? 'Autor no especificado'));
        $description = trim((string) ($bookData['description'] ?? 'Obra disponible en Hogwarts.'));
        $file = trim((string) ($bookData['file'] ?? ''));
        $type = trim((string) ($bookData['type'] ?? 'pdf'));

        if (!in_array($type, ['fisico', 'digital', 'audiolibro', 'pdf', 'epub'], true)) {
            $type = 'pdf';
        }

        $stmt->execute([
            'titulo' => $title !== '' ? $title : 'Libro sin titulo',
            'autor' => $author,
            'descripcion' => $description,
            'archivo' => $file,
            'tipo' => $type,
            'id_categoria' => $categoriaId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function findBookId(array $bookData): ?int
    {
        $file = trim((string) ($bookData['file'] ?? ''));
        if ($file !== '') {
            $stmt = $this->db->prepare(
                'SELECT id_libro FROM libros WHERE archivo = :archivo ORDER BY id_libro DESC LIMIT 1'
            );
            $stmt->execute(['archivo' => $file]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
        }

        $title = trim((string) ($bookData['title'] ?? ''));
        $author = trim((string) ($bookData['author'] ?? ''));
        if ($title !== '') {
            $stmt = $this->db->prepare(
                'SELECT id_libro
                 FROM libros
                 WHERE titulo = :titulo AND autor = :autor
                 ORDER BY id_libro DESC
                 LIMIT 1'
            );
            $stmt->execute([
                'titulo' => $title,
                'autor' => $author,
            ]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function getDefaultCategoryId(): int
    {
        $stmt = $this->db->query('SELECT id_categoria FROM categorias ORDER BY id_categoria ASC LIMIT 1');
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $this->db->exec("INSERT INTO categorias (nombre, descripcion) VALUES ('General', 'Categoria general')");
        return (int) $this->db->lastInsertId();
    }

    private function ensureInteractionTables(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS favoritos (
                id_favorito INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                id_libro INT NOT NULL,
                fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_favorito_usuario_libro (id_usuario, id_libro),
                INDEX idx_favoritos_usuario (id_usuario),
                INDEX idx_favoritos_libro (id_libro)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS lista_lectura (
                id_lista_lectura INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                id_libro INT NOT NULL,
                fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_lista_usuario_libro (id_usuario, id_libro),
                INDEX idx_lista_usuario (id_usuario),
                INDEX idx_lista_libro (id_libro)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS progreso_lectura (
                id_progreso INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                id_libro INT NOT NULL,
                porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0,
                pagina_actual INT NOT NULL DEFAULT 1,
                fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_progreso_usuario_libro (id_usuario, id_libro),
                INDEX idx_progreso_usuario (id_usuario),
                INDEX idx_progreso_libro (id_libro)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function hasReadingListTable(): bool
    {
        if ($this->readingListTableExists !== null) {
            return $this->readingListTableExists;
        }

        $stmt = $this->db->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => 'lista_lectura']);
        $this->readingListTableExists = (bool) $stmt->fetchColumn();

        return $this->readingListTableExists;
    }

    private function hasFavoriteTable(): bool
    {
        if ($this->favoriteTableExists !== null) {
            return $this->favoriteTableExists;
        }

        $stmt = $this->db->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => 'favoritos']);
        $this->favoriteTableExists = (bool) $stmt->fetchColumn();

        return $this->favoriteTableExists;
    }

    private function hasProgressTable(): bool
    {
        if ($this->progressTableExists !== null) {
            return $this->progressTableExists;
        }

        $stmt = $this->db->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => 'progreso_lectura']);
        $this->progressTableExists = (bool) $stmt->fetchColumn();

        return $this->progressTableExists;
    }
}
