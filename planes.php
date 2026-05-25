<?php
require_once __DIR__ . '/src/lib/Auth.php';

$loggedUser = current_user();
$targetUrl = $loggedUser ? app_url('perfil.php') : app_url('login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes | Hogwarts</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --login-primary: #121212;
            --login-secondary: #1f1f1f;
            --login-card: #161616;
            --login-highlight: #a3b7d6;
            --login-text: #ffffff;
            --login-muted: rgba(255, 255, 255, 0.76);
            --login-border: rgba(163, 183, 214, 0.25);
        }

        body.theme-ravenclaw {
            background: var(--login-primary);
            color: var(--login-text);
        }

        .main-header {
            background-color: var(--login-primary) !important;
            border-bottom: 1px solid var(--login-highlight) !important;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--login-highlight) !important;
        }

        .plans-page {
            min-height: calc(100vh - 80px);
            padding: 2rem 0 3rem;
            background:
                radial-gradient(circle at top left, rgba(163, 183, 214, 0.14), transparent 34%),
                var(--login-primary);
        }

        .plans-hero {
            border: 1px solid var(--login-border);
            border-radius: 18px;
            background: rgba(20, 20, 20, 0.94);
            padding: 1.6rem;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.34);
        }

        .plans-hero h2 {
            margin: 0;
            color: var(--login-text);
        }

        .plans-hero-subtitle {
            color: var(--login-muted);
            margin-top: 0.35rem;
        }

        .plans-grid {
            margin-top: 1.2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 0.9rem;
        }

        .plan-public-card {
            border: 1px solid var(--login-border);
            border-radius: 16px;
            background: var(--login-card);
            padding: 1rem;
            transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
        }

        .plan-public-card:hover {
            transform: translateY(-3px);
            border-color: var(--login-highlight);
            background: var(--login-secondary);
        }

        .plan-public-card h3 {
            margin: 0;
            font-size: 1.15rem;
            color: var(--login-text);
        }

        .plan-public-price {
            margin-top: 0.45rem;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--login-highlight);
        }

        .plan-public-price span {
            font-size: 0.8rem;
            color: var(--login-muted);
            font-weight: 500;
        }

        .plan-public-desc {
            margin-top: 0.5rem;
            font-size: 0.84rem;
            color: var(--login-muted);
        }

        .plan-public-list {
            list-style: none;
            margin: 0.8rem 0 0;
            padding: 0;
            display: grid;
            gap: 0.4rem;
            font-size: 0.82rem;
            color: var(--login-text);
        }

        .plan-public-list li {
            display: flex;
            gap: 0.45rem;
            align-items: center;
        }

        .plan-public-list i {
            color: var(--login-highlight);
        }

        .plans-actions {
            margin-top: 1.1rem;
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .plans-actions .btn-primary {
            background: var(--login-highlight) !important;
            color: var(--login-primary) !important;
            border-color: var(--login-highlight) !important;
        }

        .plans-actions .btn-secondary {
            border-color: var(--login-highlight) !important;
            color: var(--login-highlight) !important;
        }

        .plans-actions .btn-secondary:hover {
            background: var(--login-highlight) !important;
            color: var(--login-primary) !important;
        }

        @media (max-width: 768px) {
            .plans-page {
                padding-top: 1.2rem;
            }

            .plans-hero {
                padding: 1.1rem;
            }
        }
    </style>
</head>
<body class="theme-ravenclaw">
<header class="main-header">
    <div class="container header-content">
        <a href="index.php" class="logo">
            <div class="row">
                <div class="logo-fa-fallback" style="display:flex;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <h1>HOGWARTS</h1>
                    <span class="logo-subtitle">Planes de lectura</span>
                </div>
            </div>
        </a>

        <nav class="nav-menu">
            <ul>
                <li><a href="planes.php" class="active">Planes</a></li>
                <li><a href="login.php">Iniciar sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="plans-page">
    <section class="container">
        <div class="plans-hero">
            <h2>Elige tu plan de Hogwarts</h2>
            <p class="plans-hero-subtitle">Comienza gratis o sube de nivel para desbloquear más funciones de lectura.</p>

            <div class="plans-grid">
                <article class="plan-public-card">
                    <h3>Free</h3>
                    <div class="plan-public-price">$0 <span>/ mes</span></div>
                    <p class="plan-public-desc">Ideal para empezar y explorar la biblioteca básica.</p>
                    <ul class="plan-public-list">
                        <li><i class="fas fa-check"></i> Lectura en línea</li>
                        <li><i class="fas fa-check"></i> Favoritos</li>
                        <li><i class="fas fa-check"></i> Progreso básico</li>
                    </ul>
                </article>

                <article class="plan-public-card">
                    <h3>Básico</h3>
                    <div class="plan-public-price">$4.99 <span>/ mes</span></div>
                    <p class="plan-public-desc">Más títulos y mejor organización de tu lectura.</p>
                    <ul class="plan-public-list">
                        <li><i class="fas fa-check"></i> Catálogo ampliado</li>
                        <li><i class="fas fa-check"></i> Más guardados</li>
                        <li><i class="fas fa-check"></i> Preferencias avanzadas</li>
                    </ul>
                </article>

                <article class="plan-public-card">
                    <h3>Plus</h3>
                    <div class="plan-public-price">$8.99 <span>/ mes</span></div>
                    <p class="plan-public-desc">Lectura extendida para usuarios frecuentes.</p>
                    <ul class="plan-public-list">
                        <li><i class="fas fa-check"></i> Acceso extendido</li>
                        <li><i class="fas fa-check"></i> Mejor rendimiento</li>
                        <li><i class="fas fa-check"></i> Prioridad en novedades</li>
                    </ul>
                </article>

                <article class="plan-public-card">
                    <h3>Premium</h3>
                    <div class="plan-public-price">$13.99 <span>/ mes</span></div>
                    <p class="plan-public-desc">Toda la experiencia Hogwarts sin límites.</p>
                    <ul class="plan-public-list">
                        <li><i class="fas fa-check"></i> Acceso completo</li>
                        <li><i class="fas fa-check"></i> Prioridad total</li>
                        <li><i class="fas fa-check"></i> Experiencia premium</li>
                    </ul>
                </article>
            </div>

            <div class="plans-actions">
                <a href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <i class="fas fa-user-check"></i>
                    <?php echo $loggedUser ? 'Gestionar plan en mi perfil' : 'Iniciar sesión para elegir plan'; ?>
                </a>
                <?php if (!$loggedUser): ?>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        Ya tengo cuenta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>