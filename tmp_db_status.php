<?php
require_once __DIR__ . '/src/models/Database.php';

try {
    $pdo = Database::getConnection();
    $users = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $plans = (int) $pdo->query('SELECT COUNT(*) FROM planes')->fetchColumn();
    $books = (int) $pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn();

    echo "DB_OK=1\n";
    echo "USUARIOS={$users}\n";
    echo "PLANES={$plans}\n";
    echo "LIBROS={$books}\n";
} catch (Throwable $e) {
    echo "DB_OK=0\n";
    echo 'DB_ERR=' . $e->getMessage() . "\n";
}
