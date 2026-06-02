<?php
/**
 * tmp_expand_seed.php
 * 1. Valida nuevos ISBNs en Open Library
 * 2. Descarga portadas -L a assets/banners/ (nuevos + existentes del seed)
 * 3. Imprime SQL para los nuevos libros
 * Uso: php tmp_expand_seed.php
 */

define('BANNER_DIR', __DIR__ . '/assets/banners/');
define('OL_API',    'https://openlibrary.org/api/books?bibkeys=ISBN:%s&format=json&jscmd=data');
define('OL_COVER',  'https://covers.openlibrary.org/b/isbn/%s-L.jpg');
define('OL_COVERID','https://covers.openlibrary.org/b/id/%s-L.jpg');

if (!is_dir(BANNER_DIR)) { mkdir(BANNER_DIR, 0755, true); }

/* ── Libros nuevos propuestos ─────────────────────────────────────── */
$newBooks = [
    /* Literatura clásica */
    ['isbn'=>'9788497592208','titulo'=>'Cien Años de Soledad',              'autor'=>'Gabriel García Márquez',    'cat'=>'Literatura clásica',  'plan'=>1,'year'=>'1967-06-05','desc'=>'La saga de los Buendía en el mítico Macondo, obra cumbre del realismo mágico latinoamericano y Premio Nobel de Literatura.'],
    ['isbn'=>'9788491051831','titulo'=>'Crimen y Castigo',                  'autor'=>'Fiódor Dostoyevski',        'cat'=>'Literatura clásica',  'plan'=>1,'year'=>'1866-01-01','desc'=>'El estudiante Raskolnikov comete un asesinato y enfrenta la culpa, la redención y la ley moral en la Rusia zarista.'],
    ['isbn'=>'9780743273565','titulo'=>'El Gran Gatsby',                    'autor'=>'F. Scott Fitzgerald',       'cat'=>'Literatura clásica',  'plan'=>1,'year'=>'1925-04-10','desc'=>'El sueño americano encarnado en Jay Gatsby y su obsesión por revivir el pasado en la Nueva York de los años veinte.'],
    ['isbn'=>'9788491050766','titulo'=>'Orgullo y Prejuicio',               'autor'=>'Jane Austen',               'cat'=>'Literatura clásica',  'plan'=>1,'year'=>'1813-01-28','desc'=>'La señorita Bennet y el señor Darcy protagonizan una historia de amor donde el orgullo y el prejuicio chocan con el corazón.'],
    ['isbn'=>'9788420671598','titulo'=>'El Retrato de Dorian Gray',         'autor'=>'Oscar Wilde',               'cat'=>'Literatura clásica',  'plan'=>1,'year'=>'1890-07-01','desc'=>'Un joven bello vende su alma para que su retrato envejezca en su lugar, explorando la vanidad, la corrupción y la moral victoriana.'],
    ['isbn'=>'9788499893563','titulo'=>'Un Mundo Feliz',                    'autor'=>'Aldous Huxley',             'cat'=>'Literatura clásica',  'plan'=>1,'year'=>'1932-01-01','desc'=>'Distopía donde los seres humanos son fabricados en serie y condicionados para ser felices. Crítica magistral al consumismo y la tecnología.'],
    ['isbn'=>'9788446034926','titulo'=>'El Origen de las Especies',         'autor'=>'Charles Darwin',            'cat'=>'Historia y política', 'plan'=>2,'year'=>'1859-11-24','desc'=>'La obra que cambió la biología y el pensamiento humano: la evolución por selección natural explicada con rigor científico.'],
    /* Psicología */
    ['isbn'=>'9788483068618','titulo'=>'Pensar Rápido, Pensar Despacio',    'autor'=>'Daniel Kahneman',           'cat'=>'Psicología',          'plan'=>2,'year'=>'2011-10-25','desc'=>'El psicólogo Nobel explica los dos sistemas de pensamiento que gobiernan nuestras decisiones, sesgos y errores cognitivos.'],
    ['isbn'=>'9788425432026','titulo'=>'El Hombre en Busca de Sentido',     'autor'=>'Viktor E. Frankl',          'cat'=>'Psicología',          'plan'=>1,'year'=>'1946-01-01','desc'=>'Relato del psiquiatra austriaco sobre su experiencia en los campos de concentración nazis y la búsqueda de sentido como fuerza vital.'],
    ['isbn'=>'9788499882826','titulo'=>'Las Leyes de la Naturaleza Humana', 'autor'=>'Robert Greene',             'cat'=>'Psicología',          'plan'=>2,'year'=>'2018-10-23','desc'=>'Greene analiza las fuerzas irracionales que guían el comportamiento humano y enseña a comprenderlas para usarlas a nuestro favor.'],
    /* Desarrollo personal */
    ['isbn'=>'9788449324253','titulo'=>'Los 7 Hábitos de la Gente Altamente Efectiva','autor'=>'Stephen R. Covey','cat'=>'Desarrollo personal','plan'=>2,'year'=>'1989-08-15','desc'=>'Los principios atemporales de efectividad personal y profesional que han guiado a millones de personas en todo el mundo.'],
    ['isbn'=>'9788408166115','titulo'=>'Mindset: La Actitud del Éxito',     'autor'=>'Carol S. Dweck',            'cat'=>'Desarrollo personal', 'plan'=>2,'year'=>'2006-02-28','desc'=>'La psicóloga de Stanford explica cómo la mentalidad de crecimiento (growth mindset) determina el éxito frente a la mentalidad fija.'],
    ['isbn'=>'9788499980775','titulo'=>'El Poder del Ahora',                'autor'=>'Eckhart Tolle',             'cat'=>'Desarrollo personal', 'plan'=>1,'year'=>'1997-01-01','desc'=>'Guía espiritual hacia la iluminación a través de la presencia, el momento presente y la liberación del pensamiento compulsivo.'],
    /* Novela de misterio */
    ['isbn'=>'9788420471662','titulo'=>'El Nombre de la Rosa',              'autor'=>'Umberto Eco',               'cat'=>'Novela de misterio',  'plan'=>2,'year'=>'1980-01-01','desc'=>'Monje detective en una abadía medieval investiga una serie de muertes misteriosas en una magnífica novela histórica e intelectual.'],
    ['isbn'=>'9788408163435','titulo'=>'La Sombra del Viento',              'autor'=>'Carlos Ruiz Zafón',         'cat'=>'Novela de misterio',  'plan'=>2,'year'=>'2001-04-01','desc'=>'Barcelona posguerra: Daniel Sempere descubre un libro de Julián Carax y se ve envuelto en un misterio que cambiará su vida.'],
    ['isbn'=>'9788408162216','titulo'=>'La Chica del Tren',                 'autor'=>'Paula Hawkins',             'cat'=>'Novela de misterio',  'plan'=>2,'year'=>'2015-01-13','desc'=>'Rachel Watson observa a diario desde el tren una pareja perfecta hasta que un día la mujer desaparece. Thriller psicológico impactante.'],
    /* Historia y política */
    ['isbn'=>'9788499926223','titulo'=>'Sapiens: De Animales a Dioses',     'autor'=>'Yuval Noah Harari',         'cat'=>'Historia y política', 'plan'=>2,'year'=>'2011-01-01','desc'=>'Historia de la humanidad desde la prehistoria hasta el presente: cómo el Homo sapiens dominó el planeta usando lenguaje, mitos y cooperación.'],
    /* Finanzas */
    ['isbn'=>'9788418473647','titulo'=>'La Psicología del Dinero',          'autor'=>'Morgan Housel',             'cat'=>'Finanzas',            'plan'=>2,'year'=>'2020-09-08','desc'=>'19 historias sobre cómo las personas piensan en el dinero, la riqueza y la toma de decisiones financieras, con lecciones atemporales.'],
    ['isbn'=>'9788423428069','titulo'=>'El Inversor Inteligente',           'autor'=>'Benjamin Graham',           'cat'=>'Finanzas',            'plan'=>3,'year'=>'1949-01-01','desc'=>'La biblia de la inversión en valor escrita por el mentor de Warren Buffett: principios sólidos para invertir con inteligencia y disciplina.'],
    ['isbn'=>'9788416949144','titulo'=>'Los Secretos de la Mente Millonaria','autor'=>'T. Harv Eker',             'cat'=>'Finanzas',            'plan'=>1,'year'=>'2005-02-15','desc'=>'Revela cómo los patrones de pensamiento sobre el dinero determinan el nivel de riqueza y cómo reprogramar la mente para la prosperidad.'],
];

