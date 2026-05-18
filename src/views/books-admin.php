<?php
$flash = $flash ?? null;
$categories = $categories ?? [];
$books = $books ?? [];
$book = $editingBook ?? [
    'id_libro' => null,
    'isbn' => '',
    'doi' => '',
    'titulo' => '',
    'autor' => '',
    'descripcion' => '',
    'portada' => '',
    'archivo' => '',
    'tipo' => 'digital',
    'id_categoria' => 1,
    'id_banner' => null,
    'banner_imagen' => '',
    'fecha_publicado' => '',
];
$existingPdfFiles = $existingPdfFiles ?? [];
$isEditing = $book['id_libro'] !== null;

function normalizeBannerImageUrl(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (preg_match('/^(https?:)?\/\//i', $path) === 1) {
        return $path;
    }

    if (strpos($path, '/Muggle/') === 0) {
        return $path;
    }

    if (strpos($path, '/assets/') === 0) {
        return '/Muggle' . $path;
    }

    if (strpos($path, 'assets/') === 0) {
        return '/Muggle/' . $path;
    }

    return $path;
}

$activePage = 'catalogo';
include __DIR__ . '/layouts/sidebar.php';
?>
<style>
        .btn {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #111110; color: #fff; }
        .btn-secondary { background: #5F5E5A; color: #fff; }
        .btn-danger { background: #E24B4A; color: #fff; }
        .btn-icon {
            border: 0;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn-icon:hover { opacity: .8; }
        .btn-icon-edit  { background: #5F5E5A; color: #fff; }
        .btn-icon-delete { background: #E24B4A; color: #fff; }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 18px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.07);
        }
        .card h2 {
            margin-top: 0;
            color: #111110;
        }
        .flash {
            margin-bottom: 12px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
        }
        .flash.success { background: #EAF3DE; color: #3B6D11; }
        .flash.error { background: #FCEBEB; color: #A32D2D; }

        label {
            display: block;
            margin-top: 12px;
            font-size: 14px;
            font-weight: 700;
        }
        input, textarea, select {
            width: 100%;
            margin-top: 6px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
        }
        textarea { min-height: 120px; resize: vertical; }

        .inline {
            display: flex;
            gap: 8px;
            margin-top: 6px;
        }
        .inline input { margin-top: 0; }
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #f9fafb; }
        .cover {
            width: 50px;
            height: 75px;
            object-fit: cover;
            border-radius: 4px;
            background: #eee;
        }
        .banner-thumb {
            width: 90px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            background: #eee;
            border: 1px solid #e5e7eb;
        }
        .banner-cover {
            width: 100%;
            max-width: 260px;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
            border: 1px solid #e5e7eb;
        }
        .section-title {
            margin: 24px 0 12px;
            color: #111110;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .empty {
            padding: 16px;
            background: #fff8e1;
            border-radius: 8px;
            color: #92400e;
            font-weight: 700;
        }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
        .admin-content {
            padding-top: 18px;
        }
    </style>

<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Panel de Administracion de Libros</h1>
        <p class="topbar-sub">Gestion de catalogo, archivos y banner por libro</p>
    </div>
</div>

<div class="admin-content">

    <?php if ($flash !== null): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2><?= $isEditing ? 'Editar libro' : 'Nuevo libro' ?></h2>
            <form method="POST" action="admin_books.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create' ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id_libro" value="<?= (int) $book['id_libro'] ?>">
                <?php endif; ?>

                <label for="identifier">ISBN o DOI</label>
                <div class="inline">
                    <input type="text" id="identifier" value="<?= htmlspecialchars((string) ($book['isbn'] ?: $book['doi'])) ?>" placeholder="9788497594257 o 10.1000/xyz123">
                    <select id="identifierMode" style="max-width: 130px;">
                        <option value="isbn">ISBN</option>
                        <option value="doi">DOI</option>
                    </select>
                    <button type="button" class="btn btn-secondary" id="metadataLookupBtn">Autocompletar</button>
                </div>

                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars((string) $book['isbn']) ?>" placeholder="9788497594257">

                <label for="doi">DOI</label>
                <input type="text" id="doi" name="doi" value="<?= htmlspecialchars((string) $book['doi']) ?>" placeholder="10.1000/xyz123">

                <label for="titulo">Titulo *</label>
                <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars((string) $book['titulo']) ?>" list="titleSuggestions" autocomplete="off" required>
                <datalist id="titleSuggestions"></datalist>

                <label for="autor">Autor *</label>
                <input type="text" id="autor" name="autor" value="<?= htmlspecialchars((string) $book['autor']) ?>" required>

                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion"><?= htmlspecialchars((string) $book['descripcion']) ?></textarea>

                <label for="portada">URL portada</label>
                <input type="url" id="portada" name="portada" value="<?= htmlspecialchars((string) $book['portada']) ?>">

                <label for="archivo">Archivo / URL de lectura</label>
                <input type="text" id="archivo" name="archivo" value="<?= htmlspecialchars((string) $book['archivo']) ?>" placeholder="assets/books/mi-libro.pdf o URL externa">

                <label for="archivo_existente">Seleccionar PDF existente en el proyecto</label>
                <select id="archivo_existente" name="archivo_existente">
                    <option value="">-- Sin seleccionar --</option>
                    <?php foreach ($existingPdfFiles as $pdfFile): ?>
                        <option value="<?= htmlspecialchars($pdfFile) ?>" <?= (string) $book['archivo'] === (string) $pdfFile ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pdfFile) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="archivo_pdf">Subir nuevo PDF</label>
                <input type="file" id="archivo_pdf" name="archivo_pdf" accept="application/pdf">

                <label for="tipo">Tipo *</label>
                <select id="tipo" name="tipo" required>
                    <?php
                    $types = ['fisico' => 'Fisico', 'digital' => 'Digital', 'audiolibro' => 'Audiolibro'];
                    foreach ($types as $value => $label):
                    ?>
                        <option value="<?= $value ?>" <?= $book['tipo'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="id_categoria">Categoria *</label>
                <select id="id_categoria" name="id_categoria" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id_categoria'] ?>" <?= (int) $book['id_categoria'] === (int) $category['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="banner_image">Nuevo banner para este libro (opcional, max 20MB)</label>
                <input type="file" id="banner_image" name="banner_image" accept="image/*">

                <?php if ($isEditing && !empty($book['banner_imagen'])): ?>
                    <label>Banner actual del libro</label>
                    <img src="<?= htmlspecialchars(normalizeBannerImageUrl((string) $book['banner_imagen'])) ?>" alt="Banner del libro" class="banner-cover">
                <?php endif; ?>

                <label for="fecha_publicado">Fecha publicado</label>
                <input type="date" id="fecha_publicado" name="fecha_publicado" value="<?= htmlspecialchars((string) $book['fecha_publicado']) ?>">

                <div style="margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap;">
                    <button class="btn btn-primary" type="submit"><?= $isEditing ? 'Guardar cambios' : 'Crear libro' ?></button>
                    <?php if ($isEditing): ?>
                        <a href="admin_books.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Listado de libros</h2>
            <?php if (count($books) === 0): ?>
                <div class="empty">No hay libros cargados todavia.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Portada</th>
                                <th>Banner</th>
                                <th>Titulo</th>
                                <th>Autor</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($books as $row): ?>
                            <tr>
                                <td><?= (int) $row['id_libro'] ?></td>
                                <td>
                                    <?php if (!empty($row['portada'])): ?>
                                        <img src="<?= htmlspecialchars($row['portada']) ?>" alt="Portada" class="cover">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['banner_imagen'])): ?>
                                        <img src="<?= htmlspecialchars(normalizeBannerImageUrl((string) $row['banner_imagen'])) ?>" alt="Banner" class="banner-thumb">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['titulo']) ?></td>
                                <td><?= htmlspecialchars($row['autor']) ?></td>
                                <td><?= htmlspecialchars($row['tipo']) ?></td>
                                <td><?= htmlspecialchars($row['categoria']) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn-icon btn-icon-edit" href="admin_books.php?action=edit&id=<?= (int) $row['id_libro'] ?>" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                        <form method="POST" action="admin_books.php" onsubmit="return confirm('¿Seguro que quieres eliminar este libro?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_libro" value="<?= (int) $row['id_libro'] ?>">
                                            <button class="btn-icon btn-icon-delete" type="submit" title="Eliminar"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
const identifierInput = document.getElementById('identifier');
const identifierModeInput = document.getElementById('identifierMode');
const metadataLookupBtn = document.getElementById('metadataLookupBtn');
const titleInput = document.getElementById('titulo');
const titleSuggestions = document.getElementById('titleSuggestions');

metadataLookupBtn.addEventListener('click', async () => {
    const identifier = identifierInput.value.trim();
    const mode = identifierModeInput.value;

    if (!identifier) {
        alert('Ingresa un ISBN o DOI para autocompletar.');
        return;
    }

    metadataLookupBtn.disabled = true;
    metadataLookupBtn.textContent = 'Buscando...';

    try {
        const response = await fetch(`admin_books.php?action=fetch_metadata&mode=${encodeURIComponent(mode)}&identifier=${encodeURIComponent(identifier)}`);
        const payload = await response.json();

        if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'No se encontro informacion.');
        }

        const data = payload.data;
        if (data.isbn) document.getElementById('isbn').value = data.isbn;
        if (data.doi) document.getElementById('doi').value = data.doi;
        if (data.titulo) document.getElementById('titulo').value = data.titulo;
        if (data.autor) document.getElementById('autor').value = data.autor;
        if (data.descripcion) document.getElementById('descripcion').value = data.descripcion;
        if (data.portada) document.getElementById('portada').value = data.portada;
        if (data.fecha_publicado) document.getElementById('fecha_publicado').value = data.fecha_publicado;
    } catch (error) {
        alert(error.message || 'No fue posible consultar metadata del libro.');
    } finally {
        metadataLookupBtn.disabled = false;
        metadataLookupBtn.textContent = 'Autocompletar';
    }
});

let titleSearchTimeout = null;
titleInput.addEventListener('input', () => {
    const query = titleInput.value.trim();
    clearTimeout(titleSearchTimeout);

    if (query.length < 3) {
        titleSuggestions.innerHTML = '';
        return;
    }

    titleSearchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`admin_books.php?action=search_title&q=${encodeURIComponent(query)}`);
            const payload = await response.json();
            if (!response.ok || !payload.ok || !Array.isArray(payload.data)) {
                return;
            }

            titleSuggestions.innerHTML = '';
            payload.data.forEach((item) => {
                if (!item.titulo) {
                    return;
                }

                const option = document.createElement('option');
                option.value = item.titulo;
                const parts = [];
                if (item.autor) parts.push(item.autor);
                if (item.isbn) parts.push(`ISBN ${item.isbn}`);
                if (parts.length > 0) {
                    option.label = parts.join(' · ');
                }
                titleSuggestions.appendChild(option);
            });
        } catch (error) {
            // Silent fail for title suggestions.
        }
    }, 350);
});
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
