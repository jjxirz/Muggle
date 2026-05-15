<?php
$activePage = 'reportes';

include __DIR__ . '/../layouts/sidebar.php';
?>

<!-- ===== TOPBAR ===== -->
<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Reportes y estadísticas</h1>
        <p class="topbar-sub">Uso de la plataforma y lectura</p>
    </div>
    <div class="topbar-actions">
        <select class="filter-select" id="periodoReporte">
            <option value="7">Últimos 7 días</option>
            <option value="30" selected>Último mes</option>
            <option value="90">Últimos 3 meses</option>
        </select>
        <a href="#" class="btn-admin btn-admin--secondary">
            <i class="fas fa-download"></i> Exportar
        </a>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <!-- Métricas -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Sesiones este mes</div>
                <div class="stat-value"><?= $sesionesmes ?? '4,821' ?></div>
                <div class="stat-sub stat-sub--up">↑ 14%</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Horas leídas</div>
                <div class="stat-value"><?= $horasLeidas ?? '9,340' ?></div>
                <div class="stat-sub stat-sub--up">↑ 8%</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Libro más leído</div>
                <div class="stat-value" style="font-size: 1.1rem"><?= $libroTop ?? 'IT (Eso)' ?></div>
                <div class="stat-sub stat-sub--neutral">43 lecturas</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Retención 30d</div>
                <div class="stat-value"><?= $retencion ?? '72%' ?></div>
                <div class="stat-sub stat-sub--up">↑ 3%</div>
            </div>
        </div>
    </div>

    <!-- Gráfico de lecturas por día -->
    <div class="admin-card mb-4">
        <div class="admin-card__header">Lecturas por día</div>
        <div class="admin-card__body">
            <div class="bar-chart-horizontal" id="chartLecturas">
                <?php
                $lecturaDiaria = $lecturaDiaria ?? [
                    ['dia' => 'Lun', 'val' => 55, 'pct' => 55],
                    ['dia' => 'Mar', 'val' => 70, 'pct' => 70],
                    ['dia' => 'Mié', 'val' => 60, 'pct' => 60],
                    ['dia' => 'Jue', 'val' => 90, 'pct' => 90],
                    ['dia' => 'Vie', 'val' => 80, 'pct' => 80],
                    ['dia' => 'Sáb', 'val' => 100,'pct' => 100],
                    ['dia' => 'Dom', 'val' => 65, 'pct' => 65],
                ];
                foreach ($lecturaDiaria as $d): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= htmlspecialchars($d['dia']) ?></span>
                    <div class="bar-track">
                        <div class="bar-fill <?= $d['pct'] === 100 ? 'bar-fill--peak' : '' ?>"
                             style="width: <?= $d['pct'] ?>%"></div>
                    </div>
                    <span class="bar-val"><?= $d['val'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Top libros -->
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Top libros del período</div>
                <div class="admin-card__body p-0">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Libro</th>
                                    <th>Lecturas</th>
                                    <th>Horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $topLibros = $topLibros ?? [
                                    ['pos' => 1, 'titulo' => 'IT (Eso)',              'lecturas' => 43, 'horas' => 214],
                                    ['pos' => 2, 'titulo' => 'Cien años de soledad',  'lecturas' => 31, 'horas' => 155],
                                    ['pos' => 3, 'titulo' => 'Dune',                  'lecturas' => 27, 'horas' => 189],
                                    ['pos' => 4, 'titulo' => 'Origen de las especies','lecturas' => 18, 'horas' => 72],
                                ];
                                foreach ($topLibros as $lb): ?>
                                <tr>
                                    <td class="text-muted"><?= $lb['pos'] ?></td>
                                    <td><div class="book-name"><?= htmlspecialchars($lb['titulo']) ?></div></td>
                                    <td><?= $lb['lecturas'] ?></td>
                                    <td class="text-secondary"><?= $lb['horas'] ?>h</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suscripciones por mes -->
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Nuevas suscripciones por plan</div>
                <div class="admin-card__body">
                    <?php
                    $subs = $subs ?? [
                        ['plan' => 'Premium', 'cantidad' => 42, 'pct' => 85, 'color' => '#111110'],
                        ['plan' => 'Plus',    'cantidad' => 67, 'pct' => 100,'color' => '#5F5E5A'],
                        ['plan' => 'Básico',  'cantidad' => 38, 'pct' => 57, 'color' => '#B4B2A9'],
                        ['plan' => 'Free',    'cantidad' => 89, 'pct' => 55, 'color' => '#D3D1C7'],
                    ];
                    foreach ($subs as $s): ?>
                    <div class="bar-row">
                        <span class="bar-label"><?= htmlspecialchars($s['plan']) ?></span>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $s['pct'] ?>%; background: <?= $s['color'] ?>"></div>
                        </div>
                        <span class="bar-val"><?= $s['cantidad'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso de lectura por usuario -->
    <div class="admin-card">
        <div class="admin-card__header">Progreso de lectura por usuario</div>
        <div class="admin-card__body p-0">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Libro en curso</th>
                            <th>Progreso</th>
                            <th>Última sesión</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $progresoUsuarios = $progresoUsuarios ?? [
                            ['nombre' => 'María R.',  'libro' => 'Cien años de soledad', 'pct' => 68, 'ultima' => 'Hoy'],
                            ['nombre' => 'Juan P.',   'libro' => 'IT (Eso)',             'pct' => 32, 'ultima' => 'Ayer'],
                            ['nombre' => 'Pedro M.',  'libro' => 'Dune',                 'pct' => 15, 'ultima' => 'Hace 3 días'],
                            ['nombre' => 'Laura G.',  'libro' => 'Origen de las especies','pct' => 90, 'ultima' => 'Hoy'],
                        ];
                        foreach ($progresoUsuarios as $u): ?>
                        <tr>
                            <td><div class="book-name"><?= htmlspecialchars($u['nombre']) ?></div></td>
                            <td class="text-secondary"><?= htmlspecialchars($u['libro']) ?></td>
                            <td>
                                <div class="progress-cell">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?= $u['pct'] ?>%"></div>
                                    </div>
                                    <span class="progress-label"><?= $u['pct'] ?>%</span>
                                </div>
                            </td>
                            <td class="text-secondary small"><?= htmlspecialchars($u['ultima']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>