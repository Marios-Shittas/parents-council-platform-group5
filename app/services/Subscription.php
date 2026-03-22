<?php
header("Content-Type: application/json");
require_once "../config/db.php";

$token = trim((string)($_GET['token'] ?? ''));

if ($token === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing approval token."
    ]);
    exit;
}

$userStmt = $conn->prepare(
    "SELECT user_id
     FROM Users
     WHERE token = ?
       AND role = 'parent'
       AND account_status = 'waiting_payment'
       AND token_expiry IS NOT NULL
       AND token_expiry >= NOW()
     LIMIT 1"
);

if (!$userStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to validate token."
    ]);
    exit;
}

$userStmt->bind_param("s", $token);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult ? $userResult->fetch_assoc() : null;
$userStmt->close();

if (!$userData) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Invalid or expired approval token."
    ]);
    exit;
}

$user_id = (int)$userData['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as children_count FROM Children WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$children_count = (int)$result['children_count'];

$sql = "SELECT subscription_price, insurance_price FROM PricingSettings LIMIT 1";
$res = $conn->query($sql);
if (!$res) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to read pricing settings."
    ]);
    exit;
}

$pricing = $res->fetch_assoc();
if (!$pricing) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Pricing settings are missing."
    ]);
    exit;
}

$subscription_price = (float)$pricing['subscription_price'];
$insurance_price = (float)$pricing['insurance_price'];

$insurance_total = $insurance_price * $children_count;
$total = $subscription_price + $insurance_total;

echo json_encode([
    "success" => true,
    "data" => [
        "subscription_price" => $subscription_price,
        "insurance_price" => $insurance_price,
        "children_count" => $children_count,
        "insurance_total" => $insurance_total,
        "total" => $total
    ]
]);