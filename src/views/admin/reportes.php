<?php
require_once __DIR__ . '/../../lib/Auth.php';

require_admin();

require_once __DIR__ . '/../../models/ReportModel.php';

$dias = (int) ($_GET['dias'] ?? 30);
if (!in_array($dias, [7, 30, 90], true)) $dias = 30;

$report = new ReportModel();

$libroTop         = $report->libroMasLeido($dias);
$usuariosNuevos   = $report->usuariosNuevos($dias);
$usuariosConect   = $report->usuariosConectados();
$planTop          = $report->planMasContratado();
$lecturaDiaria    = $report->lecturasPorDiaSemana($dias);
$topLibros        = $report->topLibros($dias);
$subs             = $report->suscripcionesPorPlan($dias);
$progresoUsuarios = $report->progresoUsuarios();

$totalSubs = array_sum(array_column($subs, 'cantidad'));
$maxSubs   = $totalSubs > 0 ? max(array_column($subs, 'cantidad')) : 1;
$hayDatos  = $report->totalSesiones($dias) > 0;

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

<div class="admin-content">

    <!-- ── Cards de métricas ── -->
    <div class="row mb-4">

        <!-- Libro más leído -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Libro más leído</div>
                <?php if ($libroTop['titulo']): ?>
                    <div class="stat-value" style="font-size:.95rem; line-height:1.3">
                        <?= htmlspecialchars($libroTop['titulo']) ?>
                    </div>
                    <div class="stat-sub stat-sub--neutral"><?= $libroTop['lecturas'] ?> lecturas</div>
                <?php else: ?>
                    <div class="stat-value" style="opacity:.35">—</div>
                    <div class="stat-sub stat-sub--neutral">Sin datos aún</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usuarios nuevos -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios nuevos</div>
                <div class="stat-value"><?= $usuariosNuevos ?></div>
                <div class="stat-sub stat-sub--neutral">
                    <?= $usuariosNuevos === 0 ? 'Sin registros en el período' : 'registrados en el período' ?>
                </div>
            </div>
        </div>

        <!-- Usuarios conectados -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios conectados</div>
                <div class="stat-value"><?= $usuariosConect ?></div>
                <div class="stat-sub stat-sub--neutral">
                    <?= $usuariosConect === 0 ? 'Ninguno activo recientemente' : 'activos últimos 7 días' ?>
                </div>
            </div>
        </div>

        <!-- Plan más contratado -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Plan más contratado</div>
                <div class="stat-value" style="font-size:1.1rem">
                    <?= htmlspecialchars($planTop) ?>
                </div>
                <div class="stat-sub stat-sub--neutral">suscripción activa</div>
            </div>
        </div>
    </div>

    <!-- ── Lecturas por día ── -->
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

        <!-- Suscripciones por plan — barras de progreso -->
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Suscripciones activas por plan</div>
                <div class="admin-card__body">
                    <?php if ($totalSubs === 0): ?>
                        <p class="text-muted" style="font-size:13px">Sin suscripciones activas aún.</p>
                    <?php else: ?>
                        <?php foreach ($subs as $s):
                            $pct = $maxSubs > 0 ? round($s['cantidad'] / $maxSubs * 100) : 0;
                        ?>
                        <div class="subs-row">
                            <div class="subs-header">
                                <span class="subs-plan"><?= htmlspecialchars($s['plan']) ?></span>
                                <span class="subs-count"><?= $s['cantidad'] ?></span>
                            </div>
                            <div class="subs-track">
                                <div class="subs-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Progreso de lectura por usuario ── -->
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
                                    <div class="subs-track" style="flex:1">
                                        <div class="subs-fill" style="width:<?= $u['pct'] ?>%"></div>
                                    </div>
                                    <span class="subs-count" style="min-width:36px; text-align:right">
                                        <?= $u['pct'] ?>%
                                    </span>
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

<style>
/* Barras de suscripciones / progreso */
.subs-row       { margin-bottom: 14px; }
.subs-row:last-child { margin-bottom: 0; }
.subs-header    { display: flex; justify-content: space-between; margin-bottom: 5px; }
.subs-plan      { font-size: 13px; font-weight: 500; color: var(--color-black); }
.subs-count     { font-size: 12px; color: var(--color-gris-dark); font-variant-numeric: tabular-nums; }
.subs-track     {
    width: 100%;
    height: 8px;
    background: #e5e3dc;   /* gris claro */
    border-radius: 99px;
    overflow: hidden;
}
.subs-fill      {
    height: 100%;
    background: #111110;   /* negro */
    border-radius: 99px;
    transition: width .4s ease;
}
/* reutilizar subs-track en progress-cell */
.progress-cell  { display: flex; align-items: center; gap: 8px; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
