<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/UsersService.php';
require_once __DIR__ . '/ApprovalMailer.php';
require_once __DIR__ . '/../config/db.php';

$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class ForgotPasswordService {
    private $usersService;
    private $db;
    private $tokenExpirationMinutes = 600;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct() {
        $this->usersService = new UsersService();
        global $conn;
        $this->db = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function handleRequest() {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';

        $result = $this->usersService->forgot($email);

        if ($result['success']) {
            $user = $this->usersService->getUserByEmail($email);
            if ($user) {
                $name = trim((string) ($user['name'] ?? '') . ' ' . (string) ($user['surname'] ?? ''));
            }

            $token = $this->generateAndStoreToken($email);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to generate reset token.'];
            }

            $emailSent = $this->sendResetEmail($name, $email, $token);
            if (!$emailSent) {
                $this->clearResetToken($email);
                return ['success' => false, 'message' => 'Failed to send reset email.'];
            }
        }

        return $result;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function generateAndStoreToken($email) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenExpirationMinutes * 60));
            $stmt = $this->db->prepare("
                UPDATE Users
                SET token = ?, token_expiry = ?
                WHERE email = ?
            ");
            $stmt->bind_param("sss", $token, $expiresAt, $email);

            if ($stmt->execute()) {
                return $token;
            }

            return false;
        } catch (\Throwable $e) {
            error_log("Error generating reset token: " . $e->getMessage());
            return false;
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function clearResetToken($email): void
    {
        $stmt = $this->db->prepare("UPDATE Users SET token = NULL, token_expiry = NULL WHERE email = ?");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function sendResetEmail($name, $email, $token) {
        try {
            $subject = $this->resetEmailSubject();
            $resetLink = $this->buildResetLink($email, $token);
            $body = $this->buildResetEmailBody($name, $resetLink);

            $mailer = new ApprovalMailer([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);
            $mailer->sendHtmlEmail($email, $subject, $body);
            return true;
        } catch (\Throwable $smtpException) {
            error_log("Error sending reset email via SMTP: " . $smtpException->getMessage());
        }

        if (class_exists(PHPMailer::class)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->Port = SMTP_PORT;

                if (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION !== '') {
                    $mail->SMTPSecure = SMTP_ENCRYPTION;
                }

                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = $this->resetEmailSubject();
                $mail->Body = $this->buildResetEmailBody($name, $this->buildResetLink($email, $token));
                $mail->send();

                return true;
            } catch (\Throwable $phpMailerException) {
                error_log("Error sending reset email via PHPMailer: " . $phpMailerException->getMessage());
            }
        }

        $subject = $this->resetEmailSubject();
        $message = $this->buildResetEmailBody($name, $this->buildResetLink($email, $token));
        $headers =
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . ">\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=UTF-8";

        if (mail($email, $subject, $message, $headers)) {
            return true;
        }

        error_log('Error sending reset email via mail() fallback.');
        return false;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildResetLink(string $email, string $token): string
    {
        return rtrim(APP_BASE_URL, '/') . '/public/reset-password.php?email=' . urlencode($email) . '&token=' . urlencode($token);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resetEmailSubject(): string
    {
        return 'Ξεχασα τον κωδικο';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildResetEmailBody(string $name, string $resetLink): string
    {
        $safeName = htmlspecialchars(trim($name) !== '' ? trim($name) : 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return "Hi {$safeName},
            <br><br>We received a request to reset your password. Click the link below to reset your password:
            <br><br><a href='{$safeLink}'>Reset Password</a>
            <br><br>This link will expire in {$this->tokenExpirationMinutes} minutes.
            <br><br>If you didn't request a password reset, please ignore this email.
            <br><br>Best regards,
            <br>Parent Council Platform";
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $service = new ForgotPasswordService();
    $result = $service->handleRequest();
    echo json_encode($result);
}
?>
