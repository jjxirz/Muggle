<?php
require_once __DIR__ . '/src/lib/Auth.php';
require_once __DIR__ . '/src/models/AuthModel.php';
require_once __DIR__ . '/src/models/LibraryInteractionModel.php';
require_once __DIR__ . '/src/models/DashboardModel.php';

$authUser = require_login();
$authModel = new AuthModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_preferences') {
    require_valid_csrf();

    $themeEnabled = isset($_POST['theme_enabled']) && $_POST['theme_enabled'] === '1';
    $house = trim((string) ($_POST['house_preference'] ?? 'ravenclaw'));

    $authModel->updatePreferences((int) $authUser['id_usuario'], $themeEnabled, $house);
    $_SESSION['theme_enabled'] = $themeEnabled;
    $_SESSION['selected_house'] = $house;

    header('Location: perfil.php?saved=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'manage_plan') {
    require_valid_csrf();

    $submittedPlanId = (int) ($_POST['plan_id'] ?? 0);
    $planMode = (string) ($_POST['plan_mode'] ?? 'change');
    $fallbackPlanId = (int) ($_POST['current_plan_id'] ?? 0);

    if ($submittedPlanId <= 0 && $planMode === 'renew') {
        $submittedPlanId = $fallbackPlanId;
    }

    if ($submittedPlanId > 0 && $authModel->assignPlanToUser((int) $authUser['id_usuario'], $submittedPlanId)) {
        header('Location: perfil.php?plan_saved=1');
        exit();
    }

    header('Location: perfil.php?plan_saved=0');
    exit();
}

$freshUser = $authModel->findUserById((int) $authUser['id_usuario']) ?: $authUser;
$availablePlans = $authModel->getAvailablePlans();

if (empty($availablePlans)) {
    $availablePlans = [
        ['id_plan' => 1, 'nombre' => 'Free', 'precio' => 0],
        ['id_plan' => 2, 'nombre' => 'Básico', 'precio' => 4.99],
        ['id_plan' => 3, 'nombre' => 'Plus', 'precio' => 8.99],
        ['id_plan' => 4, 'nombre' => 'Premium', 'precio' => 13.99],
    ];
}

$page_title = 'Mi perfil';
$active_page = 'perfil';

require_once 'includes/header.php';

$user = [
    'id' => (int) ($freshUser['id_usuario'] ?? 0),
    'name' => (string) ($freshUser['nombre'] ?? ($_SESSION['user_name'] ?? 'Usuario')),
    'email' => (string) ($freshUser['email'] ?? ($_SESSION['user_email'] ?? '')),
    'role' => (string) ($freshUser['rol_nombre'] ?? ($_SESSION['user_role'] ?? 'usuario')),
    'plan' => (string) ($freshUser['plan_nombre'] ?? ($_SESSION['user_plan'] ?? 'Free')),
    'plan_id' => (int) ($freshUser['plan_id'] ?? 0),
    'member_since' => (string) ($freshUser['fecha_registro'] ?? date('Y-m-d')),
    'theme_enabled' => isset($freshUser['tema_habilitado']) ? (int) $freshUser['tema_habilitado'] === 1 : (bool) ($_SESSION['theme_enabled'] ?? true),
    'house' => (string) ($freshUser['casa_preferida'] ?? ($_SESSION['selected_house'] ?? 'ravenclaw')),
];

$role = strtolower($user['role']);
$is_admin = $role === 'admin' || $role === 'administrador';

$plans = [
    'Free' => [
        'name' => 'Free',
        'level' => 'Nivel 1',
        'description' => 'Acceso gratuito al catálogo base.',
        'limit' => 'Acceso básico',
        'progress' => 25,
        'features' => [
            'Lectura en línea',
            'Favoritos',
            'Historial de progreso'
        ]
    ],
    'Básico' => [
        'name' => 'Básico',
        'level' => 'Nivel 2',
        'description' => 'Más colecciones y mejor experiencia de lectura.',
        'limit' => 'Catálogo ampliado',
        'progress' => 65,
        'features' => [
            'Más categorías',
            'Mayor límite de guardados',
            'Preferencias avanzadas'
        ]
    ],
    'Plus' => [
        'name' => 'Plus',
        'level' => 'Nivel 2+',
        'description' => 'Más catálogo y mejores opciones de lectura.',
        'limit' => 'Catálogo extendido',
        'progress' => 80,
        'features' => [
            'Acceso extendido',
            'Más guardados',
            'Experiencia mejorada'
        ]
    ],
    'Premium' => [
        'name' => 'Premium',
        'level' => 'Nivel 3',
        'description' => 'Acceso completo a todas las funciones de Hogwarts.',
        'limit' => 'Sin límites',
        'progress' => 100,
        'features' => [
            'Acceso completo',
            'Prioridad en novedades',
            'Funciones premium'
        ]
    ]
];

$current_plan = $plans[$user['plan']] ?? $plans['Free'];

$libraryModel = new LibraryInteractionModel();
$favorite_books = $libraryModel->getFavoritesByUser((int) $user['id']);
$saved_books = $libraryModel->getRecentProgressByUser((int) $user['id']);

if (empty($favorite_books)) {
    $favorite_books = [];
}

if (empty($saved_books)) {
    $saved_books = [];
}

$admin_stats = [
    ['label' => 'Usuarios activos', 'value' => '0', 'icon' => 'fa-users'],
    ['label' => 'Libros disponibles', 'value' => '0', 'icon' => 'fa-book-open'],
    ['label' => 'Plan premium', 'value' => '0', 'icon' => 'fa-crown'],
    ['label' => 'Lecturas hoy', 'value' => '0', 'icon' => 'fa-chart-line'],
];

