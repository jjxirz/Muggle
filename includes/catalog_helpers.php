<?php

if (!function_exists('catalog_e')) {
    function catalog_e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Load the full published catalog from the database.
 * Returns an array of book arrays ready for renderBookCard().
 * Falls back to an empty array if the DB is not available or the table is empty.
 */
if (!function_exists('catalog_books_from_db')) {
    function catalog_books_from_db(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        try {
            require_once __DIR__ . '/../src/models/BookModel.php';
            $model = new BookModel();
            $rows  = $model->getCatalogBooks();
        } catch (Throwable $e) {
            $cache = [];
            return $cache;
        }

        $books = [];
        foreach ($rows as $index => $row) {
            $books[] = catalog_prepare_db_book($row, $index);
        }

        $cache = $books;
        return $cache;
    }
}

if (!function_exists('cleanPdfTitle')) {
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
}

if (!function_exists('getPdfSizeLabel')) {
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
}

if (!function_exists('normalizeBookPlan')) {
    function normalizeBookPlan(?string $plan, int $index): array
    {
        $plans = [
            'gratis' => ['label' => 'Gratis', 'class' => 'free'],
            'basico' => ['label' => 'Básico', 'class' => 'basico'],
            'plus' => ['label' => 'Plus', 'class' => 'plus'],
            'premium' => ['label' => 'Premium', 'class' => 'premium'],
        ];

        $normalized = strtolower(trim((string) $plan));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        if ($normalized !== '' && isset($plans[$normalized])) {
            return $plans[$normalized];
        }

        $cycle = ['gratis', 'basico', 'plus', 'premium'];
        $fallback = $cycle[$index % count($cycle)];

        return $plans[$fallback];
    }
}

if (!function_exists('guessBookMeta')) {
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
}

if (!function_exists('normalizeBookName')) {
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
}

if (!function_exists('findBookImage')) {
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
}

if (!function_exists('getPdfBooksFromFolder')) {
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
            $bookIndex = count($books);
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            $title = cleanPdfTitle($file);
            $meta = guessBookMeta($file, $title);
            $cover = findBookImage($file, $meta['title'], $imageFolderPath, $imageUrl);
            $plan = normalizeBookPlan($meta['plan'] ?? null, $bookIndex);

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
                'plan' => $plan['label'],
                'plan_class' => $plan['class'],
            ];
        }

        return $books;
    }
}

if (!function_exists('catalog_prepare_db_book')) {
    function catalog_prepare_db_book(array $row, int $index = 0): array
    {
        $file = trim((string) ($row['archivo'] ?? ''));
        $cover = trim((string) ($row['portada'] ?? ''));
        $coverUrl = '';

        if ($cover !== '') {
            $coverUrl = preg_match('#^https?://#i', $cover) ? $cover : app_url(ltrim($cover, '/'));
        }

        $pdfUrl = '';
        $readerUrl = '#';
        if ($file !== '' && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
            $pdfUrl = app_url(ltrim($file, '/'));
            $readerUrl = app_url('reader.php') . '?book=' . rawurlencode(basename($file));
        }

        $plan = normalizeBookPlan((string) ($row['plan_nombre'] ?? ''), $index);

        return [
            'title' => (string) ($row['titulo'] ?? 'Obra sin identificar'),
            'author' => (string) ($row['autor'] ?? 'Autor no especificado'),
            'category' => (string) ($row['categoria'] ?? 'Lectura digital'),
            'year' => !empty($row['fecha_publicado']) ? (string) date('Y', strtotime((string) $row['fecha_publicado'])) : 'Disponible',
            'pages' => 'Archivo disponible',
            'tags' => (string) ($row['categoria'] ?? 'Biblioteca'),
            'cover' => $coverUrl,
            'banner' => $coverUrl,
            'description' => (string) ($row['descripcion'] ?? 'Obra disponible en el catálogo digital de la biblioteca.'),
            'pdf' => $pdfUrl,
            'reader' => $readerUrl,
            'file' => $file,
            'type' => (string) ($row['tipo'] ?? 'pdf'),
            'plan' => $plan['label'],
            'plan_class' => $plan['class'],
        ];
    }
}

