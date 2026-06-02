<?php
require_once __DIR__ . '/../../lib/Auth.php';
require_once __DIR__ . '/../../models/InteractionAdminModel.php';

require_admin();

$days = (int) ($_GET['dias'] ?? 30);
$allowedDays = [7, 30, 90, 180];
if (!in_array($days, $allowedDays, true)) {
    $days = 30;
}

$errorMessage = null;
$needsReadingListMigration = false;

$summary = [
    'favorites_total' => 0,
    'reading_list_total' => 0,
    'favorite_users' => 0,
    'reading_list_users' => 0,
    'favorite_adds_recent' => 0,
    'reading_list_adds_recent' => 0,
];
$topFavorites = [];
$topReadingList = [];
$recentFavorites = [];
$recentReadingList = [];

try {
    $model = new InteractionAdminModel();
    $summary = $model->summary($days);
    $topFavorites = $model->topBooksByFavorites(10, $days);
    $topReadingList = $model->topBooksByReadingList(10, $days);
    $recentFavorites = $model->recentFavoriteEvents(12);
    $recentReadingList = $model->recentReadingListEvents(12);
    $needsReadingListMigration = !$model->tableExists('lista_lectura');
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

$activePage = 'interacciones';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Interacciones de lectura</h1>
        <p class="topbar-sub">Favoritos y lista de lectura en tiempo real</p>
    </div>
    <div class="topbar-actions">
        <select class="filter-select" onchange="location.href='?dias=' + this.value">
            <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Ultimos 7 dias</option>
            <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Ultimos 30 dias</option>
            <option value="90" <?= $days === 90 ? 'selected' : '' ?>>Ultimos 90 dias</option>
            <option value="180" <?= $days === 180 ? 'selected' : '' ?>>Ultimos 180 dias</option>
        </select>
    </div>
</div>

<div class="admin-content">

    <?php if ($errorMessage !== null): ?>
        <div class="app-flash app-flash--error" style="margin:0 0 16px 0;">
            <strong>No se pudieron cargar las métricas.</strong><br>
            <span style="font-size:13px;"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($needsReadingListMigration): ?>
        <div class="app-flash app-flash--warning" style="margin:0 0 16px 0;">
            <strong>Falta la tabla lista_lectura en la base de datos.</strong>
            <p style="margin:8px 0 0; font-size:13px;">
                Ejecuta el script actualizado en db/script.sql para habilitar métricas completas de lista de lectura.
            </p>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Favoritos totales</div>
                <div class="stat-value"><?= (int) $summary['favorites_total'] ?></div>
                <div class="stat-sub stat-sub--neutral"><?= (int) $summary['favorite_adds_recent'] ?> en <?= $days ?> dias</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Lista lectura total</div>
                <div class="stat-value"><?= (int) $summary['reading_list_total'] ?></div>
                <div class="stat-sub stat-sub--neutral"><?= (int) $summary['reading_list_adds_recent'] ?> en <?= $days ?> dias</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios con favoritos</div>
                <div class="stat-value"><?= (int) $summary['favorite_users'] ?></div>
                <div class="stat-sub stat-sub--neutral">al menos 1 libro</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios con lista</div>
                <div class="stat-value"><?= (int) $summary['reading_list_users'] ?></div>
                <div class="stat-sub stat-sub--neutral">al menos 1 libro</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Top libros en favoritos (<?= $days ?> dias)</div>
                <div class="admin-card__body p-0">
                    <?php if (empty($topFavorites)): ?>
                        <p class="text-muted" style="font-size:13px; padding:16px;">Sin datos de favoritos para este periodo.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr><th>#</th><th>Libro</th><th>Autor</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topFavorites as $i => $row): ?>
                                        <tr>
                                            <td class="text-muted"><?= $i + 1 ?></td>
                                            <td><div class="book-name"><?= htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                                            <td class="text-secondary small"><?= htmlspecialchars((string) ($row['autor'] ?? 'Autor no especificado'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= (int) $row['total'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Top libros en lista de lectura (<?= $days ?> dias)</div>
                <div class="admin-card__body p-0">
                    <?php if (empty($topReadingList)): ?>
                        <p class="text-muted" style="font-size:13px; padding:16px;">Sin datos de lista de lectura para este periodo.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr><th>#</th><th>Libro</th><th>Autor</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topReadingList as $i => $row): ?>
                                        <tr>
                                            <td class="text-muted"><?= $i + 1 ?></td>
                                            <td><div class="book-name"><?= htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                                            <td class="text-secondary small"><?= htmlspecialchars((string) ($row['autor'] ?? 'Autor no especificado'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= (int) $row['total'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Ultimos eventos de favoritos</div>
                <div class="admin-card__body p-0">
                    <?php if (empty($recentFavorites)): ?>
                        <p class="text-muted" style="font-size:13px; padding:16px;">Sin eventos recientes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr><th>Usuario</th><th>Libro</th><th>Fecha</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentFavorites as $event): ?>
                                        <tr>
                                            <td>
                                                <div class="book-name"><?= htmlspecialchars($event['usuario'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-secondary small"><?= htmlspecialchars($event['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td>
                                                <div class="book-name"><?= htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-secondary small"><?= htmlspecialchars((string) ($event['autor'] ?? 'Autor no especificado'), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td class="text-secondary small"><?= htmlspecialchars((string) $event['fecha_agregado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="admin-card h-100">
                <div class="admin-card__header">Ultimos eventos de lista de lectura</div>
                <div class="admin-card__body p-0">
                    <?php if (empty($recentReadingList)): ?>
                        <p class="text-muted" style="font-size:13px; padding:16px;">Sin eventos recientes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr><th>Usuario</th><th>Libro</th><th>Fecha</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReadingList as $event): ?>
                                        <tr>
                                            <td>
                                                <div class="book-name"><?= htmlspecialchars($event['usuario'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-secondary small"><?= htmlspecialchars($event['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td>
                                                <div class="book-name"><?= htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-secondary small"><?= htmlspecialchars((string) ($event['autor'] ?? 'Autor no especificado'), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td class="text-secondary small"><?= htmlspecialchars((string) $event['fecha_agregado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
