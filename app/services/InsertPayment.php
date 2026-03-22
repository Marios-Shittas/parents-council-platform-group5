<?php
header("Content-Type: application/json");
require_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$token = trim((string)($data['token'] ?? ''));
$includeInsurance = isset($data['includeInsurance']) ? $data['includeInsurance'] : true;

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

// Get children count
$stmt = $conn->prepare("SELECT COUNT(*) as children_count FROM Children WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$children_count = (int)$result['children_count'];

// Get pricing
$res = $conn->query("SELECT subscription_price, insurance_price FROM PricingSettings LIMIT 1");
$pricing = $res->fetch_assoc();

$subscription_price = (float)$pricing['subscription_price'];
$insurance_price = (float)$pricing['insurance_price'];

$stmt1 = $conn->prepare("
    INSERT INTO Payments (user_id, amount, payment_status, payment_type)
    VALUES (?, ?, 'pending', 'membership')
");

$stmt1->bind_param("id", $user_id, $subscription_price);
$stmt1->execute();

$membership_payment_id = $stmt1->insert_id;

$insurance_payment_id = null;

if ($includeInsurance) {
    $insurance_total = $insurance_price * $children_count;

    $stmt2 = $conn->prepare("
        INSERT INTO Payments (user_id, amount, payment_status, payment_type)
        VALUES (?, ?, 'pending', 'insurance')
    ");

    $stmt2->bind_param("id", $user_id, $insurance_total);
    $stmt2->execute();

    $insurance_payment_id = $stmt2->insert_id;
}


$log_desc = "User created membership payment (ID: $membership_payment_id)";

if ($includeInsurance) {
    $log_desc .= " and insurance payment (ID: $insurance_payment_id)";
}

$stmtLog = $conn->prepare("
    INSERT INTO Logs (user_id, action, description)
    VALUES (?, 'PAYMENT_CREATED', ?)
");

$stmtLog->bind_param("is", $user_id, $log_desc);
$stmtLog->execute();

echo json_encode([
    "success" => true,
    "membership_payment_id" => $membership_payment_id,
    "insurance_payment_id" => $insurance_payment_id
]);