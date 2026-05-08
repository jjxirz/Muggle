<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Obtener la casa seleccionada (por defecto Ravenclaw)
$house = $_GET['house'] ?? $_SESSION['selected_house'] ?? 'ravenclaw';
$validHouses = ['ravenclaw', 'gryffindor', 'slytherin', 'hufflepuff'];
if (!in_array($house, $validHouses)) $house = 'ravenclaw';

// Guardar la casa en sesión
$_SESSION['selected_house'] = $house;

// Configuración de cada casa
$houses_config = [
    'ravenclaw' => [
        'name' => 'Ravenclaw',
        'emoji' => '🦅',
        'logo_img' => 'assets/img/ravenclaw.jpg',
        'color' => '#0e1a2b',
        'secondary' => '#5f7f9e',
        'highlight' => '#cdb57c',
        'text_color' => '#ffffff'
    ],
    'gryffindor' => [
        'name' => 'Gryffindor',
        'emoji' => '🦁',
        'logo_img' => 'assets/img/gryffindor.jpg',
        'color' => '#541011',
        'secondary' => '#5c0000',
        'highlight' => '#eeba30',
        'text_color' => '#ffffff'
    ],
    'slytherin' => [
        'name' => 'Slytherin',
        'emoji' => '🐍',
        'logo_img' => 'assets/img/slytherin.jpg',
        'color' => '#1a472a',
        'secondary' => '#2a623d',
        'highlight' => '#aaaaaa',
        'text_color' => '#ffffff'
    ],
    'hufflepuff' => [
        'name' => 'Hufflepuff',
        'emoji' => '🦡',
        'logo_img' => 'assets/img/hufflepuff.jpg',
        'color' => '#806216',
        'secondary' => '#8d7331',
        'highlight' => '#372e29',
        'text_color' => '#1a1a1a'
    ]
];

$current_house = $houses_config[$house];
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_house['name']; ?> Libraries | Stream de libros mágicos</title>
    <link rel="stylesheet" href="/Muggle/assets/css/style.css">
    <style>
        /* Estilos inline para colores sólidos de la casa seleccionada */
        .main-header {
            background-color: <?php echo $current_house['color']; ?> !important;
            border-bottom: 3px solid <?php echo $current_house['highlight']; ?> !important;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            color: <?php echo $current_house['highlight']; ?> !important;
        }
        
        .btn-primary {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }
        
        .btn-primary:hover {
            background-color: <?php echo $current_house['secondary']; ?> !important;
            color: <?php echo $current_house['text_color']; ?> !important;
        }
        
        .btn-secondary {
            border: 2px solid <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['highlight']; ?> !important;
        }
        
        .btn-secondary:hover {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }
        
        .hero-badge {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }
        
        .category-card {
            background-color: <?php echo $current_house['color']; ?> !important;
        }
        
        .category-card:hover {
            background-color: <?php echo $current_house['secondary']; ?> !important;
        }
        
        .play-btn {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }
        
        .house-btn {
            border: 2px solid <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['text_color']; ?> !important;
        }
        
        .house-btn:hover {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }
        
        .logout-btn:hover {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }
        
        .section-title::after {
            background-color: <?php echo $current_house['highlight']; ?> !important;
        }
    </style>
</head>
<body class="theme-<?php echo $house; ?>">

