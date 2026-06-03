<?php

declare(strict_types=1);

$seedPath = __DIR__ . '/db/seed_libros.sql';
$sql = @file_get_contents($seedPath);

if ($sql === false) {
    fwrite(STDERR, "No se pudo leer: {$seedPath}\n");
    exit(1);
}

$rows = [];
$pattern = '/\(\s*\n\s*(NULL|\'[^\']*\')\s*,\s*\n\s*\'([^\']+)\'\s*,\s*\n\s*\'[^\']*\'\s*,\s*\n\s*\'[^\']*\'\s*,\s*\n\s*(NULL|\'[^\']*\')\s*,/m';
preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    $rows[] = [
        'title' => $match[2],
        'cover_raw' => $match[3],
    ];
}

$issues = [];
$totalCoverUrls = 0;
$okHttp = 0;

foreach ($rows as $row) {
    $title = $row['title'];
    $coverRaw = trim((string) $row['cover_raw']);

    if (strtoupper($coverRaw) === 'NULL') {
        $issues[] = "SIN_PORTADA: {$title}";
        continue;
    }

    $coverUrl = trim($coverRaw, "'");
    if (stripos($coverUrl, 'covers.openlibrary.org') === false) {
        continue;
    }

    $totalCoverUrls++;

    $headers = @get_headers($coverUrl);
    if ($headers === false || !isset($headers[0])) {
        $issues[] = "NO_HTTP: {$title} => {$coverUrl}";
        continue;
    }

    $statusLine = (string) $headers[0];
    if (strpos($statusLine, '200') === false && strpos($statusLine, '301') === false && strpos($statusLine, '302') === false) {
        $issues[] = "HTTP_FAIL: {$title} => {$statusLine} => {$coverUrl}";
        continue;
    }

    $okHttp++;
}

echo "VALIDACION_COVERS_SEED\n";
echo "TOTAL_COVERS_OPENLIBRARY={$totalCoverUrls}\n";
echo "HTTP_200={$okHttp}\n";
echo "ISSUES=" . count($issues) . "\n";

if (!empty($issues)) {
    echo "--- DETALLE ---\n";
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
}
