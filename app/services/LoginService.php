<?php 
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
}

echo json_encode($result);
?>