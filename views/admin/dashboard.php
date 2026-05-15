<?php
$activePage = 'dashboard';
include __DIR__ . '/../layouts/sidebar.php';
?>

<!-- ===== TOPBAR ===== -->
<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Dashboard</h1>
        <p class="topbar-sub">Resumen general del sistema</p>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <!-- Tarjetas de métricas -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Libros totales</div>
                <div class="stat-value"><?= $totalLibros ?? 342 ?></div>
                <div class="stat-sub stat-sub--up">↑ 12 este mes</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios activos</div>
                <div class="stat-value"><?= $usuariosActivos ?? '1,204' ?></div>
                <div class="stat-sub stat-sub--up">↑ 89 nuevos</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Plan Premium</div>
                <div class="stat-value"><?= $usuariosPremium ?? 318 ?></div>
                <div class="stat-sub stat-sub--neutral">26% del total</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Lecturas hoy</div>
                <div class="stat-value"><?= $lecturasHoy ?? 87 ?></div>
                <div class="stat-sub stat-sub--neutral">sin cambio</div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-7 mb-3">
            <div class="admin-card">
                <div class="admin-card__header">Libros por categoría</div>
                <div class="admin-card__body">
                    <?php
                    $categorias = $categoriaStats ?? [
                        ['nombre' => 'Terror',   'cantidad' => 68, 'pct' => 80],
                        ['nombre' => 'Clásicos', 'cantidad' => 55, 'pct' => 65],
                        ['nombre' => 'Ciencia',  'cantidad' => 42, 'pct' => 50],
                        ['nombre' => 'Romance',  'cantidad' => 32, 'pct' => 38],
                        ['nombre' => 'Otros',    'cantidad' => 17, 'pct' => 20],
                    ];
                    foreach ($categorias as $cat): ?>
                    <div class="bar-row">
                        <span class="bar-label"><?= htmlspecialchars($cat['nombre']) ?></span>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $cat['pct'] ?>%"></div>
                        </div>
                        <span class="bar-val"><?= $cat['cantidad'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Distribución de planes</div>
                <div class="admin-card__body">
                    <?php
                    $planes = $planesStats ?? [
                        ['nombre' => 'Premium', 'pct' => 26, 'color' => '#111110'],
                        ['nombre' => 'Plus',    'pct' => 33, 'color' => '#B4B2A9'],
                        ['nombre' => 'Básico',  'pct' => 23, 'color' => '#D3D1C7'],
                        ['nombre' => 'Free',    'pct' => 18, 'color' => '#ECEAE3'],
                    ];
                    foreach ($planes as $plan): ?>
                    <div class="plan-legend-row">
                        <span class="plan-dot" style="background: <?= $plan['color'] ?>"></span>
                        <span class="plan-nombre"><?= htmlspecialchars($plan['nombre']) ?></span>
                        <div class="bar-track flex-grow-1">
                            <div class="bar-fill" style="width: <?= $plan['pct'] ?>%; background: <?= $plan['color'] ?>"></div>
                        </div>
                        <span class="bar-val"><?= $plan['pct'] ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla libros más leídos -->
    <div class="admin-card">
        <div class="admin-card__header">Libros más leídos esta semana</div>
        <div class="admin-card__body p-0">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Libro</th>
                            <th>Categoría</th>
                            <th>Lecturas</th>
                            <th>Plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $topLibros = $librosTop ?? [
                            ['pos' => 1, 'titulo' => 'It (Eso)', 'autor' => 'Stephen King', 'categoria' => 'Terror', 'lecturas' => 43, 'plan' => 'plus'],
                            ['pos' => 2, 'titulo' => 'Cien años de soledad', 'autor' => 'G. García M.', 'categoria' => 'Clásicos', 'lecturas' => 31, 'plan' => 'premium'],
                            ['pos' => 3, 'titulo' => 'Dune', 'autor' => 'Frank Herbert', 'categoria' => 'Ciencia ficción', 'lecturas' => 27, 'plan' => 'premium'],
                        ];
                        foreach ($topLibros as $libro): ?>
                        <tr>
                            <td class="text-muted"><?= $libro['pos'] ?></td>
                            <td>
                                <div class="book-cell">
                                    <div class="book-thumb"><i class="fas fa-book"></i></div>
                                    <div>
                                        <div class="book-name"><?= htmlspecialchars($libro['titulo']) ?></div>
                                        <div class="book-author"><?= htmlspecialchars($libro['autor']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($libro['categoria']) ?></td>
                            <td><?= $libro['lecturas'] ?></td>
                            <td><span class="badge-plan badge-plan--<?= $libro['plan'] ?>"><?= strtoupper($libro['plan']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>