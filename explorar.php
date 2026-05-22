<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$page_title = 'Explorar';
$active_page = 'explorar';

require_once __DIR__ . '/includes/header.php';

$bookFiles = [];
$booksDir = __DIR__ . '/assets/books';
if (is_dir($booksDir)) {
    $items = scandir($booksDir);
    if ($items !== false) {
        foreach ($items as $item) {
            if (is_file($booksDir . '/' . $item) && strtolower(pathinfo($item, PATHINFO_EXTENSION)) === 'pdf') {
                $bookFiles[] = $item;
            }
        }
    }
}
sort($bookFiles);
?>
<section class="container">
    <div class="page-banner">
        <h2>Explorar catálogo</h2>
        <p>Descubre títulos disponibles en Hogwarts. Navega por el catálogo y continúa leyendo donde lo dejaste.</p>
    </div>

    <div class="stack-grid">
        <?php if (empty($bookFiles)): ?>
            <article class="category-card">No hay libros cargados todavía.</article>
        <?php else: ?>
            <?php foreach ($bookFiles as $file): ?>
                <?php $title = htmlspecialchars(pathinfo($file, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8'); ?>
                <article class="category-card content-card">
                    <strong><?php echo $title; ?></strong>
                    <p class="content-card-meta">Formato PDF</p>
                    <a class="view-all" href="reader.php?book=<?php echo rawurlencode($file); ?>">Abrir lector</a>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
