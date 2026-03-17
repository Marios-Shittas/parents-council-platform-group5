<?php
    header('Content-Type: application/json');
    require_once __DIR__ . '/../services/UsersService.php';
    require_once __DIR__ . '/../../vendor/autoload.php';

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    $service = new UsersService();
    $result = $service->forgot($email);

    if ($result['success']) {
        sendResetEmail($email);
    }

    echo json_encode($result);

    function sendResetEmail($email) {
        $subject = "Password Reset Request";
        $message = "Click the link below to reset your password:\n\n";
        $message .= "http://localhost/parents-council-platform-group5/public/reset-password.php?email=";
        $header = "From: noreply@parentcouncil.com";

        mail($email, $subject, $message, $header);
    }
?>