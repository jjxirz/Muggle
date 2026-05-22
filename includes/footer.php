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
                    <li><a href="mi-lista.php">Favoritos</a></li>
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
        </div>
    </div>
</footer>

</body>
</html>