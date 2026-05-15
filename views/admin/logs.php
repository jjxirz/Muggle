<?php
$activePage = 'logs';

include __DIR__ . '/../layouts/sidebar.php';
?>

<!-- ===== TOPBAR ===== -->
<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Logs del sistema</h1>
        <p class="topbar-sub">Actividad, advertencias y errores del sistema</p>
    </div>
    <div class="topbar-actions">
        <a href="#" class="btn-admin btn-admin--secondary">
            <i class="fas fa-download"></i> Exportar
        </a>
        <a href="logs.php" class="btn-admin btn-admin--secondary">
            <i class="fas fa-sync-alt"></i> Actualizar
        </a>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <!-- Métricas rápidas -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Eventos hoy</div>
                <div class="stat-value"><?= $totalLogs ?? 147 ?></div>
                <div class="stat-sub stat-sub--neutral">Total de entradas</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Advertencias</div>
                <div class="stat-value log-metric--warn"><?= $totalWarnings ?? 12 ?></div>
                <div class="stat-sub stat-sub--neutral">Requieren revisión</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Errores</div>
                <div class="stat-value log-metric--error"><?= $totalErrores ?? 3 ?></div>
                <div class="stat-sub stat-sub--neutral">Acción requerida</div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="admin-card mb-3">
        <div class="admin-card__toolbar flex-wrap" style="gap: 0.5rem">
            <!-- Búsqueda -->
            <div class="search-box" style="max-width: 280px">
                <i class="fas fa-search"></i>
                <input type="text" id="buscadorLogs" placeholder="Buscar usuario, acción, IP…">
            </div>

            <!-- Filtro tipo -->
            <div class="log-filter-group">
                <button class="log-filter-btn active" data-tipo="todos">
                    Todos
                </button>
                <button class="log-filter-btn log-filter-btn--info" data-tipo="info">
                    <i class="fas fa-info-circle"></i> Info
                </button>
                <button class="log-filter-btn log-filter-btn--warning" data-tipo="warning">
                    <i class="fas fa-exclamation-triangle"></i> Advertencia
                </button>
                <button class="log-filter-btn log-filter-btn--error" data-tipo="error">
                    <i class="fas fa-times-circle"></i> Error
                </button>
            </div>

            <!-- Filtro fecha -->
            <div class="toolbar-filters ml-auto">
                <select class="filter-select" id="filtroFechaLog">
                    <option value="hoy">Hoy</option>
                    <option value="ayer">Ayer</option>
                    <option value="7dias">Últimos 7 días</option>
                    <option value="30dias">Últimos 30 días</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de logs -->
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table logs-table" id="tablaLogs">
                <thead>
                    <tr>
                        <th style="width: 80px">Hora</th>
                        <th style="width: 100px">Tipo</th>
                        <th>Evento</th>
                        <th style="width: 160px">Usuario / IP</th>
                        <th style="width: 80px">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = $logs ?? [
                        ['hora' => '09:41', 'tipo' => 'info',    'mensaje' => 'Inicio de sesión exitoso',                    'usuario' => 'maria@mail.com',  'ip' => '192.168.1.4', 'detalle' => null],
                        ['hora' => '09:38', 'tipo' => 'info',    'mensaje' => 'Libro agregado al catálogo',                  'usuario' => 'admin',           'ip' => '192.168.1.1', 'detalle' => 'Libro: El origen de las especies'],
                        ['hora' => '09:22', 'tipo' => 'warning', 'mensaje' => 'Acceso a contenido Premium sin plan activo',  'usuario' => 'juan@mail.com',   'ip' => '10.0.0.22',   'detalle' => 'Libro ID #88'],
                        ['hora' => '09:10', 'tipo' => 'error',   'mensaje' => 'Error al subir archivo PDF (tamaño excedido)','usuario' => 'admin',           'ip' => '192.168.1.1', 'detalle' => 'Archivo: Dune.pdf · 48MB'],
                        ['hora' => '08:55', 'tipo' => 'info',    'mensaje' => 'Nuevo usuario registrado',                   'usuario' => 'ana@mail.com',    'ip' => '10.0.0.9',    'detalle' => 'Plan: Free'],
                        ['hora' => '08:30', 'tipo' => 'info',    'mensaje' => 'Membresía actualizada a Premium',             'usuario' => 'pedro@mail.com',  'ip' => '10.0.1.15',   'detalle' => 'Plan Plus → Premium'],
                        ['hora' => '08:12', 'tipo' => 'error',   'mensaje' => 'Fallo de autenticación Google OAuth',         'usuario' => '—',               'ip' => '10.0.0.9',    'detalle' => '3 intentos consecutivos'],
                        ['hora' => '07:58', 'tipo' => 'warning', 'mensaje' => 'Sesión expirada sin cierre manual',           'usuario' => 'laura@mail.com',  'ip' => '192.168.2.8', 'detalle' => null],
                        ['hora' => '07:40', 'tipo' => 'info',    'mensaje' => 'Categoría eliminada',                        'usuario' => 'admin',           'ip' => '192.168.1.1', 'detalle' => 'Categoría: Manga'],
                        ['hora' => '07:15', 'tipo' => 'warning', 'mensaje' => 'Múltiples intentos de login fallidos',        'usuario' => 'desconocido',     'ip' => '185.23.44.1', 'detalle' => '7 intentos · posible bot'],
                    ];

                    $iconos = [
                        'info'    => 'fas fa-info-circle',
                        'warning' => 'fas fa-exclamation-triangle',
                        'error'   => 'fas fa-times-circle',
                    ];

                    foreach ($logs as $i => $log): ?>
                    <tr class="log-row" data-tipo="<?= $log['tipo'] ?>">
                        <td>
                            <span class="log-hora"><?= htmlspecialchars($log['hora']) ?></span>
                        </td>
                        <td>
                            <span class="log-badge log-badge--<?= $log['tipo'] ?>">
                                <i class="<?= $iconos[$log['tipo']] ?>"></i>
                                <?= ucfirst($log['tipo'] === 'warning' ? 'aviso' : $log['tipo']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="log-mensaje"><?= htmlspecialchars($log['mensaje']) ?></div>
                        </td>
                        <td>
                            <div class="log-usuario"><?= htmlspecialchars($log['usuario']) ?></div>
                            <div class="log-ip"><?= htmlspecialchars($log['ip']) ?></div>
                        </td>
                        <td>
                            <?php if ($log['detalle']): ?>
                            <button class="log-detalle-btn" data-detalle="<?= htmlspecialchars($log['detalle']) ?>"
                                    title="<?= htmlspecialchars($log['detalle']) ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="logs-pagination">
            <span class="pagination-info">
                Mostrando <strong id="logsVisibles"><?= count($logs ?? []) ?></strong> de <strong><?= $totalLogs ?? 147 ?></strong> entradas
            </span>
            <div class="pagination-btns">
                <button class="btn-admin btn-admin--secondary btn-sm" id="btnPagAnterior" disabled>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span class="pagination-page">Página <strong id="paginaActual">1</strong></span>
                <button class="btn-admin btn-admin--secondary btn-sm" id="btnPagSiguiente">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal detalle (sin JS externo, solo CSS toggle) -->
    <div class="log-modal-overlay" id="logModalOverlay">
        <div class="log-modal">
            <div class="log-modal__header">
                <span>Detalle del evento</span>
                <button class="log-modal__close" id="logModalClose"><i class="fas fa-times"></i></button>
            </div>
            <div class="log-modal__body" id="logModalBody"></div>
        </div>
    </div>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>