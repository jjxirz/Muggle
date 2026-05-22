<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../../models/DashboardModel.php';

$dash            = new DashboardModel();
$totalLibros     = $dash->totalLibros();
$usuariosActivos = $dash->totalUsuariosActivos();
$usuariosPremium = $dash->totalPremium();
$lecturasHoy     = $dash->lecturasHoy();
$categoriaStats  = $dash->librosPorCategoria();
$planesStats     = $dash->distribucionPlanes();
$librosTop       = $dash->librosTopSemana();

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
                <div class="stat-value"><?= $totalLibros ?></div>
                <div class="stat-sub stat-sub--neutral"><?= $totalLibros === 0 ? 'Sin libros aún' : 'en catálogo' ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios activos</div>
                <div class="stat-value"><?= $usuariosActivos ?></div>
                <div class="stat-sub stat-sub--neutral"><?= $usuariosActivos === 0 ? 'Sin usuarios aún' : 'registrados' ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Plan Premium</div>
                <div class="stat-value"><?= $usuariosPremium ?></div>
                <div class="stat-sub stat-sub--neutral">
                    <?php if ($usuariosActivos > 0 && $usuariosPremium > 0): ?>
                        <?= round($usuariosPremium / $usuariosActivos * 100) ?>% del total
                    <?php else: ?>
                        Sin suscriptores aún
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Lecturas hoy</div>
                <div class="stat-value"><?= $lecturasHoy ?></div>
                <div class="stat-sub stat-sub--neutral"><?= $lecturasHoy === 0 ? 'Sin actividad hoy' : 'sesiones activas' ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-7 mb-3">
            <div class="admin-card">
                <div class="admin-card__header">Libros por categoría</div>
                <div class="admin-card__body">
                    <?php if (empty($categoriaStats) || array_sum(array_column($categoriaStats, 'cantidad')) === 0): ?>
                        <p class="text-muted" style="font-size:13px; padding: 8px 0;">No hay libros registrados aún.</p>
                    <?php else: ?>
                        <?php foreach ($categoriaStats as $cat): ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= htmlspecialchars($cat['nombre']) ?></span>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: <?= $cat['pct'] ?>%"></div>
                            </div>
                            <span class="bar-val"><?= $cat['cantidad'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Distribución de planes</div>
                <div class="admin-card__body">
                    <?php if (empty($planesStats) || array_sum(array_column($planesStats, 'pct')) === 0): ?>
                        <p class="text-muted" style="font-size:13px; padding: 8px 0;">Sin suscripciones activas aún.</p>
                    <?php else: ?>
                        <?php foreach ($planesStats as $plan): ?>
                        <div class="plan-legend-row">
                            <span class="plan-dot" style="background: <?= $plan['color'] ?>"></span>
                            <span class="plan-nombre"><?= htmlspecialchars($plan['nombre']) ?></span>
                            <div class="bar-track flex-grow-1">
                                <div class="bar-fill" style="width: <?= $plan['pct'] ?>%; background: <?= $plan['color'] ?>"></div>
                            </div>
                            <span class="bar-val"><?= $plan['pct'] ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla libros más leídos -->
    <div class="admin-card">
        <div class="admin-card__header">Libros más leídos esta semana</div>
        <div class="admin-card__body p-0">
            <?php if (empty($librosTop)): ?>
                <p class="text-muted" style="font-size:13px; padding: 16px;">Sin actividad de lectura registrada aún.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr><th>#</th><th>Libro</th><th>Categoría</th><th>Lecturas</th><th>Plan</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($librosTop as $libro): ?>
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
            <?php endif; ?>
        </div>
    </div>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>