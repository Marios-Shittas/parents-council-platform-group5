<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/UsersService.php';

class ForgotPasswordService {
    private $usersService;

    private $smtpHost = 'smtp.gmail.com';
    private $smtpUser = 'nigkaleta@gmail.com';
    private $smtpPass = 'dyjs vehc oyiy dvmv';
    private $smtpPort = 587;
    private $smtpSecure = 'tls';
    private $smtpFromEmail = 'nigkaleta@gmail.com';

    public function __construct() {
        $this->usersService = new UsersService();
    }

    public function handleRequest() {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';

        $result = $this->usersService->forgot($email);

        if ($result['success']) {
            $emailSent = $this->sendResetEmail($name, $email);
            if (!$emailSent) {
                return ['success' => false, 'message' => 'Failed to send reset email.'];
            }
        }

        return $result;
    }

    private function sendResetEmail($name, $email) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
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
            $mail->Subject = 'Password Reset Request';
            $resetLink = "http://localhost/parents-council-platform-group5/public/reset-password.php?email=" . urlencode($email);
            $mail->Body = "Hi $name,
                <br><br>We received a request to reset your password. Click the link below to reset your password:
                <br><br><a href='$resetLink'>Reset Password</a>
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