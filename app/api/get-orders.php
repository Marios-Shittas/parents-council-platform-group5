<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/OrdersService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    header('Content-Type: application/json; charset=utf-8');
    $service = new OrdersService($conn);
    
    // Ensure the column exists
    $testSql = "SHOW COLUMNS FROM Orders LIKE 'admin_seen_at'";
    $testResult = $conn->query($testSql);
    
    if (!$testResult || $testResult->num_rows === 0) {
        // Column doesn't exist, try to create it
        $alterSql = "ALTER TABLE Orders ADD COLUMN admin_seen_at datetime DEFAULT NULL";
        if (!$conn->query($alterSql)) {
            throw new RuntimeException('Failed to create admin_seen_at column: ' . $conn->error);
        }
    }
    
    $service->handleRequest();
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    error_log('Orders API Error: ' . $e->getMessage());
}

$conn->close();
