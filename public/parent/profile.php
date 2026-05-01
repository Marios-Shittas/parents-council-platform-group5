<?php
// Arxeio: public\parent\profile.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/services/UsersService.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// Leitourgia parentProfileMorfiAccountStatusLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfileFormatAccountStatusLabel(string $status): string
{
    $map = [
        'pending' => 'Σε Αναμονή',
        'approved' => 'Εγκεκριμένος',
        'rejected' => 'Απορριφθείς',
        'waiting_payment' => 'Αναμονή Πληρωμής',
        'active' => 'Ενεργός',
    ];

    return $map[$status] ?? ucfirst($status);
}

// Leitourgia parentProfileAccountStatusClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfileAccountStatusClass(string $status): string
{
    switch ($status) {
        case 'active':
            return 'status-badge status-success';
        case 'approved':
            return 'status-badge status-primary';
        case 'waiting_payment':
            return 'status-badge status-warning';
        case 'rejected':
            return 'status-badge status-danger';
        default:
            return 'status-badge status-muted';
    }
}

// Leitourgia parentProfileMorfiOrderStatusLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfileFormatOrderStatusLabel(string $status): string
{
    $map = [
        'pending' => 'Σε Αναμονή',
        'paid' => 'Πληρωμένη',
        'cancelled' => 'Ακυρωμένη',
    ];

    return $map[$status] ?? ucfirst($status);
}

// Leitourgia parentProfileOrderStatusClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfileOrderStatusClass(string $status): string
{
    switch ($status) {
        case 'paid':
            return 'status-badge status-success';
        case 'cancelled':
            return 'status-badge status-danger';
        default:
            return 'status-badge status-warning';
    }
}

// Leitourgia parentProfileMorfiPaymentStatusLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfileFormatPaymentStatusLabel(string $status): string
{
    $map = [
        'pending' => 'Σε Αναμονή',
        'completed' => 'Ολοκληρωμένη',
        'failed' => 'Αποτυχημένη',
        'refunded' => 'Επιστροφή',
    ];

    return $map[$status] ?? ucfirst($status);
}

// Leitourgia parentProfilePaymentStatusClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfilePaymentStatusClass(string $status): string
{
    switch ($status) {
        case 'completed':
            return 'status-badge status-success';
        case 'failed':
            return 'status-badge status-danger';
        case 'refunded':
            return 'status-badge status-info';
        default:
            return 'status-badge status-warning';
    }
}

// Leitourgia parentProfileMorfiPaymentTypeLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentProfileFormatPaymentTypeLabel(string $type): string
{
    $map = [
        'membership' => 'Συνδρομή',
        'insurance' => 'Ασφάλεια',
        'product' => 'Προϊόν',
    ];

    return $map[$type] ?? ucfirst($type);
}

$usersService = new UsersService();
$parentUserId = (int)($_SESSION['user_id'] ?? 0);
$parentUser = $usersService->getUserById($parentUserId);

if (!$parentUser || ($parentUser['role'] ?? '') !== 'parent') {
    require_once __DIR__ . '/../../app/includes/site_context.php';
    header('Location: ' . site_login_url());
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
