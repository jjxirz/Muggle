<?php
require_once __DIR__ . '/src/lib/Auth.php';

$googleAutoload = __DIR__ . '/google-api/vendor/autoload.php';
if (file_exists($googleAutoload)) {
    require_once $googleAutoload;
}

if (current_user() !== null) {
    header('Location: ' . app_url('index.php'));
    exit();
}

$error_message = '';
require_once __DIR__ . '/src/models/AuthModel.php';
$authModel = null;

try {
    $authModel = new AuthModel();
} catch (Throwable $exception) {
    $error_message = 'No se pudo conectar a la base de datos. Revisa DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS en tu servidor.';
}

$google_client_id = trim((string) (getenv('GOOGLE_CLIENT_ID') ?: '521033287514-narh8epm32v28lbnad8mop5fenk93ppm.apps.googleusercontent.com'));
$admin_email = trim((string) (getenv('ADMIN_EMAIL') ?: 'admin@muggle.local'));
$is_google_configured = ($google_client_id !== '');

function google_verify_id_token(string $idToken, string $expectedClientId): ?array
{
    $idToken = trim($idToken);
    if ($idToken === '' || $expectedClientId === '') {
        return null;
    }

    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "User-Agent: MuggleLogin/1.0\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        return null;
    }

    if ((string) ($payload['aud'] ?? '') !== $expectedClientId) {
        return null;
    }

    if ((string) ($payload['email_verified'] ?? '') !== 'true') {
        return null;
    }

    $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;
    if ($exp > 0 && $exp < time()) {
        return null;
    }

    return $payload;
}

// URI dinámica para que funcione aunque cambies el nombre de la carpeta local.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$google_redirect_uri = $protocol . $_SERVER['HTTP_HOST'] . $base_path . '/login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_login'])) {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $error_message = 'Tu sesión expiró. Recarga la página e intenta de nuevo.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($error_message === '' && $authModel instanceof AuthModel) {
        if (strtolower($email) !== strtolower($admin_email)) {
            $error_message = 'Solo la cuenta de administrador puede iniciar con contraseña. El resto usa Google.';
        }

        if ($error_message !== '') {
            // noop
        } else {
        $user = $authModel->findUserByEmail($email);

        if ($user !== null && ($user['estado'] ?? '') === 'activo') {
            $role = strtolower((string) ($user['rol_nombre'] ?? 'usuario'));
            if ($role !== 'admin') {
                $error_message = 'Este usuario debe iniciar sesión con Google.';
            }

            $hash = (string) ($user['password'] ?? '');
            if ($error_message === '' && $hash !== '' && password_verify($password, $hash)) {
                login_user($user);
                header('Location: ' . app_url('index.php'));
                exit();
            }
        }

        if ($error_message === '') {
            $error_message = 'Credenciales inválidas o usuario inactivo.';
        }
        }
    } elseif ($error_message === '') {
        $error_message = 'No se pudo validar tu usuario por un problema de conexión a base de datos.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    if (!$is_google_configured) {
        $error_message = 'Google Login aún no está configurado. Define GOOGLE_CLIENT_ID en el servidor.';
    } elseif (!($authModel instanceof AuthModel)) {
        $error_message = 'No se pudo validar tu usuario por un problema de conexión a base de datos.';
    } else {
        $payload = google_verify_id_token((string) ($_POST['credential'] ?? ''), $google_client_id);
        if ($payload === null) {
            $error_message = 'No se pudo validar la sesión de Google.';
        } else {
            $googleSub = trim((string) ($payload['sub'] ?? ''));
            $email = trim((string) ($payload['email'] ?? ''));
            $name = trim((string) ($payload['name'] ?? ''));

            $user = $authModel->resolveOrCreateGoogleUser($googleSub, $email, $name);
            if ($user === null) {
                $error_message = 'No se pudo iniciar sesión con Google.';
            } elseif (($user['estado'] ?? '') !== 'activo') {
                $error_message = 'Tu usuario está inactivo.';
            } else {
                $role = strtolower((string) ($user['rol_nombre'] ?? 'usuario'));
                if ($role === 'admin') {
                    $error_message = 'La cuenta admin usa inicio de sesión local.';
                } else {
                    login_user($user);
                    header('Location: ' . app_url('index.php'));
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión · Hogwarts</title>
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
            <p>Plataforma Hogwarts de lectura digital</p>
        </div>

        <div class="login-form">
            <h2>Bienvenido de vuelta</h2>
            <p class="subtitle">Accede con Google. La cuenta admin mantiene acceso por contraseña.</p>

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
                <span>acceso local (solo admin)</span>
            </div>

            <!-- Formulario manual (demo) -->
            <form action="login.php" method="POST" class="email-form">
                <?php echo csrf_input(); ?>
                <div class="input-group">
                    <label for="email"><i class="fas fa-envelope"></i> Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" value="" required>
                </div>
                <div class="input-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña" value="" required>
                </div>
                <button type="submit" name="manual_login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                </button>
            </form>

            <div class="signup-link">
                ¿No tienes cuenta? <a href="planes.php">Conoce los planes</a>
            </div>
        </div>

        <!-- <div class="login-footer">
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
