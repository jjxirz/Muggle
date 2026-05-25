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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .plans-page {
            padding: 1.5rem 0 2.8rem;
        }

        .plans-hero {
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            background: #141414;
            padding: 1.4rem;
        }

        .plans-grid {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 0.9rem;
        }

        .plan-public-card {
            border: 1px solid #2a2a2a;
            border-radius: 14px;
            background: #161616;
            padding: 1rem;
        }

        .plan-public-card h3 {
            margin: 0;
            font-size: 1.15rem;
        }

        .plan-public-price {
            margin-top: 0.45rem;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .plan-public-price span {
            font-size: 0.8rem;
            opacity: 0.76;
            font-weight: 500;
        }

        .plan-public-desc {
            margin-top: 0.5rem;
            font-size: 0.84rem;
            opacity: 0.82;
        }

        .plan-public-list {
            list-style: none;
            margin: 0.8rem 0 0;
            padding: 0;
            display: grid;
            gap: 0.4rem;
            font-size: 0.8rem;
        }

        .plan-public-list li {
            display: flex;
            gap: 0.45rem;
            align-items: center;
        }

        .plans-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<header class="main-header">
    <div class="container header-content">
        <a href="index.php" class="logo">
            <div class="row">
                <div class="logo-fa-fallback" style="display:flex;">
                    <i class="fas fa-hat-wizard"></i>
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
            <p style="opacity:0.82; margin-top:0.35rem;">Comienza gratis o sube de nivel para desbloquear más funciones de lectura.</p>

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
                    <?php echo $loggedUser ? 'Gestionar plan en mi perfil' : 'Iniciar sesión para elegir plan'; ?>
                </a>
                <?php if (!$loggedUser): ?>
                    <a href="login.php" class="btn btn-secondary">Ya tengo cuenta</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
