<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/includes/catalog_helpers.php';

require_login();

$page_title = 'Explorar';
$active_page = 'explorar';

$baseUrl = app_base_url();
$bookFiles = catalog_books_from_db();

$discoveryBand = catalog_build_discovery_band(
    array_slice($bookFiles, 0, 4),
    [
        'eyebrow' => 'Explora sin perder el hilo',
        'title' => 'Una recomendación breve para seguir descubriendo',
        'description' => 'Tomamos un título con buena entrada visual y lo acompañamos con tres opciones cercanas para que explores sin salir del flujo.',
        'cta_label' => 'Ver recomendación',
    ]
);

$extra_stylesheets = ['assets/css/book-preview.css'];

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Explorar catálogo</h2>
        <p>Descubre títulos disponibles en Hogwarts. Navega por el catálogo y continúa leyendo donde lo dejaste.</p>
    </div>

    <?php catalog_render_discovery_band($discoveryBand); ?>

    <div class="books-carousel">
        <?php if (empty($bookFiles)): ?>
            <article class="category-card">No hay libros en el catálogo todavía.</article>
        <?php else: ?>
            <?php foreach ($bookFiles as $book): ?>
                <?php renderBookCard($book); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-book-preview.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
