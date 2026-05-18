<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$baseUrl = '/Muggle';
$assetUrl = $baseUrl . '/assets';
$bookImagesPath = __DIR__ . '/assets/img-books';
$bookImagesUrl = $assetUrl . '/img-books';

$house = $_GET['house'] ?? $_SESSION['selected_house'] ?? 'ravenclaw';
$validHouses = ['ravenclaw', 'gryffindor', 'slytherin', 'hufflepuff'];

if (!in_array($house, $validHouses, true)) {
    $house = 'ravenclaw';
}

$_SESSION['selected_house'] = $house;

$houses_config = [
    'ravenclaw' => [
        'name' => 'Ravenclaw',
        'icon' => 'fa-feather-alt',
        'logo_img' => 'assets/img/ravenclaw.jpg',
        'color' => '#0e1a2b',
        'secondary' => '#5f7f9e',
        'highlight' => '#cdb57c',
        'text_color' => '#ffffff'
    ],
    'gryffindor' => [
        'name' => 'Gryffindor',
        'icon' => 'fa-shield-alt',
        'logo_img' => 'assets/img/gryffindor.jpg',
        'color' => '#541011',
        'secondary' => '#7a1d1f',
        'highlight' => '#eeba30',
        'text_color' => '#ffffff'
    ],
    'slytherin' => [
        'name' => 'Slytherin',
        'icon' => 'fa-dragon',
        'logo_img' => 'assets/img/slytherin.jpg',
        'color' => '#1a472a',
        'secondary' => '#2a623d',
        'highlight' => '#c8c8c8',
        'text_color' => '#ffffff'
    ],
    'hufflepuff' => [
        'name' => 'Hufflepuff',
        'icon' => 'fa-seedling',
        'logo_img' => 'assets/img/hufflepuff.jpg',
        'color' => '#806216',
        'secondary' => '#9a7923',
        'highlight' => '#f0c75e',
        'text_color' => '#ffffff'
    ]
];

$current_house = $houses_config[$house];
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? '';

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cleanPdfTitle(string $filename): string {
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

function getPdfSizeLabel(string $filePath): string {
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
function guessBookMeta(string $filename, string $defaultTitle): array {
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

function normalizeBookName(string $value): string {
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

function findBookImage(string $pdfFilename, string $bookTitle, string $imageFolderPath, string $imageUrl): string {
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
function getPdfBooksFromFolder(string $folderPath, string $assetUrl, string $imageFolderPath, string $imageUrl): array {
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
            'reader' => '/Muggle/reader.php?book=' . rawurlencode($file)
        ];
    }

    return $books;
}

function renderBookCard(array $book): void {
    $title = $book['title'] ?? 'Obra sin identificar';
    $author = $book['author'] ?? 'Autor no especificado';
    $description = $book['description'] ?? 'Obra disponible en el catálogo digital de la biblioteca.';
    $category = $book['category'] ?? 'Lectura digital';
    $year = $book['year'] ?? 'Disponible';
    $pages = $book['pages'] ?? 'Archivo disponible';
    $pdf = $book['pdf'] ?? '';
    $reader = $book['reader'] ?? $pdf;
    $cover = $book['cover'] ?? '';
    $banner = $book['banner'] ?? '';
    $tags = $book['tags'] ?? $category;
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
                <a href="#" class="play-btn js-open-preview" aria-label="Vista previa de <?php echo e($title); ?>">▶</a>
            </div>
        </div>

        <div class="book-info">
            <h4><?php echo e($title); ?></h4>
            <p><?php echo e($author); ?></p>
        </div>
    </div>
    <?php
}

function normalizeAssetImageUrl(string $value, string $assetUrl, string $folder): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $value) || str_starts_with($value, '/')) {
        return $value;
    }

    if (str_starts_with($value, 'banners/')) {
        return $assetUrl . '/banners/' . rawurlencode(basename($value));
    }

    if (str_starts_with($value, 'img-books/')) {
        return $assetUrl . '/img-books/' . rawurlencode(basename($value));
    }

    if (str_starts_with($value, 'assets/')) {
        return '/Muggle/' . ltrim($value, '/');
    }

    $decodedName = urldecode(basename($value));
    $folderPath = __DIR__ . '/assets/' . trim($folder, '/');

    if (is_dir($folderPath)) {
        $targetName = normalizeBookName($decodedName);
        $files = scandir($folderPath);

        if ($files !== false) {
            foreach ($files as $file) {
                $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
                if (!is_file($filePath)) {
                    continue;
                }

                $fileName = normalizeBookName($file);
                if ($fileName === $targetName) {
                    return $assetUrl . '/' . trim($folder, '/') . '/' . rawurlencode($file);
                }
            }

            foreach ($files as $file) {
                $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
                if (!is_file($filePath)) {
                    continue;
                }

                $fileName = normalizeBookName($file);
                if (
                    $fileName !== '' &&
                    $targetName !== '' &&
                    (strpos($fileName, $targetName) !== false || strpos($targetName, $fileName) !== false)
                ) {
                    return $assetUrl . '/' . trim($folder, '/') . '/' . rawurlencode($file);
                }
            }
        }
    }

    return $assetUrl . '/' . trim($folder, '/') . '/' . rawurlencode($decodedName);
}

