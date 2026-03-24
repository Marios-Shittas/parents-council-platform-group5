<?php
/**
 * Session validation endpoint
 * Returns JSON indicating if user is still logged in
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

echo json_encode([
    'isLoggedIn' => $isLoggedIn,
    'role' => $userRole,
    'userId' => $isLoggedIn ? $_SESSION['user_id'] : null
]);
?>
