<?php

require_once __DIR__ . '/Database.php';

class CatalogoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ─────────────────────────────────────────────
    //  LIBROS
    // ─────────────────────────────────────────────

    /**
     * Devuelve todos los libros con join de categoría.
     * Acepta filtros opcionales: búsqueda por texto, categoría y tipo.
     */
    public function getAllBooks(string $search = '', int $categoriaId = 0, string $tipo = ''): array
    {
        $conditions = ['1=1'];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(l.titulo LIKE :search OR l.autor LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($categoriaId > 0) {
            $conditions[] = 'l.id_categoria = :id_categoria';
            $params['id_categoria'] = $categoriaId;
        }

        if ($tipo !== '') {
            $conditions[] = 'l.tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->db->prepare(
            "SELECT l.*, c.nombre AS categoria_nombre
             FROM libros l
             INNER JOIN categorias c ON c.id_categoria = l.id_categoria
             WHERE {$where}
             ORDER BY l.id_libro DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Devuelve un libro por ID.
     */
    public function findBook(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, c.nombre AS categoria_nombre
             FROM libros l
             INNER JOIN categorias c ON c.id_categoria = l.id_categoria
             WHERE l.id_libro = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Actualiza un libro existente.
     * Solo toca los campos que gestiona el catálogo.
     */
    public function updateBook(int $id, array $data): bool
    {
        // Si se sube un nuevo archivo PDF lo procesamos
        $archivo = $this->handlePdfUpload() ?? $this->nullableString($data['archivo'] ?? null);
        $portada  = $this->handleImageUpload('portada_file', 'portadas') ?? $this->nullableString($data['portada'] ?? null);

        $tipo = $data['tipo'] ?? 'digital';
        $allowedTipos = ['fisico', 'digital', 'audiolibro', 'pdf', 'epub'];
        if (!in_array($tipo, $allowedTipos, true)) {
            $tipo = 'digital';
        }

        $fecha = trim((string) ($data['fecha_publicado'] ?? ''));

        $stmt = $this->db->prepare(
            'UPDATE libros SET
                titulo          = :titulo,
                autor           = :autor,
                descripcion     = :descripcion,
                id_categoria    = :id_categoria,
                tipo            = :tipo,
                isbn            = :isbn,
                doi             = :doi,
                fecha_publicado = :fecha_publicado,
                portada         = :portada,
                archivo         = :archivo
             WHERE id_libro = :id'
        );

        return $stmt->execute([
            'titulo'          => trim((string) ($data['titulo'] ?? '')),
            'autor'           => trim((string) ($data['autor'] ?? '')),
            'descripcion'     => $this->nullableString($data['descripcion'] ?? null),
            'id_categoria'    => (int) ($data['id_categoria'] ?? 1),
            'tipo'            => $tipo,
            'isbn'            => $this->nullableString($data['isbn'] ?? null),
            'doi'             => $this->nullableString($data['doi'] ?? null),
            'fecha_publicado' => $fecha === '' ? null : $fecha,
            'portada'         => $portada,
            'archivo'         => $archivo,
            'id'              => $id,
        ]);
    }

    /**
     * Elimina un libro por ID.
     */
    public function deleteBook(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM libros WHERE id_libro = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ─────────────────────────────────────────────
    //  CATEGORÍAS
    // ─────────────────────────────────────────────

    /**
     * Lista todas las categorías con el conteo de libros asociados.
     */
    public function getAllCategories(string $search = ''): array
    {
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = 'WHERE c.nombre LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT
                c.id_categoria,
                c.nombre,
                c.descripcion,
                COUNT(l.id_libro) AS total_libros
             FROM categorias c
             LEFT JOIN libros l ON l.id_categoria = c.id_categoria
             {$where}
             GROUP BY c.id_categoria, c.nombre, c.descripcion
             ORDER BY c.nombre ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Devuelve una categoría por ID.
     */
    public function findCategory(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM categorias WHERE id_categoria = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Crea una nueva categoría.
     */
    public function createCategory(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categorias (nombre, descripcion)
             VALUES (:nombre, :descripcion)'
        );
        $stmt->execute([
            'nombre'      => trim((string) ($data['nombre'] ?? '')),
            'descripcion' => $this->nullableString($data['descripcion'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza una categoría existente.
     */
    public function updateCategory(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categorias
             SET nombre      = :nombre,
                 descripcion = :descripcion
             WHERE id_categoria = :id'
        );

        return $stmt->execute([
            'nombre'      => trim((string) ($data['nombre'] ?? '')),
            'descripcion' => $this->nullableString($data['descripcion'] ?? null),
            'id'          => $id,
        ]);
    }

    /**
     * Elimina una categoría (solo si no tiene libros asociados).
     */
    public function deleteCategory(int $id): bool
    {
        // Verificar que no tenga libros
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM libros WHERE id_categoria = :id'
        );
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar: la categoría tiene libros asociados.');
        }

        $stmt = $this->db->prepare('DELETE FROM categorias WHERE id_categoria = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Devuelve todas las categorías (para selects/filtros).
     */
    public function getCategoryOptions(): array
    {
        $stmt = $this->db->query(
            'SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC'
        );
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────
    //  HELPERS PRIVADOS
    // ─────────────────────────────────────────────

    private function nullableString($value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    /**
     * Maneja la subida de un PDF nuevo para el libro.
     * Retorna la ruta relativa o null si no se subió nada.
     */
    private function handlePdfUpload(): ?string
    {
        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES['archivo_pdf'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al subir el PDF (código ' . $file['error'] . ').');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new RuntimeException('Solo se permiten archivos PDF.');
        }

        if ((int) $file['size'] > 50 * 1024 * 1024) {
            throw new RuntimeException('El PDF no puede superar los 50MB.');
        }

        $dir = __DIR__ . '/../../assets/books';
        $this->ensureWritable($dir);

        $base     = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo((string) $file['name'], PATHINFO_FILENAME));
        $fileName = trim((string) $base, '_') . '_' . date('Ymd_His') . '.pdf';
        $dest     = $dir . '/' . $fileName;

        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new RuntimeException('No se pudo guardar el PDF en disco.');
        }

        return 'assets/books/' . $fileName;
    }

    /**
     * Maneja la subida de una imagen (portada, etc.).
     * Retorna la ruta relativa o null si no se subió nada.
     */
    private function handleImageUpload(string $inputName, string $subfolder): ?string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$inputName];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Error al subir la imagen '{$inputName}' (código " . $file['error'] . ').');
        }

        $ext      = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.');
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException("La imagen '{$inputName}' no puede superar los 5MB.");
        }

        $dir = __DIR__ . '/../../assets/' . $subfolder;
        $this->ensureWritable($dir);

        $base     = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo((string) $file['name'], PATHINFO_FILENAME));
        $fileName = trim((string) $base, '_') . '_' . date('Ymd_His') . '.' . $ext;
        $dest     = $dir . '/' . $fileName;

        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new RuntimeException("No se pudo guardar la imagen en disco.");
        }

        return 'assets/' . $subfolder . '/' . $fileName;
    }

    private function ensureWritable(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("El directorio no tiene permisos de escritura: {$dir}");
        }
    }
}