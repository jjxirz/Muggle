<?php
require_once __DIR__ . '/../../lib/Auth.php';
require_once __DIR__ . '/../../lib/App.php';

require_admin();

require_once __DIR__ . '/../../controllers/Catalogocontroller.php';

try {
    $controller = new CatalogoController();
    $data       = $controller->handle();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<p style="color:red;padding:20px">Error al cargar el catálogo: '
        . htmlspecialchars($e->getMessage()) . '</p>';
    exit();
}

// Extraer variables para la vista
$tab          = $data['tab'];
$libros       = $data['libros'];
$categorias   = $data['categorias'];   // para selects
$catList      = $data['catList'];      // para la tabla
$libroEditar  = $data['libroEditar'];
$catEditar    = $data['catEditar'];
$flash        = $data['flash'];
$filterSearch = $data['filterSearch'];
$filterCatId  = $data['filterCatId'];
$filterTipo   = $data['filterTipo'];

$activePage   = 'catalogo';
include __DIR__ . '/../layouts/sidebar.php';
?>

<!-- ===== TOPBAR ===== -->
<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Gestión de catálogo</h1>
        <p class="topbar-sub">Libros, categorías y visibilidad</p>
    </div>
    <div class="topbar-actions">
        <?php if ($tab !== 'form'): ?>
        <a href="admin_books.php"
           class="btn-admin btn-admin--primary">
            <i class="fas fa-plus"></i> Nuevo libro
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== FLASH ===== -->
<?php if ($flash): ?>
<div class="flash-msg flash-msg--<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <!-- Tabs -->
    <div class="admin-tabs">
        <a href="?tab=listado"    class="admin-tab <?= $tab === 'listado'    ? 'active' : '' ?>">Listado</a>
        <a href="?tab=categorias" class="admin-tab <?= $tab === 'categorias' ? 'active' : '' ?>">Categorías</a>
        <?php if ($tab === 'form'): ?>
        <a href="?tab=form<?= $libroEditar ? '&id='.(int)$libroEditar['id_libro'] : '' ?>"
           class="admin-tab active">Editar libro</a>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════
         TAB: LISTADO
    ══════════════════════════════════════════ -->
    <?php if ($tab === 'listado'): ?>
    <div class="admin-card">

        <!-- Barra de filtros -->
        <form method="GET" action="" id="formFiltros">
            <input type="hidden" name="tab" value="listado">
            <div class="admin-card__toolbar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           name="q"
                           id="buscadorLibros"
                           placeholder="Buscar libro o autor…"
                           value="<?= htmlspecialchars($filterSearch) ?>"
                           oninput="this.form.submit()">
                </div>
                <div class="toolbar-filters">
                    <select class="filter-select" name="cat" onchange="this.form.submit()">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['id_categoria'] ?>"
                            <?= $filterCatId === (int)$c['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" name="tipo" onchange="this.form.submit()">
                        <option value="">Todos los tipos</option>
                        <?php foreach (['digital' => 'Digital', 'fisico' => 'Físico', 'pdf' => 'PDF', 'epub' => 'EPUB', 'audiolibro' => 'Audiolibro'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $filterTipo === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="admin-table" id="tablaLibros">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Autor</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($libros)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:32px;color:#888;">
                            No se encontraron libros<?= $filterSearch !== '' ? " para «{$filterSearch}»" : '' ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($libros as $libro): ?>
                    <tr data-cat="<?= htmlspecialchars($libro['categoria_nombre']) ?>">
                        <td>
                            <div class="book-cell">
                                <div class="book-thumb">
                                    <?php if (!empty($libro['portada'])): ?>
                                     <img src="<?= htmlspecialchars(app_url($libro['portada'])) ?>"
                                         alt="portada" style="width:36px;height:48px;object-fit:cover;border-radius:4px;">
                                    <?php else: ?>
                                    <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="book-name"><?= htmlspecialchars($libro['titulo']) ?></div>
                                    <div class="book-author"><?= htmlspecialchars($libro['autor'] ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-secondary"><?= htmlspecialchars($libro['autor'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($libro['categoria_nombre']) ?></td>
                        <td><span class="badge-tipo"><?= htmlspecialchars($libro['tipo']) ?></span></td>
                        <td>
                            <div class="action-btns">
                                <?php if (!empty($libro['archivo'])): ?>
                                <a href="<?= htmlspecialchars(app_url($libro['archivo'])) ?>"
                                   target="_blank"
                                   class="action-btn" title="Ver archivo">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?tab=form&id=<?= (int)$libro['id_libro'] ?>"
                                   class="action-btn" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($libro['titulo'])) ?>»? Esta acción no se puede deshacer.')">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action"   value="delete_book">
                                    <input type="hidden" name="id_libro" value="<?= (int)$libro['id_libro'] ?>">
                                    <button type="submit" class="action-btn action-btn--danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════
         TAB: CATEGORÍAS
    ══════════════════════════════════════════ -->
    <?php if ($tab === 'categorias'): ?>
    <div class="admin-card">
        <div class="admin-card__toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscadorCat" placeholder="Buscar categoría…">
            </div>
            <button type="button" class="btn-admin btn-admin--primary ml-auto" id="btnNuevaCat">
                <i class="fas fa-plus"></i> Nueva categoría
            </button>
        </div>

        <div class="table-responsive">
            <table class="admin-table" id="tablaCats">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Libros</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($catList)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:32px;color:#888;">
                            No hay categorías registradas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($catList as $cat): ?>
                    <tr>
                        <td><div class="book-name"><?= htmlspecialchars($cat['nombre']) ?></div></td>
                        <td class="text-secondary" style="font-size:13px;">
                            <?= htmlspecialchars($cat['descripcion'] ?? '—') ?>
                        </td>
                        <td><?= (int)$cat['total_libros'] ?></td>
                        <td>
                            <div class="action-btns">
                                <!-- Editar: abre modal con datos precargados -->
                                <button type="button"
                                        class="action-btn btn-edit-cat"
                                        title="Editar"
                                        data-id="<?= (int)$cat['id_categoria'] ?>"
                                        data-nombre="<?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>"
                                        data-descripcion="<?= htmlspecialchars($cat['descripcion'] ?? '', ENT_QUOTES) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <!-- Eliminar -->
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar la categoría «<?= htmlspecialchars(addslashes($cat['nombre'])) ?>»?')">
                                                                        <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action"       value="delete_category">
                                    <input type="hidden" name="id_categoria" value="<?= (int)$cat['id_categoria'] ?>">
                                    <button type="submit"
                                            class="action-btn action-btn--danger"
                                            title="Eliminar"
                                            <?= (int)$cat['total_libros'] > 0 ? 'disabled title="Tiene libros asociados"' : '' ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════
         TAB: EDITAR LIBRO
    ══════════════════════════════════════════ -->
    <?php if ($tab === 'form'): ?>
    <div class="admin-card">
        <?php if (!$libroEditar): ?>
            <div style="padding:32px;text-align:center;color:#888;">
                <i class="fas fa-exclamation-circle" style="font-size:32px;margin-bottom:12px;"></i>
                <p>No se encontró el libro solicitado.</p>
                <a href="?tab=listado" class="btn-admin btn-admin--secondary">Volver al listado</a>
            </div>
        <?php else: ?>
        <div class="admin-card__body">
            <form action="catalogo.php"
                  method="POST"
                  enctype="multipart/form-data"
                  id="formEditLibro">
                                <?php echo csrf_input(); ?>

                <input type="hidden" name="action"   value="update_book">
                <input type="hidden" name="id_libro" value="<?= (int)$libroEditar['id_libro'] ?>">

                <!-- Información principal -->
                <div class="form-section-label">Información del libro</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Título <span class="required">*</span></label>
                            <input type="text" name="titulo" class="form-control admin-input"
                                   value="<?= htmlspecialchars($libroEditar['titulo']) ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Autor</label>
                            <input type="text" name="autor" class="form-control admin-input"
                                   value="<?= htmlspecialchars($libroEditar['autor'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Categoría <span class="required">*</span></label>
                            <select name="id_categoria" class="form-control admin-input" required>
                                <option value="">Seleccionar…</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int)$c['id_categoria'] ?>"
                                    <?= (int)$libroEditar['id_categoria'] === (int)$c['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-control admin-input">
                                <?php foreach (['digital' => 'Digital', 'fisico' => 'Físico', 'pdf' => 'PDF', 'epub' => 'EPUB', 'audiolibro' => 'Audiolibro'] as $v => $l): ?>
                                <option value="<?= $v ?>"
                                    <?= $libroEditar['tipo'] === $v ? 'selected' : '' ?>>
                                    <?= $l ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" class="form-control admin-input"
                                   value="<?= htmlspecialchars($libroEditar['isbn'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Fecha de publicación</label>
                            <input type="date" name="fecha_publicado" class="form-control admin-input"
                                   value="<?= htmlspecialchars($libroEditar['fecha_publicado'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Descripción / sinopsis</label>
                            <textarea name="descripcion" class="form-control admin-input" rows="4"><?= htmlspecialchars($libroEditar['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Archivos -->
                <div class="form-section-label">Archivos actuales / reemplazar</div>
                <div class="row">
                    <!-- Portada -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Portada</label>
                            <?php if (!empty($libroEditar['portada'])): ?>
                            <div style="margin-bottom:8px;">
                                <img src="<?= htmlspecialchars(app_url($libroEditar['portada'])) ?>"
                                     alt="portada actual"
                                     style="height:80px;border-radius:6px;border:1px solid #ddd;">
                                <small class="text-muted d-block"><?= htmlspecialchars($libroEditar['portada']) ?></small>
                                <!-- Guardar la ruta actual si no se sube nueva -->
                                <input type="hidden" name="portada" value="<?= htmlspecialchars($libroEditar['portada']) ?>">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="portada_file" class="form-control admin-input"
                                   accept="image/*"
                                   style="padding:4px;">
                            <small class="text-muted">Dejar vacío para conservar la portada actual.</small>
                        </div>
                    </div>
                    <!-- PDF -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Archivo PDF / EPUB</label>
                            <?php if (!empty($libroEditar['archivo'])): ?>
                            <div style="margin-bottom:8px;">
                                <a href="<?= htmlspecialchars(app_url($libroEditar['archivo'])) ?>"
                                   target="_blank" class="btn-admin btn-admin--secondary" style="font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-eye"></i> Ver archivo actual
                                </a>
                                <small class="text-muted d-block" style="margin-top:4px;"><?= htmlspecialchars($libroEditar['archivo']) ?></small>
                                <!-- Guardar la ruta actual si no se sube nuevo -->
                                <input type="hidden" name="archivo" value="<?= htmlspecialchars($libroEditar['archivo']) ?>">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="archivo_pdf" class="form-control admin-input"
                                   accept=".pdf,.epub"
                                   style="padding:4px;">
                            <small class="text-muted">Dejar vacío para conservar el archivo actual.</small>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="?tab=listado" class="btn-admin btn-admin--secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn-admin btn-admin--primary">
                        <i class="fas fa-check"></i> Actualizar libro
                    </button>
                </div>

            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>


<!-- ══════════════════════════════════════════════════════
     MODAL: NUEVA CATEGORÍA
══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalCat">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalCatTitulo">
                <i class="fas fa-tag"></i> <span id="modalCatTituloTexto">Nueva categoría</span>
            </h2>
            <button class="modal-close" id="modalCatClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formCat" action="catalogo.php?tab=categorias" method="POST">
                <?php echo csrf_input(); ?>
                <!-- action oculto: create_category | update_category -->
                <input type="hidden" name="action"       id="catAction"  value="create_category">
                <input type="hidden" name="id_categoria" id="catId"      value="">

                <div class="form-group">
                    <label class="form-label">Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" id="catNombre"
                           class="form-control admin-input"
                           placeholder="Ej: Fantasía" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
                    <textarea name="descripcion" id="catDescripcion"
                              class="form-control admin-input" rows="3"
                              placeholder="Descripción breve…"></textarea>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="btn-admin btn-admin--secondary" id="modalCatCancelar">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-admin btn-admin--primary">
                        <i class="fas fa-check"></i>
                        <span id="modalCatBtnTexto">Guardar categoría</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     ESTILOS EXTRAS
══════════════════════════════════════════════════════ -->
<style>
/* Flash */
.flash-msg {
    margin: 12px 24px 0;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
}
.flash-msg--success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.flash-msg--error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* Badge tipo */
.badge-tipo {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 600;
    background: #e5e7eb;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .4px;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 500;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity .22s ease;
}
.modal-overlay.open { display: flex; opacity: 1; }
.modal-box {
    background: #fff;
    border-radius: 10px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 8px 40px rgba(0,0,0,.28);
    transform: translateY(14px);
    transition: transform .22s ease;
    margin: 16px;
}
.modal-overlay.open .modal-box { transform: translateY(0); }
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 1px solid #e5e5e5;
}
.modal-title { font-size: 15px; font-weight: 600; margin: 0; display:flex; align-items:center; gap:8px; }
.modal-close {
    background: none; border: none; font-size: 24px; line-height: 1;
    cursor: pointer; color: #888; padding: 0 4px;
}
.modal-close:hover { color: #e24b4a; }
.modal-body { padding: 20px 22px 8px; }
.modal-footer-actions {
    display: flex; justify-content: flex-end; gap: 10px;
    padding: 16px 0 6px;
}

/* Búsqueda en categorías */
#buscadorCat { width: 100%; }
</style>

<!-- ══════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════ -->
<script>
(function () {
    // ── Modal categoría ──────────────────────────────
    var overlay   = document.getElementById('modalCat');
    var btnAbrir  = document.getElementById('btnNuevaCat');
    var btnCerrar = document.getElementById('modalCatClose');
    var btnCancel = document.getElementById('modalCatCancelar');

    var catAction      = document.getElementById('catAction');
    var catId          = document.getElementById('catId');
    var catNombre      = document.getElementById('catNombre');
    var catDescripcion = document.getElementById('catDescripcion');
    var tituloTexto    = document.getElementById('modalCatTituloTexto');
    var btnTexto       = document.getElementById('modalCatBtnTexto');

    function abrirModalNuevo() {
        catAction.value      = 'create_category';
        catId.value          = '';
        catNombre.value      = '';
        catDescripcion.value = '';
        tituloTexto.textContent = 'Nueva categoría';
        btnTexto.textContent    = 'Guardar categoría';
        overlay.classList.add('open');
        setTimeout(function(){ catNombre.focus(); }, 80);
    }

    function abrirModalEditar(id, nombre, descripcion) {
        catAction.value      = 'update_category';
        catId.value          = id;
        catNombre.value      = nombre;
        catDescripcion.value = descripcion;
        tituloTexto.textContent = 'Editar categoría';
        btnTexto.textContent    = 'Actualizar categoría';
        overlay.classList.add('open');
        setTimeout(function(){ catNombre.focus(); }, 80);
    }

    function cerrarModal() {
        overlay.classList.remove('open');
    }

    if (btnAbrir)  btnAbrir.addEventListener('click', abrirModalNuevo);
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancel) btnCancel.addEventListener('click', cerrarModal);
    if (overlay)   overlay.addEventListener('click', function(e){ if (e.target === overlay) cerrarModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') cerrarModal(); });

    // Botones "editar categoría" en la tabla
    document.querySelectorAll('.btn-edit-cat').forEach(function(btn) {
        btn.addEventListener('click', function() {
            abrirModalEditar(
                this.dataset.id,
                this.dataset.nombre,
                this.dataset.descripcion
            );
        });
    });

    // ── Buscador en tabla de categorías ─────────────
    var buscadorCat = document.getElementById('buscadorCat');
    if (buscadorCat) {
        buscadorCat.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#tablaCats tbody tr').forEach(function(row) {
                var nombre = row.querySelector('.book-name');
                if (nombre) {
                    row.style.display = nombre.textContent.toLowerCase().includes(q) ? '' : 'none';
                }
            });
        });
    }

    // ── Auto-envío del form de filtros con debounce ──
    var buscadorLibros = document.getElementById('buscadorLibros');
    if (buscadorLibros) {
        var timer;
        buscadorLibros.addEventListener('input', function() {
            clearTimeout(timer);
            var form = this.form;
            timer = setTimeout(function(){ form.submit(); }, 350);
        });
        // Cancelar el oninput inline para evitar doble submit
        buscadorLibros.removeAttribute('oninput');
    }
})();
</script>