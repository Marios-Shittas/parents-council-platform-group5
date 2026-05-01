<?php
// Arxeio: app\core\AuthSession.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.

class AuthSession
{
    private function detectBaseUrl(): string
    {
        $configuredBaseUrl = trim((string) getenv('APP_BASE_URL'));
        if ($configuredBaseUrl !== '') {
            return rtrim($configuredBaseUrl, '/');
        }

        $scheme = $this->detectRequestValue('HTTP_X_FORWARDED_PROTO');
        if ($scheme === '') {
            $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
            $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        }

        $host = $this->detectRequestValue('HTTP_X_FORWARDED_HOST', 'HTTP_HOST');
        if ($host === '') {
            $host = trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = '';

        if (preg_match('#^(.*?)/public(?:/|$)#', $scriptName, $matches)) {
            $basePath = $matches[1] !== '' ? $matches[1] : '';
        } elseif (preg_match('#^(.*?)/app(?:/|$)#', $scriptName, $matches)) {
            $basePath = $matches[1] !== '' ? $matches[1] : '';
        }

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }

    private function detectRequestValue(string $primaryKey, string $fallbackKey = ''): string
    {
        $value = trim((string) ($_SERVER[$primaryKey] ?? ''));
        if ($value !== '') {
            return explode(',', $value)[0];
        }

        if ($fallbackKey !== '') {
            $fallbackValue = trim((string) ($_SERVER[$fallbackKey] ?? ''));
            if ($fallbackValue !== '') {
                return explode(',', $fallbackValue)[0];
            }
        }

        return '';
    }

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
        $baseUrl = $this->detectBaseUrl();

        $role = $this->userRole();

        if ($role === 'admin') {
            return $baseUrl . '/public/admin/home.php';
        }

        if ($role === 'parent') {
            return $baseUrl . '/public/parent/home.php';
        }

        return $baseUrl . '/public/login.php';
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
