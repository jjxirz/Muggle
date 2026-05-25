<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$baseUrl = app_base_url();
$assetUrl = $baseUrl . '/assets';
$bookImagesPath = __DIR__ . '/assets/img-books';
$bookImagesUrl = $assetUrl . '/img-books';

$themeEnabled = (bool) ($_SESSION['theme_enabled'] ?? true);
$brandTitle = 'HOGWARTS';

$house = $_GET['house'] ?? $_SESSION['selected_house'] ?? 'ravenclaw';
$validHouses = ['ravenclaw', 'gryffindor', 'slytherin', 'hufflepuff'];

if (!$themeEnabled || !in_array($house, $validHouses, true)) {
    $house = 'ravenclaw';
}

$_SESSION['selected_house'] = $house;

$houses_config = [
    'ravenclaw' => [
        'name' => 'Ravenclaw',
        'icon' => 'fa-feather-alt',
        'logo_img' => 'assets/img/ravenclaw.jpg',
        'color' => '#121212',
        'secondary' => '#1f1f1f',
        'highlight' => '#a3b7d6',
        'text_color' => '#ffffff'
    ],
    'gryffindor' => [
        'name' => 'Gryffindor',
        'icon' => 'fa-shield-alt',
        'logo_img' => 'assets/img/gryffindor.jpg',
        'color' => '#121212',
        'secondary' => '#1f1f1f',
        'highlight' => '#d6a3a3',
        'text_color' => '#ffffff'
    ],
    'slytherin' => [
        'name' => 'Slytherin',
        'icon' => 'fa-dragon',
        'logo_img' => 'assets/img/slytherin.jpg',
        'color' => '#121212',
        'secondary' => '#1f1f1f',
        'highlight' => '#a3d6b7',
        'text_color' => '#ffffff'
    ],
    'hufflepuff' => [
        'name' => 'Hufflepuff',
        'icon' => 'fa-seedling',
        'logo_img' => 'assets/img/hufflepuff.jpg',
        'color' => '#121212',
        'secondary' => '#1f1f1f',
        'highlight' => '#d6c6a3',
        'text_color' => '#ffffff'
    ]
];

$current_house = $houses_config[$house];
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? '';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cleanPdfTitle(string $filename): string
{
    $title = pathinfo($filename, PATHINFO_FILENAME);

    $title = (string) preg_replace('/_?\d{8}_\d{6}$/', '', $title);
    $title = (string) preg_replace('/^\d+[\s_\-]*/', '', $title);
    $title = str_replace(['_', '-'], ' ', $title);
    $title = (string) preg_replace('/\s+/', ' ', $title);
    $title = trim($title);

    if ($title === '') {
        return 'Obra sin identificar';
    }

    return mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');
}

function getPdfSizeLabel(string $filePath): string
{
    if (!is_file($filePath)) {
        return 'Archivo disponible';
    }

    $bytes = filesize($filePath);

    if ($bytes === false) {
        return 'Archivo disponible';
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }

    return round($bytes / 1024) . ' KB';
}

/**
 * @return array{title:string, author:string, category:string, year:string, tags:string, description:string}
 */
function guessBookMeta(string $filename, string $defaultTitle): array
{
    $name = mb_strtolower($filename, 'UTF-8');

    $meta = [
        'title' => $defaultTitle,
        'author' => 'Autor no especificado',
        'category' => 'Lectura digital',
        'year' => 'Disponible',
        'tags' => 'Biblioteca, Lectura digital',
        'description' => 'Obra disponible en el catálogo digital de la biblioteca.'
    ];

    if (strpos($name, 'quijote') !== false) {
        $meta['title'] = 'Don Quijote de la Mancha';
        $meta['author'] = 'Miguel de Cervantes';
        $meta['category'] = 'Novela clásica';
        $meta['year'] = '1605';
        $meta['tags'] = 'Novela clásica, Literatura española';
        $meta['description'] = 'Una de las obras más importantes de la literatura española, centrada en las aventuras de Don Quijote y Sancho Panza.';
        return $meta;
    }

    if (strpos($name, 'arte') !== false && strpos($name, 'guerra') !== false) {
        $meta['title'] = 'El arte de la guerra';
        $meta['author'] = 'Sun Tzu';
        $meta['category'] = 'Estrategia';
        $meta['year'] = 'Clásico';
        $meta['tags'] = 'Estrategia, Liderazgo';
        $meta['description'] = 'Texto clásico sobre estrategia, planificación, liderazgo y toma de decisiones.';
        return $meta;
    }

    if (strpos($name, 'principito') !== false) {
        $meta['title'] = 'El Principito';
        $meta['author'] = 'Antoine de Saint-Exupéry';
        $meta['category'] = 'Novela corta';
        $meta['year'] = '1943';
        $meta['tags'] = 'Novela corta, Literatura universal';
        $meta['description'] = 'Relato literario sobre la amistad, la imaginación y la forma en que los adultos entienden el mundo.';
        return $meta;
    }

    if (strpos($name, '1984') !== false) {
        $meta['title'] = '1984';
        $meta['author'] = 'George Orwell';
        $meta['category'] = 'Distopía';
        $meta['year'] = '1949';
        $meta['tags'] = 'Distopía, Literatura política';
        $meta['description'] = 'Novela distópica sobre vigilancia, poder y control social.';
        return $meta;
    }

    if (preg_match('/^(.*?)\s+autor\s+(.*?)$/i', $defaultTitle, $matches)) {
        $meta['title'] = trim($matches[1]);
        $meta['author'] = trim($matches[2]);
    }

    return $meta;
}

