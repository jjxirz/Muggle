<?php
session_start();

// Verificar si ya está logueado
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// Configuración de Google OAuth (debes crear tu propio cliente ID)
// Obtén tu Client ID en: https://console.cloud.google.com/
$google_client_id = "TU_GOOGLE_CLIENT_ID.apps.googleusercontent.com"; // Reemplazar
$google_redirect_uri = "http://localhost/hogwarts-libraries/login.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión · Hogwarts Libraries</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <!-- Google Identity Services SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="theme-ravenclaw">

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">📚</div>
            <h1>HOGWARTS<br>LIBRARIES</h1>
            <p>Streaming de libros mágicos</p>
        </div>

        <div class="login-form">
            <h2>Bienvenido de vuelta</h2>
            <p class="subtitle">Inicia sesión para continuar leyendo</p>

            <!-- Opciones de inicio de sesión -->
            <div class="social-login">
                <div id="g_id_onload"
                    data-client_id="<?php echo $google_client_id; ?>"
                    data-login_uri="<?php echo $google_redirect_uri; ?>"
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
                    <input type="email" name="email" placeholder="correo@ejemplo.com" value="a@gmail.com" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Contraseña" value="123" required>
                </div>
                <button type="submit" name="manual_login" class="btn-login">Iniciar sesión</button>
            </form>

            <div class="signup-link">
                ¿No tienes cuenta? <a href="#">Regístrate gratis</a>
            </div>
        </div>

        <div class="login-footer">
            <p>Al iniciar sesión aceptas nuestros <a href="#">Términos</a> y <a href="#">Política de privacidad</a></p>
            <div class="house-badge">🦅 🦁 🐍 🦡</div>
        </div>
    </div>
</div>

<?php
// Manejar login manual (demo)
if (isset($_POST['manual_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Demo: credenciales simples
    if ($email === "a@gmail.com" && $password === "123") {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = "Usuario Demo";
        $_SESSION['user_picture'] = null;
        header('Location: index.php');
        exit();
    } else {
        echo "<script>alert('Credenciales incorrectas. Usa a@gmail.com / 123');</script>";
    }
}

// Procesar respuesta de Google (POST desde el botón)
// En producción, necesitas verificar el token con Google
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    // Aquí debes verificar el token JWT con la librería de Google
    // Por simplicidad, simulamos login exitoso con Google
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_email'] = "google_user@example.com";
    $_SESSION['user_name'] = "Usuario Google";
    $_SESSION['user_picture'] = null;
    header('Location: index.php');
    exit();
}
?>
</body>
</html>