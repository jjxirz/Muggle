<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado. Solo administradores.');
}

require_once __DIR__ . '/../../controllers/BookController.php';

try {
    $controller = new BookController();
    $data = $controller->handle();

    $books = $data['books'];
    $categories = $data['categories'];
    $editingBook = $data['editingBook'];
    $banners = $data['banners'];
    $editingBanner = $data['editingBanner'];
    $flash = $data['flash'];
    $existingPdfFiles = $data['existingPdfFiles'];

    require __DIR__ . '/../books-admin.php';
} catch (Throwable $exception) {
    http_response_code(500);
    $safeMessage = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error de configuracion | Admin Libros</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f8fafc; color: #111827; }
            .wrap { max-width: 840px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
            h1 { margin-top: 0; color: #b91c1c; }
            code, pre { background: #f1f5f9; border-radius: 6px; padding: 2px 6px; }
            pre { padding: 12px; overflow-x: auto; }
            .hint { background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px; border-radius: 8px; }
            a { color: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="wrap">
            <h1>No se pudo abrir el panel de admin</h1>
            <p>El sistema encontro un problema al cargar el modulo de administracion.</p>
            <p><strong>Detalle tecnico:</strong> <?php echo $safeMessage; ?></p>

            <div class="hint">
                <p><strong>Como resolverlo:</strong></p>
                <p>1. Verifica que exista la base <code>biblioteca_digital</code> y sus tablas requeridas.</p>
                <p>2. Define las variables de entorno PHP/Apache: <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>.</p>
                <p>3. Ejecuta el schema del proyecto y reinicia el servidor web.</p>
            </div>

            <pre>CREATE DATABASE IF NOT EXISTS biblioteca_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE biblioteca_digital;
SOURCE db/script.sql;</pre>

            <p>Si quieres volver al inicio: <a href="/index.php">Ir a inicio</a></p>
        </div>
    </body>
    </html>
    <?php
}
