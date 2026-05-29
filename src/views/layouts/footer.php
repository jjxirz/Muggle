</div>

</div>

<footer class="admin-footer">
    <span>Hogwarts &copy; <?= date('Y') ?> — Panel administrativo</span>

    <button type="button" class="footer-dev-btn footer-dev-btn--admin" data-open-developers-modal>
        <i class="fas fa-users-cog"></i>
        Ver desarrolladores
    </button>

    <span>Biblioteca digital</span>
</footer>

<?php require_once __DIR__ . '/../../../includes/developers-modal.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(app_url('assets/js/admin.js')); ?>"></script>
</body>
</html>

