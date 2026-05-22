<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/src/models/LibraryInteractionModel.php';

$user = require_login();
$model = new LibraryInteractionModel();

$page_title = 'Mi lista';
$active_page = 'mi-lista';

$favorites = $model->getFavoritesByUser((int) $user['id_usuario']);
$progress = $model->getRecentProgressByUser((int) $user['id_usuario']);

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
        <div class="stack-grid">
            <?php if (empty($favorites)): ?>
                <article class="category-card">Aún no agregas favoritos desde el catálogo.</article>
            <?php else: ?>
                <?php foreach ($favorites as $book): ?>
                    <article class="category-card content-card">
                        <strong><?php echo htmlspecialchars($book['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="content-card-meta"><?php echo htmlspecialchars($book['autor'] ?? 'Autor no especificado', ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-section" id="progreso">
        <div class="section-header">
            <h3 class="section-title">Progreso reciente</h3>
        </div>
        <div class="stack-grid">
            <?php if (empty($progress)): ?>
                <article class="category-card">No hay progreso guardado todavía.</article>
            <?php else: ?>
                <?php foreach ($progress as $row): ?>
                    <article class="category-card content-card">
                        <strong><?php echo htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="content-card-meta">Página <?php echo (int) $row['pagina_actual']; ?> · <?php echo (int) $row['porcentaje']; ?>%</p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
