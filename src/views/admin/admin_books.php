<?php
require_once __DIR__ . '/../../lib/Auth.php';
require_once __DIR__ . '/../../controllers/BookController.php';

require_admin();

$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
if (in_array($action, ['fetch_metadata', 'search_title'], true)) {
    $controller = new BookController();
    $controller->handle();
    exit;
}

// Legacy route kept for compatibility: catalog management is centralized in catalogo.php.
$target = 'catalogo.php?tab=listado';
if (isset($_GET['action']) && (string) $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $target = 'catalogo.php?tab=form&id=' . (int) $_GET['id'];
}

header('Location: ' . $target);
exit();