function normalizeBookName(string $value): string
{
    $value = pathinfo($value, PATHINFO_FILENAME);
    $value = mb_strtolower($value, 'UTF-8');

    $replacements = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ñ' => 'n',
        'ü' => 'u'
    ];

    $value = strtr($value, $replacements);
    $value = (string) preg_replace('/_?\d{8}_\d{6}$/', '', $value);
    $value = (string) preg_replace('/^\d+[\s_\-]*/', '', $value);
    $value = (string) preg_replace('/[^a-z0-9]/', '', $value);

    return $value;
}

function findBookImage(string $pdfFilename, string $bookTitle, string $imageFolderPath, string $imageUrl): string
{
    if (!is_dir($imageFolderPath)) {
        return '';
    }

    $manualImages = [
        '1984.pdf' => '1884.jpeg'
    ];

    if (isset($manualImages[$pdfFilename])) {
        $manualPath = $imageFolderPath . DIRECTORY_SEPARATOR . $manualImages[$pdfFilename];

        if (is_file($manualPath)) {
            return $imageUrl . '/' . rawurlencode($manualImages[$pdfFilename]);
        }
    }

    $files = scandir($imageFolderPath);

    if ($files === false) {
        return '';
    }

    $validExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $pdfName = normalizeBookName($pdfFilename);
    $titleName = normalizeBookName($bookTitle);

    foreach ($files as $file) {
        $filePath = $imageFolderPath . DIRECTORY_SEPARATOR . $file;
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!is_file($filePath) || !in_array($extension, $validExtensions, true)) {
            continue;
        }

        $imageName = normalizeBookName($file);

        if ($imageName === $pdfName || $imageName === $titleName) {
            return $imageUrl . '/' . rawurlencode($file);
        }

        if (
            $imageName !== '' &&
            $titleName !== '' &&
            (strpos($imageName, $titleName) !== false || strpos($titleName, $imageName) !== false)
        ) {
            return $imageUrl . '/' . rawurlencode($file);
        }
    }

    return '';
}

/**
 * @return array<int, array<string, string>>
 */
function getPdfBooksFromFolder(string $folderPath, string $assetUrl, string $imageFolderPath, string $imageUrl): array
{
    if (!is_dir($folderPath)) {
        return [];
    }

    $files = scandir($folderPath);

    if ($files === false) {
        return [];
    }

    $pdfFiles = [];

    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;

        if (is_file($filePath) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
            $pdfFiles[] = $file;
        }
    }

    natcasesort($pdfFiles);

    $books = [];

    foreach ($pdfFiles as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        $title = cleanPdfTitle($file);
        $meta = guessBookMeta($file, $title);
        $cover = findBookImage($file, $meta['title'], $imageFolderPath, $imageUrl);

        $books[] = [
            'title' => $meta['title'],
            'author' => $meta['author'],
            'category' => $meta['category'],
            'year' => $meta['year'],
            'pages' => getPdfSizeLabel($filePath),
            'tags' => $meta['tags'],
            'cover' => $cover,
            'banner' => $cover,
            'description' => $meta['description'],
            'pdf' => $assetUrl . '/books/' . rawurlencode($file),
            'reader' => app_url('reader.php') . '?book=' . rawurlencode($file),
            'file' => 'assets/books/' . $file,
            'type' => 'pdf',
        ];
    }

    return $books;
}

