<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/includes/catalog_helpers.php';

require_login();

$page_title = 'Explorar';
$active_page = 'explorar';

$baseUrl = app_base_url();
$assetUrl = $baseUrl . '/assets';
$bookImagesPath = __DIR__ . '/assets/img-books';
$bookImagesUrl = $assetUrl . '/img-books';
$bookFiles = getPdfBooksFromFolder(__DIR__ . '/assets/books', $assetUrl, $bookImagesPath, $bookImagesUrl);

$extra_stylesheets = ['assets/css/book-preview.css'];

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Explorar catálogo</h2>
        <p>Descubre títulos disponibles en Hogwarts. Navega por el catálogo y continúa leyendo donde lo dejaste.</p>
    </div>

    <div class="books-carousel">
        <?php if (empty($bookFiles)): ?>
            <article class="category-card">No hay libros cargados todavía.</article>
        <?php else: ?>
            <?php foreach ($bookFiles as $book): ?>
                <?php renderBookCard($book); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-book-preview.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
