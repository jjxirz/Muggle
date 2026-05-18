<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$page_title = $page_title ?? 'Hogwarts Libraries';
$active_page = $active_page ?? '';

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
$user_role = $_SESSION['user_role'] ?? 'usuario';
$user_plan = $_SESSION['user_plan'] ?? 'Essential';

function activeClass($page, $active_page) {
    return $page === $active_page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title); ?> | <?= h($current_house['name']); ?> Libraries</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        :root {
            --house-primary: <?= h($current_house['color']); ?>;
            --house-secondary: <?= h($current_house['secondary']); ?>;
            --house-highlight: <?= h($current_house['highlight']); ?>;
            --house-text: <?= h($current_house['text_color']); ?>;
        }

        .main-header {
            background-color: var(--house-primary) !important;
            border-bottom-color: var(--house-highlight) !important;
        }

        .nav-menu a:hover,
        .nav-menu a.active,
        .view-all:hover,
        .footer-col a:hover,
        .profile-link:hover {
            color: var(--house-highlight) !important;
        }

        .btn-primary,
        .hero-badge,
        .play-btn,
        .active-house,
        .books-carousel::-webkit-scrollbar-thumb,
        .section-title::after,
        .profile-plan-badge,
        .profile-stat-icon,
        .profile-progress-fill {
            background-color: var(--house-highlight) !important;
        }

        .btn-primary,
        .hero-badge,
        .play-btn,
        .active-house,
        .profile-plan-badge,
        .profile-stat-icon {
            color: var(--house-primary) !important;
        }

        .btn-primary:hover,
        .category-card:hover,
        .logout-btn:hover,
        .profile-action:hover {
            background-color: var(--house-secondary) !important;
            color: var(--house-text) !important;
        }

        .btn-secondary,
        .house-btn,
        .profile-action {
            border-color: var(--house-highlight) !important;
            color: var(--house-highlight) !important;
        }

        .btn-secondary:hover,
        .house-btn:hover {
            background-color: var(--house-highlight) !important;
            color: var(--house-primary) !important;
        }

        .category-card,
        .profile-card,
        .profile-side-card {
            background-color: var(--house-primary) !important;
        }
    </style>
</head>

<body class="theme-<?= h($house); ?>">

<header class="main-header">
    <div class="container header-content">
        <a href="index.php" class="logo">
            <div class="row">
                <img
                    src="<?= h($current_house['logo_img']); ?>"
                    alt="Logo de <?= h($current_house['name']); ?>"
                    class="logo-img"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >

                <div class="logo-fa-fallback" style="display: none;">
                    <i class="fas <?= h($current_house['icon']); ?>"></i>
                </div>

                <div>
                    <h1><?= strtoupper(h($current_house['name'])); ?> LIBRARIES</h1>
                    <span class="logo-subtitle">Streaming de libros mágicos</span>
                </div>
            </div>
        </a>

        <nav class="nav-menu">
            <ul>
                <li>
                    <a href="index.php" class="<?= activeClass('inicio', $active_page); ?>">
                        Inicio
                    </a>
                </li>

                <li>
                    <a href="#" class="<?= activeClass('explorar', $active_page); ?>">
                        Explorar
                    </a>
                </li>

                <li>
                    <a href="#" class="<?= activeClass('mi-lista', $active_page); ?>">
                        Mi lista
                    </a>
                </li>

                <li>
                    <a href="#" class="<?= activeClass('categorias', $active_page); ?>">
                        Categorías
                    </a>
                </li>

                <li>
                    <a href="perfil.php" class="<?= activeClass('perfil', $active_page); ?>">
                        Perfil
                    </a>
                </li>

                <li class="user-nav-item">
                    <div class="user-menu">
                        <span class="user-name">
                            <i class="fas fa-user-circle"></i>
                            <?= h($user_name); ?>
                        </span>

                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            Cerrar sesión
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</header>

<main class="page-main">