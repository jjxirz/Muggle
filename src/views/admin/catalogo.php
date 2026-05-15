<?php
$activePage = 'catalogo';

$tab         = $_GET['tab'] ?? 'listado';
$libroEditar = $libroEditar ?? null;
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
        <a href="?tab=form" class="btn-admin btn-admin--primary">
            <i class="fas fa-plus"></i> Nuevo libro
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <!-- Tabs -->
    <div class="admin-tabs">
        <a href="?tab=listado"    class="admin-tab <?= $tab === 'listado'    ? 'active' : '' ?>">Listado</a>
        <a href="?tab=categorias" class="admin-tab <?= $tab === 'categorias' ? 'active' : '' ?>">Categorías</a>
        <a href="?tab=form"       class="admin-tab <?= $tab === 'form'       ? 'active' : '' ?>">
            <?= $libroEditar ? 'Editar libro' : 'Nuevo libro' ?>
        </a>
    </div>

    <!-- ── TAB: LISTADO ── -->
    <?php if ($tab === 'listado'): ?>
    <div class="admin-card">
        <div class="admin-card__toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscadorLibros" placeholder="Buscar libro o autor…">
            </div>
            <div class="toolbar-filters">
                <select class="filter-select" id="filtroCat">
                    <option value="">Todas las categorías</option>
                    <?php
                    $cats = $categorias ?? ['Terror', 'Clásicos', 'Ciencia', 'Romance', 'Ciencia ficción'];
                    foreach ($cats as $c): ?>
                    <option value="<?= htmlspecialchars(is_array($c) ? $c['nombre'] : $c) ?>">
                        <?= htmlspecialchars(is_array($c) ? $c['nombre'] : $c) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select class="filter-select" id="filtroPlan">
                    <option value="">Todos los planes</option>
                    <option value="free">Free</option>
                    <option value="basico">Básico</option>
                    <option value="plus">Plus</option>
                    <option value="premium">Premium</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="admin-table" id="tablaLibros">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Autor</th>
                        <th>Categoría</th>
                        <th>Plan</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $libros = $libros ?? [
                        ['id' => 1, 'titulo' => 'Cien años de soledad', 'autor' => 'Gabriel García M.', 'categoria' => 'Clásicos',        'plan' => 'premium', 'estado' => 'activo'],
                        ['id' => 2, 'titulo' => 'IT (Eso)',              'autor' => 'Stephen King',       'categoria' => 'Terror',           'plan' => 'plus',    'estado' => 'activo'],
                        ['id' => 3, 'titulo' => 'El origen de las especies', 'autor' => 'Charles Darwin','categoria' => 'Ciencia',          'plan' => 'free',    'estado' => 'inactivo'],
                        ['id' => 4, 'titulo' => 'Dune',                 'autor' => 'Frank Herbert',      'categoria' => 'Ciencia ficción',  'plan' => 'premium', 'estado' => 'activo'],
                    ];
                    foreach ($libros as $libro): ?>
                    <tr data-cat="<?= htmlspecialchars($libro['categoria']) ?>"
                        data-plan="<?= htmlspecialchars($libro['plan']) ?>">
                        <td>
                            <div class="book-cell">
                                <div class="book-thumb"><i class="fas fa-book"></i></div>
                                <div>
                                    <div class="book-name"><?= htmlspecialchars($libro['titulo']) ?></div>
                                    <div class="book-author"><?= htmlspecialchars($libro['autor']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-secondary"><?= htmlspecialchars($libro['autor']) ?></td>
                        <td><?= htmlspecialchars($libro['categoria']) ?></td>
                        <td><span class="badge-plan badge-plan--<?= $libro['plan'] ?>"><?= strtoupper($libro['plan']) ?></span></td>
                        <td><span class="badge-estado badge-estado--<?= $libro['estado'] ?>"><?= ucfirst($libro['estado']) ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="#" class="action-btn" title="Vista previa">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?tab=form&id=<?= $libro['id'] ?>" class="action-btn" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="#"
                                   class="action-btn action-btn--danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Eliminar este libro?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TAB: CATEGORÍAS ── -->
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
                        <th>Libros</th>
                        <th>Audiolibros</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $catList = $catList ?? [
                        ['id' => 1, 'nombre' => 'Terror',          'libros' => 68, 'audios' => 12],
                        ['id' => 2, 'nombre' => 'Clásicos',        'libros' => 55, 'audios' => 8],
                        ['id' => 3, 'nombre' => 'Ciencia',         'libros' => 42, 'audios' => 5],
                        ['id' => 4, 'nombre' => 'Romance',         'libros' => 32, 'audios' => 3],
                        ['id' => 5, 'nombre' => 'Ciencia ficción', 'libros' => 28, 'audios' => 6],
                    ];
                    foreach ($catList as $cat): ?>
                    <tr>
                        <td><div class="book-name"><?= htmlspecialchars($cat['nombre']) ?></div></td>
                        <td><?= $cat['libros'] ?></td>
                        <td><?= $cat['audios'] ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="?tab=form&amp;id=<?= $cat['id'] ?>" class="action-btn" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="#"
                                   class="action-btn action-btn--danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Eliminar esta categoría?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TAB: FORMULARIO NUEVO / EDITAR ── -->
    <?php if ($tab === 'form'): ?>
    <div class="admin-card">
        <div class="admin-card__body">
            <form action="#"
                  method="POST"
                  enctype="multipart/form-data"
                  id="formLibro">

                <?php if ($libroEditar): ?>
                <input type="hidden" name="id" value="<?= $libroEditar['id'] ?>">
                <?php endif; ?>

                <!-- Información del libro -->
                <div class="form-section-label">Información del libro</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Título <span class="required">*</span></label>
                            <input type="text" name="titulo" class="form-control admin-input"
                                   placeholder="Ej: Cien años de soledad"
                                   value="<?= htmlspecialchars($libroEditar['titulo'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Autor <span class="required">*</span></label>
                            <input type="text" name="autor" class="form-control admin-input"
                                   placeholder="Nombre del autor"
                                   value="<?= htmlspecialchars($libroEditar['autor'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Categoría <span class="required">*</span></label>
                            <select name="categoria_id" class="form-control admin-input" required>
                                <option value="">Seleccionar…</option>
                                <?php foreach (($catList ?? []) as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= isset($libroEditar['categoria_id']) && $libroEditar['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Plan requerido <span class="required">*</span></label>
                            <select name="plan" class="form-control admin-input" required>
                                <?php
                                $planes = ['free' => 'Free', 'basico' => 'Básico', 'plus' => 'Plus', 'premium' => 'Premium'];
                                foreach ($planes as $val => $label): ?>
                                <option value="<?= $val ?>"
                                    <?= isset($libroEditar['plan']) && $libroEditar['plan'] === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Descripción / sinopsis</label>
                            <textarea name="descripcion" class="form-control admin-input" rows="3"
                                      placeholder="Sinopsis breve del libro…"><?= htmlspecialchars($libroEditar['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Archivos -->
                <div class="form-section-label">Archivos</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Portada del libro <?= $libroEditar ? '' : '<span class="required">*</span>' ?></label>
                            <div class="upload-area" id="uploadPortada" data-input="portada">
                                <i class="fas fa-image upload-icon"></i>
                                <span>Subir imagen (JPG, PNG)</span>
                                <span class="upload-hint">Máx. 2MB</span>
                            </div>
                            <input type="file" name="portada" id="portada" accept="image/*" class="d-none"
                                   <?= $libroEditar ? '' : 'required' ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Archivo del libro <?= $libroEditar ? '' : '<span class="required">*</span>' ?></label>
                            <div class="upload-area" id="uploadArchivo" data-input="archivo_libro">
                                <i class="fas fa-file-pdf upload-icon"></i>
                                <span>Subir PDF / EPUB</span>
                                <span class="upload-hint">Máx. 50MB</span>
                            </div>
                            <input type="file" name="archivo_libro" id="archivo_libro" accept=".pdf,.epub" class="d-none"
                                   <?= $libroEditar ? '' : 'required' ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Banner del libro</label>
                            <div class="upload-area" id="uploadBanner" data-input="banner">
                                <i class="fas fa-images upload-icon"></i>
                                <span>Subir banner</span>
                                <span class="upload-hint">Recomendado 1200×300px</span>
                            </div>
                            <input type="file" name="banner" id="banner" accept="image/*" class="d-none">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Audiolibro <span class="text-muted">(opcional)</span></label>
                            <div class="upload-area" id="uploadAudio" data-input="audiolibro">
                                <i class="fas fa-music upload-icon"></i>
                                <span>Subir MP3 / AAC</span>
                                <span class="upload-hint">Máx. 200MB</span>
                            </div>
                            <input type="file" name="audiolibro" id="audiolibro" accept=".mp3,.aac,.m4a" class="d-none">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="?tab=listado" class="btn-admin btn-admin--secondary">Cancelar</a>
                    <button type="submit" class="btn-admin btn-admin--primary">
                        <i class="fas fa-check"></i>
                        <?= $libroEditar ? 'Actualizar libro' : 'Guardar libro' ?>
                    </button>
                </div>

            </form>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<!-- ===== MODAL: NUEVA CATEGORÍA ===== -->
<div class="modal-overlay" id="modalNuevaCat">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-tag"></i> Nueva categoría</h2>
            <button class="modal-close" id="modalNuevaCatClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formNuevaCat" action="#" method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" id="catNombre" class="form-control admin-input"
                           placeholder="Ej: Fantasía" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
                    <textarea name="descripcion" class="form-control admin-input" rows="3"
                              placeholder="Descripción breve de la categoría…"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Icono <span class="text-muted">(opcional)</span></label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" name="icono" id="catIcono" class="form-control admin-input"
                               placeholder="Ej: book, star, heart…" style="flex:1;">
                        <span id="catIconoPreview" style="font-size:22px;width:32px;text-align:center;">
                            <i class="fas fa-tag"></i>
                        </span>
                    </div>
                    <small class="text-muted">Nombre del icono de Font Awesome 5 (sin "fa-")</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Visible para usuarios</label>
                    <div style="display:flex;gap:16px;margin-top:4px;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="visible" value="1" checked> Sí
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="visible" value="0"> No
                        </label>
                    </div>
                </div>
                <div class="modal-footer-actions">
                    <button type="button" class="btn-admin btn-admin--secondary" id="modalNuevaCatCancelar">Cancelar</button>
                    <button type="submit" class="btn-admin btn-admin--primary">
                        <i class="fas fa-check"></i> Guardar categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ===== FIN MODAL ===== -->

<style>
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
.modal-overlay.open {
    display: flex;
    opacity: 1;
}
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
</style>

<script>
(function () {
    var overlay   = document.getElementById('modalNuevaCat');
    var btnAbrir  = document.getElementById('btnNuevaCat');
    var btnCerrar = document.getElementById('modalNuevaCatClose');
    var btnCancel = document.getElementById('modalNuevaCatCancelar');
    var iconoInput   = document.getElementById('catIcono');
    var iconoPreview = document.getElementById('catIconoPreview');

    function abrirModal()  { if (overlay) { overlay.classList.add('open');    document.getElementById('catNombre').focus(); } }
    function cerrarModal() { if (overlay)   overlay.classList.remove('open'); }

    if (btnAbrir)  btnAbrir.addEventListener('click', abrirModal);
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancel) btnCancel.addEventListener('click', cerrarModal);
    if (overlay)   overlay.addEventListener('click', function(e){ if (e.target === overlay) cerrarModal(); });

    if (iconoInput && iconoPreview) {
        iconoInput.addEventListener('input', function () {
            var val = this.value.trim().replace(/^fa-?/, '');
            iconoPreview.innerHTML = val
                ? '<i class="fas fa-' + val + '"></i>'
                : '<i class="fas fa-tag"></i>';
        });
    }

    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') cerrarModal(); });
})();
</script>
