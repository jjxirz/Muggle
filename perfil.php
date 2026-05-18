<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Mi perfil';
$active_page = 'perfil';

require_once 'includes/header.php';

/*
    Estos datos son temporales para que la vista funcione.
    Luego se reemplazan por consultas a la base de datos.
*/

$user = [
    'name' => $_SESSION['user_name'] ?? 'Usuario Demo',
    'email' => $_SESSION['user_email'] ?? 'usuario@demo.com',
    'role' => $_SESSION['user_role'] ?? 'usuario',
    'plan' => $_SESSION['user_plan'] ?? 'Essential',
    'member_since' => $_SESSION['member_since'] ?? '2026-05-17'
];

$role = strtolower($user['role']);
$is_admin = $role === 'admin' || $role === 'administrador';

$plans = [
    'Essential' => [
        'name' => 'Essential',
        'level' => 'Nivel 1',
        'description' => 'Acceso básico a la biblioteca, favoritos y lista personal.',
        'limit' => 'Hasta 10 libros guardados',
        'progress' => 35,
        'features' => [
            'Lectura en línea',
            'Lista de favoritos',
            'Historial básico'
        ]
    ],
    'Extra' => [
        'name' => 'Extra',
        'level' => 'Nivel 2',
        'description' => 'Más control sobre tus listas, recomendaciones y colecciones.',
        'limit' => 'Hasta 50 libros guardados',
        'progress' => 65,
        'features' => [
            'Recomendaciones personalizadas',
            'Colecciones privadas',
            'Mayor límite de guardados'
        ]
    ],
    'Deluxe' => [
        'name' => 'Deluxe',
        'level' => 'Nivel 3',
        'description' => 'Acceso completo a funciones avanzadas de la biblioteca.',
        'limit' => 'Guardados ilimitados',
        'progress' => 100,
        'features' => [
            'Acceso completo',
            'Prioridad en novedades',
            'Funciones premium'
        ]
    ]
];

$current_plan = $plans[$user['plan']] ?? $plans['Essential'];

$favorite_books = [
    [
        'title' => 'Crimen y Castigo',
        'author' => 'Fiódor Dostoyevski',
        'category' => 'Clásico',
        'icon' => 'fa-book'
    ],
    [
        'title' => 'El arte de la guerra',
        'author' => 'Sun Tzu',
        'category' => 'Estrategia',
        'icon' => 'fa-chess-knight'
    ],
    [
        'title' => 'Don Quijote de la Mancha',
        'author' => 'Miguel de Cervantes',
        'category' => 'Literatura',
        'icon' => 'fa-feather-alt'
    ]
];

$saved_books = [
    [
        'title' => 'La República',
        'author' => 'Platón',
        'status' => 'Guardado para leer',
        'icon' => 'fa-bookmark'
    ],
    [
        'title' => 'Hábitos Atómicos',
        'author' => 'James Clear',
        'status' => 'En progreso',
        'icon' => 'fa-clock'
    ],
    [
        'title' => '1984',
        'author' => 'George Orwell',
        'status' => 'Pendiente',
        'icon' => 'fa-eye'
    ]
];

$admin_stats = [
    [
        'label' => 'Usuarios registrados',
        'value' => '128',
        'icon' => 'fa-users'
    ],
    [
        'label' => 'Libros disponibles',
        'value' => '342',
        'icon' => 'fa-book-open'
    ],
    [
        'label' => 'Planes activos',
        'value' => '3',
        'icon' => 'fa-crown'
    ],
    [
        'label' => 'Lecturas del mes',
        'value' => '876',
        'icon' => 'fa-chart-line'
    ]
];
?>

