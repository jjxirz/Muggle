<?php

require_once __DIR__ . '/Database.php';

class ReportModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── MÉTRICAS PRINCIPALES ───────────────────────────────────────────────

    /** Número de registros en progreso_lectura en los últimos $dias días */
    public function totalSesiones(int $dias = 30): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM progreso_lectura
             WHERE fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :dias DAY)'
        );
        $stmt->execute(['dias' => $dias]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Horas estimadas leídas: asumimos una velocidad media de 30 páginas/hora.
     * Si la tabla estuviera vacía devuelve 0.
     */
    public function horasLeidas(int $dias = 30): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(pagina_actual), 0) / 30
             FROM progreso_lectura
             WHERE fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :dias DAY)'
        );
        $stmt->execute(['dias' => $dias]);
        return (int) $stmt->fetchColumn();
    }

    /** Libro más leído (más registros en progreso_lectura) en el período */
    public function libroMasLeido(int $dias = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.titulo, COUNT(pl.id_progreso) AS lecturas
             FROM progreso_lectura pl
             JOIN libros l ON l.id_libro = pl.id_libro
             WHERE pl.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :dias DAY)
             GROUP BY pl.id_libro
             ORDER BY lecturas DESC
             LIMIT 1'
        );
        $stmt->execute(['dias' => $dias]);
        $row = $stmt->fetch();
        return $row ?: ['titulo' => null, 'lecturas' => 0];
    }

    /**
     * Retención: porcentaje de usuarios que tienen al menos una sesión
     * en los últimos 30 días respecto al total de usuarios activos.
     */
    public function retencion30d(): int
    {
        $totalActivos = (int) $this->db->query(
            'SELECT COUNT(*) FROM usuarios WHERE estado = "activo"'
        )->fetchColumn();

        if ($totalActivos === 0) return 0;

        $stmt = $this->db->prepare(
            'SELECT COUNT(DISTINCT id_usuario) FROM progreso_lectura
             WHERE fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );
        $stmt->execute();
        $activos = (int) $stmt->fetchColumn();

        return (int) round($activos / $totalActivos * 100);
    }

    // ── GRÁFICOS ───────────────────────────────────────────────────────────

    /**
     * Lecturas agrupadas por día de la semana (últimos $dias días).
     * Devuelve 7 filas: Lun → Dom con su total y porcentaje relativo al max.
     */
    public function lecturasPorDiaSemana(int $dias = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT DAYOFWEEK(fecha_actualizacion) AS dow,
                    COUNT(*) AS total
             FROM progreso_lectura
             WHERE fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :dias DAY)
             GROUP BY dow'
        );
        $stmt->execute(['dias' => $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // dow => total

        // DAYOFWEEK: 1=Dom, 2=Lun … 7=Sáb
        $labels = [2 => 'Lun', 3 => 'Mar', 4 => 'Mié', 5 => 'Jue', 6 => 'Vie', 7 => 'Sáb', 1 => 'Dom'];
        $max    = !empty($rows) ? max($rows) : 1;
        $max    = $max ?: 1;

        $result = [];
        foreach ($labels as $dow => $label) {
            $val      = (int) ($rows[$dow] ?? 0);
            $result[] = [
                'dia' => $label,
                'val' => $val,
                'pct' => (int) round($val / $max * 100),
            ];
        }
        return $result;
    }

    /** Top N libros del período por número de sesiones de lectura */
    public function topLibros(int $dias = 30, int $limite = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.titulo,
                    COUNT(pl.id_progreso)              AS lecturas,
                    COALESCE(SUM(pl.pagina_actual),0) / 30 AS horas
             FROM progreso_lectura pl
             JOIN libros l ON l.id_libro = pl.id_libro
             WHERE pl.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL :dias DAY)
             GROUP BY pl.id_libro
             ORDER BY lecturas DESC
             LIMIT :limite'
        );
        $stmt->bindValue('dias',   $dias,   PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $rows   = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $i => $r) {
            $result[] = [
                'pos'     => $i + 1,
                'titulo'  => $r['titulo'],
                'lecturas'=> (int) $r['lecturas'],
                'horas'   => (int) $r['horas'],
            ];
        }
        return $result;
    }

    /** Nuevas suscripciones por plan en los últimos $dias días */
    public function suscripcionesPorPlan(int $dias = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.nombre,
                    COUNT(s.id_suscripcion) AS cantidad
             FROM planes p
             LEFT JOIN suscripciones s
                    ON s.id_plan = p.id_plan
                   AND s.fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
             GROUP BY p.id_plan
             ORDER BY p.precio DESC'
        );
        $stmt->execute(['dias' => $dias]);
        $rows   = $stmt->fetchAll();
        $max    = !empty($rows) ? max(array_column($rows, 'cantidad')) : 1;
        $max    = $max ?: 1;
        $colores = ['#111110', '#5F5E5A', '#B4B2A9', '#D3D1C7'];
        $result  = [];
        foreach ($rows as $i => $r) {
            $result[] = [
                'plan'     => $r['nombre'],
                'cantidad' => (int) $r['cantidad'],
                'pct'      => (int) round($r['cantidad'] / $max * 100),
                'color'    => $colores[$i] ?? '#D3D1C7',
            ];
        }
        return $result;
    }

    /** Progreso de lectura por usuario (últimos activos) */
    public function progresoUsuarios(int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.nombre,
                    l.titulo AS libro,
                    pl.porcentaje AS pct,
                    pl.fecha_actualizacion
             FROM progreso_lectura pl
             JOIN usuarios u ON u.id_usuario = pl.id_usuario
             JOIN libros   l ON l.id_libro   = pl.id_libro
             ORDER BY pl.fecha_actualizacion DESC
             LIMIT :limite'
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $rows   = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $diff   = time() - strtotime($r['fecha_actualizacion']);
            if ($diff < 86400)       $ultima = 'Hoy';
            elseif ($diff < 172800)  $ultima = 'Ayer';
            else                     $ultima = 'Hace ' . floor($diff / 86400) . ' días';
            $result[] = [
                'nombre' => $r['nombre'],
                'libro'  => $r['libro'],
                'pct'    => (int) round((float) $r['pct']),
                'ultima' => $ultima,
            ];
        }
        return $result;
    }

    /** Usuarios nuevos registrados en los últimos $dias días */
    public function usuariosNuevos(int $dias = 30): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios
             WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL :dias DAY)'
        );
        $stmt->execute(['dias' => $dias]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Usuarios "conectados": los que tienen al menos un registro de progreso
     * en los últimos 7 días.
     */
    public function usuariosConectados(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(DISTINCT id_usuario) FROM progreso_lectura
             WHERE fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Plan con más suscripciones activas */
    public function planMasContratado(): string
    {
        $stmt = $this->db->query(
            'SELECT p.nombre, COUNT(s.id_suscripcion) AS total
             FROM planes p
             LEFT JOIN suscripciones s ON s.id_plan = p.id_plan AND s.estado = "activa"
             GROUP BY p.id_plan
             ORDER BY total DESC
             LIMIT 1'
        );
        $row = $stmt->fetch();
        return $row && $row['total'] > 0 ? $row['nombre'] : '—';
    }
}