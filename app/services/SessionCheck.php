<?php
// Arxeio: app\services\SessionCheck.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * Endpoint elegxou syndedemenis synedrias
 * Epistrefei JSON pou deixnei an o xristis paramenei syndedemenos
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// Elegxei an o xristis einai syndedemenos
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

echo json_encode([
    'isLoggedIn' => $isLoggedIn,
    'role' => $userRole,
    'userId' => $isLoggedIn ? $_SESSION['user_id'] : null
]);
?>
