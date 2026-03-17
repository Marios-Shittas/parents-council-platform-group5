<?php 
header('Content-Type: application/json');
require_once __DIR__ . '/../services/UsersService.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$service = new UsersService();
echo json_encode($service->login($email, $password));
?>