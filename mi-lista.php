<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/src/models/LibraryInteractionModel.php';
require_once __DIR__ . '/includes/catalog_helpers.php';

$user = require_login();
$model = new LibraryInteractionModel();

$page_title = 'Mi lista';
$active_page = 'mi-lista';

$extra_stylesheets = ['assets/css/book-preview.css'];

$favorites = $model->getFavoritesByUser((int) $user['id_usuario']);
$progress = $model->getRecentProgressByUser((int) $user['id_usuario']);

$favoriteCards = [];
foreach ($favorites as $index => $book) {
    $favoriteCards[] = catalog_prepare_db_book($book, $index);
}

$progressCards = [];
foreach ($progress as $index => $book) {
    $progressCards[] = catalog_prepare_db_book($book, $index);
    $progressCards[$index]['pages'] = 'Página ' . (int) ($book['pagina_actual'] ?? 0) . ' · ' . (int) ($book['porcentaje'] ?? 0) . '%';
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Mi lista y favoritos</h2>
        <p>Esta vista agrupa tus favoritos y el avance reciente de lectura.</p>
    </div>

    <div class="row-section" id="favoritos">
        <div class="section-header">
            <h3 class="section-title">Favoritos</h3>
        </div>
        <div class="books-carousel">
            <?php if (empty($favoriteCards)): ?>
                <article class="category-card">Aún no agregas favoritos desde el catálogo.</article>
            <?php else: ?>
                <?php foreach ($favoriteCards as $book): ?>
                    <?php renderBookCard($book); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-section" id="progreso">
        <div class="section-header">
            <h3 class="section-title">Progreso reciente</h3>
        </div>
        <div class="books-carousel">
            <?php if (empty($progressCards)): ?>
                <article class="category-card">No hay progreso guardado todavía.</article>
            <?php else: ?>
                <?php foreach ($progressCards as $book): ?>
                    <?php renderBookCard($book); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-book-preview.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
