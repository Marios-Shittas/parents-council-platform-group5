<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/UsersService.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

class ForgotPasswordService {
    private $usersService;
    private $db;

    private $tokenExpirationMinutes = 600; 

    public function __construct() {
        $this->usersService = new UsersService();
        global $conn;
        $this->db = $conn;
    }

    public function handleRequest() {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';

        $result = $this->usersService->forgot($email);

        if ($result['success']) {
            // Generate and store reset token in Users table
            $token = $this->generateAndStoreToken($email);
            
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to generate reset token.'];
            }

            $emailSent = $this->sendResetEmail($name, $email, $token);
            if (!$emailSent) {
                return ['success' => false, 'message' => 'Failed to send reset email.'];
            }
        }

        return $result;
    }

    /**
     * Generate a secure token and store it in the Users table
     * @param string $email User's email
     * @return string|false Token if successful, false otherwise
     */
    private function generateAndStoreToken($email) {
        try {
            // Generate a secure random token
            $token = bin2hex(random_bytes(32));
            
            // Store token in Users table with expiration time
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
        } catch (Exception $e) {
            error_log("Error generating reset token: " . $e->getMessage());
            return false;
        }
    }

    private function sendResetEmail($name, $email, $token) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->Port = SMTP_PORT;
            $mail->SMTPSecure = SMTP_ENCRYPTION;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $resetLink = rtrim(APP_BASE_URL, '/') . "/public/reset-password.php?email=" . urlencode($email) . "&token=" . urlencode($token);
            $mail->Body = "Hi $name,
                <br><br>We received a request to reset your password. Click the link below to reset your password:
                <br><br><a href='$resetLink'>Reset Password</a>
                <br><br>This link will expire in {$this->tokenExpirationMinutes} minutes.
                <br><br>If you didn't request a password reset, please ignore this email.
                <br><br>Best regards,
                <br>Parent Council Platform";

            $mail->send();
            return true;
        }
        catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            return false;
        }
    }
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = new ForgotPasswordService();
    $result = $service->handleRequest();
    echo json_encode($result);
}
?>
