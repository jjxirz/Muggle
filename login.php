<?php
session_start();

// Verificar si ya está logueado
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error_message = '';

// Configuración de Google OAuth
// Reemplaza este valor con tu Client ID real de Google Cloud Console.
$google_client_id = 'TU_GOOGLE_CLIENT_ID.apps.googleusercontent.com';

// URI dinámica para que funcione aunque cambies el nombre de la carpeta local.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$google_redirect_uri = $protocol . $_SERVER['HTTP_HOST'] . $base_path . '/login.php';

// Manejar login manual (demo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Demo: credenciales simples
    if ($email === 'a@gmail.com' && $password === '123') {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = 'Usuario Demo';
        $_SESSION['user_picture'] = null;
        header('Location: index.php');
        exit();
    }

    $error_message = 'Credenciales incorrectas. Usa a@gmail.com / 123';
}

// Procesar respuesta de Google (POST desde el botón)
// En producción, necesitas verificar el token JWT con Google antes de crear la sesión.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_email'] = 'google_user@example.com';
    $_SESSION['user_name'] = 'Usuario Google';
    $_SESSION['user_picture'] = null;
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión · Hogwarts Libraries</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Identity Services SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="theme-ravenclaw">

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <h1>HOGWARTS<br>LIBRARIES</h1>
            <p>Streaming de libros mágicos</p>
        </div>

        <div class="login-form">
            <h2>Bienvenido de vuelta</h2>
            <p class="subtitle">Inicia sesión para continuar leyendo</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Opciones de inicio de sesión -->
            <div class="social-login">
                <div id="g_id_onload"
                    data-client_id="<?php echo htmlspecialchars($google_client_id); ?>"
                    data-login_uri="<?php echo htmlspecialchars($google_redirect_uri); ?>"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                    data-type="standard"
                    data-size="large"
                    data-theme="outline"
                    data-text="sign_in_with"
                    data-shape="rectangular"
                    data-logo_alignment="left">
                </div>
            </div>

            <div class="divider">
                <span>o continúa con email</span>
            </div>

            <!-- Formulario manual (demo) -->
            <form action="login.php" method="POST" class="email-form">
                <div class="input-group">
                    <label for="email"><i class="fas fa-envelope"></i> Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" value="a@gmail.com" required>
                </div>
                <div class="input-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña" value="123" required>
                </div>
                <button type="submit" name="manual_login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                </button>
            </form>

            <div class="signup-link">
                ¿No tienes cuenta? <a href="#">Regístrate gratis</a>
            </div>
        </div>

        <div class="login-footer">
            <p>Al iniciar sesión aceptas nuestros <a href="#">Términos</a> y <a href="#">Política de privacidad</a></p>
            <div class="house-badge" aria-label="Casas de Hogwarts">
                <span><i class="fas fa-feather-alt"></i> Ravenclaw</span>
                <span><i class="fas fa-shield-alt"></i> Gryffindor</span>
                <span><i class="fas fa-dragon"></i> Slytherin</span>
                <span><i class="fas fa-seedling"></i> Hufflepuff</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
