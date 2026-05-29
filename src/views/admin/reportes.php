<?php
require_once __DIR__ . '/../../lib/Auth.php';
require_admin();
require_once __DIR__ . '/../../models/ReportModel.php';

$periodo = $_GET['periodo'] ?? '30';
$desde   = $_GET['desde']   ?? null;
$hasta   = $_GET['hasta']   ?? null;

$diasMap = ['7' => 7, '30' => 30, '180' => 180, '365' => 365];
$dias    = $diasMap[$periodo] ?? 30;

$esCustom   = ($periodo === 'custom' && $desde && $hasta);
$desdeParam = $esCustom ? $desde : null;
$hastaParam = $esCustom ? $hasta : null;

$report = new ReportModel();

$libroTop         = $report->libroMasLeido($dias, $desdeParam, $hastaParam);
$usuariosNuevos   = $report->usuariosNuevos($dias, $desdeParam, $hastaParam);
$usuariosConect   = $report->usuariosConectados($desdeParam, $hastaParam);
$planTop          = $report->planMasContratado($desdeParam, $hastaParam);
$lecturaDiaria    = $report->lecturasPorDiaSemana($dias, $desdeParam, $hastaParam);
$topLibros        = $report->topLibros($dias, 10, $desdeParam, $hastaParam);
$subs             = $report->suscripcionesPorPlan($dias, $desdeParam, $hastaParam);
$progresoUsuarios = $report->progresoUsuarios(10, $desdeParam, $hastaParam);
$librosAgregados  = $report->librosAgregados($dias, $desdeParam, $hastaParam);
$usuariosAgregados = $report->usuariosAgregados($dias, $desdeParam, $hastaParam);
$hayDatos         = $report->totalSesiones($dias, $desdeParam, $hastaParam) > 0;

$maxSubs  = !empty($subs) ? max(array_column($subs, 'cantidad')) : 1;
$maxSubs  = $maxSubs ?: 1;
$totalSubs = array_sum(array_column($subs, 'cantidad'));