<header class="main-header">
    <div class="container header-content">
        <div class="logo">
            <div class="row">
                <!-- Logo que cambia según la casa seleccionada -->
                <img src="<?php echo $current_house['logo_img']; ?>" alt="<?php echo $current_house['name']; ?> Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <div class="logo-icon" style="display: none;"><?php echo $current_house['emoji']; ?></div>
                <h1><?php echo strtoupper($current_house['name']); ?> LIBRARIES</h1>
            </div>
        </div>
        
        <nav class="nav-menu">
            <ul>
                <li><a href="#" class="active">Inicio</a></li>
                <li><a href="#">Explorar</a></li>
                <li><a href="#">Mi lista</a></li>
                <li><a href="#">Categorías</a></li>
                <li class="user-nav-item">
                    <div class="user-menu">
                        <span class="user-name">👤 <?php echo htmlspecialchars($user_name); ?></span>
                        <a href="logout.php" class="logout-btn">Cerrar sesión</a>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero">
        <div class="container hero-content">
            <span class="hero-badge">🔥 RECOMENDACIÓN DEL DÍA · CASA <?php echo strtoupper($current_house['name']); ?></span>
            <h1 class="hero-title">Crimen y Castigo</h1>
            <p class="hero-description">Fiódor Dostoyevski · Crimen · Psicología · Clásico</p>
            <p class="hero-synopsis">Raskolnikov, un joven estudiante, planea y comete un asesinato para probar su teoría sobre hombres extraordinarios...</p>
            <div class="hero-buttons">
                <a href="#" class="btn btn-primary">▶ LEER AHORA</a>
                <a href="#" class="btn btn-secondary">➕ MI LISTA</a>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 1: TENDENCIAS -->
    <section class="row-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">🔥 Tendencias ahora mismo</h2>
                <a href="#" class="view-all">Ver todo ›</a>
            </div>
            <div class="books-carousel">
                <div class="book-card">
                    <div class="book-cover">
                        <div class="book-rating">⭐ 4.8</div>
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=1984" alt="1984" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>1984</h4>
                        <p>George Orwell</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <div class="book-rating">⭐ 4.9</div>
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Cien+años+de+soledad" alt="Cien años de soledad" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Cien años de soledad</h4>
                        <p>Gabriel García Márquez</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <div class="book-rating">⭐ 4.7</div>
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=El+principito" alt="El principito" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>El principito</h4>
                        <p>Antoine de Saint-Exupéry</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <div class="book-rating">⭐ 4.6</div>
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Orgullo+y+prejuicio" alt="Orgullo y prejuicio" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Orgullo y prejuicio</h4>
                        <p>Jane Austen</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <div class="book-rating">⭐ 4.9</div>
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Don+Quijote" alt="Don Quijote" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Don Quijote de la Mancha</h4>
                        <p>Miguel de Cervantes</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 2: RECOMENDADOS -->
    <section class="row-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">📖 Recomendados para ti</h2>
                <a href="#" class="view-all">Ver todo ›</a>
            </div>
            <div class="books-carousel">
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Hamlet" alt="Hamlet" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Hamlet</h4>
                        <p>William Shakespeare</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=La+metamorfosis" alt="La metamorfosis" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>La metamorfosis</h4>
                        <p>Franz Kafka</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Moby+Dick" alt="Moby Dick" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Moby Dick</h4>
                        <p>Herman Melville</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=El+gran+Gatsby" alt="El gran Gatsby" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>El gran Gatsby</h4>
                        <p>F. Scott Fitzgerald</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Rayuela" alt="Rayuela" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Rayuela</h4>
                        <p>Julio Cortázar</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 3: CLÁSICOS -->
    <section class="row-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">🏆 Clásicos imperdibles</h2>
                <a href="#" class="view-all">Ver todo ›</a>
            </div>
            <div class="books-carousel">
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=La+divina+comedia" alt="La divina comedia" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>La divina comedia</h4>
                        <p>Dante Alighieri</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Frankenstein" alt="Frankenstein" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Frankenstein</h4>
                        <p>Mary Shelley</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Drácula" alt="Drácula" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Drácula</h4>
                        <p>Bram Stoker</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=El+retrato+de+Dorian+Gray" alt="El retrato de Dorian Gray" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>El retrato de Dorian Gray</h4>
                        <p>Oscar Wilde</p>
                    </div>
                </div>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="https://placehold.co/200x300/1a2a3a/ffffff?text=Crimen+y+castigo" alt="Crimen y castigo" class="cover-img">
                        <div class="book-overlay">
                            <a href="#" class="play-btn">▶</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4>Crimen y castigo</h4>
                        <p>Dostoyevski</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN CATEGORÍAS -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">📚 Explora por categorías</h2>
            <div class="categories-grid">
                <div class="category-card">🎭 Clásicos</div>
                <div class="category-card">🔮 Ciencia ficción</div>
                <div class="category-card">🕵️ Misterio</div>
                <div class="category-card">💕 Romance</div>
                <div class="category-card">🧛 Terror</div>
                <div class="category-card">📖 Filosofía</div>
            </div>
        </div>
    </section>

    <!-- SELECTOR DE CASAS (abajo, como pediste) -->
    <section class="house-selector-section">
        <div class="container">
            <h2 class="section-title">🏰 Cambiar casa de Hogwarts</h2>
            <div class="house-buttons">
                <a href="?house=ravenclaw" class="house-btn <?php echo $house == 'ravenclaw' ? 'active-house' : ''; ?>">
                    🦅 Ravenclaw
                </a>
                <a href="?house=gryffindor" class="house-btn <?php echo $house == 'gryffindor' ? 'active-house' : ''; ?>">
                    🦁 Gryffindor
                </a>
                <a href="?house=slytherin" class="house-btn <?php echo $house == 'slytherin' ? 'active-house' : ''; ?>">
                    🐍 Slytherin
                </a>
                <a href="?house=hufflepuff" class="house-btn <?php echo $house == 'hufflepuff' ? 'active-house' : ''; ?>">
                    🦡 Hufflepuff
                </a>
            </div>
        </div>
    </section>
</main>

<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-col">
                <h4>📚 <?php echo $current_house['name']; ?> Libraries</h4>
                <p>Streaming de libros gratuito.<br>Miles de títulos a un clic.</p>
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
                <h4>Soporte</h4>
                <ul>
                    <li><a href="#">Preguntas frecuentes</a></li>
                    <li><a href="#">Contacto</a></li>
                    <li><a href="#">Términos</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>© <?php echo date('Y'); ?> <?php echo $current_house['name']; ?> Libraries · Casa <?php echo $current_house['name']; ?> 🏰</p>
        </div>
    </div>
</footer>

</body>
</html>