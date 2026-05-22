<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$page_title = 'Preguntas frecuentes';
$active_page = 'explorar';

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Preguntas frecuentes</h2>
        <p>Respuestas rápidas sobre uso de la plataforma Hogwarts.</p>
    </div>

    <div class="stack-grid">
        <article class="category-card content-card">
            <strong>¿Cómo cambio de tema?</strong>
            <p class="content-card-meta">Desde Perfil puedes activar/desactivar tema y elegir casa.</p>
        </article>
        <article class="category-card content-card">
            <strong>¿Cómo guardo progreso?</strong>
            <p class="content-card-meta">El progreso se guarda automáticamente al avanzar páginas en el lector.</p>
        </article>
        <article class="category-card content-card">
            <strong>¿Cómo agrego favoritos?</strong>
            <p class="content-card-meta">En la vista previa de un libro usa el botón Mi lista para agregar/quitar.</p>
        </article>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