// Etiqueta legible del período para el PDF
if ($esCustom) {
    $labelPeriodo = date('d/m/Y', strtotime($desde)) . ' – ' . date('d/m/Y', strtotime($hasta));
} else {
    $labelMap = ['7' => 'Últimos 7 días', '30' => 'Último mes', '180' => 'Últimos 6 meses', '365' => 'Último año'];
    $labelPeriodo = $labelMap[$periodo] ?? 'Último mes';
}

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
        <!-- Rango personalizado — aparece a la IZQUIERDA del select -->
        <div class="rpt-rango" id="rangoPersonalizado"
             style="<?= $periodo !== 'custom' ? 'display:none' : 'display:flex' ?>">
            <input type="date" id="inputDesde" class="form-control admin-input rpt-date"
                   value="<?= htmlspecialchars($desde ?? date('Y-m-d', strtotime('-30 days'))) ?>">
            <span class="rpt-sep">→</span>
            <input type="date" id="inputHasta" class="form-control admin-input rpt-date"
                   value="<?= htmlspecialchars($hasta ?? date('Y-m-d')) ?>">
            <button class="btn-admin btn-admin--primary rpt-apply" id="btnAplicar">Aplicar</button>
        </div>

        <select class="filter-select" id="selectPeriodo" style="min-width:160px">
            <option value="7"     <?= $periodo==='7'      ? 'selected':'' ?>>Últimos 7 días</option>
            <option value="30"    <?= $periodo==='30'     ? 'selected':'' ?>>Último mes</option>
            <option value="180"   <?= $periodo==='180'    ? 'selected':'' ?>>Últimos 6 meses</option>
            <option value="365"   <?= $periodo==='365'    ? 'selected':'' ?>>Último año</option>
            <option value="custom"<?= $periodo==='custom' ? 'selected':'' ?>>Personalizado</option>
        </select>

        <button class="btn-admin btn-admin--secondary" id="btnExportPdf" title="Exportar PDF">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </button>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content" id="reporteContenido">

    <!-- Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Libro más leído</div>
                <?php if ($libroTop['titulo']): ?>
                    <div class="stat-value" style="font-size:.95rem;line-height:1.3"><?= htmlspecialchars($libroTop['titulo']) ?></div>
                    <div class="stat-sub stat-sub--neutral"><?= $libroTop['lecturas'] ?> lecturas</div>
                <?php else: ?>
                    <div class="stat-value" style="opacity:.35">—</div>
                    <div class="stat-sub stat-sub--neutral">Sin datos aún</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios nuevos</div>
                <div class="stat-value"><?= $usuariosNuevos ?></div>
                <div class="stat-sub stat-sub--neutral"><?= $usuariosNuevos===0 ? 'Sin registros en el período' : 'registrados en el período' ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Usuarios conectados</div>
                <div class="stat-value"><?= $usuariosConect ?></div>
                <div class="stat-sub stat-sub--neutral"><?= $usuariosConect===0 ? 'Ninguno activo en el período' : 'activos en el período' ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-label">Plan más contratado</div>
                <div class="stat-value" style="font-size:1.1rem"><?= htmlspecialchars($planTop) ?></div>
                <div class="stat-sub stat-sub--neutral">en el período</div>
            </div>
        </div>
    </div>

    <!-- Lecturas por día -->
    <div class="admin-card mb-4">
        <div class="admin-card__header">Lecturas por día de la semana</div>
        <div class="admin-card__body">
            <?php if (!$hayDatos): ?>
                <p class="text-muted" style="font-size:13px">Sin actividad en este período.</p>
            <?php else: ?>
                <?php foreach ($lecturaDiaria as $d): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= $d['dia'] ?></span>
                    <div class="bar-track"><div class="bar-fill <?= $d['pct']===100?'bar-fill--peak':'' ?>" style="width:<?= $d['pct'] ?>%"></div></div>
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
                        <p class="text-muted" style="font-size:13px;padding:16px">Sin lecturas registradas.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>#</th><th>Libro</th><th>Lecturas</th><th>Horas</th></tr></thead>
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
                <div class="admin-card__header">Suscripciones activas por plan</div>
                <div class="admin-card__body">
                    <?php if ($totalSubs === 0): ?>
                        <p class="text-muted" style="font-size:13px">Sin suscripciones activas aún.</p>
                    <?php else: ?>
                        <?php foreach ($subs as $s):
                            $pct = (int) round($s['cantidad'] / $maxSubs * 100);
                        ?>
                        <div class="subs-row">
                            <div class="subs-header">
                                <span class="subs-plan"><?= htmlspecialchars($s['plan']) ?></span>
                                <span class="subs-count"><?= $s['cantidad'] ?> usuario<?= $s['cantidad']!==1?'s':'' ?></span>
                            </div>
                            <div class="subs-track"><div class="subs-fill" style="width:<?= $pct ?>%"></div></div>
                        </div>
                        <?php endforeach; ?>
                        <p class="subs-nota">La barra completa equivale al plan con más suscriptores.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso de lectura -->
    <div class="admin-card">
        <div class="admin-card__header">Progreso de lectura por usuario</div>
        <div class="admin-card__body p-0">
            <?php if (empty($progresoUsuarios)): ?>
                <p class="text-muted" style="font-size:13px;padding:16px">Ningún usuario ha registrado progreso aún.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead><tr><th>Usuario</th><th>Libro en curso</th><th>Progreso</th><th>Última sesión</th></tr></thead>
                    <tbody>
                        <?php foreach ($progresoUsuarios as $u): ?>
                        <tr>
                            <td><div class="book-name"><?= htmlspecialchars($u['nombre']) ?></div></td>
                            <td class="text-secondary"><?= htmlspecialchars($u['libro']) ?></td>
                            <td>
                                <div class="progress-cell">
                                    <div class="subs-track" style="flex:1"><div class="subs-fill" style="width:<?= $u['pct'] ?>%"></div></div>
                                    <span style="font-size:12px;min-width:36px;text-align:right;color:var(--color-gris-dark)"><?= $u['pct'] ?>%</span>
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

<!-- ===================================================
     MODAL PDF
