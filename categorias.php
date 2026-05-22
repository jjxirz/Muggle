<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$page_title = 'Categorías';
$active_page = 'categorias';

$categories = [
    ['name' => 'Clásicos', 'desc' => 'Obras imprescindibles de la literatura universal.'],
    ['name' => 'Fantasía', 'desc' => 'Historias mágicas con mundos extraordinarios.'],
    ['name' => 'Ciencia ficción', 'desc' => 'Futuro, tecnología y sociedades alternativas.'],
    ['name' => 'Misterio', 'desc' => 'Investigación, intriga y tramas de tensión.'],
    ['name' => 'Historia', 'desc' => 'Narrativas basadas en hechos y procesos históricos.'],
    ['name' => 'Filosofía', 'desc' => 'Pensamiento crítico y reflexión sobre la condición humana.'],
];

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Categorías</h2>
        <p>Selecciona una categoría para encontrar lecturas afines a tu estilo.</p>
    </div>

    <div class="stack-grid">
        <?php foreach ($categories as $category): ?>
            <article class="category-card content-card">
                <strong><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <p class="content-card-meta"><?php echo htmlspecialchars($category['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="view-all" href="explorar.php">Explorar</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
