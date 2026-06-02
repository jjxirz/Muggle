<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/src/models/LibraryInteractionModel.php';
require_once __DIR__ . '/includes/catalog_helpers.php';

$user = require_login();
$model = new LibraryInteractionModel();

$page_title = 'Mi lista';
$active_page = 'mi-lista';

$extra_stylesheets = ['assets/css/book-preview.css'];

$readingList = $model->getReadingListByUser((int) $user['id_usuario']);
$favorites = $model->getFavoritesByUser((int) $user['id_usuario']);
$progress = $model->getRecentProgressByUser((int) $user['id_usuario']);

$readingListCards = [];
foreach ($readingList as $index => $book) {
    $readingListCards[] = catalog_prepare_db_book($book, $index);
}

$favoriteCards = [];
foreach ($favorites as $index => $book) {
    $favoriteCards[] = catalog_prepare_db_book($book, $index);
}

$progressCards = [];
foreach ($progress as $index => $book) {
    $progressCards[] = catalog_prepare_db_book($book, $index);
    $progressCards[$index]['pages'] = 'Página ' . (int) ($book['pagina_actual'] ?? 0) . ' · ' . (int) ($book['porcentaje'] ?? 0) . '%';
}

$discoverySource = !empty($favoriteCards) ? $favoriteCards : (!empty($readingListCards) ? $readingListCards : $progressCards);
$discoveryBand = catalog_build_discovery_band(
    array_slice($discoverySource, 0, 4),
    [
        'eyebrow' => 'Basado en tu actividad',
        'title' => 'Una recomendación alineada con tu biblioteca personal',
        'description' => 'Tomamos señales suaves de tus favoritos, tu lista y tu avance para mantener visible una siguiente lectura sin invadir la página.',
        'cta_label' => 'Ver sugerencia',
    ]
);

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Mi lista y favoritos</h2>
        <p>Esta vista agrupa tu lista de lectura, tus favoritos y el avance reciente.</p>
    </div>

    <?php catalog_render_discovery_band($discoveryBand); ?>

    <div class="row-section" id="continuar-leyendo">
        <div class="section-header">
            <h3 class="section-title">Continuar leyendo</h3>
        </div>
        <div class="books-carousel">
            <?php if (empty($progressCards)): ?>
                <article class="category-card">Todavía no tienes lecturas con progreso guardado.</article>
            <?php else: ?>
                <?php foreach ($progressCards as $book): ?>
                    <?php renderBookCard($book); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-section" id="lista-lectura">
        <div class="section-header">
            <h3 class="section-title">Lista de lectura</h3>
        </div>
        <div class="books-carousel">
            <?php if (empty($readingListCards)): ?>
                <article class="category-card">Aún no agregas libros a tu lista de lectura.</article>
            <?php else: ?>
                <?php foreach ($readingListCards as $book): ?>
                    <?php renderBookCard($book); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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

</section>

<?php require_once __DIR__ . '/includes/public-book-preview.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