/**
 * @return array<int, array<string, string>>
 */
function getBooksFromDatabase(string $assetUrl): array
{
    try {
        require_once __DIR__ . '/src/models/Database.php';
        $db = Database::getConnection();
        $stmt = $db->query(
            'SELECT l.*, c.nombre AS categoria, b.imagen AS banner_imagen
             FROM libros l
             INNER JOIN categorias c ON c.id_categoria = l.id_categoria
             LEFT JOIN banners b ON b.id_banner = l.id_banner
             ORDER BY l.id_libro DESC'
        );
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $books = [];
    foreach ($rows as $row) {
        $archivo = trim((string) ($row['archivo'] ?? ''));
        $readerUrl = '';
        if ($archivo !== '') {
            if (preg_match('/^https?:\/\//i', $archivo)) {
                $readerUrl = $archivo;
            } elseif (str_starts_with($archivo, 'assets/books/')) {
                $readerUrl = '/Muggle/reader.php?book=' . rawurlencode(basename($archivo));
            } else {
                $readerUrl = $archivo;
            }
        }

        $portada = normalizeAssetImageUrl((string) ($row['portada'] ?? ''), $assetUrl, 'img-books');
        $banner  = normalizeAssetImageUrl((string) ($row['banner_imagen'] ?? ''), $assetUrl, 'banners');
        $year    = !empty($row['fecha_publicado']) ? substr((string) $row['fecha_publicado'], 0, 4) : 'Disponible';
        $titulo  = trim((string) ($row['titulo'] ?? ''));
        $autor   = trim((string) ($row['autor'] ?? ''));
        $cat     = trim((string) ($row['categoria'] ?? 'General'));

        $books[] = [
            'title'       => $titulo !== '' ? $titulo : 'Sin título',
            'author'      => $autor !== '' ? $autor : 'Autor no especificado',
            'category'    => $cat,
            'year'        => $year,
            'pages'       => ucfirst((string) ($row['tipo'] ?? 'digital')),
            'tags'        => $cat,
            'cover'       => $portada,
            'banner'      => $banner,
            'description' => trim((string) ($row['descripcion'] ?? 'Obra disponible en el catálogo digital.')),
            'pdf'         => $archivo !== '' ? ($assetUrl . '/books/' . rawurlencode(basename($archivo))) : '',
            'reader'      => $readerUrl,
            'source'      => 'db',
        ];
    }
    return $books;
}

$pdfBooks = getPdfBooksFromFolder(__DIR__ . '/assets/books', $assetUrl, $bookImagesPath, $bookImagesUrl);
$dbBooks  = getBooksFromDatabase($assetUrl);

// Quitar de PDF los que ya existen en DB (mismo archivo base)
$dbFilenames = [];
foreach ($dbBooks as $dbBook) {
    if ($dbBook['pdf'] !== '') {
        $dbFilenames[] = basename($dbBook['pdf']);
    }
}
$pdfBooksFiltered = array_values(array_filter($pdfBooks, function (array $b) use ($dbFilenames): bool {
    return !in_array(basename($b['pdf']), $dbFilenames, true);
}));

// Featured: primer libro de DB con banner, si no el primero de DB, si no el primero PDF
$featuredBook = null;
foreach ($dbBooks as $candidate) {
    if ($candidate['banner'] !== '') {
        $featuredBook = $candidate;
        break;
    }
}
if ($featuredBook === null) {
    $featuredBook = $dbBooks[0] ?? $pdfBooksFiltered[0] ?? null;
}

$bookSections = array_chunk($pdfBooksFiltered, 5);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_house['name']); ?> Libraries | Stream de libros mágicos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/book-preview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .main-header {
            background-color: <?php echo $current_house['color']; ?> !important;
            border-bottom-color: <?php echo $current_house['highlight']; ?> !important;
        }

        .nav-menu a:hover,
        .nav-menu a.active,
        .view-all:hover,
        .footer-col a:hover {
            color: <?php echo $current_house['highlight']; ?> !important;
        }

        .btn-primary,
        .hero-badge,
        .play-btn,
        .active-house,
        .books-carousel::-webkit-scrollbar-thumb,
        .section-title::after {
            background-color: <?php echo $current_house['highlight']; ?> !important;
        }

        .btn-primary,
        .hero-badge,
        .play-btn,
        .active-house {
            color: <?php echo $current_house['color']; ?> !important;
        }

        .btn-primary:hover,
        .category-card:hover,
        .logout-btn:hover {
            background-color: <?php echo $current_house['secondary']; ?> !important;
            color: <?php echo $current_house['text_color']; ?> !important;
        }

        .btn-secondary,
        .house-btn {
            border-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['highlight']; ?> !important;
        }

        .btn-secondary:hover,
        .house-btn:hover {
            background-color: <?php echo $current_house['highlight']; ?> !important;
            color: <?php echo $current_house['color']; ?> !important;
        }

        .category-card {
            background-color: <?php echo $current_house['color']; ?> !important;
        }

        .hero {
            background-color: <?php echo $current_house['color']; ?>;
            border-bottom: 1px solid <?php echo $current_house['highlight']; ?> !important;
            <?php if (!empty($featuredBook['banner'])): ?>
            background:
                linear-gradient(
                    90deg,
                    <?php echo $current_house['color']; ?>cc 0%,
                    <?php echo $current_house['color']; ?>99 35%,
                    <?php echo $current_house['color']; ?>33 62%,
                    transparent 100%
                ),
                url('<?php echo e($featuredBook['banner']); ?>') center / cover no-repeat;
            <?php endif; ?>
        }
    </style>
