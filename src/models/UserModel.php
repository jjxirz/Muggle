<?php

require_once __DIR__ . '/Database.php';

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTables();
    }

    // ── Garantiza que los planes base existan ──────────────────────────────
    private function ensureTables(): void
    {
        $this->db->exec(
            "INSERT IGNORE INTO planes (id_plan, nombre, precio, descripcion) VALUES
             (1, 'Free',    0.00,  'Acceso a libros gratuitos'),
             (2, 'Básico',  4.99,  'Catálogo básico'),
             (3, 'Plus',    8.99,  'Catálogo ampliado con audiolibros parcial'),
             (4, 'Premium', 13.99, 'Catálogo completo con audiolibros')"
        );
    }

    // ── USUARIOS ───────────────────────────────────────────────────────────

    public function allUsuarios(): array
    {
        $stmt = $this->db->query(
            'SELECT u.*,
                    r.nombre AS rol_nombre,
                    p.nombre AS plan_nombre,
                    s.estado AS suscripcion_estado,
                    s.fecha_inicio,
                    s.fecha_fin
             FROM usuarios u
             LEFT JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             ORDER BY u.fecha_registro DESC'
        );
        return $stmt->fetchAll();
    }

    public function findUsuario(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, s.id_plan, s.estado AS suscripcion_estado
             FROM usuarios u
             LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = "activa"
             WHERE u.id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createUsuario(array $data): int
    {
        // Insertar usuario
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password, estado, id_rol)
             VALUES (:nombre, :email, :password, :estado, :id_rol)'
        );
        $stmt->execute([
            'nombre'   => trim($data['nombre']),
            'email'    => trim($data['email']),
            'password' => !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null,
            'estado'   => $data['estado'] ?? 'activo',
            'id_rol'   => (int) ($data['id_rol'] ?? 2),
        ]);
        $idUsuario = (int) $this->db->lastInsertId();

        // Asignar plan si se indicó
        $idPlan = (int) ($data['id_plan'] ?? 0);
        if ($idPlan > 0) {
            $this->asignarPlan($idUsuario, $idPlan);
        }

        return $idUsuario;
    }

    public function updateEstado(int $id, string $estado): bool
    {
        $allowed = ['activo', 'inactivo', 'baneado'];
        if (!in_array($estado, $allowed, true)) return false;

        $stmt = $this->db->prepare(
            'UPDATE usuarios SET estado = :estado WHERE id_usuario = :id'
        );
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public function deleteUsuario(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ── PLANES ─────────────────────────────────────────────────────────────

    public function allPlanes(): array
    {
        $stmt = $this->db->query(
            'SELECT p.*,
                    COUNT(s.id_suscripcion) AS total_usuarios
             FROM planes p
             LEFT JOIN suscripciones s ON s.id_plan = p.id_plan AND s.estado = "activa"
             GROUP BY p.id_plan
             ORDER BY p.precio ASC'
        );
        return $stmt->fetchAll();
    }

    // ── SUSCRIPCIONES ──────────────────────────────────────────────────────

    private function asignarPlan(int $idUsuario, int $idPlan): void
    {
        // Cancela suscripción activa anterior si existe
        $this->db->prepare(
            'UPDATE suscripciones SET estado = "cancelada"
             WHERE id_usuario = :id AND estado = "activa"'
        )->execute(['id' => $idUsuario]);

        $stmt = $this->db->prepare(
            'INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, estado)
             VALUES (:id_usuario, :id_plan, CURDATE(), "activa")'
        );
        $stmt->execute(['id_usuario' => $idUsuario, 'id_plan' => $idPlan]);
    }

    // ── STATS para dashboard ───────────────────────────────────────────────

    public function totalUsuarios(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public function totalUsuariosActivos(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE estado = "activo"'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function totalPorPlan(string $plan): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM suscripciones s
             JOIN planes p ON p.id_plan = s.id_plan
             WHERE s.estado = "activa" AND LOWER(p.nombre) = LOWER(:plan)'
        );
        $stmt->execute(['plan' => $plan]);
        return (int) $stmt->fetchColumn();
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
                'cantidad' => (int) $row['cantidad'],
                'pct'      => $pct,
                'color'    => $colores[$i] ?? '#D3D1C7',
            ];
        }
        return $result;
    }
}