=================================================== -->
<div class="log-modal-overlay" id="modalPdf">
    <div class="log-modal" style="max-width:480px;width:95%;max-height:90vh;overflow-y:auto">
        <div class="log-modal__header">
            <span><i class="fas fa-file-pdf" style="margin-right:6px"></i>Exportar reporte PDF</span>
            <button class="log-modal__close" id="cerrarModalPdf"><i class="fas fa-times"></i></button>
        </div>
        <div class="log-modal__body" style="padding:20px 24px">
            <p style="font-size:13px;color:var(--color-gris-dark);margin-bottom:16px">
                Se generará un PDF con todas las métricas del período <strong><?= htmlspecialchars($labelPeriodo) ?></strong>.
            </p>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfCards"   checked> Métricas principales
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfLectura" checked> Lecturas por día
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfTop"     checked> Top libros
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfSubs"    checked> Suscripciones por plan
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfProgreso" checked> Progreso de usuarios
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfLibrosAgregados" checked> Libros agregados en el período
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" id="pdfUsuariosAgregados" checked> Usuarios registrados en el período
                </label>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button class="btn-admin btn-admin--secondary" id="cancelarPdf">Cancelar</button>
                <button class="btn-admin btn-admin--primary"   id="confirmarPdf">
                    <i class="fas fa-download"></i> Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Toolbar ── */
.rpt-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
}
.rpt-rango {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}
.rpt-date  { max-width: 130px; padding: 5px 8px; font-size: 12px; }
.rpt-sep   { font-size: 12px; color: var(--color-gris-mid); }
.rpt-apply { padding: 6px 12px; font-size: 12px; white-space: nowrap; }

