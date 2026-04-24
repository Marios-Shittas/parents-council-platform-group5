<?php

final class AuthHelper
{
    /**
     * Starts a PHP session when needed.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Returns current authenticated user id from session.
     */
    public static function userId(): int
    {
        self::startSession();
        return (int)($_SESSION['user_id'] ?? 0);
    }

    /**
     * Returns current authenticated role from session.
     */
    public static function userRole(): string
    {
        self::startSession();
        return (string)($_SESSION['role'] ?? '');
    }

    /**
     * Provides default redirect URL based on current role.
     */
    public static function redirectUrlForCurrentRole(): string
    {
        $role = self::userRole();

        if ($role === 'admin') {
            return '/parents-council-platform-group5/public/admin/home.php';
        }

        if ($role === 'parent') {
            return '/parents-council-platform-group5/public/parent/home.php';
        }

        return '/parents-council-platform-group5/public/login.php';
    }

    /**
     * Enforces required role, returning JSON or redirect on failure.
     */
    public static function requireRole(string $requiredRole, array $options = []): void
    {
        self::startSession();

        $userId = self::userId();
        $userRole = self::userRole();

        if ($userId > 0 && $userRole === $requiredRole) {
            return;
        }

        $mode = (string)($options['mode'] ?? 'redirect');
        $message = (string)($options['message'] ?? 'Unauthorized access.');
        $statusCode = $userId > 0 ? 403 : 401;

        if ($mode === 'json') {
            http_response_code($statusCode);
            echo json_encode([
                'success' => false,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirectTo = (string)($options['redirect_to'] ?? self::redirectUrlForCurrentRole());
        header('Location: ' . $redirectTo);
        exit;
    }
}
