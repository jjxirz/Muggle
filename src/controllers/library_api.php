<?php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/LibraryInteractionModel.php';

header('Content-Type: application/json; charset=utf-8');

$user = require_login();
$model = new LibraryInteractionModel();

$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($token)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'message' => 'Token CSRF invalido.']);
        exit();
    }
}

function getBookPayloadFromRequest(): array
{
    $file = trim((string) ($_POST['file'] ?? $_GET['file'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? $_GET['title'] ?? ''));
    $author = trim((string) ($_POST['author'] ?? $_GET['author'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? $_GET['description'] ?? ''));
    $type = trim((string) ($_POST['type'] ?? $_GET['type'] ?? 'pdf'));

    return [
        'file' => $file,
        'title' => $title,
        'author' => $author,
        'description' => $description,
        'type' => $type,
    ];
}

try {
    switch ($action) {
        case 'toggle_favorite':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'message' => 'Metodo no permitido']);
                exit();
            }

            $book = getBookPayloadFromRequest();
            $result = $model->toggleFavorite((int) $user['id_usuario'], $book);

            echo json_encode(['ok' => true, 'data' => $result]);
            exit();

        case 'favorite_status':
            $book = getBookPayloadFromRequest();
            $isFavorite = $model->isFavorite((int) $user['id_usuario'], $book);

            echo json_encode(['ok' => true, 'data' => ['is_favorite' => $isFavorite]]);
            exit();

        case 'save_progress':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'message' => 'Metodo no permitido']);
                exit();
            }

            $book = getBookPayloadFromRequest();
            $page = (int) ($_POST['page'] ?? 0);
            $totalPages = (int) ($_POST['total_pages'] ?? 1);
            $saved = $model->saveProgress((int) $user['id_usuario'], $book, $page, $totalPages);

            echo json_encode(['ok' => $saved]);
            exit();

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Accion no valida']);
            exit();
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al procesar la solicitud',
        'detail' => $exception->getMessage(),
    ]);
}