/* ── Barras ── */
.subs-row { margin-bottom: 16px; }
.subs-row:last-of-type { margin-bottom: 4px; }
.subs-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.subs-plan   { font-size: 13px; font-weight: 500; color: var(--color-black); }
.subs-count  { font-size: 12px; color: var(--color-gris-dark); font-variant-numeric: tabular-nums; }
.subs-track  { width: 100%; height: 8px; background: #e5e3dc; border-radius: 99px; overflow: hidden; }
.subs-fill   { height: 100%; background: #111110; border-radius: 99px; transition: width .5s ease; }
.subs-nota   { font-size: 11px; color: var(--color-gris-mid); margin-top: 10px; margin-bottom: 0; }
.progress-cell { display: flex; align-items: center; gap: 8px; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
(function () {

    /* ── Filtro ── */
    var select = document.getElementById('selectPeriodo');
    var rango  = document.getElementById('rangoPersonalizado');

    select.addEventListener('change', function () {
        if (this.value === 'custom') {
            rango.style.display = 'flex';
        } else {
            rango.style.display = 'none';
            location.href = '?periodo=' + encodeURIComponent(this.value);
        }
    });

    document.getElementById('btnAplicar').addEventListener('click', function () {
        var d = document.getElementById('inputDesde').value;
        var h = document.getElementById('inputHasta').value;
        if (!d || !h)  { alert('Selecciona ambas fechas.'); return; }
        if (d > h)     { alert('La fecha inicio debe ser anterior a la fecha final.'); return; }
        location.href = '?periodo=custom&desde=' + encodeURIComponent(d) + '&hasta=' + encodeURIComponent(h);
    });

    /* ── Modal PDF ── */
    var modalPdf = document.getElementById('modalPdf');

    document.getElementById('btnExportPdf').addEventListener('click', function () {
        modalPdf.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
    function cerrarPdf() {
        modalPdf.classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('cerrarModalPdf').addEventListener('click', cerrarPdf);
    document.getElementById('cancelarPdf').addEventListener('click', cerrarPdf);
    modalPdf.addEventListener('click', function(e){ if (e.target === modalPdf) cerrarPdf(); });

    /* ── Generación PDF ── */
    document.getElementById('confirmarPdf').addEventListener('click', function () {

        var { jsPDF } = window.jspdf;
        var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        var margen   = 18;
        var anchoDoc = 210 - margen * 2;
        var y        = margen;
        var gris     = [120, 118, 114];
        var negro    = [17, 17, 16];
        var crema    = [240, 238, 231];
        var periodo  = '<?= addslashes($labelPeriodo) ?>';

        /* ── Encabezado ── */
        doc.setFillColor(17, 17, 16);
        doc.rect(0, 0, 210, 28, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.setTextColor(240, 238, 231);
        doc.text('HOGWARTS LIBRARIES', margen, 12);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor(180, 178, 169);
        doc.text('Reporte de estadísticas · ' + periodo, margen, 19);
        doc.setFontSize(8);
        doc.text('Generado el ' + new Date().toLocaleDateString('es-ES', {day:'2-digit',month:'long',year:'numeric'}), margen, 24);
        y = 36;

        /* ── Función helpers ── */
        function titulo(txt) {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.setTextColor(negro[0], negro[1], negro[2]);
            doc.text(txt.toUpperCase(), margen, y);
            doc.setDrawColor(220, 218, 210);
            doc.setLineWidth(0.3);
            doc.line(margen, y + 1.5, 210 - margen, y + 1.5);
            y += 7;
        }

        function salto(n) { y += (n || 6); }

        function checkPagina(necesita) {
            if (y + necesita > 275) {
                doc.addPage();
                y = margen;
            }
        }

        /* ── SECCIÓN 1: Cards de métricas ── */
        if (document.getElementById('pdfCards').checked) {
            titulo('Métricas del período');
            var cards = [
                ['Libro más leído',       '<?= addslashes($libroTop["titulo"] ?: "Sin datos") ?>', '<?= $libroTop["lecturas"] ?> lecturas'],
                ['Usuarios nuevos',       '<?= $usuariosNuevos ?>',  'registrados en el período'],
                ['Usuarios conectados',   '<?= $usuariosConect ?>',  'activos en el período'],
                ['Plan más contratado',   '<?= addslashes($planTop) ?>', 'en el período'],
            ];
            var colW = anchoDoc / 4;
            cards.forEach(function(c, i) {
                var x = margen + i * colW;
                doc.setFillColor(crema[0], crema[1], crema[2]);
                doc.roundedRect(x, y, colW - 3, 22, 2, 2, 'F');
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7);
                doc.setTextColor(gris[0], gris[1], gris[2]);
                doc.text(c[0], x + 3, y + 5);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10);
                doc.setTextColor(negro[0], negro[1], negro[2]);
                var val = doc.splitTextToSize(c[1], colW - 6);
                doc.text(val, x + 3, y + 11);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7);
                doc.setTextColor(gris[0], gris[1], gris[2]);
                doc.text(c[2], x + 3, y + 19);
            });
            y += 28;
        }

        /* ── SECCIÓN 2: Lecturas por día ── */
        if (document.getElementById('pdfLectura').checked) {
            checkPagina(50);
            titulo('Lecturas por día de la semana');
            var diasData = <?= json_encode(array_map(fn($d) => ['dia' => $d['dia'], 'val' => $d['val'], 'pct' => $d['pct']], $lecturaDiaria)) ?>;
            if (diasData.length === 0) {
                doc.setFont('helvetica','italic'); doc.setFontSize(9); doc.setTextColor(gris[0],gris[1],gris[2]);
                doc.text('Sin actividad en este período.', margen, y); y += 8;
            } else {
                var barH = 4, barGap = 3, labelW = 12, numW = 10;
                diasData.forEach(function(d) {
                    var barW = (anchoDoc - labelW - numW) * d.pct / 100;
                    doc.setFont('helvetica','normal'); doc.setFontSize(8); doc.setTextColor(negro[0],negro[1],negro[2]);
                    doc.text(d.dia, margen, y + barH - 0.5);
                    doc.setFillColor(229, 227, 220);
                    doc.roundedRect(margen + labelW, y, anchoDoc - labelW - numW, barH, 1, 1, 'F');
                    if (barW > 0) { doc.setFillColor(17,17,16); doc.roundedRect(margen + labelW, y, barW, barH, 1, 1, 'F'); }
                    doc.setFontSize(7); doc.setTextColor(gris[0],gris[1],gris[2]);
                    doc.text(String(d.val), margen + anchoDoc - numW + 2, y + barH - 0.5);
                    y += barH + barGap;
                });
                y += 4;
            }
        }

        /* ── SECCIÓN 3: Top libros ── */
        if (document.getElementById('pdfTop').checked) {
            checkPagina(40);
            titulo('Top libros del período');
            var librosData = <?= json_encode(array_map(fn($l) => [$l['pos'], $l['titulo'], $l['lecturas'], $l['horas'].'h'], $topLibros)) ?>;
            if (librosData.length === 0) {
                doc.setFont('helvetica','italic'); doc.setFontSize(9); doc.setTextColor(gris[0],gris[1],gris[2]);
                doc.text('Sin lecturas registradas.', margen, y); y += 8;
            } else {
                doc.autoTable({
                    startY: y,
                    head: [['#', 'Título', 'Lecturas', 'Horas']],
                    body: librosData,
                    margin: { left: margen, right: margen },
                    styles: { fontSize: 8, cellPadding: 2.5 },
                    headStyles: { fillColor: [17,17,16], textColor: [240,238,231], fontStyle: 'bold', fontSize: 8 },
                    alternateRowStyles: { fillColor: [248, 247, 243] },
                    columnStyles: { 0: { cellWidth: 8 }, 2: { cellWidth: 18, halign: 'center' }, 3: { cellWidth: 14, halign: 'center' } },
                });
                y = doc.lastAutoTable.finalY + 6;
            }
        }

        /* ── SECCIÓN 4: Suscripciones ── */
        if (document.getElementById('pdfSubs').checked) {
            checkPagina(45);
            titulo('Suscripciones activas por plan');
            var subsData = <?= json_encode(array_map(fn($s) => ['plan' => $s['plan'], 'cantidad' => $s['cantidad'], 'pct' => (int)round($s['cantidad'] / max($maxSubs,1) * 100)], $subs)) ?>;
        var librosAgregadosData = <?= json_encode(array_map(fn($l) => [
            date('d/m/Y', strtotime($l['fecha_publicado'])),
            $l['titulo'],
            $l['autor'],
            $l['categoria'] ?? '—',
            ucfirst($l['tipo'])
        ], $librosAgregados)) ?>;
        var usuariosAgregadosData = <?= json_encode(array_map(fn($u) => [
            date('d/m/Y', strtotime($u['fecha'])),
            $u['nombre'],
            $u['email'],
            $u['plan'],
            ucfirst($u['estado'])
        ], $usuariosAgregados)) ?>;
            var maxVal = <?= $maxSubs ?>;
            if (subsData.length === 0 || maxVal === 0) {
                doc.setFont('helvetica','italic'); doc.setFontSize(9); doc.setTextColor(gris[0],gris[1],gris[2]);
                doc.text('Sin suscripciones.', margen, y); y += 8;
            } else {
                var barH2 = 4, barGap2 = 6, labelW2 = 22, numW2 = 16;
                subsData.forEach(function(s) {
                    var barW = (anchoDoc - labelW2 - numW2) * s.pct / 100;
                    doc.setFont('helvetica','normal'); doc.setFontSize(8); doc.setTextColor(negro[0],negro[1],negro[2]);
                    doc.text(s.plan, margen, y + barH2 - 0.5);
                    doc.setFillColor(229, 227, 220);
                    doc.roundedRect(margen + labelW2, y, anchoDoc - labelW2 - numW2, barH2, 1, 1, 'F');
                    if (barW > 0) { doc.setFillColor(17,17,16); doc.roundedRect(margen + labelW2, y, barW, barH2, 1, 1, 'F'); }
                    doc.setFontSize(7); doc.setTextColor(gris[0],gris[1],gris[2]);
                    doc.text(s.cantidad + ' usuarios', margen + anchoDoc - numW2 + 2, y + barH2 - 0.5);
                    y += barH2 + barGap2;
                });
                y += 2;
            }
        }

        /* ── SECCIÓN 5: Progreso usuarios ── */
        if (document.getElementById('pdfProgreso').checked) {
            checkPagina(40);
            titulo('Progreso de lectura por usuario');
            var progrData = <?= json_encode(array_map(fn($u) => [$u['nombre'], $u['libro'], $u['pct'].'%', $u['ultima']], $progresoUsuarios)) ?>;
            if (progrData.length === 0) {
                doc.setFont('helvetica','italic'); doc.setFontSize(9); doc.setTextColor(gris[0],gris[1],gris[2]);
                doc.text('Sin progreso registrado.', margen, y); y += 8;
            } else {
                doc.autoTable({
                    startY: y,
                    head: [['Usuario', 'Libro', 'Progreso', 'Última sesión']],
                    body: progrData,
                    margin: { left: margen, right: margen },
                    styles: { fontSize: 8, cellPadding: 2.5 },
                    headStyles: { fillColor: [17,17,16], textColor: [240,238,231], fontStyle: 'bold', fontSize: 8 },
                    alternateRowStyles: { fillColor: [248, 247, 243] },
                    columnStyles: { 2: { cellWidth: 18, halign: 'center' }, 3: { cellWidth: 24, halign: 'center' } },
                });
                y = doc.lastAutoTable.finalY + 6;
            }
        }

        /* ── SECCIÓN 6: Libros agregados ── */
        if (document.getElementById('pdfLibrosAgregados').checked) {
            checkPagina(40);
            titulo('Libros agregados en el período');
            if (librosAgregadosData.length === 0) {
                doc.setFont('helvetica','italic'); doc.setFontSize(9); doc.setTextColor(gris[0],gris[1],gris[2]);
                doc.text('Ningún libro agregado en este período.', margen, y); y += 8;
            } else {
                doc.autoTable({
                    startY: y,
                    head: [['Fecha', 'Título', 'Autor', 'Categoría', 'Tipo']],
                    body: librosAgregadosData,
                    margin: { left: margen, right: margen },
                    styles: { fontSize: 8, cellPadding: 2.5, overflow: 'ellipsize' },
                    headStyles: { fillColor: [17,17,16], textColor: [240,238,231], fontStyle: 'bold', fontSize: 8 },
                    alternateRowStyles: { fillColor: [248,247,243] },
                    columnStyles: { 0: { cellWidth: 18 }, 3: { cellWidth: 24 }, 4: { cellWidth: 16 } },
                });
                y = doc.lastAutoTable.finalY + 6;
            }
        }

        /* ── SECCIÓN 7: Usuarios registrados ── */
        if (document.getElementById('pdfUsuariosAgregados').checked) {
            checkPagina(40);
            titulo('Usuarios registrados en el período');
            if (usuariosAgregadosData.length === 0) {
                doc.setFont('helvetica','italic'); doc.setFontSize(9); doc.setTextColor(gris[0],gris[1],gris[2]);
                doc.text('Ningún usuario registrado en este período.', margen, y); y += 8;
            } else {
                doc.autoTable({
                    startY: y,
                    head: [['Fecha', 'Nombre', 'Email', 'Plan', 'Estado']],
                    body: usuariosAgregadosData,
                    margin: { left: margen, right: margen },
                    styles: { fontSize: 8, cellPadding: 2.5, overflow: 'ellipsize' },
                    headStyles: { fillColor: [17,17,16], textColor: [240,238,231], fontStyle: 'bold', fontSize: 8 },
                    alternateRowStyles: { fillColor: [248,247,243] },
                    columnStyles: { 0: { cellWidth: 18 }, 3: { cellWidth: 16 }, 4: { cellWidth: 16 } },
                });
                y = doc.lastAutoTable.finalY + 6;
            }
        }

        /* ── Pie de página en todas las páginas ── */
        var totalPags = doc.internal.getNumberOfPages();
        for (var i = 1; i <= totalPags; i++) {
            doc.setPage(i);
            doc.setDrawColor(220, 218, 210);
            doc.setLineWidth(0.3);
            doc.line(margen, 287, 210 - margen, 287);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(gris[0], gris[1], gris[2]);
            doc.text('Hogwarts Libraries · Panel administrativo', margen, 292);
            doc.text('Página ' + i + ' de ' + totalPags, 210 - margen, 292, { align: 'right' });
        }

        /* ── Descarga ── */
        var fecha = new Date().toISOString().slice(0,10);
        doc.save('reporte-hogwarts-' + fecha + '.pdf');
        cerrarPdf();
    });

})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