/* ── ISBNs ya en el seed (para descargar sus banners) ───────────────── */
$existingIsbns = [
    '9788415618713','9781986286121','9781507170656','9788491050773',
    '9781480096134','9788026803423','9788432290039','9781476787862',
    '9781535213158','9783903352513','9798669592257','9780525504368',
    '9781533049711','9788494096358','9781702785891','9781914284465',
    '9798646039256','9781082568886','9788467028904','9788416029747',
    '9781947783423','9786077477204','9788479538163','9786079765453',
    '9788492892037','9788408143666','9788498728538','9789501524765',
    '9788466655507','9788466651929','9788490705933','9788401464867',
    '9788447319244','9788401463662','9788447319343','9788484506119',
    '9788484506133','9788420426365','9788497597524','9786079202613',
    '9788449320651','9788449342585','9788494151040','9788413843322',
    '9788499925585','9788433410474','9788497341974','9798850586317',
    '9780071466981','9781644736623','9788413141893','9789681320089',
    '9798484730667','9781644730096','9781455525447',
    // sin ISBN en el seed: usar slug
];

/* ── Libros sin ISBN en el seed: portadas especiales ─────────────────── */
$noIsbnBanners = [
    '1984'       => ['slug'=>'orwell-1984',      'ol_search'=>'1984 George Orwell'],
    'don_quijote'=> ['slug'=>'cervantes-quijote', 'ol_search'=>'Don Quijote Cervantes'],
    'harry_potter'=> ['slug'=>'rowling-hp1',      'ol_search'=>'Harry Potter Piedra Filosofal Rowling'],
];

