<?php
require_once __DIR__ . '/src/lib/Auth.php';

require_login();

$baseUrl = app_base_url();
$assetUrl = $baseUrl . '/assets';
$themeEnabled = (bool) ($_SESSION['theme_enabled'] ?? true);
$house = $_GET['house'] ?? $_SESSION['selected_house'] ?? 'ravenclaw';
$validHouses = ['ravenclaw', 'gryffindor', 'slytherin', 'hufflepuff'];

if (!$themeEnabled || !in_array($house, $validHouses, true)) {
    $house = 'ravenclaw';
}

$_SESSION['selected_house'] = $house;

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
    if ($title === '') return 'Lectura digital';
    return mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');
}

$book = $_GET['book'] ?? '';
$book = basename((string)$book);
$bookPath = __DIR__ . '/assets/books/' . $book;

$isValidBook = $book !== ''
    && strtolower(pathinfo($book, PATHINFO_EXTENSION)) === 'pdf'
    && is_file($bookPath);

$bookTitle = $isValidBook ? cleanBookTitle($book) : 'Libro no encontrado';
$pdfUrl    = $isValidBook ? $assetUrl . '/books/' . rawurlencode($book) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo e($bookTitle); ?> | Lector</title>
    <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/reader.css">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
</head>

<body>
    <aside class="reader-sidebar" id="readerSidebar">
        <a href="<?php echo $baseUrl; ?>/index.php?house=<?php echo e($house); ?>" class="reader-back">
            <span class="reader-side-icon">←</span>
        </a>

        <button type="button" id="fullscreenBtn" class="reader-side-action">
            <span class="reader-side-icon" id="fullscreenIcon">⛶</span>
        </button>

        <button type="button" id="viewModeBtn" class="reader-side-action">
            <span class="reader-side-icon" id="viewModeIcon">2</span>
        </button>

        <div class="reader-side-center">
            <div class="reader-page-counter">
                <input type="number" id="currentPage" class="reader-page-input" min="1" step="1" value="1" aria-label="Página actual">
                <span class="reader-page-separator">/</span>
                <span id="totalPages">0</span>
            </div>

            <div class="reader-page-jump">
                <button type="button" id="prevPageBtn" class="reader-side-nav">❮</button>
                <button type="button" id="nextPageBtn" class="reader-side-nav">❯</button>
            </div>

            <div class="reader-page-progress" id="readerPercent">0%</div>
        </div>

        <button type="button" id="ttsToggleBtn" class="reader-side-action">
            <span class="reader-side-icon">🔊</span>
        </button>

        <button type="button" id="shortcutsToggle" class="reader-side-action">
            <span class="reader-side-icon">⌨</span>
        </button>

        <div id="shortcutsHelp" class="reader-shortcuts" aria-hidden="true">
            <div class="reader-shortcuts-title">Atajos</div>
            <div class="reader-shortcuts-item">← / → página</div>
            <div class="reader-shortcuts-item">F pantalla completa</div>
        </div>
    </aside>

    <main class="reader-main">
        <?php if (!$isValidBook): ?>
            <section class="reader-error">
                <h2>No se encontró el libro</h2>
                <p>El archivo solicitado no existe o no es un PDF válido.</p>
                <a href="<?php echo $baseUrl; ?>/index.php">Regresar al catálogo</a>
            </section>
        <?php else: ?>
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
                        <p class="reader-loading-note">Los libros extensos pueden tardar unos segundos antes de abrirse.</p>
                    </div>
                </div>
            </section>

            <section class="flipbook-wrapper" id="flipbookWrapper">
                <div id="flipbook" class="flipbook"></div>
            </section>
        <?php endif; ?>
    </main>

    <section id="ttsFloatingPanel" class="reader-tts-panel" aria-hidden="true">
        <div class="reader-tts-panel-title">Lectura en voz</div>
        <div class="reader-tts-panel-actions">
            <button type="button" id="ttsReadBtn" class="reader-tts-btn">Leer página</button>
            <button type="button" id="ttsStopBtn" class="reader-tts-btn">Detener</button>
            <button type="button" id="ttsCloseBtn" class="reader-tts-btn reader-tts-btn-secondary">Cerrar</button>
        </div>
    </section>

    <div id="ttsToast" class="reader-tts-toast" aria-live="polite" aria-atomic="true"></div>

    <?php if ($isValidBook): ?>
        <script>
            window.READER_CONFIG = {
                pdfUrl:    "<?php echo e($pdfUrl); ?>",
                workerUrl: "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js",
                title:     "<?php echo e($bookTitle); ?>",
                file:      "<?php echo e('assets/books/' . $book); ?>",
                apiUrl:    "<?php echo e(app_url('src/controllers/library_api.php')); ?>",
                csrfToken: "<?php echo e(csrf_token()); ?>"
            };
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.min.js"></script>
        <script src="<?php echo $assetUrl; ?>/js/reader.js"></script>
        <script src="<?php echo $assetUrl; ?>/js/reader-tts.js"></script>
    <?php endif; ?>
</body>
</html>