<?php
require_once __DIR__ . '/src/models/Database.php';

$pdo = Database::getConnection();

$cols = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'usuarios'
       AND column_name IN ('auth_provider','google_sub','prueba_7d_usada')"
)->fetchColumn();

$trial = (int) $pdo->query("SELECT COUNT(*) FROM planes WHERE nombre = 'Prueba 7 dias'")->fetchColumn();

$adminAuth = $pdo->query("SELECT auth_provider, prueba_7d_usada FROM usuarios WHERE id_usuario = 1")->fetch();

echo 'AUTH_COLS=' . $cols . PHP_EOL;
echo 'TRIAL_PLAN=' . $trial . PHP_EOL;
echo 'ADMIN_AUTH=' . (string) ($adminAuth['auth_provider'] ?? 'n/a') . PHP_EOL;
echo 'ADMIN_TRIAL_USED=' . (string) ($adminAuth['prueba_7d_usada'] ?? 'n/a') . PHP_EOL;