/* ── Helpers ──────────────────────────────────────────────────────── */
function httpGet(string $url): ?string {
    $ctx = stream_context_create(['http'=>[
        'timeout'        => 15,
        'user_agent'     => 'MuggleApp/1.0 (library catalog)',
        'ignore_errors'  => true,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r === false ? null : $r;
}

function downloadBanner(string $isbn): ?string {
    $filename = BANNER_DIR . $isbn . '.jpg';
    if (file_exists($filename)) {
        echo "  [skip] Banner ya existe: {$isbn}.jpg\n";
        return "assets/banners/{$isbn}.jpg";
    }
    $url = sprintf(OL_COVER, $isbn);
    $data = httpGet($url);
    // Open Library devuelve imagen "no cover" de ~807 bytes si no existe
    if ($data && strlen($data) > 2000) {
        file_put_contents($filename, $data);
        echo "  [OK]   Banner descargado: {$isbn}.jpg\n";
        return "assets/banners/{$isbn}.jpg";
    }
    echo "  [--]   Sin portada disponible para ISBN {$isbn}\n";
    return null;
}

function downloadBannerBySlug(string $slug, string $coverUrl): ?string {
    $filename = BANNER_DIR . $slug . '.jpg';
    if (file_exists($filename)) {
        echo "  [skip] Banner ya existe: {$slug}.jpg\n";
        return "assets/banners/{$slug}.jpg";
    }
    $data = httpGet($coverUrl);
    if ($data && strlen($data) > 2000) {
        file_put_contents($filename, $data);
        echo "  [OK]   Banner descargado: {$slug}.jpg\n";
        return "assets/banners/{$slug}.jpg";
    }
    return null;
}

function sqlStr(?string $s): string {
    if ($s === null) return 'NULL';
    return "'" . str_replace(["'","\\"], ["''","\\\\"], $s) . "'";
}

function olApiQuery(string $isbn): ?array {
    $url  = sprintf(OL_API, $isbn);
    $raw  = httpGet($url);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    return $data["ISBN:{$isbn}"] ?? null;
}

/* ════════════════════════════════════════════════════════════════════
   1. Descargar banners de libros EXISTENTES en el seed
   ════════════════════════════════════════════════════════════════════ */
echo "\n=== Banners de libros existentes ===\n";
foreach ($existingIsbns as $isbn) {
    downloadBanner($isbn);
    usleep(300000); // 0.3 s entre requests
}

// Banners sin ISBN (buscar por Open Library search API)
echo "\n=== Banners para libros sin ISBN ===\n";
$noIsbnCoverIds = [
    // Obtenidos manualmente buscando en openlibrary.org
    'orwell-1984'       => '14574978', // 1984 OL cover id
    'cervantes-quijote' => '10397651', // Don Quijote OL cover id
    'rowling-hp1'       => '8228691',  // Harry Potter OL cover id
];
foreach ($noIsbnCoverIds as $slug => $coverId) {
    $url = sprintf(OL_COVERID, $coverId);
    downloadBannerBySlug($slug, $url);
    usleep(300000);
}

/* ════════════════════════════════════════════════════════════════════
   2. Validar nuevos ISBNs y descargar sus banners
   ════════════════════════════════════════════════════════════════════ */
echo "\n=== Validando libros nuevos en Open Library ===\n";
$results = [];
foreach ($newBooks as $book) {
    $isbn = $book['isbn'];
    echo "ISBN {$isbn} — {$book['titulo']}... ";
    $info = olApiQuery($isbn);
    sleep(1); // respetar rate limit OL

    if ($info) {
        $portada = null;
        if (!empty($info['cover']['large'])) {
            $portada = $info['cover']['large'];
        } elseif (!empty($info['cover']['medium'])) {
            $portada = str_replace('-M.jpg', '-L.jpg', $info['cover']['medium']);
        }
        // Descargar banner
        downloadBanner($isbn);
        usleep(300000);

        $book['portada']  = $portada;
        $book['found']    = true;
        $book['ol_title'] = $info['title'] ?? $book['titulo'];
        echo "ENCONTRADO" . ($portada ? " (con portada)" : " (sin portada)") . "\n";
    } else {
        downloadBanner($isbn); // intentar igual con ISBN endpoint
        usleep(300000);
        $book['portada'] = null;
        $book['found']   = false;
        echo "no encontrado en API\n";
    }
    $results[] = $book;
}

/* ════════════════════════════════════════════════════════════════════
   3. Generar SQL para los nuevos libros
   ════════════════════════════════════════════════════════════════════ */
echo "\n\n=== SQL para agregar a seed_libros.sql ===\n";
echo "-- ── Libros adicionales (generados por tmp_expand_seed.php) ─────────\n";

// Agrupar por categoría para insertar con cabecera
$byCategory = [];
foreach ($results as $b) {
    $byCategory[$b['cat']][] = $b;
}

foreach ($byCategory as $cat => $books) {
    echo "\n-- Categoría: {$cat}\n";
    foreach ($books as $b) {
        $isbn     = sqlStr($b['isbn']);
        $titulo   = sqlStr($b['titulo']);
        $autor    = sqlStr($b['autor']);
        $desc     = sqlStr($b['desc']);
        $portada  = sqlStr($b['portada'] ?? null);
        $plan     = (int)$b['plan'];
        $year     = sqlStr($b['year']);
        $catSql   = sqlStr($b['cat']);
        // Banner local (si se descargó)
        $bannerFile = BANNER_DIR . $b['isbn'] . '.jpg';
        $bannerNote = file_exists($bannerFile) ? ' -- banner: assets/banners/'.$b['isbn'].'.jpg' : '';

        echo "INSERT IGNORE INTO libros (isbn, titulo, autor, descripcion, portada, archivo, tipo, fecha_publicado, id_categoria, id_plan_minimo, estado_publicacion) VALUES\n";
        echo "({$isbn}, {$titulo}, {$autor}, {$desc}, {$portada}, NULL, 'pdf', {$year},\n";
        echo " (SELECT id_categoria FROM categorias WHERE nombre = {$catSql} LIMIT 1), {$plan}, 'publicado');{$bannerNote}\n";
    }
}

/* ════════════════════════════════════════════════════════════════════
   4. Resumen y lista de PDFs públicos disponibles
   ════════════════════════════════════════════════════════════════════ */
echo "\n\n=== Lista de PDFs en dominio público (Project Gutenberg) ===\n";
$gutenberg = [
    'El Príncipe (Maquiavelo)'                => 'https://www.gutenberg.org/ebooks/1232   (es/en)',
    'Alicia en el País de las Maravillas'     => 'https://www.gutenberg.org/ebooks/31536  (es)',
    'El Arte de la Guerra'                    => 'https://www.gutenberg.org/ebooks/132    (en)',
    'Edipo Rey'                               => 'https://www.gutenberg.org/ebooks/31     (en)',
    'El Corazón de las Tinieblas'             => 'https://www.gutenberg.org/ebooks/219    (en)',
    'El Fantasma de Canterville'              => 'https://www.gutenberg.org/ebooks/14522  (es)',
    'La Metamorfosis'                         => 'https://www.gutenberg.org/ebooks/5200   (de original)',
    'Don Quijote de la Mancha'               => 'https://www.gutenberg.org/ebooks/2000   (es)',
    'Crimen y Castigo'                        => 'https://www.gutenberg.org/ebooks/2197   (en, trad. Garnett)',
    'El Gran Gatsby'                          => 'https://www.gutenberg.org/ebooks/64317  (en, PD en US desde 2021)',
    'Orgullo y Prejuicio'                     => 'https://www.gutenberg.org/ebooks/1342   (en)',
    'El Retrato de Dorian Gray'               => 'https://www.gutenberg.org/ebooks/174    (en)',
    'El Origen de las Especies'               => 'https://www.gutenberg.org/ebooks/1228   (en)',
    'De la Brevedad de la Vida (Séneca)'      => 'https://www.gutenberg.org/ebooks/1236   (en, trad. latina)',
    'La Perla (Steinbeck)'                    => 'NO dominio público todavía (murió 1968)',
    'El Viejo y el Mar'                       => 'NO dominio público todavía (murió 1961)',
    'Esperando a Godot'                       => 'NO dominio público todavía (murió 1989)',
    'Cien Años de Soledad'                    => 'NO dominio público (murió 2014)',
    'Sapiens'                                 => 'NO dominio público (vivo)',
    'La Sombra del Viento'                    => 'NO dominio público (murió 2020)',
];
foreach ($gutenberg as $titulo => $link) {
    echo "  {$titulo}\n    {$link}\n";
}

// Resumen
$found   = count(array_filter($results, fn($b) => $b['found']));
$notFound= count($results) - $found;
$banners = count(glob(BANNER_DIR . '*.jpg'));
echo "\n=== Resumen ===\n";
echo "Libros nuevos:     " . count($results) . "\n";
echo "API encontró:      {$found}\n";
echo "API no encontró:   {$notFound}\n";
echo "Banners en disco:  {$banners}\n";
echo "Directorio:        " . BANNER_DIR . "\n";
