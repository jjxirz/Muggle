<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$baseUrl = '/Muggle';
$assetUrl = $baseUrl . '/assets';

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cleanBookTitle(string $filename): string {
    $title = pathinfo($filename, PATHINFO_FILENAME);

    $title = (string) preg_replace('/_?\d{8}_\d{6}$/', '', $title);
    $title = (string) preg_replace('/^\d+[\s_\-]*/', '', $title);
    $title = str_replace(['_', '-'], ' ', $title);
    $title = (string) preg_replace('/\s+/', ' ', $title);
    $title = trim($title);

    if ($title === '') {
        return 'Lectura digital';
    }

    return mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');
}

$book = $_GET['book'] ?? '';
$book = basename((string)$book);

$bookPath = __DIR__ . '/assets/books/' . $book;

$isValidBook = $book !== ''
    && strtolower(pathinfo($book, PATHINFO_EXTENSION)) === 'pdf'
    && is_file($bookPath);

$bookTitle = $isValidBook ? cleanBookTitle($book) : 'Libro no encontrado';
$pdfUrl = $isValidBook ? $assetUrl . '/books/' . rawurlencode($book) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($bookTitle); ?> | Lector</title>

    <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/reader.css">
</head>

<body>
    <header class="reader-header">
        <div class="reader-brand">
            <a href="<?php echo $baseUrl; ?>/index.php" class="reader-back">← Volver</a>

            <div>
                <h1><?php echo e($bookTitle); ?></h1>
                <p>Modo lectura</p>
            </div>
        </div>

        
    </header>

    <main class="reader-main">
        <?php if (!$isValidBook): ?>
            <section class="reader-error">
                <h2>No se encontró el libro</h2>
                <p>El archivo solicitado no existe o no es un PDF válido.</p>
                <a href="<?php echo $baseUrl; ?>/index.php">Regresar al catálogo</a>
            </section>
        <?php else: ?>
            <section class="reader-toolbar">
                <button type="button" id="prevPageBtn"><</button>

                <div class="reader-page-info">
                    Página <span id="currentPage">1</span> de <span id="totalPages">0</span>
                </div>

                <button type="button" id="nextPageBtn">></button>
            </section>

            <section class="reader-loading" id="readerLoading">
                <div class="reader-loading-card">
                    <div class="reader-book-loader">
                        <div class="reader-book-page reader-book-page-one"></div>
                        <div class="reader-book-page reader-book-page-two"></div>
                        <div class="reader-book-page reader-book-page-three"></div>
                    </div>

                    <div class="reader-loading-content">
                        <span class="reader-loading-label">Preparando lector digital</span>

                        <h2 id="loadingTitle"><?php echo e($bookTitle); ?></h2>

                        <p id="loadingText">Cargando archivo...</p>

                        <div class="reader-progress">
                            <div class="reader-progress-bar" id="loadingProgressBar"></div>
                        </div>

                        <div class="reader-progress-info">
                            <span id="loadingPercent">0%</span>
                            <span>Renderizando páginas del libro</span>
                        </div>

                        <p class="reader-loading-note">
                            Los libros extensos pueden tardar unos segundos antes de abrirse.
                        </p>
                    </div>
                </div>
            </section>

            <section class="flipbook-wrapper" id="flipbookWrapper">
                <div id="flipbook" class="flipbook"></div>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($isValidBook): ?>
        <script>
            window.READER_CONFIG = {
                pdfUrl: "<?php echo e($pdfUrl); ?>",
                workerUrl: "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js",
                title: "<?php echo e($bookTitle); ?>"
            };
        </script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.min.js"></script>
        <script src="<?php echo $assetUrl; ?>/js/reader.js"></script>
    <?php endif; ?>
</body>
</html>