<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/UsersService.php';

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function adminLogsRespond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

try {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        adminLogsRespond(403, [
            'success' => false,
            'message' => 'Unauthorized access.',
        ]);
        exit;
    }

    $usersService = new UsersService();
    $email = trim((string)($_GET['email'] ?? ''));
    $lookup = $usersService->getParentLogsByEmail($email);

    adminLogsRespond(200, [
        'success' => true,
        'parent' => $lookup['parent'] ?? null,
        'logs' => $lookup['logs'] ?? [],
        'found' => !empty($lookup['found']),
        'searched_email' => $email,
    ]);
} catch (Throwable $exception) {
    adminLogsRespond(500, [
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
