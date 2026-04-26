<?php
// Arxeio: app\includes\auth.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.

require_once __DIR__ . '/../core/AuthSession.php';

if (!function_exists('auth_start_session')) {
// Thin compatibility wrapper pou ksekina to synedria meso tou AuthSession OO layer.
    function auth_start_session(): void
    {
        (new AuthSession())->start();
    }
}

if (!function_exists('auth_user_id')) {
// Epistrefei to authenticated xristis id apo to synedria meso tou kentrikou AuthSession API.
    function auth_user_id(): int
    {
        return (new AuthSession())->userId();
    }
}

if (!function_exists('auth_user_role')) {
// Epistrefei to role string tou synedria oste ta role reads na einai consistency se legacy code.
    function auth_user_role(): string
    {
        return (new AuthSession())->userRole();
    }
}

if (!function_exists('auth_redirect_url_for_current_role')) {
// Kanei delegate ton ypologismo role-based redirect stin kentriki logiki tou AuthSession.
    function auth_redirect_url_for_current_role(): string
    {
        return (new AuthSession())->redirectUrlForCurrentRole();
    }
}

if (!function_exists('auth_require_role')) {
// Legacy-friendly authorization guard pou epivalei required role me redirect i JSON behavior.
    function auth_require_role(string $requiredRole, array $options = []): void
    {
        (new AuthSession())->requireRole($requiredRole, $options);
    }
}
