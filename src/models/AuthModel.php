<?php

require_once __DIR__ . '/Database.php';

class AuthModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensurePreferenceColumns();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id_usuario,
                    u.nombre,
                    u.email,
                    u.password,
                    u.estado,
                    u.id_rol,
                    u.tema_habilitado,
                    u.casa_preferida,
                    r.nombre AS rol_nombre,
                    p.nombre AS plan_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findUserById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id_usuario,
                    u.nombre,
                    u.email,
                    u.estado,
                    u.id_rol,
                    u.tema_habilitado,
                    u.casa_preferida,
                    u.fecha_registro,
                    r.nombre AS rol_nombre,
                    s.id_plan AS plan_id,
                    p.nombre AS plan_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             WHERE u.id_usuario = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePreferences(int $id, bool $themeEnabled, string $house): bool
    {
        $houses = ['ravenclaw', 'gryffindor', 'slytherin', 'hufflepuff'];
        if (!in_array($house, $houses, true)) {
            $house = 'ravenclaw';
        }

        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET tema_habilitado = :theme, casa_preferida = :house
             WHERE id_usuario = :id'
        );

        return $stmt->execute([
            'theme' => $themeEnabled ? 1 : 0,
            'house' => $house,
            'id' => $id,
        ]);
    }

    public function getAvailablePlans(): array
    {
        $stmt = $this->db->query(
            'SELECT id_plan, nombre, precio, descripcion
             FROM planes
             ORDER BY precio ASC, id_plan ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public function assignPlanToUser(int $userId, int $planId): bool
    {
        if ($userId <= 0 || $planId <= 0) {
            return false;
        }

        $checkPlan = $this->db->prepare('SELECT id_plan FROM planes WHERE id_plan = :id LIMIT 1');
        $checkPlan->execute(['id' => $planId]);

        if (!$checkPlan->fetch()) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $cancel = $this->db->prepare(
                'UPDATE suscripciones
                 SET estado = "cancelada", fecha_fin = COALESCE(fecha_fin, CURDATE())
                 WHERE id_usuario = :id_usuario AND estado = "activa"'
            );
            $cancel->execute(['id_usuario' => $userId]);

            $insert = $this->db->prepare(
                'INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, estado)
                 VALUES (:id_usuario, :id_plan, CURDATE(), "activa")'
            );

            $insert->execute([
                'id_usuario' => $userId,
                'id_plan' => $planId,
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    private function ensurePreferenceColumns(): void
    {
        if (!$this->columnExists('usuarios', 'tema_habilitado')) {
            $this->db->exec('ALTER TABLE usuarios ADD COLUMN tema_habilitado TINYINT(1) NOT NULL DEFAULT 1');
        }

        if (!$this->columnExists('usuarios', 'casa_preferida')) {
            $this->db->exec("ALTER TABLE usuarios ADD COLUMN casa_preferida VARCHAR(20) NOT NULL DEFAULT 'ravenclaw'");
        }
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