if (!function_exists('catalog_preview_data_attrs')) {
    function catalog_preview_data_attrs(array $book): string
    {
        $attributes = [
            'data-title' => (string) ($book['title'] ?? 'Obra sin identificar'),
            'data-author' => (string) ($book['author'] ?? 'Autor no especificado'),
            'data-description' => (string) ($book['description'] ?? 'Obra disponible en el catálogo digital de la biblioteca.'),
            'data-category' => (string) ($book['category'] ?? 'Lectura digital'),
            'data-year' => (string) ($book['year'] ?? 'Disponible'),
            'data-pages' => (string) ($book['pages'] ?? 'Archivo disponible'),
            'data-pdf' => (string) ($book['pdf'] ?? ''),
            'data-reader' => (string) ($book['reader'] ?? ''),
            'data-file' => (string) ($book['file'] ?? ''),
            'data-type' => (string) ($book['type'] ?? 'pdf'),
            'data-cover' => (string) ($book['cover'] ?? ''),
            'data-banner' => (string) ($book['banner'] ?? ''),
            'data-tags' => (string) ($book['tags'] ?? ''),
        ];

        $html = '';
        foreach ($attributes as $name => $value) {
            $html .= ' ' . $name . '="' . catalog_e($value) . '"';
        }

        return $html;
    }
}

if (!function_exists('catalog_unique_books')) {
    function catalog_unique_books(array $books): array
    {
        $seen = [];
        $unique = [];

        foreach ($books as $book) {
            $key = mb_strtolower(
                trim((string) ($book['file'] ?? '')) . '|' . trim((string) ($book['title'] ?? '')) . '|' . trim((string) ($book['author'] ?? '')),
                'UTF-8'
            );

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $book;
        }

        return $unique;
    }
}