function renderBookCard(array $book): void
{
    $title = $book['title'] ?? 'Obra sin identificar';
    $author = $book['author'] ?? 'Autor no especificado';
    $description = $book['description'] ?? 'Obra disponible en el catálogo digital de la biblioteca.';
    $category = $book['category'] ?? 'Lectura digital';
    $year = $book['year'] ?? 'Disponible';
    $pages = $book['pages'] ?? 'Archivo disponible';
    $pdf = $book['pdf'] ?? '';
    $reader = $book['reader'] ?? $pdf;
    $file = $book['file'] ?? '';
    $cover = $book['cover'] ?? '';
    $banner = $book['banner'] ?? '';
    $tags = $book['tags'] ?? $category;
    $type = $book['type'] ?? 'pdf';
?>
    <div class="book-card js-book-preview"
        role="button"
        tabindex="0"
        data-title="<?php echo e($title); ?>"
        data-author="<?php echo e($author); ?>"
        data-description="<?php echo e($description); ?>"
        data-category="<?php echo e($category); ?>"
        data-year="<?php echo e($year); ?>"
        data-pages="<?php echo e($pages); ?>"
        data-pdf="<?php echo e($pdf); ?>"
        data-reader="<?php echo e($reader); ?>"
        data-file="<?php echo e($file); ?>"
        data-type="<?php echo e($type); ?>"
        data-cover="<?php echo e($cover); ?>"
        data-banner="<?php echo e($banner); ?>"
        data-tags="<?php echo e($tags); ?>">
        <div class="book-cover">
            <?php if ($cover !== ''): ?>
                <img src="<?php echo e($cover); ?>" alt="<?php echo e($title); ?>" class="cover-img">
            <?php else: ?>
                <div class="book-cover-fallback">
                    <span><?php echo e(mb_substr($title, 0, 1, 'UTF-8')); ?></span>
                    <strong><?php echo e($title); ?></strong>
                </div>
            <?php endif; ?>

            <div class="book-overlay">
                <a href="#" class="play-btn js-open-preview" aria-label="Vista previa de <?php echo e($title); ?>">
                    <i class="fas fa-play" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <div class="book-info">
            <h4><?php echo e($title); ?></h4>
            <p><?php echo e($author); ?></p>
        </div>
    </div>
<?php
}

