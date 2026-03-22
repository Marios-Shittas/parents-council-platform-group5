<?php
header("Content-Type: application/json");
require_once "../config/db.php";

$user_id = 2;

$data = json_decode(file_get_contents("php://input"), true);
$includeInsurance = isset($data['includeInsurance']) ? $data['includeInsurance'] : true;

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

/* ---------------------------
   1. Membership payment
----------------------------*/
$stmt1 = $conn->prepare("
    INSERT INTO Payments (user_id, amount, payment_status, payment_type)
    VALUES (?, ?, 'pending', 'membership')
");

$stmt1->bind_param("id", $user_id, $subscription_price);
$stmt1->execute();

$membership_payment_id = $stmt1->insert_id;

/* ---------------------------
   2. Insurance payment (optional)
----------------------------*/
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

/* ---------------------------
   3. LOGGING 🔥
----------------------------*/

// Log main payment creation
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

/* ---------------------------
   Response
----------------------------*/
echo json_encode([
    "success" => true,
    "membership_payment_id" => $membership_payment_id,
    "insurance_payment_id" => $insurance_payment_id
]);