<?php

require_once __DIR__ . '/Database.php';

class BookModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureAdminTables();
    }

    private function ensureAdminTables(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS categorias (
                id_categoria INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(150) NOT NULL UNIQUE,
                descripcion TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS banners (
                id_banner INT AUTO_INCREMENT PRIMARY KEY,
                imagen VARCHAR(500) NOT NULL,
                fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS libros (
                id_libro INT AUTO_INCREMENT PRIMARY KEY,
                isbn VARCHAR(20) NULL UNIQUE,
                doi VARCHAR(100) NULL UNIQUE,
                titulo VARCHAR(255) NOT NULL,
                autor VARCHAR(255) NULL,
                descripcion TEXT NULL,
                portada VARCHAR(500) NULL,
                archivo VARCHAR(500) NULL,
                tipo ENUM('fisico', 'digital', 'audiolibro', 'pdf', 'epub') NOT NULL DEFAULT 'digital',
                id_categoria INT NOT NULL,
                id_banner INT NULL,
                fecha_publicado DATE NULL,
                CONSTRAINT fk_libros_categoria
                    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT,
                CONSTRAINT fk_libros_banner
                    FOREIGN KEY (id_banner) REFERENCES banners(id_banner)
                    ON UPDATE CASCADE
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        if (!$this->columnExists('libros', 'id_banner')) {
            $this->db->exec('ALTER TABLE libros ADD COLUMN id_banner INT NULL AFTER id_categoria');
        }

        if (!$this->constraintExists('libros', 'fk_libros_banner')) {
            $this->db->exec(
                'ALTER TABLE libros
                 ADD CONSTRAINT fk_libros_banner
                 FOREIGN KEY (id_banner) REFERENCES banners(id_banner)
                 ON UPDATE CASCADE
                 ON DELETE SET NULL'
            );
        }

        $this->db->exec(
            "INSERT IGNORE INTO categorias (id_categoria, nombre, descripcion)
             VALUES (1, 'General', 'Categoria general')"
        );

        $this->migrateLegacyUppercaseTables();
    }

    private function migrateLegacyUppercaseTables(): void
    {
        if ($this->tableExists('CATEGORIAS') && $this->isTableEmpty('categorias')) {
            $this->db->exec(
                "INSERT INTO categorias (id_categoria, nombre, descripcion)
                 SELECT id_categoria, nombre, descripcion FROM CATEGORIAS"
            );
        }

        if ($this->tableExists('LIBROS') && $this->isTableEmpty('libros')) {
            $this->db->exec(
                "INSERT INTO libros (titulo, autor, descripcion, portada, archivo, tipo, id_categoria, fecha_publicado)
                 SELECT
                    titulo,
                    autor,
                    descripcion,
                    portada,
                    archivo,
                    CASE
                        WHEN tipo IN ('audiolibro', 'digital', 'fisico', 'pdf', 'epub') THEN tipo
                        ELSE 'digital'
                    END,
                    COALESCE(id_categoria, 1),
                    fecha_publicado
                 FROM LIBROS"
            );
        }
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name'
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

    private function constraintExists(string $tableName, string $constraintName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.table_constraints
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND constraint_name = :constraint_name'
        );
        $stmt->execute([
            'table_name' => $tableName,
            'constraint_name' => $constraintName,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function isTableEmpty(string $tableName): bool
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$tableName}");
        return (int) $stmt->fetchColumn() === 0;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT l.*, c.nombre AS categoria, b.imagen AS banner_imagen
             FROM libros l
             INNER JOIN categorias c ON c.id_categoria = l.id_categoria
             LEFT JOIN banners b ON b.id_banner = l.id_banner
             ORDER BY l.id_libro DESC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, b.imagen AS banner_imagen
             FROM libros l
             LEFT JOIN banners b ON b.id_banner = l.id_banner
             WHERE l.id_libro = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $book = $stmt->fetch();

        return $book ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO libros (isbn, doi, titulo, autor, descripcion, portada, archivo, tipo, id_categoria, id_banner, fecha_publicado)
             VALUES (:isbn, :doi, :titulo, :autor, :descripcion, :portada, :archivo, :tipo, :id_categoria, :id_banner, :fecha_publicado)'
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
                 id_banner = :id_banner,
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

    public function allBanners(): array
    {
        $stmt = $this->db->query(
            'SELECT *
             FROM banners
             ORDER BY id_banner DESC'
        );

        return $stmt->fetchAll();
    }

    public function findBanner(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM banners WHERE id_banner = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $banner = $stmt->fetch();

        return $banner ?: null;
    }

    public function createBanner(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO banners (imagen)
             VALUES (:imagen)'
        );

        $stmt->execute([
            'imagen' => trim((string) ($data['imagen'] ?? '')),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateBanner(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE banners
             SET imagen = :imagen
             WHERE id_banner = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'imagen' => trim((string) ($data['imagen'] ?? '')),
        ]);
    }

    public function deleteBanner(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM banners WHERE id_banner = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function saveBannerForBook(int $bookId, string $imagePath): void
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return;
        }

        $book = $this->find($bookId);
        if ($book === null) {
            throw new RuntimeException('No se encontro el libro para asociar su banner.');
        }

        $existingBannerId = isset($book['id_banner']) ? (int) $book['id_banner'] : 0;
        if ($existingBannerId > 0) {
            $this->updateBanner($existingBannerId, ['imagen' => $imagePath]);
            return;
        }

        $bannerId = $this->createBanner(['imagen' => $imagePath]);
        $stmt = $this->db->prepare('UPDATE libros SET id_banner = :id_banner WHERE id_libro = :id_libro');
        $stmt->execute([
            'id_banner' => $bannerId,
            'id_libro' => $bookId,
        ]);
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
            'id_banner' => $this->nullableInt($data['id_banner'] ?? null),
            'fecha_publicado' => $fecha === '' ? null : $fecha,
        ];
    }

    private function nullableString($value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $intValue = (int) $value;
        return $intValue > 0 ? $intValue : null;
    }

}
