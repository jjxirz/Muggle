<?php

require_once __DIR__ . '/../lib/App.php';
require_once __DIR__ . '/../models/AuthModel.php';

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrf_token(): string
{
    ensure_session_started();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verify_csrf_token(string $token): bool
{
    ensure_session_started();

    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals((string) $_SESSION['csrf_token'], $token);
}

function require_valid_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($token)) {
        http_response_code(419);
        exit('Token CSRF invalido. Recarga la página e intenta de nuevo.');
    }
}

function login_user(array $user): void
{
    ensure_session_started();
    session_regenerate_id(true);

    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = (int) $user['id_usuario'];
    $_SESSION['user_name'] = (string) $user['nombre'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) ($user['rol_nombre'] ?? 'usuario');
    $_SESSION['user_plan'] = (string) ($user['plan_nombre'] ?? 'Free');

    $themeEnabled = isset($user['tema_habilitado']) ? (int) $user['tema_habilitado'] === 1 : true;
    $_SESSION['theme_enabled'] = $themeEnabled;

    $house = (string) ($user['casa_preferida'] ?? 'ravenclaw');
    $validHouses = ['ravenclaw', 'gryffindor', 'slytherin', 'hufflepuff'];
    $_SESSION['selected_house'] = in_array($house, $validHouses, true) ? $house : 'ravenclaw';
}

function logout_user(): void
{
    ensure_session_started();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function current_user(): ?array
{
    ensure_session_started();

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0 || !($_SESSION['user_logged_in'] ?? false)) {
        return null;
    }

    try {
        $auth = new AuthModel();
        $user = $auth->findUserById($id);
    } catch (Throwable $exception) {
        logout_user();
        return null;
    }

    if ($user === null || ($user['estado'] ?? '') !== 'activo') {
        logout_user();
        return null;
    }

    $_SESSION['user_name'] = (string) $user['nombre'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) ($user['rol_nombre'] ?? 'usuario');
    $_SESSION['user_plan'] = (string) ($user['plan_nombre'] ?? 'Free');
    $_SESSION['theme_enabled'] = isset($user['tema_habilitado']) ? (int) $user['tema_habilitado'] === 1 : true;
    $_SESSION['selected_house'] = (string) ($user['casa_preferida'] ?? 'ravenclaw');

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: ' . app_url('login.php'));
        exit();
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    $role = strtolower((string) ($user['rol_nombre'] ?? 'usuario'));

    if ($role !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado. Solo administradores.');
    }

    return $user;
}
