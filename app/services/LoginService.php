<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../services/UsersService.php';
require_once __DIR__ . '/../services/TwoFactorAuthService.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$service = new UsersService();
$result = $service->login($email, $password);

if($result['success']) {
    unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role'], $_SESSION['2fa_verified']);

    $_SESSION['temp_user_id'] = $result['user']['user_id'];
    $_SESSION['temp_email'] = $result['user']['email'];
    $_SESSION['temp_role'] = $result['user']['role'];
    $_SESSION['temp_name'] = $result['user']['name'] ?? 'User';
    $_SESSION['temp_token'] = $result['token'];
    $_SESSION['pending_2fa'] = true;

    $twoFactorService = new TwoFactorAuthService();
    $sendResult = $twoFactorService->send2FACode(
        (string) $_SESSION['temp_email'],
        (string) $_SESSION['temp_name'],
        (string) $_SESSION['temp_token']
    );

    if (!$sendResult['success']) {
        unset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_name'], $_SESSION['temp_token'], $_SESSION['pending_2fa']);
        echo json_encode([
            'success' => false,
            'message' => $sendResult['message'] ?? 'Failed to send 2FA code.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'requires_2fa' => true,
        'message' => '2FA code sent successfully.'
    ]);
    exit;
}

echo json_encode($result);
?>