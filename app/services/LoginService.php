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
    $fullName = trim((string)($result['user']['name'] ?? '') . ' ' . (string)($result['user']['surname'] ?? ''));
    if ($fullName === '') {
        $fullName = 'User';
    }

    $_SESSION['temp_user_id'] = $result['user']['user_id'];
    $_SESSION['temp_email'] = $result['user']['email'];
    $_SESSION['temp_role'] = $result['user']['role'];
    $_SESSION['temp_name'] = $fullName;
    $_SESSION['temp_token'] = $result['token'];
    $_SESSION['pending_2fa'] = true;

    unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role'], $_SESSION['2fa_verified']);

    $twoFactorService = new TwoFactorAuthService();
    $email = (string)$result['user']['email'];
    $token = (string)$result['token'];
    $sendResult = $twoFactorService->send2FACode($email, $fullName, $token);

    if (!$sendResult['success']) {
        unset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_name'], $_SESSION['temp_token'], $_SESSION['pending_2fa']);
        $result = $sendResult;
        echo json_encode($result);
        exit;
    }

    $result['requires_2fa'] = true;
    $result['redirect'] = 'two-factor-authentication.php';
}

echo json_encode($result);
?>