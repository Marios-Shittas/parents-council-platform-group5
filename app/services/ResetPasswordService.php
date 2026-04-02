<?php
require_once __DIR__ . '/UsersService.php';

class ResetPasswordService {
    private $usersService;

    public function __construct() {
        $this->usersService = new UsersService();
    }

    public function handleRequest() {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Reset the password
        $result = $this->usersService->resetPassword($email, $newPassword);

        return $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = new ResetPasswordService();
    $result = $service->handleRequest();
    echo json_encode($result);
}
?>