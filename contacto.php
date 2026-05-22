<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$page_title = 'Contacto';
$active_page = 'explorar';

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="page-banner">
        <h2>Contacto</h2>
        <p>¿Necesitas ayuda? Escríbenos por los canales oficiales de Hogwarts.</p>
    </div>

    <div class="stack-grid">
        <article class="category-card content-card">
            <strong>Soporte técnico</strong>
            <p class="content-card-meta">soporte@hogwarts.local</p>
        </article>
        <article class="category-card content-card">
            <strong>Atención general</strong>
            <p class="content-card-meta">contacto@hogwarts.local</p>
        </article>
        <article class="category-card content-card">
            <strong>Horario</strong>
            <p class="content-card-meta">Lunes a viernes, 08:00 - 18:00</p>
        </article>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