$pdfBooks = getPdfBooksFromFolder(__DIR__ . '/assets/books', $assetUrl, $bookImagesPath, $bookImagesUrl);
$featuredBook = $pdfBooks[0] ?? null;
$featuredSlides = array_slice($pdfBooks, 0, 5);
$bookSections = array_chunk($pdfBooks, 5);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hogwarts | Biblioteca digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/book-preview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="app-base-url" content="<?php echo e($baseUrl); ?>">
    <style>
        .logo-brand-core {
            font-size: 0.72rem;
            opacity: 0.75;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: block;
        }

        .theme-switch-inline {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-left: 0.5rem;
        }

        .theme-switch-inline select {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 999px;
            padding: 0.25rem 0.8rem;
            font-size: 0.75rem;
        }

        .main-header {
            background-color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?> !important;
            border-bottom-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#d3d1c7'; ?> !important;
        }

        .nav-menu a:hover,
        .nav-menu a.active,
        .view-all:hover,
        .footer-col a:hover {
            color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
        }

        .btn-primary,
        .hero-badge,
        .play-btn,
        .active-house,
        .books-carousel::-webkit-scrollbar-thumb,
        .section-title::after {
            background-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
        }

        .btn-primary,
        .hero-badge,
        .play-btn,
        .active-house {
            color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?> !important;
        }

        .btn-primary:hover,
        .category-card:hover,
        .logout-btn:hover {
            background-color: <?php echo $themeEnabled ? $current_house['secondary'] : '#5f5e5a'; ?> !important;
            color: <?php echo $themeEnabled ? $current_house['text_color'] : '#ffffff'; ?> !important;
        }

        .btn-secondary,
        .house-btn {
            border-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
            color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
        }

        .btn-secondary:hover,
        .house-btn:hover {
            background-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
            color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?> !important;
        }

        .category-card {
            background-color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?> !important;
        }

        .hero {
            background-color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?>;
            border-bottom: 1px solid <?php echo $themeEnabled ? $current_house['highlight'] : '#d3d1c7'; ?> !important;
        }

        .featured-hero {
            position: relative;
            min-height: 90vh;
            overflow: hidden;
            background-color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?>;
            border-bottom: 1px solid <?php echo $themeEnabled ? $current_house['highlight'] : '#d3d1c7'; ?> !important;
            margin: 0 0 2rem;
        }

        .featured-hero .hero-slide {
            position: relative;
            width: 100%;
            min-height: 90vh;
            background-color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?>;
            background-position: center;
            background-size: cover;
            display: none;
        }

        .featured-hero .hero-slide.is-active {
            display: grid;
            place-items: center;
            z-index: 2;
            animation: heroFadeIn 0.85s cubic-bezier(0.22, 1, 0.36, 1);
        }

        @keyframes heroFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: rgba(8, 8, 8, 0.46);
        }

        .featured-hero .hero-content.featured-hero-content {
            position: relative;
            z-index: 2;
            width: 100%;
            min-height: 90vh;
            display: grid;
            place-items: center;
            padding: 0 1.2rem;
            max-width: 1280px;
            margin: 0 auto;
        }

        .featured-hero .hero-panel.featured-hero-panel {
            width: min(980px, 100%);
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(10, 10, 10, 0.88);
            border-radius: 18px;
            padding: 2.9rem 3rem;
            backdrop-filter: blur(2px);
            margin: 0 auto;
            text-align: center;
            justify-self: center;
            box-shadow: 0 18px 54px rgba(0, 0, 0, 0.38);
        }

        .featured-hero .hero-badge {
            margin-bottom: 1.1rem;
            font-size: 0.76rem;
            letter-spacing: 0.03em;
        }

        .hero-panel .hero-title {
            color: #ffffff;
            font-size: clamp(2.3rem, 5.4vw, 4rem);
            line-height: 1.02;
            margin-bottom: 0.95rem;
            text-shadow: 0 2px 14px rgba(0, 0, 0, 0.45);
        }

        .hero-panel .hero-description {
            color: rgba(255, 255, 255, 0.92);
            font-size: 1.08rem;
            margin-bottom: 0.75rem;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-panel .hero-synopsis {
            color: rgba(255, 255, 255, 0.88);
            font-size: 1.04rem;
            line-height: 1.62;
            max-width: 66ch;
            margin-bottom: 1.45rem;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-panel .hero-buttons {
            justify-content: center;
            gap: 0.8rem;
        }

        .featured-hero .hero-buttons .btn {
            min-height: 46px;
            padding: 0.8rem 1.35rem;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .featured-hero {
                min-height: 72vh;
            }

            .featured-hero .hero-slide {
                min-height: 72vh;
            }

            .featured-hero .hero-content.featured-hero-content {
                min-height: 74vh;
                width: 100%;
                padding: 0 1rem;
            }

            .featured-hero .hero-panel.featured-hero-panel {
                width: 100%;
                padding: 1.55rem 1.15rem;
                border-radius: 14px;
            }

            .featured-hero .hero-badge {
                margin-bottom: 0.8rem;
            }

            .hero-panel .hero-title {
                font-size: clamp(1.8rem, 7vw, 2.5rem);
                margin-bottom: 0.65rem;
            }

            .hero-panel .hero-description {
                font-size: 0.95rem;
            }

            .hero-panel .hero-synopsis {
                font-size: 0.92rem;
                line-height: 1.5;
                margin-bottom: 1rem;
            }

            .featured-hero .hero-buttons .btn {
                min-height: 42px;
                padding: 0.7rem 1rem;
                font-size: 0.88rem;
            }
        }

        .hero-carousel-dots {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 1.2rem;
            z-index: 3;
            display: flex;
            justify-content: center;
            gap: 0.45rem;
        }

        .hero-carousel-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.25);
            cursor: pointer;
        }

        .hero-carousel-dot.is-active {
            background: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?>;
            border-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?>;
        }

        .category-card {
            color: #ffffff !important;
            text-decoration: none !important;
        }

        .house-btn.active-house {
            background-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
            color: <?php echo $themeEnabled ? $current_house['color'] : '#111110'; ?> !important;
            border-color: <?php echo $themeEnabled ? $current_house['highlight'] : '#f5f4f0'; ?> !important;
        }
    </style>
</head>

