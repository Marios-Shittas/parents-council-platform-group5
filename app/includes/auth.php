<?php

require_once __DIR__ . '/../core/AuthSession.php';

if (!function_exists('auth_start_session')) {
    // Ksekinaei session meso tis AuthSession klasis.
    function auth_start_session(): void
    {
        (new AuthSession())->start();
    }
}

if (!function_exists('auth_user_id')) {
    // Girnaei to trexon user id apo to session.
    function auth_user_id(): int
    {
        return (new AuthSession())->userId();
    }
}

if (!function_exists('auth_user_role')) {
    // Girnaei ton trexon user role apo to session.
    function auth_user_role(): string
    {
        return (new AuthSession())->userRole();
    }
}

if (!function_exists('auth_redirect_url_for_current_role')) {
    // Epilegei redirect URL analoga me ton rolo tou user.
    function auth_redirect_url_for_current_role(): string
    {
        return (new AuthSession())->redirectUrlForCurrentRole();
    }
}

if (!function_exists('auth_require_role')) {
    // Prostatevei routes pou apaiteitai sygkekrimenos rolos.
    function auth_require_role(string $requiredRole, array $options = []): void
    {
        (new AuthSession())->requireRole($requiredRole, $options);
    }
}
