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
        const openButtons = document.querySelectorAll('[data-open-developers-modal]');
        const closeBtn = document.getElementById('closeDevelopersModal');
        const acceptBtn = document.getElementById('acceptDevelopersModal');

        if (!modal || openButtons.length === 0 || !closeBtn || !acceptBtn) {
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

        openButtons.forEach(function(button) {
            button.addEventListener('click', openModal);
        });

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