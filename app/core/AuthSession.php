<?php

class AuthSession
{
    // Ksekinaei session mono an den exei idi anoiksei.
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Girnaei to logged-in user id i 0 an den iparxei session.
    public function userId(): int
    {
        $this->start();

        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // Girnaei ton rolo tou trexontos xristi.
    public function userRole(): string
    {
        $this->start();

        return (string) ($_SESSION['role'] ?? '');
    }

    // Epilegei pou prepei na paei o xristis analoga me ton rolo tou.
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

    // Prostatevei routes pou apaiteitai sygkekrimenos rolos.
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