<body class="theme-<?php echo htmlspecialchars($house); ?>">

    <header class="main-header">
        <div class="container header-content">
            <div class="logo">
                <div class="row">
                    <img src="<?php echo $themeEnabled ? htmlspecialchars($current_house['logo_img']) : ''; ?>" alt="Logo principal de Hogwarts" class="logo-img" style="<?php echo $themeEnabled ? '' : 'display:none;'; ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="logo-fa-fallback" style="display: <?php echo $themeEnabled ? 'none' : 'flex'; ?>;">
                        <i class="fas <?php echo $themeEnabled ? htmlspecialchars($current_house['icon']) : 'fa-hat-wizard'; ?>"></i>
                    </div>
                    <div>
                        <h1><?php echo $brandTitle; ?></h1>
                        <span class="logo-brand-core">
                            <?php echo $themeEnabled ? 'Tema: ' . htmlspecialchars($current_house['name']) : 'Tema clásico'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <nav class="nav-menu">
                <ul>
                    <li><a href="index.php" class="active">Inicio</a></li>
                    <li><a href="explorar.php">Explorar</a></li>
                    <li><a href="mi-lista.php">Mi lista</a></li>
                    <li><a href="categorias.php">Categorías</a></li>

                    <?php if (strtolower((string) ($_SESSION['user_role'] ?? 'usuario')) === 'admin'): ?>
                        <li><a href="<?php echo e($baseUrl . '/src/views/admin/dashboard.php'); ?>">Admin</a></li>
                    <?php endif; ?>

                    <li class="profile-dropdown-item">
                        <div class="profile-dropdown">
                            <button type="button" class="profile-dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo e($user_name); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            <div class="profile-dropdown-menu">
                                <div class="dropdown-user-summary">
                                    <i class="fas fa-user-circle"></i>
                                    <div>
                                        <strong><?php echo e($user_name); ?></strong>
                                        <span><?php echo e($user_email !== '' ? $user_email : 'Perfil de lectura'); ?></span>
                                    </div>
                                </div>

                                <?php if ($themeEnabled): ?>
                                    <form method="GET" action="" class="dropdown-theme-box">
                                        <label for="house-select-index">
                                            <i class="fas fa-palette"></i>
                                            Cambiar tema
                                        </label>
                                        <select id="house-select-index" name="house" onchange="this.form.submit()">
                                            <option value="ravenclaw" <?= $house === 'ravenclaw' ? 'selected' : ''; ?>>Ravenclaw</option>
                                            <option value="gryffindor" <?= $house === 'gryffindor' ? 'selected' : ''; ?>>Gryffindor</option>
                                            <option value="slytherin" <?= $house === 'slytherin' ? 'selected' : ''; ?>>Slytherin</option>
                                            <option value="hufflepuff" <?= $house === 'hufflepuff' ? 'selected' : ''; ?>>Hufflepuff</option>
                                        </select>
                                    </form>
                                <?php endif; ?>

                                <a href="perfil.php" class="profile-dropdown-link">
                                    <i class="fas fa-id-card"></i>
                                    Ingresar a perfil
                                </a>

                                <a href="logout.php" class="logout-btn">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Cerrar sesión
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="featured-hero" id="heroCarousel">
            <?php if (!empty($featuredSlides)): ?>
                <?php foreach ($featuredSlides as $index => $slideBook): ?>
                    <article class="hero-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" style="<?php echo !empty($slideBook['banner']) ? 'background-image:url(\'' . e($slideBook['banner']) . '\');' : ''; ?>">
                        <div class="hero-overlay"></div>
                        <div class="hero-content featured-hero-content">
                            <div class="hero-panel featured-hero-panel">
                                <span class="hero-badge">Destacado de la biblioteca</span>
                                <h1 class="hero-title"><?php echo e($slideBook['title']); ?></h1>
                                <p class="hero-description">
                                    <?php echo e($slideBook['author']); ?> ·
                                    <?php echo e($slideBook['category']); ?> ·
                                    <?php echo e($slideBook['pages']); ?>
                                </p>
                                <p class="hero-synopsis"><?php echo e($slideBook['description']); ?></p>
                                <div class="hero-buttons">
                                    <a href="#"
                                        class="btn btn-primary js-book-preview"
                                        data-title="<?php echo e($slideBook['title']); ?>"
                                        data-author="<?php echo e($slideBook['author']); ?>"
                                        data-description="<?php echo e($slideBook['description']); ?>"
                                        data-category="<?php echo e($slideBook['category']); ?>"
                                        data-year="<?php echo e($slideBook['year']); ?>"
                                        data-pages="<?php echo e($slideBook['pages']); ?>"
                                        data-pdf="<?php echo e($slideBook['pdf']); ?>"
                                        data-reader="<?php echo e($slideBook['reader']); ?>"
                                        data-file="<?php echo e($slideBook['file'] ?? ''); ?>"
                                        data-type="<?php echo e($slideBook['type'] ?? 'pdf'); ?>"
                                        data-cover="<?php echo e($slideBook['cover']); ?>"
                                        data-banner="<?php echo e($slideBook['banner']); ?>"
                                        data-tags="<?php echo e($slideBook['tags']); ?>">
                                        Vista previa
                                    </a>
                                    <a href="<?php echo e($slideBook['reader']); ?>" class="btn btn-secondary">Comenzar lectura</a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div class="hero-carousel-dots" id="heroCarouselDots">
                    <?php foreach ($featuredSlides as $index => $slideBook): ?>
                        <button type="button" class="hero-carousel-dot <?php echo $index === 0 ? 'is-active' : ''; ?>" aria-label="Destacado <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <article class="hero-slide is-active">
                    <div class="hero-overlay"></div>
                    <div class="hero-content featured-hero-content">
                        <div class="hero-panel featured-hero-panel">
                            <span class="hero-badge">Biblioteca digital</span>
                            <h1 class="hero-title">Catálogo sin obras disponibles</h1>
                            <p class="hero-description">Agrega archivos PDF en la carpeta assets/books para mostrarlos en la biblioteca.</p>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </section>

        <section class="row-section" id="catalogo-pdf">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Catálogo destacado</h2>
                    <a href="explorar.php" class="view-all">Ver todo</a>
                </div>

                <?php if (!empty($bookSections[0])): ?>
                    <div class="books-carousel">
                        <?php foreach ($bookSections[0] as $book): ?>
                            <?php renderBookCard($book); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-catalog">
                        No hay obras disponibles. Coloca archivos PDF en la carpeta assets/books.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($bookSections[1])): ?>
            <section class="row-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Recomendados</h2>
                        <a href="explorar.php" class="view-all">Ver todo</a>
                    </div>

                    <div class="books-carousel">
                        <?php foreach ($bookSections[1] as $book): ?>
                            <?php renderBookCard($book); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($bookSections[2])): ?>
            <section class="row-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Clásicos</h2>
                        <a href="explorar.php" class="view-all">Ver todo</a>
                    </div>

                    <div class="books-carousel">
                        <?php foreach ($bookSections[2] as $book): ?>
                            <?php renderBookCard($book); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="categories-section" id="categorias">
            <div class="container">
                <h2 class="section-title">Explora por categorías</h2>
                <div class="categories-grid">
                    <a href="categorias.php" class="category-card">Clásicos</a>
                    <a href="categorias.php" class="category-card">Ciencia ficción</a>
                    <a href="categorias.php" class="category-card">Misterio</a>
                    <a href="categorias.php" class="category-card">Romance</a>
                    <a href="categorias.php" class="category-card">Terror</a>
                    <a href="categorias.php" class="category-card">Filosofía</a>
                </div>
            </div>
        </section>

    </main>

    <div class="book-preview-backdrop" id="bookPreviewBackdrop" aria-hidden="true">
        <div class="book-preview-modal" role="dialog" aria-modal="true" aria-labelledby="bookPreviewTitle">
            <button type="button" class="book-preview-close" id="bookPreviewClose" aria-label="Cerrar">×</button>

            <div class="book-preview-hero" id="bookPreviewHero">
                <div class="book-preview-gradient"></div>
                <div class="book-preview-hero-content">
                    <span class="book-preview-pill" id="bookPreviewCategoryTop">Lectura digital</span>
                    <h2 id="bookPreviewTitle">Título</h2>
                    <p class="book-preview-author" id="bookPreviewAuthor">Autor</p>
                    <div class="book-preview-actions">
                        <a href="#" class="book-preview-read-btn" id="bookPreviewReadBtn">Comenzar lectura</a>
                        <button type="button" class="book-preview-list-btn" id="bookPreviewFavoriteBtn">Mi lista</button>
                    </div>
                </div>
            </div>

            <div class="book-preview-body">
                <div class="book-preview-cover-box" id="bookPreviewCoverBox">
                    <img src="" alt="" class="book-preview-cover-img" id="bookPreviewCoverImg">
                    <span id="bookPreviewInitial">L</span>
                    <strong id="bookPreviewCoverTitle">Libro</strong>
                </div>

                <div>
                    <div class="book-preview-meta">
                        <span id="bookPreviewYear">Disponible</span>
                        <span id="bookPreviewCategory">Lectura digital</span>
                        <span id="bookPreviewPages">Archivo disponible</span>
                    </div>

                    <p class="book-preview-description" id="bookPreviewDescription">
                        Obra disponible en el catálogo digital de la biblioteca.
                    </p>

                    <div class="book-preview-tags" id="bookPreviewTags"></div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4>Hogwarts</h4>
                    <p>Hogwarts · Biblioteca digital.<br>Obras disponibles para lectura continua.</p>
                </div>
                <div class="footer-col">
                    <h4>Explorar</h4>
                    <ul>
                        <li><a href="explorar.php">Explorar</a></li>
                        <li><a href="categorias.php">Categorías</a></li>
                        <li><a href="mi-lista.php">Mi lista</a></li>
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
                <p>© <?php echo date('Y'); ?> Hogwarts · <?php echo $themeEnabled ? 'Tema ' . htmlspecialchars($current_house['name']) : 'Tema clásico'; ?></p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const heroCarousel = document.getElementById('heroCarousel');
            const heroDotsWrap = document.getElementById('heroCarouselDots');

            if (heroCarousel && heroDotsWrap) {
                const slides = Array.from(heroCarousel.querySelectorAll('.hero-slide'));
                const dots = Array.from(heroDotsWrap.querySelectorAll('.hero-carousel-dot'));
                let current = 0;
                let autoRotate = null;

                function showSlide(index) {
                    slides.forEach((slide, idx) => {
                        slide.classList.toggle('is-active', idx === index);
                    });

                    dots.forEach((dot, idx) => {
                        dot.classList.toggle('is-active', idx === index);
                    });

                    current = index;
                }

                if (slides.length > 0) {
                    showSlide(0);
                }

                function startAutoRotate() {
                    if (autoRotate) {
                        clearInterval(autoRotate);
                    }

                    autoRotate = setInterval(() => {
                        const next = (current + 1) % slides.length;
                        showSlide(next);
                    }, 5500);
                }

                dots.forEach((dot, idx) => {
                    dot.addEventListener('click', () => {
                        showSlide(idx);
                        startAutoRotate();
                    });
                });

                if (slides.length > 1) {
                    startAutoRotate();
                }
            }

            const backdrop = document.getElementById('bookPreviewBackdrop');
            const closeBtn = document.getElementById('bookPreviewClose');

            if (!backdrop || !closeBtn) {
                return;
            }

            const hero = document.getElementById('bookPreviewHero');
            const title = document.getElementById('bookPreviewTitle');
            const author = document.getElementById('bookPreviewAuthor');
            const categoryTop = document.getElementById('bookPreviewCategoryTop');
            const initial = document.getElementById('bookPreviewInitial');
            const coverTitle = document.getElementById('bookPreviewCoverTitle');
            const coverBox = document.getElementById('bookPreviewCoverBox');
            const coverImg = document.getElementById('bookPreviewCoverImg');
            const year = document.getElementById('bookPreviewYear');
            const category = document.getElementById('bookPreviewCategory');
            const pages = document.getElementById('bookPreviewPages');
            const description = document.getElementById('bookPreviewDescription');
            const tags = document.getElementById('bookPreviewTags');
            const readBtn = document.getElementById('bookPreviewReadBtn');
            const favoriteBtn = document.getElementById('bookPreviewFavoriteBtn');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const apiUrl = '<?php echo e(app_url('src/controllers/library_api.php')); ?>';
            let activeBookPayload = null;

            function buildBookPayload(data) {
                return {
                    file: data.file || '',
                    title: data.title || '',
                    author: data.author || '',
                    description: data.description || '',
                    type: data.type || 'pdf'
                };
            }

            async function refreshFavoriteButton(payload) {
                if (!favoriteBtn || !payload) {
                    return;
                }

                try {
                    const params = new URLSearchParams({
                        action: 'favorite_status',
                        file: payload.file,
                        title: payload.title,
                        author: payload.author,
                        description: payload.description,
                        type: payload.type
                    });

                    const response = await fetch(apiUrl + '?' + params.toString(), {
                        credentials: 'same-origin'
                    });
                    const result = await response.json();

                    if (!response.ok || !result.ok) {
                        favoriteBtn.textContent = 'Mi lista';
                        return;
                    }

                    favoriteBtn.textContent = result.data.is_favorite ? 'Quitar de mi lista' : 'Agregar a mi lista';
                } catch (error) {
                    favoriteBtn.textContent = 'Mi lista';
                }
            }

            function setText(element, value, fallback) {
                if (!element) {
                    return;
                }

                element.textContent = value && value.trim() !== '' ? value : fallback;
            }

            function openPreview(card) {
                const data = card.dataset;
                const bookTitle = data.title || 'Obra sin identificar';
                const bookCategory = data.category || 'Lectura digital';
                const bookPdf = data.pdf || '';
                const bookReader = data.reader || '';
                const bookCover = data.cover || '';
                activeBookPayload = buildBookPayload(data);

                setText(title, bookTitle, 'Obra sin identificar');
                setText(author, data.author, 'Autor no especificado');
                setText(categoryTop, bookCategory, 'Lectura digital');
                setText(initial, bookTitle.substring(0, 1), 'L');
                setText(coverTitle, bookTitle, 'Libro');
                setText(year, data.year, 'Disponible');
                setText(category, bookCategory, 'Lectura digital');
                setText(pages, data.pages, 'Archivo disponible');
                setText(description, data.description, 'Obra disponible en el catálogo digital de la biblioteca.');

                if (bookCover && coverImg && coverBox) {
                    coverImg.src = bookCover;
                    coverImg.alt = 'Portada de ' + bookTitle;
                    coverImg.style.display = 'block';
                    coverBox.classList.add('has-image');

                    if (initial) {
                        initial.style.display = 'none';
                    }

                    if (coverTitle) {
                        coverTitle.style.display = 'none';
                    }
                } else if (coverImg && coverBox) {
                    coverImg.removeAttribute('src');
                    coverImg.alt = '';
                    coverImg.style.display = 'none';
                    coverBox.classList.remove('has-image');

                    if (initial) {
                        initial.style.display = 'inline-flex';
                    }

                    if (coverTitle) {
                        coverTitle.style.display = 'block';
                    }
                }

                if (hero) {
                    const backgroundImage = data.banner || bookCover;
                    hero.style.backgroundImage = backgroundImage ? 'url("' + backgroundImage + '")' : 'none';
                }

                if (tags) {
                    tags.innerHTML = '';

                    const tagList = (data.tags || bookCategory)
                        .split(',')
                        .map(function(tag) {
                            return tag.trim();
                        })
                        .filter(Boolean);

                    tagList.forEach(function(tag) {
                        const span = document.createElement('span');
                        span.textContent = tag;
                        tags.appendChild(span);
                    });
                }

                if (bookPdf) {
                    readBtn.href = bookReader || bookPdf;
                    readBtn.textContent = 'Comenzar lectura';
                    readBtn.style.pointerEvents = 'auto';
                    readBtn.style.opacity = '1';
                } else {
                    readBtn.href = '#';
                    readBtn.textContent = 'Lectura no disponible';
                    readBtn.style.pointerEvents = 'none';
                    readBtn.style.opacity = '0.55';
                }

                refreshFavoriteButton(activeBookPayload);

                backdrop.classList.add('is-open');
                backdrop.setAttribute('aria-hidden', 'false');
                document.body.classList.add('preview-open');
                closeBtn.focus();
            }

            function closePreview() {
                backdrop.classList.remove('is-open');
                backdrop.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('preview-open');
            }

            document.querySelectorAll('.js-book-preview').forEach(function(card) {
                card.addEventListener('click', function(event) {
                    event.preventDefault();
                    openPreview(card);
                });

                card.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openPreview(card);
                    }
                });
            });

            closeBtn.addEventListener('click', closePreview);

            if (favoriteBtn) {
                favoriteBtn.addEventListener('click', async function() {
                    if (!activeBookPayload || !csrfToken) {
                        return;
                    }

                    const payload = new URLSearchParams({
                        action: 'toggle_favorite',
                        csrf_token: csrfToken,
                        file: activeBookPayload.file,
                        title: activeBookPayload.title,
                        author: activeBookPayload.author,
                        description: activeBookPayload.description,
                        type: activeBookPayload.type
                    });

                    favoriteBtn.disabled = true;

                    try {
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: payload.toString()
                        });

                        const result = await response.json();
                        if (!response.ok || !result.ok) {
                            throw new Error('No se pudo actualizar favoritos.');
                        }

                        favoriteBtn.textContent = result.data.is_favorite ? 'Quitar de mi lista' : 'Agregar a mi lista';
                    } catch (error) {
                        alert('No se pudo actualizar tu lista en este momento.');
                    } finally {
                        favoriteBtn.disabled = false;
                    }
                });
            }

            backdrop.addEventListener('click', function(event) {
                if (event.target === backdrop) {
                    closePreview();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
                    closePreview();
                }
            });
        });
    </script>

</body>

</html>