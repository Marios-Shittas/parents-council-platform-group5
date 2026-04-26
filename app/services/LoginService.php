<?php
// Arxeio: app\services\LoginService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../services/UsersService.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$service = new UsersService();
$result = $service->login($email, $password);

if($result['success']) {
    $_SESSION['user_id'] = $result['user']['user_id'];
    $_SESSION['email'] = $result['user']['email'];
    $_SESSION['role'] = $result['user']['role'];
    unset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_name'], $_SESSION['temp_token'], $_SESSION['pending_2fa'], $_SESSION['2fa_verified']);
}

echo json_encode($result);
?>
