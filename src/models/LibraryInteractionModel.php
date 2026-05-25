<?php

require_once __DIR__ . '/Database.php';

class LibraryInteractionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function toggleFavorite(int $userId, array $bookData): array
    {
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

    public function isFavorite(int $userId, array $bookData): bool
    {
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

    public function saveProgress(int $userId, array $bookData, int $page, int $totalPages): bool
    {
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
        $stmt = $this->db->prepare(
            'SELECT l.titulo, l.autor, c.nombre AS categoria
             FROM favoritos f
             INNER JOIN libros l ON l.id_libro = f.id_libro
             LEFT JOIN categorias c ON c.id_categoria = l.id_categoria
             WHERE f.id_usuario = :user_id
             ORDER BY f.fecha_agregado DESC
             LIMIT 12'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getRecentProgressByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.titulo, l.autor, p.porcentaje, p.pagina_actual, p.fecha_actualizacion
             FROM progreso_lectura p
             INNER JOIN libros l ON l.id_libro = p.id_libro
             WHERE p.id_usuario = :user_id
             ORDER BY p.fecha_actualizacion DESC
             LIMIT 12'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
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
}
