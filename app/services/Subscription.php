<?php
header("Content-Type: application/json");
require_once "../config/db.php";

$user_id = 2;

$stmt = $conn->prepare("SELECT COUNT(*) as children_count FROM Children WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$children_count = (int)$result['children_count'];

$sql = "SELECT subscription_price, insurance_price FROM PricingSettings LIMIT 1";
$res = $conn->query($sql);
$pricing = $res->fetch_assoc();

$subscription_price = (float)$pricing['subscription_price'];
$insurance_price = (float)$pricing['insurance_price'];

$insurance_total = $insurance_price * $children_count;
$total = $subscription_price + $insurance_total;

echo json_encode([
    "subscription_price" => $subscription_price,
    "insurance_price" => $insurance_price,
    "children_count" => $children_count,
    "insurance_total" => $insurance_total,
    "total" => $total
]);