<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ApprovalMailer.php';

class TwoFactorAuthService
{
    private mysqli $conn;
    private int $tokenExpirationMinutes = 10;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function send2FACode(string $email, string $name, string $token): array
    {
        $email = trim($email);
        $name = trim($name);
        $token = trim($token);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        if ($token === '') {
            return ['success' => false, 'message' => 'Invalid token for 2FA code generation.'];
        }

        $code = $this->derive8CharCodeFromToken($token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenExpirationMinutes * 60));

        $stmt = $this->conn->prepare('UPDATE Users SET token = ?, token_expiry = ? WHERE email = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Unable to save 2FA token.'];
        }

        $stmt->bind_param('sss', $token, $expiresAt, $email);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Unable to save 2FA token.'];
        }

        $stmt->close();

        try {
            $mailer = new ApprovalMailer([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);

            $safeName = $name !== '' ? $name : 'User';
            $body = "Hi {$safeName},<br><br>Your two-factor authentication code is: <strong>{$code}</strong><br><br>This code expires in {$this->tokenExpirationMinutes} minutes.";
            $mailer->sendHtmlEmail($email, 'Two-Factor Authentication Code', $body);

            return ['success' => true, 'message' => '2FA code sent successfully.'];
        } catch (Throwable $e) {
            error_log('2FA email send failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send 2FA code.'];
        }
    }

    public function verify2FACode(string $email, string $code): array
    {
        $stmt = $this->conn->prepare('SELECT token, token_expiry FROM Users WHERE email = ? LIMIT 1');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Unable to verify 2FA code.'];
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$user || empty($user['token']) || empty($user['token_expiry'])) {
            return ['success' => false, 'message' => 'No 2FA code found.'];
        }

        if (strtotime((string) $user['token_expiry']) < time()) {
            $this->clear2FAData($email);
            return ['success' => false, 'message' => '2FA code has expired.'];
        }

        $expectedCode = $this->derive8CharCodeFromToken((string) $user['token']);
        if (strtolower(trim($code)) !== strtolower($expectedCode)) {
            return ['success' => false, 'message' => 'Invalid 2FA code.'];
        }

        $this->clear2FAData($email);
        return ['success' => true, 'message' => '2FA verification successful.'];
    }

    private function clear2FAData(string $email): void
    {
        $stmt = $this->conn->prepare('UPDATE Users SET token = NULL, token_expiry = NULL WHERE email = ?');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }

    private function derive8CharCodeFromToken(string $token): string
    {
        $token = trim($token);

        if ($token === '') {
            return '';
        }

        $chars = str_split($token);
        $length = count($chars);

        $picked = [];
        $pickedIndexes = [];

        $picked[] = $chars[0];
        $pickedIndexes[0] = true;

        for ($i = 4; $i < $length && count($picked) < 8; $i += 4) {
            $picked[] = $chars[$i];
            $pickedIndexes[$i] = true;
        }

        for ($i = 1; $i < $length && count($picked) < 8; $i++) {
            if (!isset($pickedIndexes[$i])) {
                $picked[] = $chars[$i];
            }
        }

        return substr(implode('', $picked), 0, 8);
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $service = new TwoFactorAuthService();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $data = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        if (!isset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_token'], $_SESSION['pending_2fa'])) {
            echo json_encode(['success' => false, 'message' => 'Login required before 2FA.']);
            exit;
        }

        $email = (string) $_SESSION['temp_email'];
        $name = trim((string) ($_SESSION['temp_name'] ?? 'User'));
        $token = (string) $_SESSION['temp_token'];

        echo json_encode($service->send2FACode($email, $name, $token));
        exit;
    }

    if ($method === 'PUT') {
        $code = trim((string) ($data['code'] ?? ''));

        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Code is required.']);
            exit;
        }

        if (!isset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['pending_2fa'])) {
            echo json_encode(['success' => false, 'message' => '2FA session not found.']);
            exit;
        }

        $email = (string) $_SESSION['temp_email'];
        $result = $service->verify2FACode($email, $code);

        if ($result['success']) {
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['email'] = $_SESSION['temp_email'];
            $_SESSION['role'] = $_SESSION['temp_role'];
            $_SESSION['2fa_verified'] = true;

            unset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_name'], $_SESSION['temp_token'], $_SESSION['pending_2fa']);
        }

        echo json_encode($result);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