</head>

<body class="theme-<?php echo htmlspecialchars($house); ?>">

<header class="main-header">
    <div class="container header-content">
        <div class="logo">
            <div class="row">
                <img src="<?php echo htmlspecialchars($current_house['logo_img']); ?>" alt="Logo de <?php echo htmlspecialchars($current_house['name']); ?>" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fa-fallback" style="display: none;">
                    <i class="fas <?php echo htmlspecialchars($current_house['icon']); ?>"></i>
                </div>
                <h1><?php echo strtoupper(htmlspecialchars($current_house['name'])); ?> LIBRARIES</h1>
            </div>
        </div>

        <nav class="nav-menu">
            <ul>
                <li><a href="#" class="active">Inicio</a></li>
                <li><a href="#">Explorar</a></li>
                <li><a href="#">Mi lista</a></li>
                <li><a href="#">Categorías</a></li>
                <li><a href="src/views/admin/admin_books.php">Admin</a></li>
                <li class="user-nav-item">
                    <div class="user-menu">
                        <span class="user-name"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?></span>
                        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-content">
            <?php if ($featuredBook): ?>
                <span class="hero-badge">Destacado de la biblioteca</span>
                <h1 class="hero-title"><?php echo e($featuredBook['title']); ?></h1>
                <p class="hero-description">
                    <?php echo e($featuredBook['author']); ?> ·
                    <?php echo e($featuredBook['category']); ?> ·
                    <?php echo e($featuredBook['pages']); ?>
                </p>
                <p class="hero-synopsis"><?php echo e($featuredBook['description']); ?></p>
                <div class="hero-buttons">
                    <a href="#"
                       class="btn btn-primary js-book-preview"
                       data-title="<?php echo e($featuredBook['title']); ?>"
                       data-author="<?php echo e($featuredBook['author']); ?>"
                       data-description="<?php echo e($featuredBook['description']); ?>"
                       data-category="<?php echo e($featuredBook['category']); ?>"
                       data-year="<?php echo e($featuredBook['year']); ?>"
                       data-pages="<?php echo e($featuredBook['pages']); ?>"
                       data-pdf="<?php echo e($featuredBook['pdf']); ?>"
                       data-reader="<?php echo e($featuredBook['reader']); ?>"
                       data-cover="<?php echo e($featuredBook['cover']); ?>"
                       data-banner="<?php echo e($featuredBook['banner']); ?>"
                       data-tags="<?php echo e($featuredBook['tags']); ?>">
                        Vista previa
                    </a>
                    <a href="<?php echo e($featuredBook['reader']); ?>" class="btn btn-secondary">Comenzar lectura</a>
                </div>
            <?php else: ?>
                <span class="hero-badge">Biblioteca digital</span>
                <h1 class="hero-title">Catálogo sin obras disponibles</h1>
                <p class="hero-description">Agrega archivos PDF en la carpeta assets/books para mostrarlos en la biblioteca.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($dbBooks)): ?>
    <section class="row-section" id="catalogo">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">De la biblioteca</h2>
                <a href="#catalogo" class="view-all">Ver todo</a>
            </div>
            <div class="books-carousel">
                <?php foreach ($dbBooks as $book): ?>
                    <?php renderBookCard($book); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="row-section" id="catalogo-pdf">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Catálogo destacado</h2>
                <a href="#catalogo-pdf" class="view-all">Ver todo</a>
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
                    <a href="#catalogo" class="view-all">Ver todo</a>
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
                    <a href="#catalogo" class="view-all">Ver todo</a>
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
                <div class="category-card">Clásicos</div>
                <div class="category-card">Ciencia ficción</div>
                <div class="category-card">Misterio</div>
                <div class="category-card">Romance</div>
                <div class="category-card">Terror</div>
                <div class="category-card">Filosofía</div>
            </div>
        </div>
    </section>

    <section class="house-selector-section">
        <div class="container">
            <h2 class="section-title">Cambiar casa de Hogwarts</h2>
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
                    <button type="button" class="book-preview-list-btn">Mi lista</button>
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
                <h4><?php echo e($current_house['name']); ?> Libraries</h4>
                <p>Streaming de libros gratuito.<br>Obras digitales disponibles para lectura.</p>
            </div>
            <div class="footer-col">
                <h4>Explorar</h4>
                <ul>
                    <li><a href="#catalogo">Catálogo</a></li>
                    <li><a href="#categorias">Categorías</a></li>
                    <li><a href="#">Mi lista</a></li>
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
            <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($current_house['name']); ?> Libraries · Casa <?php echo htmlspecialchars($current_house['name']); ?></p>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
                .map(function (tag) {
                    return tag.trim();
                })
                .filter(Boolean);

            tagList.forEach(function (tag) {
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

    document.querySelectorAll('.js-book-preview').forEach(function (card) {
        card.addEventListener('click', function (event) {
            event.preventDefault();
            openPreview(card);
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview(card);
            }
        });
    });

    closeBtn.addEventListener('click', closePreview);

    backdrop.addEventListener('click', function (event) {
        if (event.target === backdrop) {
            closePreview();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
            closePreview();
        }
    });
});
</script>

</body>

</html>