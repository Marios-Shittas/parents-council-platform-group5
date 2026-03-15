<?php 
header('Content-Type: application/json');
require_once __DIR__ . '/../services/UsersService.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$service = new UsersService();
$userdb = $service->getUserByEmail($email);

if (!$userdb || $email !== $userdb['email'] || $password !== $userdb['password']) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

echo json_encode(['success' => true, 'role' => $userdb['role'], 'message' => 'Login successful.']);
?>