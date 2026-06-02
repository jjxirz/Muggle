<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/includes/catalog_helpers.php';

require_login();

$page_title = 'Categorías';
$active_page = 'categorias';

$baseUrl = app_base_url();
$books = catalog_books_from_db();

$discoveryBand = catalog_build_discovery_band(
    array_slice($books, 0, 4),
    [
        'eyebrow' => 'Cruza entre temas',
        'title' => 'Una sugerencia para salir de tu categoría habitual',
        'description' => 'Inspirado en patrones de discovery más suaves: una recomendación principal y una pequeña pila de rutas relacionadas.',
        'cta_label' => 'Abrir sugerencia',
    ]
);

$groupedBooks = [];
foreach ($books as $book) {
    $cat = (string) ($book['category'] ?? 'Lectura digital');
    if (!isset($groupedBooks[$cat])) {
        $groupedBooks[$cat] = [];
    }
    $groupedBooks[$cat][] = $book;
}

$extra_stylesheets = ['assets/css/book-preview.css'];

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Categorías</h2>
        <p>Explora el catálogo por temática y abre la vista previa desde cada tarjeta.</p>
    </div>

    <?php catalog_render_discovery_band($discoveryBand); ?>

    <?php if (empty($groupedBooks)): ?>
        <article class="category-card">No hay libros cargados todavía.</article>
    <?php else: ?>
        <?php foreach ($groupedBooks as $categoryName => $categoryBooks): ?>
            <div class="row-section">
                <div class="section-header">
                    <h3 class="section-title"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <a href="explorar.php" class="view-all">Ver todo</a>
                </div>

                <div class="books-carousel">
                    <?php foreach ($categoryBooks as $book): ?>
                        <?php renderBookCard($book); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/public-book-preview.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