if ($is_admin) {
    $dashboardModel = new DashboardModel();
    $admin_stats = [
        ['label' => 'Usuarios activos', 'value' => (string) $dashboardModel->totalUsuariosActivos(), 'icon' => 'fa-users'],
        ['label' => 'Libros disponibles', 'value' => (string) $dashboardModel->totalLibros(), 'icon' => 'fa-book-open'],
        ['label' => 'Plan premium', 'value' => (string) $dashboardModel->totalPremium(), 'icon' => 'fa-crown'],
        ['label' => 'Lecturas hoy', 'value' => (string) $dashboardModel->lecturasHoy(), 'icon' => 'fa-chart-line'],
    ];
}

$themeMessage = isset($_GET['saved']) ? 'Preferencias guardadas correctamente.' : '';
$planMessage = isset($_GET['plan_saved'])
    ? (($_GET['plan_saved'] === '1') ? 'Plan actualizado correctamente.' : 'No se pudo actualizar el plan.')
    : '';
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
                <form method="POST" action="perfil.php" class="profile-preferences-form">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="save_preferences">

                    <label class="profile-action profile-action-inline profile-toggle-row">
                        <i class="fas fa-palette"></i>
                        <span>Tema por casa</span>
                        <input type="checkbox" name="theme_enabled" value="1" <?= $user['theme_enabled'] ? 'checked' : ''; ?>>
                    </label>

                    <select name="house_preference" class="profile-action profile-action-select">
                        <option value="ravenclaw" <?= $user['house'] === 'ravenclaw' ? 'selected' : ''; ?>>Ravenclaw</option>
                        <option value="gryffindor" <?= $user['house'] === 'gryffindor' ? 'selected' : ''; ?>>Gryffindor</option>
                        <option value="slytherin" <?= $user['house'] === 'slytherin' ? 'selected' : ''; ?>>Slytherin</option>
                        <option value="hufflepuff" <?= $user['house'] === 'hufflepuff' ? 'selected' : ''; ?>>Hufflepuff</option>
                    </select>

                    <button type="submit" class="profile-action profile-action-submit">
                        <i class="fas fa-save"></i>
                        Guardar preferencias
                    </button>
                </form>
            </div>
        </div>

        <?php if ($themeMessage !== ''): ?>
            <p class="profile-feedback profile-feedback--ok"><?php echo h($themeMessage); ?></p>
        <?php endif; ?>

        <?php if ($planMessage !== ''): ?>
            <p class="profile-feedback <?php echo isset($_GET['plan_saved']) && $_GET['plan_saved'] === '1' ? 'profile-feedback--ok' : 'profile-feedback--error'; ?>"><?php echo h($planMessage); ?></p>
        <?php endif; ?>

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

                    <form method="POST" action="perfil.php" class="profile-plan-form">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="action" value="manage_plan">
                        <input type="hidden" name="current_plan_id" value="<?= (int) $user['plan_id']; ?>">

                        <label class="profile-plan-label" for="plan_id">Renovar o cambiar plan</label>
                        <select id="plan_id" name="plan_id" class="profile-plan-select">
                            <?php foreach ($availablePlans as $planOption): ?>
                                <option value="<?= (int) $planOption['id_plan']; ?>" <?= (int) $planOption['id_plan'] === (int) $user['plan_id'] ? 'selected' : ''; ?>>
                                    <?= h($planOption['nombre']); ?> · $<?= number_format((float) ($planOption['precio'] ?? 0), 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="profile-plan-actions">
                            <button type="submit" name="plan_mode" value="renew" class="profile-action profile-plan-btn">
                                <i class="fas fa-sync-alt"></i>
                                Renovar actual
                            </button>
                            <button type="submit" name="plan_mode" value="change" class="profile-action profile-plan-btn">
                                <i class="fas fa-random"></i>
                                Cambiar plan
                            </button>
                        </div>
                    </form>
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
                            <strong><?= h(date('Y-m-d', strtotime($user['member_since']))); ?></strong>
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

                        <a href="mi-lista.php#favoritos" class="view-all">
                            Ver todos <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <div class="profile-book-grid">
                        <?php if (empty($favorite_books)): ?>
                            <p>No tienes libros favoritos todavía.</p>
                        <?php else: ?>
                            <?php foreach ($favorite_books as $book): ?>
                                <article class="profile-card">
                                    <div class="profile-book-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>

                                    <div class="profile-book-info">
                                        <span><?= h($book['categoria'] ?? 'General'); ?></span>
                                        <h3><?= h($book['titulo']); ?></h3>
                                        <p><?= h($book['autor']); ?></p>
                                    </div>

                                    <span class="profile-link">
                                        <i class="fas fa-book-reader"></i>
                                    </span>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="profile-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-bookmark"></i>
                            Guardados / Mi lista
                        </h2>

                        <a href="mi-lista.php#progreso" class="view-all">
                            Ver lista <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <div class="profile-list">
                        <?php if (empty($saved_books)): ?>
                            <p>No hay progreso de lectura guardado todavía.</p>
                        <?php else: ?>
                            <?php foreach ($saved_books as $book): ?>
                                <article class="profile-list-item">
                                    <div class="profile-list-icon">
                                        <i class="fas fa-bookmark"></i>
                                    </div>

                                    <div>
                                        <h3><?= h($book['titulo']); ?></h3>
                                        <p><?= h($book['autor']); ?></p>
                                    </div>

                                    <span><?= h((string) $book['porcentaje']); ?>%</span>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>