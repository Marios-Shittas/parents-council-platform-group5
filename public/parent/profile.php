<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/ParentProfilePageHelper.php';
require_once __DIR__ . '/../../app/services/UsersService.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

AuthHelper::requireRole('parent');

$usersService = new UsersService();
$parentUserId = (int)($_SESSION['user_id'] ?? 0);
$parentUser = $usersService->getUserById($parentUserId);

if (!$parentUser || ($parentUser['role'] ?? '') !== 'parent') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

$children = $usersService->getChildrenByUserId($parentUserId);
$orders = $usersService->getOrdersByUserId($parentUserId);
$payments = $usersService->getPaymentsByUserId($parentUserId);
$insuredChildIds = $usersService->getCompletedInsuredChildIdsByUserId($parentUserId);
$insuredChildIdsLookup = array_fill_keys(array_map('intval', $insuredChildIds), true);
$childrenPendingInsurance = array_values(array_filter($children, static function (array $child) use ($insuredChildIdsLookup): bool {
    $childId = (int)($child['child_id'] ?? 0);
    return $childId > 0 && !isset($insuredChildIdsLookup[$childId]);
}));
$insurancePricePerChild = $usersService->getInsurancePriceSetting();
$pendingInsuranceChildrenCount = count($childrenPendingInsurance);
$pendingInsuranceTotal = $insurancePricePerChild * $pendingInsuranceChildrenCount;
$orderItemsByOrderId = $usersService->getOrderItemsGroupedByOrderIds(array_map(static function (array $order): int {
    return (int)($order['order_id'] ?? 0);
}, $orders));

$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/parent-profile.php';
