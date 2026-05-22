<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../../models/ReportModel.php';

$dias = (int) ($_GET['dias'] ?? 30);
if (!in_array($dias, [7, 30, 90], true)) $dias = 30;

$report = new ReportModel();

$totalSesiones   = $report->totalSesiones($dias);
$horasLeidas     = $report->horasLeidas($dias);
$libroTop        = $report->libroMasLeido($dias);
$retencion       = $report->retencion30d();
$lecturaDiaria   = $report->lecturasPorDiaSemana($dias);
$topLibros       = $report->topLibros($dias);
$subs            = $report->suscripcionesPorPlan($dias);
$progresoUsuarios= $report->progresoUsuarios();

$hayDatos = $totalSesiones > 0;

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
        <select class="filter-select" id="periodoReporte"
                onchange="location.href='?dias='+this.value">
            <option value="7"  <?= $dias ===  7 ? 'selected' : '' ?>>Últimos 7 días</option>
            <option value="30" <?= $dias === 30 ? 'selected' : '' ?>>Último mes</option>
            <option value="90" <?= $dias === 90 ? 'selected' : '' ?>>Últimos 3 meses</option>
        </select>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <?php if (!$hayDatos): ?>
    <div style="padding:40px 16px; text-align:center; color:var(--color-gris-mid); font-size:13px; margin-bottom:24px">
        <i class="fas fa-chart-bar" style="font-size:32px; opacity:0.2; display:block; margin-bottom:10px"></i>
        Sin actividad de lectura registrada en este período.<br>
        Los reportes se poblarán cuando los usuarios comiencen a leer.
    </div>
    <?php endif; ?>

    <!-- Métricas -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Sesiones este período</div>
                <div class="stat-value"><?= number_format($totalSesiones) ?></div>
                <div class="stat-sub stat-sub--neutral">
                    <?= $totalSesiones === 0 ? 'Sin sesiones aún' : 'registradas' ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Horas leídas</div>
                <div class="stat-value"><?= number_format($horasLeidas) ?></div>
                <div class="stat-sub stat-sub--neutral">
                    <?= $horasLeidas === 0 ? 'Sin datos aún' : 'estimadas' ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Libro más leído</div>
                <?php if ($libroTop['titulo']): ?>
                    <div class="stat-value" style="font-size:1rem; line-height:1.3">
                        <?= htmlspecialchars($libroTop['titulo']) ?>
                    </div>
                    <div class="stat-sub stat-sub--neutral"><?= $libroTop['lecturas'] ?> lecturas</div>
                <?php else: ?>
                    <div class="stat-value" style="font-size:1rem; opacity:.4">—</div>
                    <div class="stat-sub stat-sub--neutral">Sin datos aún</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Retención 30d</div>
                <div class="stat-value"><?= $retencion ?>%</div>
                <div class="stat-sub stat-sub--neutral">
                    <?= $retencion === 0 ? 'Sin usuarios activos' : 'de usuarios activos' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lecturas por día de la semana -->
    <div class="admin-card mb-4">
        <div class="admin-card__header">Lecturas por día de la semana</div>
        <div class="admin-card__body">
            <?php if (!$hayDatos): ?>
                <p class="text-muted" style="font-size:13px">Sin actividad en este período.</p>
            <?php else: ?>
                <?php foreach ($lecturaDiaria as $d): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= $d['dia'] ?></span>
                    <div class="bar-track">
                        <div class="bar-fill <?= $d['pct'] === 100 ? 'bar-fill--peak' : '' ?>"
                             style="width: <?= $d['pct'] ?>%"></div>
                    </div>
                    <span class="bar-val"><?= $d['val'] ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Top libros -->
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Top libros del período</div>
                <div class="admin-card__body p-0">
                    <?php if (empty($topLibros)): ?>
                        <p class="text-muted" style="font-size:13px; padding:16px">Sin lecturas registradas.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr><th>#</th><th>Libro</th><th>Lecturas</th><th>Horas</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topLibros as $lb): ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Suscripciones por plan -->
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Nuevas suscripciones por plan</div>
                <div class="admin-card__body">
                    <?php if (empty($subs) || array_sum(array_column($subs, 'cantidad')) === 0): ?>
                        <p class="text-muted" style="font-size:13px">Sin nuevas suscripciones en este período.</p>
                    <?php else: ?>
                        <?php foreach ($subs as $s): ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= htmlspecialchars($s['plan']) ?></span>
                            <div class="bar-track">
                                <div class="bar-fill"
                                     style="width: <?= $s['pct'] ?>%; background: <?= $s['color'] ?>">
                                </div>
                            </div>
                            <span class="bar-val"><?= $s['cantidad'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso de lectura por usuario -->
    <div class="admin-card">
        <div class="admin-card__header">Progreso de lectura por usuario</div>
        <div class="admin-card__body p-0">
            <?php if (empty($progresoUsuarios)): ?>
                <p class="text-muted" style="font-size:13px; padding:16px">
                    Ningún usuario ha registrado progreso de lectura aún.
                </p>
            <?php else: ?>
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
                        <?php foreach ($progresoUsuarios as $u): ?>
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
            <?php endif; ?>
        </div>
    </div>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
