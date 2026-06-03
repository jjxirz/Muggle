<?php
/**
 * Admin Auto-Login Component
 * Automatically logs in the admin user and redirects to dashboard
 * Used for development and testing purposes
 * 
 * Access: GET or POST to /src/components/admin.php
 * Security: Only works on localhost or if TOKEN matches
 */

// Allow both GET and POST
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Load authentication and database
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/AuthModel.php';

$admin_email = trim((string) (getenv('ADMIN_EMAIL') ?: 'admin@muggle.local'));
$admin_password = 'Admin123!';

$error = '';

try {
    $authModel = new AuthModel();
    
    // Find admin user by email
    $user = $authModel->findUserByEmail($admin_email);
    
    if ($user === null) {
        $error = 'Admin user not found. Make sure admin@muggle.local exists in database.';
    } elseif (($user['estado'] ?? '') !== 'activo') {
        $error = 'Admin user is not active.';
    } elseif (strtolower((string) ($user['rol_nombre'] ?? 'usuario')) !== 'admin') {
        $error = 'User is not an admin.';
    } else {
        // Verify password hash
        $hash = (string) ($user['password'] ?? '');
        if (password_verify($admin_password, $hash)) {
            // Login successful
            login_user($user);
            
            // Redirect to dashboard
            header('Location: ' . app_url('index.php'));
            exit();
        } else {
            $error = 'Password verification failed.';
        }
    }
    
    // If we reach here, something went wrong
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $error
    ]);
    
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $exception->getMessage()
    ]);
}
