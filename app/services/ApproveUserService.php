<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ApprovalMailer.php';

// Leitourgia sendApprovalEmailMessage: xeirizetai to antistoixo kommati tis selidas i tou service.
function sendApprovalEmailMessage(string $email, string $link): void
{
    $smtpFailureMessage = '';

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

        $mailer->sendApprovalEmail($email, $link);
        return;
    } catch (Throwable $smtpException) {
        $smtpFailureMessage = $smtpException->getMessage();
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        $emailApprovalPath = __DIR__ . '/EmailApproval.php';
        if (file_exists($emailApprovalPath)) {
            require_once $emailApprovalPath;
        }

        if (
            class_exists('Kozzy\\ParentsCouncilPlatformGroup5\\services\\EmailApproval') &&
            class_exists('PHPMailer\\PHPMailer\\PHPMailer')
        ) {
            $emailService = new \Kozzy\ParentsCouncilPlatformGroup5\services\EmailApproval([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);

            $emailService->sendApprovalEmail($email, $link);
            return;
        }
    }

    $subject = ApprovalMailer::approvalEmailSubject();
    $message = ApprovalMailer::approvalEmailHtmlBody($link);
    $headers =
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . ">\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/html; charset=UTF-8";

    if (!mail($email, $subject, $message, $headers)) {
        $suffix = $smtpFailureMessage !== '' ? ' SMTP: ' . $smtpFailureMessage : '';
        throw new RuntimeException('Failed to send approval email.' . $suffix);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

// Windows cmd curl often wraps JSON in single quotes; try to recover that payload shape.
if (!is_array($payload) && is_string($rawBody)) {
    $trimmed = trim($rawBody);
    $len = strlen($trimmed);

    if ($len >= 2 && $trimmed[0] === '\'' && $trimmed[$len - 1] === '\'') {
        $trimmed = substr($trimmed, 1, -1);
        $payload = json_decode($trimmed, true);
    }
}

if (!is_array($payload) && !empty($_POST)) {
    $payload = $_POST;
}

$payload = is_array($payload) ? $payload : [];
$email = trim((string) ($payload['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid email is required. For Windows cmd, use: -d "{\"email\":\"user@example.com\"}"',
    ]);
    exit;
}

$token = bin2hex(random_bytes(32));
$expiryHours = defined('APPROVAL_LINK_EXPIRY_HOURS') ? max(1, APPROVAL_LINK_EXPIRY_HOURS) : 168;
$expiry = date('Y-m-d H:i:s', strtotime('+' . $expiryHours . ' hours'));
$link = rtrim(APP_BASE_URL, '/') . '/public/subscription.php?token=' . urlencode($token);

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "UPDATE Users
         SET account_status = 'waiting_payment', token = ?, token_expiry = ?
         WHERE email = ? AND role = 'parent'"
    );

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare user update statement');
    }

    $stmt->bind_param('sss', $token, $expiry, $email);

    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to update approved user');
    }

    if ($stmt->affected_rows < 1) {
        throw new RuntimeException('No parent user was updated. Check email and current status.');
    }

    $stmt->close();

    sendApprovalEmailMessage($email, $link);

    $logStmt = $conn->prepare(
        "INSERT INTO Logs (user_id, action, description)
         SELECT user_id, 'USER_APPROVED', CONCAT('Approval email sent to: ', email)
         FROM Users WHERE email = ? LIMIT 1"
    );

    if ($logStmt) {
        $logStmt->bind_param('s', $email);
        $logStmt->execute();
        $logStmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'User approved and email sent successfully.',
        'subscription_link' => $link,
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
