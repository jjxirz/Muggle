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
            </div>

            <div class="footer-col">
                <h4>Explorar</h4>
                <ul>
                    <li><a href="#">Tendencias</a></li>
                    <li><a href="#">Novedades</a></li>
                    <li><a href="#">Los más leídos</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Mi cuenta</h4>
                <ul>
                    <li><a href="perfil.php">Perfil</a></li>
                    <li><a href="#">Mi lista</a></li>
                    <li><a href="#">Favoritos</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Soporte</h4>
                <ul>
                    <li><a href="#">Preguntas frecuentes</a></li>
                    <li><a href="#">Contacto</a></li>
                    <li><a href="#">Términos</a></li>
                </ul>
            </div>
        </div>

        <div class="copyright">
            <p>
                © <?= date('Y'); ?> <?= h($current_house['name']); ?> Libraries · Sistema de biblioteca en línea
            </p>
        </div>
    </div>
</footer>

</body>
</html>