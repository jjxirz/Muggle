<?php

require_once __DIR__ . '/Database.php';

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function totalLibros(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM libros')->fetchColumn();
    }

    public function totalUsuariosActivos(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE estado = "activo"'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function totalPremium(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM suscripciones s
             JOIN planes p ON p.id_plan = s.id_plan
             WHERE s.estado = "activa" AND LOWER(p.nombre) = "premium"'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function lecturasHoy(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM progreso_lectura
             WHERE DATE(fecha_actualizacion) = CURDATE()'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function librosPorCategoria(): array
    {
        $stmt = $this->db->query(
            'SELECT c.nombre, COUNT(l.id_libro) AS cantidad
             FROM categorias c
             LEFT JOIN libros l ON l.id_categoria = c.id_categoria
             GROUP BY c.id_categoria
             ORDER BY cantidad DESC
             LIMIT 6'
        );
        $rows = $stmt->fetchAll();
        $max  = !empty($rows) ? max(array_column($rows, 'cantidad')) : 1;
        $max  = $max ?: 1;

        return array_map(fn($r) => [
            'nombre'   => $r['nombre'],
            'cantidad' => (int) $r['cantidad'],
            'pct'      => (int) round($r['cantidad'] / $max * 100),
        ], $rows);
    }

    public function distribucionPlanes(): array
    {
        $stmt = $this->db->query(
            'SELECT p.nombre,
                    COUNT(s.id_suscripcion) AS cantidad
             FROM planes p
             LEFT JOIN suscripciones s ON s.id_plan = p.id_plan AND s.estado = "activa"
             GROUP BY p.id_plan
             ORDER BY p.precio DESC'
        );
        $rows  = $stmt->fetchAll();
        $total = array_sum(array_column($rows, 'cantidad'));

        $colores = ['#111110', '#888780', '#B4B2A9', '#D3D1C7'];
        $result  = [];
        foreach ($rows as $i => $row) {
            $pct      = $total > 0 ? round($row['cantidad'] / $total * 100) : 0;
            $result[] = [
                'nombre'   => $row['nombre'],
                'pct'      => $pct,
                'color'    => $colores[$i] ?? '#D3D1C7',
            ];
        }
        return $result;
    }

    public function librosTopSemana(): array
    {
        // Usa progreso_lectura para top real; si está vacío devuelve []
        $stmt = $this->db->query(
            'SELECT l.titulo, l.autor,
                    c.nombre AS categoria,
                    COUNT(pl.id_progreso) AS lecturas,
                    COALESCE(p.nombre, "free") AS plan_nombre
             FROM progreso_lectura pl
             JOIN libros l ON l.id_libro = pl.id_libro
             LEFT JOIN categorias c ON c.id_categoria = l.id_categoria
             LEFT JOIN suscripciones s ON s.id_usuario = pl.id_usuario AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             WHERE pl.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY l.id_libro
             ORDER BY lecturas DESC
             LIMIT 5'
        );
        $rows   = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $i => $row) {
            $slug = strtolower(str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $row['plan_nombre']));
            $result[] = [
                'pos'       => $i + 1,
                'titulo'    => $row['titulo'],
                'autor'     => $row['autor'],
                'categoria' => $row['categoria'],
                'lecturas'  => (int) $row['lecturas'],
                'plan'      => $slug,
            ];
        }
        return $result;
    }
}
