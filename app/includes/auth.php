<?php

require_once __DIR__ . '/site_context.php';

if (!function_exists('auth_start_session')) {
    function auth_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('auth_user_id')) {
    function auth_user_id(): int
    {
        auth_start_session();
        return (int) ($_SESSION['user_id'] ?? 0);
    }
}

if (!function_exists('auth_user_role')) {
    function auth_user_role(): string
    {
        auth_start_session();
        return (string) ($_SESSION['role'] ?? '');
    }
}

if (!function_exists('auth_redirect_url_for_current_role')) {
    function auth_redirect_url_for_current_role(): string
    {
        $role = auth_user_role();

        if ($role === 'admin') {
            return site_public_url('admin/home.php');
        }

        if ($role === 'parent') {
            return site_public_url('parent/home.php');
        }

        return site_login_url();
    }
}

if (!function_exists('auth_require_role')) {
    function auth_require_role(string $requiredRole, array $options = []): void
    {
        auth_start_session();

        $userId = auth_user_id();
        $userRole = auth_user_role();

        if ($userId > 0 && $userRole === $requiredRole) {
            return;
        }

        $mode = (string) ($options['mode'] ?? 'redirect');
        $message = (string) ($options['message'] ?? 'Unauthorized access.');
        $statusCode = $userId > 0 ? 403 : 401;

        if ($mode === 'json') {
            http_response_code($statusCode);
            echo json_encode([
                'success' => false,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirectTo = (string) ($options['redirect_to'] ?? auth_redirect_url_for_current_role());
        header('Location: ' . $redirectTo);
        exit;
    }
}