if (!function_exists('catalog_pick_discovery_books')) {
    function catalog_pick_discovery_books(array $books, array $preferredCategories = [], array $excludeTitles = [], int $supportLimit = 3): array
    {
        $books = catalog_unique_books($books);
        $preferredCategories = array_map(static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8'), $preferredCategories);
        $excludeTitles = array_map(static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8'), $excludeTitles);

        $featured = null;

        foreach ($books as $book) {
            $title = mb_strtolower(trim((string) ($book['title'] ?? '')), 'UTF-8');
            $category = mb_strtolower(trim((string) ($book['category'] ?? '')), 'UTF-8');

            if (in_array($title, $excludeTitles, true)) {
                continue;
            }

            if (!empty($preferredCategories) && in_array($category, $preferredCategories, true)) {
                $featured = $book;
                break;
            }
        }

        if ($featured === null) {
            foreach ($books as $book) {
                $title = mb_strtolower(trim((string) ($book['title'] ?? '')), 'UTF-8');
                if (!in_array($title, $excludeTitles, true)) {
                    $featured = $book;
                    break;
                }
            }
        }

        if ($featured === null) {
            return ['featured' => null, 'support' => []];
        }

        $support = [];
        $featuredTitle = mb_strtolower(trim((string) ($featured['title'] ?? '')), 'UTF-8');
        $featuredCategory = mb_strtolower(trim((string) ($featured['category'] ?? '')), 'UTF-8');

        foreach ($books as $book) {
            $title = mb_strtolower(trim((string) ($book['title'] ?? '')), 'UTF-8');
            $category = mb_strtolower(trim((string) ($book['category'] ?? '')), 'UTF-8');

            if ($title === $featuredTitle || in_array($title, $excludeTitles, true)) {
                continue;
            }

            if ($category === $featuredCategory || empty($support)) {
                $support[] = $book;
            }

            if (count($support) >= $supportLimit) {
                break;
            }
        }

        return ['featured' => $featured, 'support' => $support];
    }
}

if (!function_exists('renderDiscoveryBand')) {
    function renderDiscoveryBand(?array $featuredBook, array $supportBooks = [], array $options = []): void
    {
        if ($featuredBook === null) {
            return;
        }

        $eyebrow = (string) ($options['eyebrow'] ?? 'Recomendación para ti');
        $title = (string) ($options['title'] ?? 'Una lectura que encaja con tu ritmo');
        $description = (string) ($options['description'] ?? 'Una selección breve para descubrir algo nuevo sin romper el flujo de la página.');
        $readLabel = (string) ($options['read_label'] ?? 'Abrir lectura');
        $previewLabel = (string) ($options['preview_label'] ?? 'Vista previa');
        ?>
        <section class="discovery-band">
            <div class="discovery-band__inner">
                <div class="discovery-band__copy">
                    <span class="discovery-band__eyebrow"><?php echo catalog_e($eyebrow); ?></span>
                    <h3 class="discovery-band__title"><?php echo catalog_e($title); ?></h3>
                    <p class="discovery-band__description"><?php echo catalog_e($description); ?></p>
                    <div class="discovery-band__featured-meta">
                        <strong><?php echo catalog_e((string) ($featuredBook['title'] ?? 'Obra destacada')); ?></strong>
                        <span><?php echo catalog_e((string) ($featuredBook['author'] ?? 'Autor no especificado')); ?> · <?php echo catalog_e((string) ($featuredBook['category'] ?? 'Lectura digital')); ?></span>
                    </div>
                    <div class="discovery-band__actions">
                        <a href="#" class="btn btn-primary js-book-preview"<?php echo catalog_preview_data_attrs($featuredBook); ?>><?php echo catalog_e($previewLabel); ?></a>
                        <?php if (!empty($featuredBook['reader']) || !empty($featuredBook['pdf'])): ?>
                            <a href="<?php echo catalog_e((string) ($featuredBook['reader'] ?? $featuredBook['pdf'] ?? '#')); ?>" class="btn btn-secondary"><?php echo catalog_e($readLabel); ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($supportBooks)): ?>
                    <div class="discovery-band__shelf" aria-label="Lecturas relacionadas">
                        <?php foreach ($supportBooks as $supportBook): ?>
                            <article class="discovery-band__mini-card js-book-preview" role="button" tabindex="0"<?php echo catalog_preview_data_attrs($supportBook); ?>>
                                <div class="discovery-band__mini-cover">
                                    <?php if (!empty($supportBook['cover'])): ?>
                                        <img src="<?php echo catalog_e((string) $supportBook['cover']); ?>" alt="<?php echo catalog_e((string) ($supportBook['title'] ?? 'Libro')); ?>">
                                    <?php else: ?>
                                        <span><?php echo catalog_e(mb_substr((string) ($supportBook['title'] ?? 'L'), 0, 1, 'UTF-8')); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="discovery-band__mini-copy">
                                    <strong><?php echo catalog_e((string) ($supportBook['title'] ?? 'Libro')); ?></strong>
                                    <span><?php echo catalog_e((string) ($supportBook['category'] ?? 'Lectura digital')); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('renderBookCard')) {
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
        $plan = $book['plan'] ?? 'Gratis';
        $planClass = $book['plan_class'] ?? 'free';
        ?>
        <div class="book-card js-book-preview"
             role="button"
             tabindex="0"
             data-title="<?php echo catalog_e($title); ?>"
             data-author="<?php echo catalog_e($author); ?>"
             data-description="<?php echo catalog_e($description); ?>"
             data-category="<?php echo catalog_e($category); ?>"
             data-year="<?php echo catalog_e($year); ?>"
             data-pages="<?php echo catalog_e($pages); ?>"
             data-pdf="<?php echo catalog_e($pdf); ?>"
             data-reader="<?php echo catalog_e($reader); ?>"
             data-file="<?php echo catalog_e($file); ?>"
             data-type="<?php echo catalog_e($type); ?>"
             data-cover="<?php echo catalog_e($cover); ?>"
             data-banner="<?php echo catalog_e($banner); ?>"
             data-tags="<?php echo catalog_e($tags); ?>">
            <div class="book-cover">
                <span class="book-plan-badge book-plan-badge--<?php echo catalog_e($planClass); ?>"><?php echo catalog_e($plan); ?></span>
                <span class="book-progress-indicator" aria-label="Lectura en progreso" title="Lectura en progreso">
                    <i class="fas fa-book-open" aria-hidden="true"></i>
                </span>
                <span class="book-favorite-indicator" aria-label="Favorito" title="Favorito">
                    <i class="fas fa-star" aria-hidden="true"></i>
                </span>

                <?php if ($cover !== ''): ?>
                    <img src="<?php echo catalog_e($cover); ?>" alt="<?php echo catalog_e($title); ?>" class="cover-img">
                <?php else: ?>
                    <div class="book-cover-fallback">
                        <span><?php echo catalog_e(mb_substr((string) $title, 0, 1, 'UTF-8')); ?></span>
                        <strong><?php echo catalog_e($title); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="book-overlay">
                    <a href="#" class="play-btn js-open-preview" aria-label="Vista previa de <?php echo catalog_e($title); ?>">
                        <i class="fas fa-play" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="book-info">
                <h4><?php echo catalog_e($title); ?></h4>
                <p><?php echo catalog_e($author); ?></p>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('catalog_build_discovery_band')) {
    function catalog_build_discovery_band(array $books, array $options = []): ?array
    {
        $books = array_values(array_filter($books, static function ($book) {
            return is_array($book) && !empty($book['title']);
        }));

        if (empty($books)) {
            return null;
        }

        $featured = $books[0];
        $companions = array_slice($books, 1, 3);

        return [
            'eyebrow' => (string) ($options['eyebrow'] ?? 'Sugerencia para seguir leyendo'),
            'title' => (string) ($options['title'] ?? ($featured['title'] ?? 'Lectura recomendada')),
            'description' => (string) ($options['description'] ?? ($featured['description'] ?? '')),
            'meta' => (string) ($options['meta'] ?? (($featured['author'] ?? 'Autor no especificado') . ' · ' . ($featured['category'] ?? 'Lectura digital'))),
            'cta_label' => (string) ($options['cta_label'] ?? 'Ver recomendación'),
            'featured' => $featured,
            'companions' => $companions,
        ];
    }
}

if (!function_exists('catalog_render_discovery_band')) {
    function catalog_render_discovery_band(?array $band): void
    {
        if ($band === null) {
            return;
        }

        $featured = $band['featured'] ?? [];
        $companions = $band['companions'] ?? [];
        ?>
        <section class="discovery-band">
            <div class="discovery-band__copy">
                <span class="discovery-band__eyebrow"><?php echo catalog_e($band['eyebrow'] ?? 'Recomendación'); ?></span>
                <h3 class="discovery-band__title"><?php echo catalog_e($band['title'] ?? 'Lectura sugerida'); ?></h3>
                <p class="discovery-band__meta"><?php echo catalog_e($band['meta'] ?? 'Biblioteca digital'); ?></p>
                <p class="discovery-band__description"><?php echo catalog_e($band['description'] ?? ''); ?></p>
                <a href="#"
                   class="btn btn-secondary js-book-preview"
                   data-title="<?php echo catalog_e($featured['title'] ?? ''); ?>"
                   data-author="<?php echo catalog_e($featured['author'] ?? ''); ?>"
                   data-description="<?php echo catalog_e($featured['description'] ?? ''); ?>"
                   data-category="<?php echo catalog_e($featured['category'] ?? ''); ?>"
                   data-year="<?php echo catalog_e($featured['year'] ?? ''); ?>"
                   data-pages="<?php echo catalog_e($featured['pages'] ?? ''); ?>"
                   data-pdf="<?php echo catalog_e($featured['pdf'] ?? ''); ?>"
                   data-reader="<?php echo catalog_e($featured['reader'] ?? ''); ?>"
                   data-file="<?php echo catalog_e($featured['file'] ?? ''); ?>"
                   data-type="<?php echo catalog_e($featured['type'] ?? 'pdf'); ?>"
                   data-cover="<?php echo catalog_e($featured['cover'] ?? ''); ?>"
                   data-banner="<?php echo catalog_e($featured['banner'] ?? ''); ?>"
                   data-tags="<?php echo catalog_e($featured['tags'] ?? ''); ?>">
                    <i class="fas fa-sparkles" aria-hidden="true"></i>
                    <?php echo catalog_e($band['cta_label'] ?? 'Ver recomendación'); ?>
                </a>
            </div>

            <div class="discovery-band__stack">
                <?php foreach ($companions as $companion): ?>
                    <button type="button"
                            class="discovery-band__mini js-book-preview"
                            aria-label="Vista previa de <?php echo catalog_e($companion['title'] ?? 'Libro'); ?>"
                            data-title="<?php echo catalog_e($companion['title'] ?? ''); ?>"
                            data-author="<?php echo catalog_e($companion['author'] ?? ''); ?>"
                            data-description="<?php echo catalog_e($companion['description'] ?? ''); ?>"
                            data-category="<?php echo catalog_e($companion['category'] ?? ''); ?>"
                            data-year="<?php echo catalog_e($companion['year'] ?? ''); ?>"
                            data-pages="<?php echo catalog_e($companion['pages'] ?? ''); ?>"
                            data-pdf="<?php echo catalog_e($companion['pdf'] ?? ''); ?>"
                            data-reader="<?php echo catalog_e($companion['reader'] ?? ''); ?>"
                            data-file="<?php echo catalog_e($companion['file'] ?? ''); ?>"
                            data-type="<?php echo catalog_e($companion['type'] ?? 'pdf'); ?>"
                            data-cover="<?php echo catalog_e($companion['cover'] ?? ''); ?>"
                            data-banner="<?php echo catalog_e($companion['banner'] ?? ''); ?>"
                            data-tags="<?php echo catalog_e($companion['tags'] ?? ''); ?>">
                        <?php if (!empty($companion['cover'])): ?>
                            <img src="<?php echo catalog_e($companion['cover']); ?>" alt="<?php echo catalog_e($companion['title'] ?? 'Libro'); ?>">
                        <?php else: ?>
                            <span><?php echo catalog_e(mb_substr((string) ($companion['title'] ?? 'L'), 0, 1, 'UTF-8')); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