<section class="profile-page">
    <div class="container">

        <div class="profile-hero">
            <div class="profile-user-block">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>

                <div class="profile-user-info">
                    <span class="profile-small-label">
                        <?= $is_admin ? 'Panel de administrador' : 'Perfil de usuario'; ?>
                    </span>

                    <h2><?= h($user['name']); ?></h2>

                    <p>
                        <i class="fas fa-envelope"></i>
                        <?= h($user['email']); ?>
                    </p>

                    <div class="profile-badges">
                        <span class="profile-role-badge">
                            <i class="fas <?= $is_admin ? 'fa-user-shield' : 'fa-user'; ?>"></i>
                            <?= $is_admin ? 'Administrador' : 'Usuario'; ?>
                        </span>

                        <span class="profile-plan-badge">
                            <i class="fas fa-crown"></i>
                            Plan <?= h($current_plan['name']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <a href="#" class="profile-action">
                    <i class="fas fa-pen"></i>
                    Editar perfil
                </a>

                <a href="#" class="profile-action">
                    <i class="fas fa-cog"></i>
                    Configuración
                </a>
            </div>
        </div>

        <div class="profile-grid">

            <aside class="profile-sidebar">
                <div class="profile-side-card">
                    <h3>
                        <i class="fas fa-crown"></i>
                        Plan contratado
                    </h3>

                    <div class="plan-box">
                        <span><?= h($current_plan['level']); ?></span>
                        <h4><?= h($current_plan['name']); ?></h4>
                        <p><?= h($current_plan['description']); ?></p>
                    </div>

                    <div class="profile-progress">
                        <div class="profile-progress-top">
                            <span>Uso del plan</span>
                            <strong><?= h($current_plan['progress']); ?>%</strong>
                        </div>

                        <div class="profile-progress-bar">
                            <div class="profile-progress-fill" style="width: <?= h($current_plan['progress']); ?>%;"></div>
                        </div>

                        <small><?= h($current_plan['limit']); ?></small>
                    </div>

                    <ul class="plan-features">
                        <?php foreach ($current_plan['features'] as $feature): ?>
                            <li>
                                <i class="fas fa-check"></i>
                                <?= h($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="profile-side-card">
                    <h3>
                        <i class="fas fa-id-card"></i>
                        Información
                    </h3>

                    <div class="profile-info-list">
                        <div>
                            <span>Rol</span>
                            <strong><?= $is_admin ? 'Administrador' : 'Usuario normal'; ?></strong>
                        </div>

                        <div>
                            <span>Casa actual</span>
                            <strong><?= h($current_house['name']); ?></strong>
                        </div>

                        <div>
                            <span>Miembro desde</span>
                            <strong><?= h($user['member_since']); ?></strong>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="profile-content">

                <?php if ($is_admin): ?>
                    <section class="profile-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-chart-line"></i>
                                Resumen administrativo
                            </h2>
                        </div>

                        <div class="admin-stats-grid">
                            <?php foreach ($admin_stats as $stat): ?>
                                <div class="profile-stat-card">
                                    <div class="profile-stat-icon">
                                        <i class="fas <?= h($stat['icon']); ?>"></i>
                                    </div>

                                    <div>
                                        <strong><?= h($stat['value']); ?></strong>
                                        <span><?= h($stat['label']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="profile-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-heart"></i>
                            Libros favoritos
                        </h2>

                        <a href="#" class="view-all">
                            Ver todos <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <div class="profile-book-grid">
                        <?php foreach ($favorite_books as $book): ?>
                            <article class="profile-card">
                                <div class="profile-book-icon">
                                    <i class="fas <?= h($book['icon']); ?>"></i>
                                </div>

                                <div class="profile-book-info">
                                    <span><?= h($book['category']); ?></span>
                                    <h3><?= h($book['title']); ?></h3>
                                    <p><?= h($book['author']); ?></p>
                                </div>

                                <a href="#" class="profile-link">
                                    <i class="fas fa-book-reader"></i>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="profile-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-bookmark"></i>
                            Guardados / Mi lista
                        </h2>

                        <a href="#" class="view-all">
                            Ver lista <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <div class="profile-list">
                        <?php foreach ($saved_books as $book): ?>
                            <article class="profile-list-item">
                                <div class="profile-list-icon">
                                    <i class="fas <?= h($book['icon']); ?>"></i>
                                </div>

                                <div>
                                    <h3><?= h($book['title']); ?></h3>
                                    <p><?= h($book['author']); ?></p>
                                </div>

                                <span><?= h($book['status']); ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>