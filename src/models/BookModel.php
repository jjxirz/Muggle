<?php

require_once __DIR__ . '/Database.php';

class BookModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT l.*, c.nombre AS categoria
             FROM libros l
             INNER JOIN categorias c ON c.id_categoria = l.id_categoria
             ORDER BY l.id_libro DESC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM libros WHERE id_libro = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $book = $stmt->fetch();

        return $book ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO libros (isbn, doi, titulo, autor, descripcion, portada, archivo, tipo, id_categoria, fecha_publicado)
             VALUES (:isbn, :doi, :titulo, :autor, :descripcion, :portada, :archivo, :tipo, :id_categoria, :fecha_publicado)'
        );

        $stmt->execute($this->sanitizeData($data));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->sanitizeData($data);
        $payload['id'] = $id;

        $stmt = $this->db->prepare(
            'UPDATE libros
             SET isbn = :isbn,
                 doi = :doi,
                 titulo = :titulo,
                 autor = :autor,
                 descripcion = :descripcion,
                 portada = :portada,
                 archivo = :archivo,
                 tipo = :tipo,
                 id_categoria = :id_categoria,
                 fecha_publicado = :fecha_publicado
             WHERE id_libro = :id'
        );

        return $stmt->execute($payload);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM libros WHERE id_libro = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function categories(): array
    {
        $stmt = $this->db->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    private function sanitizeData(array $data): array
    {
        $tipo = $data['tipo'] ?? 'digital';
        $allowedTypes = ['fisico', 'digital', 'audiolibro'];
        if (!in_array($tipo, $allowedTypes, true)) {
            $tipo = 'digital';
        }

        $fecha = trim((string) ($data['fecha_publicado'] ?? ''));

        return [
            'isbn' => $this->nullableString($data['isbn'] ?? null),
            'doi' => $this->nullableString($data['doi'] ?? null),
            'titulo' => trim((string) ($data['titulo'] ?? '')),
            'autor' => trim((string) ($data['autor'] ?? '')),
            'descripcion' => $this->nullableString($data['descripcion'] ?? null),
            'portada' => $this->nullableString($data['portada'] ?? null),
            'archivo' => $this->nullableString($data['archivo'] ?? null),
            'tipo' => $tipo,
            'id_categoria' => (int) ($data['id_categoria'] ?? 1),
            'fecha_publicado' => $fecha === '' ? null : $fecha,
        ];
    }

    private function nullableString($value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }
}
