<?php
require_once __DIR__ . '/src/lib/Auth.php';

logout_user();
header('Location: ' . app_url('login.php'));
exit();
?>