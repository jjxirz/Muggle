<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$page_title = 'Términos';
$active_page = 'explorar';

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Términos de uso</h2>
        <p>Condiciones generales para acceder y utilizar Hogwarts.</p>
    </div>

    <div class="stack-grid">
        <article class="category-card content-card">
            <strong>Uso personal</strong>
            <p class="content-card-meta">La cuenta y lecturas son para uso personal del titular.</p>
        </article>
        <article class="category-card content-card">
            <strong>Protección de datos</strong>
            <p class="content-card-meta">Se almacenan datos mínimos para sesión, favoritos y progreso.</p>
        </article>
        <article class="category-card content-card">
            <strong>Actualizaciones</strong>
            <p class="content-card-meta">Estos términos pueden cambiar para mejorar seguridad y servicio.</p>
        </article>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
