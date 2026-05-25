<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$page_title = $page_title ?? 'Hogwarts Libraries';
$active_page = $active_page ?? '';
$theme_enabled = (bool) ($_SESSION['theme_enabled'] ?? true);

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

if (!$theme_enabled) {
    $current_house = [
        'name' => 'Hogwarts',
        'icon' => 'fa-hat-wizard',
        'logo_img' => '',
        'color' => '#111110',
        'secondary' => '#5f5e5a',
        'highlight' => '#f5f4f0',
        'text_color' => '#ffffff',
    ];
}

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'usuario';
$user_plan = $_SESSION['user_plan'] ?? 'Essential';

function activeClass($page, $active_page)
{
    return $page === $active_page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title); ?> | Hogwarts</title>

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

        .category-card {
            color: var(--house-text) !important;
            text-decoration: none !important;
        }

        .house-btn.active-house {
            background-color: var(--house-highlight) !important;
            color: var(--house-primary) !important;
            border-color: var(--house-highlight) !important;
        }

        .main-header,
        .nav-menu,
        .nav-menu ul {
            overflow: visible !important;
        }

        .nav-menu ul {
            align-items: center;
        }

        .profile-dropdown-item {
            position: relative;
            display: flex;
            align-items: center;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            border-radius: 999px;
            padding: 0.45rem 0.75rem;
            font: inherit;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .profile-dropdown-toggle:hover,
        .profile-dropdown-toggle.active,
        .profile-dropdown:focus-within .profile-dropdown-toggle {
            border-color: var(--house-highlight);
            color: var(--house-highlight);
            background: rgba(255, 255, 255, 0.12);
        }

        .profile-dropdown-toggle .fa-chevron-down {
            font-size: 0.72rem;
            opacity: 0.82;
        }

        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.65rem);
            right: 0;
            width: min(260px, 88vw);
            display: none;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            background: #151515;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.38);
            z-index: 9999;
        }

        .profile-dropdown:hover .profile-dropdown-menu,
        .profile-dropdown:focus-within .profile-dropdown-menu {
            display: block;
        }

        .dropdown-user-summary {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.35rem 0.35rem 0.7rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.55rem;
        }

        .dropdown-user-summary i {
            color: var(--house-highlight);
            font-size: 1.4rem;
        }

        .dropdown-user-summary strong,
        .dropdown-user-summary span {
            display: block;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dropdown-user-summary span {
            font-size: 0.76rem;
            opacity: 0.72;
        }

        .profile-dropdown-link,
        .profile-dropdown-menu .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            width: 100%;
            padding: 0.62rem 0.65rem;
            border-radius: 10px;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .profile-dropdown-link:hover,
        .profile-dropdown-menu .logout-btn:hover {
            background: var(--house-secondary) !important;
            color: var(--house-text) !important;
        }

        .dropdown-theme-box {
            padding: 0.58rem 0.65rem;
            margin: 0.25rem 0 0.35rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
        }

        .dropdown-theme-box label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.45rem;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.78);
        }

        .dropdown-theme-box select {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 999px;
            background: #101010;
            color: #ffffff;
            padding: 0.45rem 0.65rem;
            font-size: 0.82rem;
        }

        @media (max-width: 900px) {
            .nav-menu ul {
                flex-wrap: wrap;
                justify-content: flex-start;
            }

            .profile-dropdown-menu {
                right: auto;
                left: 0;
            }
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
                        style="<?= $current_house['logo_img'] === '' ? 'display:none;' : ''; ?>"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                    <div class="logo-fa-fallback" style="display: none;">
                        <i class="fas <?= h($current_house['icon']); ?>"></i>
                    </div>

                    <div>
                        <h1>HOGWARTS</h1>
                        <span class="logo-subtitle">
                            <?= $theme_enabled ? 'Tema ' . h($current_house['name']) : 'Tema clásico'; ?>
                        </span>
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
                        <a href="explorar.php" class="<?= activeClass('explorar', $active_page); ?>">
                            Explorar
                        </a>
                    </li>

                    <li>
                        <a href="mi-lista.php" class="<?= activeClass('mi-lista', $active_page); ?>">
                            Mi lista
                        </a>
                    </li>

                    <li>
                        <a href="categorias.php" class="<?= activeClass('categorias', $active_page); ?>">
                            Categorías
                        </a>
                    </li>

                    <?php if (strtolower((string) $user_role) === 'admin'): ?>
                        <li>
                            <a href="src/views/admin/dashboard.php">
                                Admin
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="profile-dropdown-item">
                        <div class="profile-dropdown">
                            <button
                                type="button"
                                class="profile-dropdown-toggle <?= activeClass('perfil', $active_page); ?>"
                                aria-haspopup="true"
                                aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                                <span><?= h($user_name); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            <div class="profile-dropdown-menu">
                                <div class="dropdown-user-summary">
                                    <i class="fas fa-user-circle"></i>

                                    <div>
                                        <strong><?= h($user_name); ?></strong>
                                        <span><?= h($user_email !== '' ? $user_email : $user_plan); ?></span>
                                    </div>
                                </div>

                                <?php if ($theme_enabled): ?>
                                    <form method="GET" action="" class="dropdown-theme-box">
                                        <label for="house-select-header">
                                            <i class="fas fa-palette"></i>
                                            Cambiar tema
                                        </label>

                                        <select id="house-select-header" name="house" onchange="this.form.submit()">
                                            <option value="ravenclaw" <?= $house === 'ravenclaw' ? 'selected' : ''; ?>>
                                                Ravenclaw
                                            </option>

                                            <option value="gryffindor" <?= $house === 'gryffindor' ? 'selected' : ''; ?>>
                                                Gryffindor
                                            </option>

                                            <option value="slytherin" <?= $house === 'slytherin' ? 'selected' : ''; ?>>
                                                Slytherin
                                            </option>

                                            <option value="hufflepuff" <?= $house === 'hufflepuff' ? 'selected' : ''; ?>>
                                                Hufflepuff
                                            </option>
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

    <main class="page-main">