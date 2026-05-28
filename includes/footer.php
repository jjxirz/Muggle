<?php
if (!isset($current_house)) {
    $current_house = [
        'name' => 'Hogwarts',
        'icon' => 'fa-book-open'
    ];
}
?>

</main>

<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-col">
                <h4>
                    <i class="fas <?= h($current_house['icon']); ?>"></i>
                    <?= h($current_house['name']); ?> Libraries
                </h4>
                <p>
                    Biblioteca en línea con estilo de streaming.<br>
                    Tus libros favoritos en un solo lugar.
                </p>

                <button type="button" class="footer-dev-btn" id="openDevelopersModal">
                    <i class="fas fa-users-cog"></i>
                    Ver desarrolladores
                </button>
            </div>

            <div class="footer-col">
                <h4>Explorar</h4>
                <ul>
                    <li><a href="explorar.php">Explorar</a></li>
                    <li><a href="categorias.php">Categorías</a></li>
                    <li><a href="index.php#catalogo-pdf">Catálogo destacado</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Mi cuenta</h4>
                <ul>
                    <li><a href="perfil.php">Perfil</a></li>
                    <li><a href="mi-lista.php">Mi lista</a></li>
                    <li><a href="mi-lista.php#favoritos">Favoritos</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Soporte</h4>
                <ul>
                    <li><a href="faq.php">Preguntas frecuentes</a></li>
                    <li><a href="contacto.php">Contacto</a></li>
                    <li><a href="terminos.php">Términos</a></li>
                </ul>
            </div>
        </div>

        <div class="copyright">
            <p>
                © <?= date('Y'); ?> <?= h($current_house['name']); ?> Libraries · Sistema de biblioteca en línea
            </p>
            <p class="footer-dev-credit">
                Desarrollado por estudiantes de Ingeniería en Sistemas.
            </p>
        </div>
    </div>
</footer>

<div class="developers-modal-overlay" id="developersModal" aria-hidden="true">
    <div class="developers-modal" role="dialog" aria-modal="true" aria-labelledby="developersModalTitle">
        <div class="developers-modal-header">
            <div>
                <span class="developers-modal-label">Créditos del proyecto</span>
                <h3 id="developersModalTitle">Desarrolladores</h3>
            </div>

            <button type="button" class="developers-modal-close" id="closeDevelopersModal" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="developers-modal-body">
            <div class="developers-company-box">
                <i class="fas fa-building"></i>
                <div>
                    <strong>Empresa ficticia</strong>
                    <span>Equipo de Desarrollo Anónimo</span>
                </div>
            </div>

            <p>
                Este sistema de biblioteca virtual fue desarrollado como proyecto académico por estudiantes de Ingeniería en Sistemas.
            </p>

            <div class="developers-list">
                <article class="developer-item">
                    <i class="fas fa-user"></i>
                    <span>David</span>
                </article>

                <article class="developer-item">
                    <i class="fas fa-user"></i>
                    <span>Rene</span>
                </article>

                <article class="developer-item">
                    <i class="fas fa-user"></i>
                    <span>Aldo</span>
                </article>

                <article class="developer-item">
                    <i class="fas fa-user"></i>
                    <span>Fredis</span>
                </article>

                <article class="developer-item">
                    <i class="fas fa-user"></i>
                    <span>Jeremias</span>
                </article>
            </div>
        </div>

        <div class="developers-modal-footer">
            <button type="button" class="developers-modal-action" id="acceptDevelopersModal">
                Entendido
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('developersModal');
        const openBtn = document.getElementById('openDevelopersModal');
        const closeBtn = document.getElementById('closeDevelopersModal');
        const acceptBtn = document.getElementById('acceptDevelopersModal');

        if (!modal || !openBtn || !closeBtn || !acceptBtn) {
            return;
        }

        function openModal() {
            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            modal.classList.remove('is-visible');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        acceptBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
                closeModal();
            }
        });
    });
</script>

</body>

</html>