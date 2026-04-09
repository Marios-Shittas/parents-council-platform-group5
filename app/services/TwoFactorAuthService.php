<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

class TwoFactorAuthService {
    private mysqli $conn;

    private $smtpHost = 'smtp.gmail.com';
    private $smtpUser = 'nigkaleta@gmail.com';
    private $smtpPass = 'dyjs vehc oyiy dvmv';
    private $smtpPort = 587;
    private $smtpSecure = 'tls';
    private $smtpFromEmail = 'nigkaleta@gmail.com';
    private $tokenExpirationMinutes = 10; // 10 minutes for 2FA codes

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getToken($email){
        $stmt = $this->conn->prepare("SELECT token FROM Users WHERE email = ?");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user ? $user['token'] : null;
    }

    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name, surname FROM Users WHERE email = ?");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user ?: null;
    }

    private function ensureUserToken($email) {
        $token = $this->getToken($email);
        if ($token) {
            return $token;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            error_log('Failed to generate 2FA token: ' . $e->getMessage());
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE Users SET token = ? WHERE email = ?");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();
        $stmt->close();

        return $token;
    }

    private function deriveCodeFromToken($token) {
        $digits = '';

        for ($i = 0; $i < strlen($token) && strlen($digits) < 6; $i++) {
            $char = $token[$i];
            if (ctype_digit($char)) {
                $digits .= $char;
            } else {
                $digits .= ord($char) % 10;
            }
        }

        if (strlen($digits) < 6) {
            $hash = hash('sha256', $token);
            for ($i = 0; $i < strlen($hash) && strlen($digits) < 6; $i++) {
                $digits .= hexdec($hash[$i]) % 10;
            }
        }

        return str_pad(substr($digits, 0, 6), 6, '0', STR_PAD_LEFT);
    }

    public function generate2FACode($email){
        $token = $this->ensureUserToken($email);
        if (!$token) {
            return null;
        }

        return $this->deriveCodeFromToken($token);
    }

    public function send2FACode($email, $name) {
        $token = $this->ensureUserToken($email);
        if (!$token) {
            return ['success' => false, 'message' => 'Unable to generate 2FA token'];
        }

        $code = $this->deriveCodeFromToken($token);
        if (!$code) {
            return ['success' => false, 'message' => 'Unable to generate 2FA code'];
        }

        $expiry = date('Y-m-d H:i:s', strtotime("+{$this->tokenExpirationMinutes} minutes"));
        $stmt = $this->conn->prepare("UPDATE Users SET token_expiry = ? WHERE email = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Unable to save 2FA code expiration'];
        }

        $stmt->bind_param("ss", $expiry, $email);
        $stmt->execute();
        $stmt->close();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->Port = $this->smtpPort;
            $mail->SMTPSecure = $this->smtpSecure;

            $mail->setFrom($this->smtpFromEmail, 'Parent Council Platform');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Two Factor Authentication Code';
            $mail->Body = "Hi $name,
                    <br><br>Your two-factor authentication code is: <b>$code</b>
                    <br><br>This code will expire in {$this->tokenExpirationMinutes} minutes.
                    <br><br>Best regards,<br>Parent Council Platform";

            $mail->send();
            return ['success' => true, 'message' => '2FA code sent successfully'];
        }
        catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send 2FA code'];
        }
    }

    public function verify2FACode($email, $code) {
        $stmt = $this->conn->prepare("SELECT token, token_expiry FROM Users WHERE email = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Unable to verify 2FA code'];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !$user['token'] || !$user['token_expiry']) {
            return ['success' => false, 'message' => 'No 2FA code found'];
        }

        if (strtotime($user['token_expiry']) < time()) {
            $stmt = $this->conn->prepare("UPDATE Users SET token_expiry = NULL WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();
            }
            return ['success' => false, 'message' => '2FA code has expired'];
        }

        $expectedCode = $this->deriveCodeFromToken($user['token']);
        if ($expectedCode !== $code) {
            return ['success' => false, 'message' => 'Invalid 2FA code'];
        }

        $stmt = $this->conn->prepare("UPDATE Users SET token_expiry = NULL WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
        }

        return ['success' => true, 'message' => '2FA verification successful'];
    }
}

// API handling
$service = new TwoFactorAuthService();
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email']) || !isset($_SESSION['pending_2fa'])) {
        echo json_encode(['success' => false, 'message' => 'Login required before 2FA']);
        exit;
    }

    $email = $_SESSION['temp_email'];

    $user = $service->getUserByEmail($email);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $name = $user['name'] . ' ' . $user['surname'];
    $result = $service->send2FACode($email, $name);
    echo json_encode($result);
} elseif ($method === 'PUT') {
    $code = $data['code'] ?? '';

    if (!$code) {
        echo json_encode(['success' => false, 'message' => 'Code is required']);
        exit;
    }

    if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email']) || !isset($_SESSION['pending_2fa'])) {
        echo json_encode(['success' => false, 'message' => '2FA session not found']);
        exit;
    }

    $email = $_SESSION['temp_email'];
    $result = $service->verify2FACode($email, $code);

    if ($result['success']) {
        // Promote temp session to full session
        $_SESSION['user_id'] = $_SESSION['temp_user_id'];
        $_SESSION['email'] = $_SESSION['temp_email'];
        $_SESSION['role'] = $_SESSION['temp_role'];
        $_SESSION['2fa_verified'] = true;

        // Clear temp session
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_email']);
        unset($_SESSION['temp_role']);
        unset($_SESSION['pending_2fa']);
    }

    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>