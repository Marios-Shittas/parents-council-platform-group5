<?php
// Arxeio: app\core\AuthSession.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.

class AuthSession
{
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
// Epistrefei to xristis id apo to synedria kai epistrefei 0 an den yparxei syndedemenos xristis.
    public function userId(): int
    {
        $this->start();

        return (int) ($_SESSION['user_id'] ?? 0);
    }
// Epistrefei ton rolo tou trexontos xristi apo to synedria (px admin/parent) i kenh timi.
    public function userRole(): string
    {
        $this->start();

        return (string) ($_SESSION['role'] ?? '');
    }
// Xartografei ton rolo sto antistoixo proepilegmeno landing page kai dinei safe fallback sto login.
    public function redirectUrlForCurrentRole(): string
    {
        $role = $this->userRole();

        if ($role === 'admin') {
            return '/parents-council-platform-group5/public/admin/home.php';
        }

        if ($role === 'parent') {
            return '/parents-council-platform-group5/public/parent/home.php';
        }

        return '/parents-council-platform-group5/public/login.php';
    }
// Authorization gate: epitrepei request mono an o synedria xristis exei ton apaitoumeno rolo.
// Se apotyxia, epistrefei JSON (401/403) i kanei redirect analoga me to mode.
    public function requireRole(string $requiredRole, array $options = []): void
    {
        $this->start();

        $userId = $this->userId();
        $userRole = $this->userRole();

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

        $redirectTo = (string) ($options['redirect_to'] ?? $this->redirectUrlForCurrentRole());
        header('Location: ' . $redirectTo);
        exit;
    }
}
