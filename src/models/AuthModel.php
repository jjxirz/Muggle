<?php

require_once __DIR__ . '/Database.php';

class AuthModel
{
    private PDO $db;
    private bool $hasPictureColumn;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensurePreferenceColumns();
        $this->ensureAuthColumns();
        $this->ensurePictureColumn();
        $this->ensureTrialPlan();
        $this->hasPictureColumn = $this->columnExists('usuarios', 'google_picture_url');
    }

    private function getUserSelectSQL(): string
    {
        $pictureCol = $this->hasPictureColumn ? ', u.google_picture_url' : '';
        return "SELECT u.id_usuario,
                    u.nombre,
                    u.email,
                    u.password,
                    u.estado,
                    u.id_rol,
                    u.tema_habilitado,
                    u.casa_preferida,
                    u.auth_provider,
                    u.google_sub{$pictureCol},
                    u.prueba_7d_usada,
                    r.nombre AS rol_nombre,
                    p.nombre AS plan_nombre";
    }

    private function getUserSelectSQLWithoutPassword(): string
    {
        $pictureCol = $this->hasPictureColumn ? ', u.google_picture_url' : '';
        return "SELECT u.id_usuario,
                    u.nombre,
                    u.email,
                    u.estado,
                    u.id_rol,
                    u.tema_habilitado,
                    u.casa_preferida,
                    u.auth_provider,
                    u.google_sub{$pictureCol},
                    u.prueba_7d_usada,
                    u.fecha_registro,
                    r.nombre AS rol_nombre,
                    s.id_plan AS plan_id,
                    p.nombre AS plan_nombre";
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            $this->getUserSelectSQL() . '
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

    public function findUserByGoogleSub(string $googleSub): ?array
    {
        $googleSub = trim($googleSub);
        if ($googleSub === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            $this->getUserSelectSQL() . '
             FROM usuarios u
             LEFT JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = "activa"
             LEFT JOIN planes p ON p.id_plan = s.id_plan
             WHERE u.google_sub = :google_sub
             LIMIT 1'
        );
        $stmt->execute(['google_sub' => $googleSub]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findUserById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            $this->getUserSelectSQLWithoutPassword() . '
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

    public function resolveOrCreateGoogleUser(string $googleSub, string $email, string $name, string $picture = ''): ?array
    {
        $googleSub = trim($googleSub);
        $email = trim(strtolower($email));
        $name = trim($name);
        $picture = trim($picture);

        if ($googleSub === '' || $email === '') {
            return null;
        }

        if ($name === '') {
            $name = 'Usuario Google';
        }

        $existingBySub = $this->findUserByGoogleSub($googleSub);
        if ($existingBySub !== null) {
            // Update picture if provided and column exists
            if ($picture !== '' && $this->hasPictureColumn) {
                $stmt = $this->db->prepare(
                    'UPDATE usuarios
                     SET google_picture_url = :picture
                     WHERE id_usuario = :id_usuario'
                );
                $stmt->execute([
                    'picture' => $picture,
                    'id_usuario' => (int) $existingBySub['id_usuario'],
                ]);
            }
            return $this->findUserById((int) $existingBySub['id_usuario']);
        }

        $existingByEmail = $this->findUserByEmail($email);
        if ($existingByEmail !== null) {
            $role = strtolower((string) ($existingByEmail['rol_nombre'] ?? 'usuario'));
            if ($role !== 'admin') {
                $updateSQL = 'UPDATE usuarios SET google_sub = :google_sub, auth_provider = "google"';
                $updateParams = [
                    'google_sub' => $googleSub,
                    'id_usuario' => (int) $existingByEmail['id_usuario'],
                ];

                if ($picture !== '' && $this->hasPictureColumn) {
                    $updateSQL .= ', google_picture_url = :picture';
                    $updateParams['picture'] = $picture;
                }

                $updateSQL .= ' WHERE id_usuario = :id_usuario';

                $stmt = $this->db->prepare($updateSQL);
                $stmt->execute($updateParams);
            }

            return $this->findUserById((int) $existingByEmail['id_usuario']);
        }

        $insertSQL = 'INSERT INTO usuarios (nombre, email, password, estado, id_rol, auth_provider, google_sub';
        if ($this->hasPictureColumn) {
            $insertSQL .= ', google_picture_url';
        }
        $insertSQL .= ', prueba_7d_usada) VALUES (:nombre, :email, :password, "activo", 2, "google", :google_sub';
        if ($this->hasPictureColumn) {
            $insertSQL .= ', :picture';
        }
        $insertSQL .= ', 0)';

        $stmt = $this->db->prepare($insertSQL);
        $insertParams = [
            'nombre' => $name,
            'email' => $email,
            'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'google_sub' => $googleSub,
        ];

        if ($this->hasPictureColumn) {
            $insertParams['picture'] = $picture;
        }

        $stmt->execute($insertParams);

        return $this->findUserById((int) $this->db->lastInsertId());
    }

    public function assignPlanToUser(int $userId, int $planId): bool
    {
        if ($userId <= 0 || $planId <= 0) {
            return false;
        }

        $checkPlan = $this->db->prepare('SELECT id_plan, duracion_dias FROM planes WHERE id_plan = :id LIMIT 1');
        $checkPlan->execute(['id' => $planId]);

        $plan = $checkPlan->fetch();
        if (!$plan) {
            return false;
        }

        $durationDays = max(0, (int) ($plan['duracion_dias'] ?? 30));

        $this->db->beginTransaction();

        try {
            $cancel = $this->db->prepare(
                'UPDATE suscripciones
                 SET estado = "cancelada", fecha_fin = COALESCE(fecha_fin, CURDATE())
                 WHERE id_usuario = :id_usuario AND estado = "activa"'
            );
            $cancel->execute(['id_usuario' => $userId]);

            $insert = $this->db->prepare(
                'INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, fecha_fin, estado)
                 VALUES (:id_usuario, :id_plan, CURDATE(), :fecha_fin, "activa")'
            );

            $fechaFin = $durationDays > 0
                ? (new DateTimeImmutable('today'))->modify('+' . $durationDays . ' days')->format('Y-m-d')
                : null;

            $insert->execute([
                'id_usuario' => $userId,
                'id_plan' => $planId,
                'fecha_fin' => $fechaFin,
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

    public function getSubscriptionSnapshot(int $userId): array
    {
        $activeStmt = $this->db->prepare(
            'SELECT s.id_suscripcion,
                    s.id_plan,
                    s.estado,
                    s.fecha_inicio,
                    s.fecha_fin,
                    p.nombre AS plan_nombre,
                    p.precio AS plan_precio,
                    p.descripcion AS plan_descripcion,
                    p.duracion_dias AS plan_duracion_dias
             FROM suscripciones s
             INNER JOIN planes p ON p.id_plan = s.id_plan
             WHERE s.id_usuario = :id_usuario
               AND s.estado = "activa"
             ORDER BY s.fecha_inicio DESC, s.id_suscripcion DESC
             LIMIT 1'
        );
        $activeStmt->execute(['id_usuario' => $userId]);
        $active = $activeStmt->fetch() ?: null;

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM suscripciones
             WHERE id_usuario = :id_usuario'
        );
        $countStmt->execute(['id_usuario' => $userId]);
        $subscriptionsCount = (int) $countStmt->fetchColumn();

        $trialUsedStmt = $this->db->prepare(
            'SELECT prueba_7d_usada
             FROM usuarios
             WHERE id_usuario = :id_usuario
             LIMIT 1'
        );
        $trialUsedStmt->execute(['id_usuario' => $userId]);
        $trialUsed = (int) $trialUsedStmt->fetchColumn() === 1;

        return [
            'active' => $active,
            'subscriptions_count' => $subscriptionsCount,
            'has_any_subscription' => $subscriptionsCount > 0,
            'trial_used' => $trialUsed,
            'can_claim_trial' => (!$trialUsed && $subscriptionsCount === 0),
        ];
    }

    public function activateTrialPlan(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $snapshot = $this->getSubscriptionSnapshot($userId);
        if (!$snapshot['can_claim_trial']) {
            return false;
        }

        $trialPlanId = $this->getTrialPlanId();
        if ($trialPlanId <= 0) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $insert = $this->db->prepare(
                'INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, fecha_fin, estado)
                 VALUES (:id_usuario, :id_plan, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), "activa")'
            );
            $insert->execute([
                'id_usuario' => $userId,
                'id_plan' => $trialPlanId,
            ]);

            $markTrial = $this->db->prepare(
                'UPDATE usuarios
                 SET prueba_7d_usada = 1
                 WHERE id_usuario = :id_usuario'
            );
            $markTrial->execute(['id_usuario' => $userId]);

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

    private function ensureAuthColumns(): void
    {
        if (!$this->columnExists('usuarios', 'auth_provider')) {
            $this->db->exec("ALTER TABLE usuarios ADD COLUMN auth_provider ENUM('local','google') NOT NULL DEFAULT 'local'");
        }

        if (!$this->columnExists('usuarios', 'google_sub')) {
            $this->db->exec('ALTER TABLE usuarios ADD COLUMN google_sub VARCHAR(64) NULL');
        }

        if (!$this->indexExists('usuarios', 'uq_usuarios_google_sub')) {
            $this->db->exec('ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_google_sub (google_sub)');
        }

        if (!$this->columnExists('usuarios', 'prueba_7d_usada')) {
            $this->db->exec('ALTER TABLE usuarios ADD COLUMN prueba_7d_usada TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    private function ensureTrialPlan(): void
    {
        $this->db->exec(
            "INSERT IGNORE INTO planes (id_plan, nombre, precio, descripcion, duracion_dias)
             VALUES (5, 'Prueba 7 dias', 0.00, 'Prueba gratuita por 7 dias con beneficios del plan Basico', 7)"
        );
    }

    private function ensurePictureColumn(): void
    {
        if (!$this->columnExists('usuarios', 'google_picture_url')) {
            $this->db->exec('ALTER TABLE usuarios ADD COLUMN google_picture_url VARCHAR(255) NULL AFTER google_sub');
        }
    }

    private function getTrialPlanId(): int
    {
        $stmt = $this->db->prepare(
            'SELECT id_plan
             FROM planes
             WHERE nombre = :nombre
             LIMIT 1'
        );
        $stmt->execute(['nombre' => 'Prueba 7 dias']);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND index_name = :index_name'
        );

        $stmt->execute([
            'table_name' => $tableName,
            'index_name' => $indexName,
        ]);

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
