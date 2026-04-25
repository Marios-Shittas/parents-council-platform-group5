<?php
require_once __DIR__ . '/../../app/services/UsersService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

// Leitourgia isPrimaryProtectedAdmin: xeirizetai to antistoixo kommati tis selidas i tou service.
function isPrimaryProtectedAdmin(int $userId): bool
{
    return $userId === 1;
}

// Leitourgia normalizeUserRole: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeUserRole(string $role): string
{
    return in_array($role, ['admin', 'parent'], true) ? $role : 'parent';
}

// Leitourgia normalizeUserStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeUserStatus(string $status): string
{
    $allowed = ['pending', 'approved', 'rejected', 'waiting_payment', 'active'];
    return in_array($status, $allowed, true) ? $status : 'pending';
}

// Leitourgia normalizeUserSort: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeUserSort(string $sort): string
{
    $allowed = ['pending_first', 'newest', 'oldest', 'name_az', 'status_az'];
    return in_array($sort, $allowed, true) ? $sort : 'pending_first';
}

// Leitourgia normalizeScheduleStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeScheduleStatus(string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'inactive';
}

// Leitourgia normalizeScheduleFeature: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeScheduleFeature(string $feature): string
{
    $normalized = trim((string)$feature);
    if ($normalized === 'cleanup_applications' || $normalized === 'cleanuo_submissions') {
        $normalized = 'cleanup_submissions';
    }

    $allowed = ['registration', 'delete_users', 'cleanup_submissions'];
    return in_array($normalized, $allowed, true) ? $normalized : 'registration';
}

// Leitourgia scheduleFeatureLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function scheduleFeatureLabel(string $feature): string
{
    $map = [
        'registration' => 'Εγγραφές',
        'delete_users' => 'Διαγραφή Χρηστών',
        'cleanup_submissions' => 'Καθαρισμός Υποβολών',
    ];

    return $map[$feature] ?? $feature;
}

// Leitourgia normalizeDateTimeLocalInput: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeDateTimeLocalInput(string $value): ?string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }

    $dateTime = DateTime::createFromFormat('d/m/Y H:i', $trimmed);

    if (!$dateTime instanceof DateTime) {
        // Keep backward compatibility in case the browser still submits datetime-local format.
        $dateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $trimmed);
        if (!$dateTime instanceof DateTime) {
            return null;
        }
    }

    return $dateTime->format('Y-m-d H:i:s');
}

// Leitourgia toDateTimeLocalValue: xeirizetai to antistoixo kommati tis selidas i tou service.
function toDateTimeLocalValue(?string $value): string
{
    if (!is_string($value) || trim($value) === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d\\TH:i', $timestamp) : '';
}

// Leitourgia redirectWithFlash: xeirizetai to antistoixo kommati tis selidas i tou service.
function redirectWithFlash(string $message, string $type = 'info', int $manageChildrenUserId = 0): void
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $type;
    $redirectUrl = 'users.php';
    if ($manageChildrenUserId > 0) {
        $redirectUrl .= '?manage_children=' . $manageChildrenUserId;
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// Leitourgia formatRoleLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function formatRoleLabel(string $role): string
{
    return $role === 'admin' ? 'Διαχειριστής' : 'Γονέας';
}

// Leitourgia formatStatusLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function formatStatusLabel(string $status): string
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

// Leitourgia roleBadgeClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function roleBadgeClass(string $role): string
{
    return $role === 'admin'
        ? 'status-pill status-pill--accent'
        : 'status-pill status-pill--neutral';
}

// Leitourgia statusBadgeClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function statusBadgeClass(string $status): string
{
    switch ($status) {
        case 'active':
            return 'status-pill status-pill--success';
        case 'approved':
            return 'status-pill status-pill--info';
        case 'waiting_payment':
            return 'status-pill status-pill--warning';
        case 'rejected':
            return 'status-pill status-pill--danger';
        default:
            return 'status-pill status-pill--neutral';
    }
}

// Leitourgia formatOrderStatusLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function formatOrderStatusLabel(string $status): string
{
    $map = [
        'pending' => 'Σε Αναμονή',
        'paid' => 'Πληρωμένη',
        'cancelled' => 'Ακυρωμένη',
    ];

    return $map[$status] ?? ucfirst($status);
}

// Leitourgia orderStatusBadgeClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function orderStatusBadgeClass(string $status): string
{
    switch ($status) {
        case 'paid':
            return 'badge bg-success-subtle text-success-emphasis';
        case 'cancelled':
            return 'badge bg-danger-subtle text-danger-emphasis';
        default:
            return 'badge bg-warning-subtle text-warning-emphasis';
    }
}

// Leitourgia formatPaymentStatusLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function formatPaymentStatusLabel(string $status): string
{
    $map = [
        'pending' => 'Σε Αναμονή',
        'completed' => 'Ολοκληρωμένη',
        'failed' => 'Αποτυχημένη',
        'refunded' => 'Επιστροφή',
    ];

    return $map[$status] ?? ucfirst($status);
}

// Leitourgia paymentStatusBadgeClass: xeirizetai to antistoixo kommati tis selidas i tou service.
function paymentStatusBadgeClass(string $status): string
{
    switch ($status) {
        case 'completed':
            return 'badge bg-success-subtle text-success-emphasis';
        case 'failed':
            return 'badge bg-danger-subtle text-danger-emphasis';
        case 'refunded':
            return 'badge bg-info-subtle text-info-emphasis';
        default:
            return 'badge bg-warning-subtle text-warning-emphasis';
    }
}

// Leitourgia formatPaymentTypeLabel: xeirizetai to antistoixo kommati tis selidas i tou service.
function formatPaymentTypeLabel(string $type): string
{
    $map = [
        'membership' => 'Συνδρομή',
        'insurance' => 'Ασφάλεια',
        'product' => 'Προϊόν',
    ];

    return $map[$type] ?? ucfirst($type);
}

// Leitourgia exportCellText: xeirizetai to antistoixo kommati tis selidas i tou service.
function exportCellText(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Leitourgia formatExportDate: xeirizetai to antistoixo kommati tis selidas i tou service.
function formatExportDate(?string $value): string
{
    if (!is_string($value) || trim($value) === '') {
        return '—';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : (string)$value;
}

// Leitourgia buildChildExportSummary: xeirizetai to antistoixo kommati tis selidas i tou service.
function buildChildExportSummary(array $children): string
{
    if (empty($children)) {
        return '—';
    }

    $parts = [];
    foreach ($children as $child) {
        $name = trim(((string)($child['name'] ?? '')) . ' ' . ((string)($child['surname'] ?? '')));
        $dob = !empty($child['date_of_birth']) ? date('d/m/Y', strtotime((string)$child['date_of_birth'])) : '—';
        $schoolClass = trim((string)($child['school_class'] ?? ''));
        $parts[] = trim($name) . ' | Γεν.: ' . $dob . ' | Τάξη: ' . ($schoolClass !== '' ? $schoolClass : '—');
    }

    return implode(" \n", $parts);
}

// Leitourgia buildInsuredChildrenSummary: xeirizetai to antistoixo kommati tis selidas i tou service.
function buildInsuredChildrenSummary(array $children, array $insuredChildIds, bool $hasCompletedInsurance): string
{
    if (empty($children)) {
        return '—';
    }

    $resolvedInsuredChildIds = $insuredChildIds;
    if ($hasCompletedInsurance && empty($resolvedInsuredChildIds)) {
        foreach ($children as $child) {
            $childId = (int)($child['child_id'] ?? 0);
            if ($childId > 0) {
                $resolvedInsuredChildIds[] = $childId;
            }
        }
    }

    $insuredLookup = array_flip(array_map('intval', $resolvedInsuredChildIds));
    $parts = [];

    foreach ($children as $child) {
        $childId = (int)($child['child_id'] ?? 0);
        $name = trim(((string)($child['name'] ?? '')) . ' ' . ((string)($child['surname'] ?? '')));
        $parts[] = $name . ': ' . (isset($insuredLookup[$childId]) ? 'Ναι' : 'Όχι');
    }

    return implode(" \n", $parts);
}

// Leitourgia buildPaymentSummary: xeirizetai to antistoixo kommati tis selidas i tou service.
function buildPaymentSummary(array $payments, string $paymentType): string
{
    $filtered = array_values(array_filter($payments, static function ($payment) use ($paymentType) {
        return (string)($payment['payment_type'] ?? '') === $paymentType;
    }));

    if (empty($filtered)) {
        return '—';
    }

    $parts = [];
    foreach ($filtered as $payment) {
        $parts[] = sprintf(
            '#%d | %s | %s | %s | ποσό: %s | JCC: %s',
            (int)($payment['payment_id'] ?? 0),
            formatPaymentTypeLabel((string)($payment['payment_type'] ?? '')),
            formatPaymentStatusLabel((string)($payment['payment_status'] ?? 'pending')),
            formatExportDate($payment['payment_date'] ?? null),
            number_format((float)($payment['amount'] ?? 0), 2),
            trim((string)($payment['transaction_id'] ?? '')) !== '' ? (string)$payment['transaction_id'] : '—'
        );
    }

    return implode(" \n", $parts);
}

// Leitourgia buildProductTransactionSummary: xeirizetai to antistoixo kommati tis selidas i tou service.
function buildProductTransactionSummary(array $payments): string
{
    $filtered = array_values(array_filter($payments, static function ($payment) {
        return (string)($payment['payment_type'] ?? '') === 'product'
            && trim((string)($payment['transaction_id'] ?? '')) !== '';
    }));

    if (empty($filtered)) {
        return '—';
    }

    $parts = [];
    foreach ($filtered as $payment) {
        $parts[] = sprintf(
            '#%d: %s',
            (int)($payment['payment_id'] ?? 0),
            trim((string)($payment['transaction_id'] ?? '')) !== '' ? (string)$payment['transaction_id'] : '—'
        );
    }

    return implode(" \n", $parts);
}

// Leitourgia buildOrdersExportSummary: xeirizetai to antistoixo kommati tis selidas i tou service.
function buildOrdersExportSummary(array $orders, array $orderItemsByOrderId): string
{
    if (empty($orders)) {
        return '—';
    }

    $parts = [];
    foreach ($orders as $order) {
        $orderId = (int)($order['order_id'] ?? 0);
        $items = $orderItemsByOrderId[$orderId] ?? [];
        if (empty($items)) {
            continue;
        }

        $itemParts = [];

        foreach ($items as $item) {
            $size = trim((string)($item['size'] ?? ''));
            $itemParts[] = sprintf(
                '%s x%d%s',
                (string)($item['product_name'] ?? 'Προϊόν'),
                (int)($item['quantity'] ?? 0),
                $size !== '' ? ' [' . $size . ']' : ''
            );
        }

        $parts[] = sprintf(
            '#%d | %s | %s | σύνολο: %s | είδη: %s',
            $orderId,
            formatOrderStatusLabel((string)($order['order_status'] ?? 'pending')),
            formatExportDate($order['created_at'] ?? null),
            number_format((float)($order['total_price'] ?? 0), 2),
            !empty($itemParts) ? implode(', ', $itemParts) : '—'
        );
    }

    if (empty($parts)) {
        return '—';
    }

    return implode(" \n", $parts);
}

$usersService = new UsersService();
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_parent') {
        $result = $usersService->createUserByAdmin([
            'name' => trim((string)($_POST['name'] ?? '')),
            'surname' => trim((string)($_POST['surname'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'phone_number' => trim((string)($_POST['phone_number'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'role' => 'parent',
            'account_status' => normalizeUserStatus((string)($_POST['account_status'] ?? 'active')),
        ], $currentAdminId);

        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger'
        );
    }

    if ($action === 'create_child') {
        $parentUserId = (int)($_POST['parent_user_id'] ?? 0);
        $result = $usersService->createChildForParent($parentUserId, [
            'name' => trim((string)($_POST['child_name'] ?? '')),
            'surname' => trim((string)($_POST['child_surname'] ?? '')),
            'date_of_birth' => trim((string)($_POST['child_date_of_birth'] ?? '')),
            'school_class' => trim((string)($_POST['child_school_class'] ?? '')),
        ], $currentAdminId);

        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger',
            $parentUserId
        );
    }

    if ($action === 'update_child') {
        $parentUserId = (int)($_POST['parent_user_id'] ?? 0);
        $childId = (int)($_POST['child_id'] ?? 0);
        $result = $usersService->updateChildForParent($childId, $parentUserId, [
            'name' => trim((string)($_POST['child_name'] ?? '')),
            'surname' => trim((string)($_POST['child_surname'] ?? '')),
            'date_of_birth' => trim((string)($_POST['child_date_of_birth'] ?? '')),
            'school_class' => trim((string)($_POST['child_school_class'] ?? '')),
        ], $currentAdminId);

        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger',
            $parentUserId
        );
    }

    if ($action === 'delete_child') {
        $parentUserId = (int)($_POST['parent_user_id'] ?? 0);
        $childId = (int)($_POST['child_id'] ?? 0);
        $result = $usersService->deleteChildForParent($childId, $parentUserId, $currentAdminId);

        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger',
            $parentUserId
        );
    }

    if ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            redirectWithFlash('Μη έγκυρος χρήστης.', 'danger');
        }

        $requestedRole = normalizeUserRole((string)($_POST['role'] ?? 'parent'));
        $requestedStatus = normalizeUserStatus((string)($_POST['account_status'] ?? 'pending'));

        if (isPrimaryProtectedAdmin($userId)) {
            $requestedRole = 'admin';
            $requestedStatus = 'active';
        }

        if ($userId === $currentAdminId) {
            $requestedRole = 'admin';
            $requestedStatus = 'active';
        }

        $result = $usersService->updateUserByAdmin($userId, [
            'name' => trim((string)($_POST['name'] ?? '')),
            'surname' => trim((string)($_POST['surname'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'phone_number' => trim((string)($_POST['phone_number'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'rejection_message' => trim((string)($_POST['rejection_message'] ?? '')),
            'role' => $requestedRole,
            'account_status' => $requestedStatus,
        ], $currentAdminId);

        $flashMessage = $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.';
        if (!empty($result['approval_email_sent']) && !empty($result['approval_email'])) {
            $flashMessage = 'Στάλθηκε email έγκρισης στο ' . $result['approval_email'] . ' και ο χρήστης μεταφέρθηκε σε αναμονή πληρωμής.';
        }
        if (!empty($result['rejection_email_sent']) && !empty($result['rejection_email'])) {
            $flashMessage = 'Στάλθηκε email απόρριψης στο ' . $result['rejection_email'] . '.';
        }

        redirectWithFlash(
            $flashMessage,
            !empty($result['success']) ? 'success' : 'danger'
        );
    }

    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            redirectWithFlash('Μη έγκυρος χρήστης.', 'danger');
        }

        if (isPrimaryProtectedAdmin($userId)) {
            redirectWithFlash('Ο admin 1 είναι προστατευμένος και δεν μπορεί να διαγραφεί.', 'warning');
        }

        if ($userId === $currentAdminId) {
            redirectWithFlash('Δεν μπορείς να διαγράψεις τον λογαριασμό με τον οποίο είσαι συνδεδεμένος.', 'warning');
        }

        $result = $usersService->deleteUserByAdmin($userId, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger'
        );
    }

    if ($action === 'update_registration_schedule') {
        $scheduleId = (int)($_POST['registration_schedule_id'] ?? 0);
        $feature = normalizeScheduleFeature((string)($_POST['schedule_feature'] ?? 'registration'));
        $startDate = normalizeDateTimeLocalInput((string)($_POST['registration_start_date'] ?? ''));
        $endDate = normalizeDateTimeLocalInput((string)($_POST['registration_end_date'] ?? ''));
        $status = normalizeScheduleStatus((string)($_POST['registration_status'] ?? 'active'));

        if ($scheduleId <= 0 || $startDate === null || $endDate === null) {
            redirectWithFlash('Συμπλήρωσε έγκυρες ημερομηνίες για το πρόγραμμα εγγραφών.', 'danger');
        }

        $result = $usersService->updateSystemSchedule($scheduleId, $feature, $startDate, $endDate, $status, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger'
        );
    }

    if ($action === 'add_registration_schedule') {
        $feature = normalizeScheduleFeature((string)($_POST['schedule_feature'] ?? 'registration'));
        $startDate = normalizeDateTimeLocalInput((string)($_POST['registration_start_date'] ?? ''));
        $endDate = normalizeDateTimeLocalInput((string)($_POST['registration_end_date'] ?? ''));
        $status = normalizeScheduleStatus((string)($_POST['registration_status'] ?? 'active'));

        if ($startDate === null || $endDate === null) {
            redirectWithFlash('Συμπλήρωσε έγκυρες ημερομηνίες για τη νέα περίοδο εγγραφών.', 'danger');
        }

        $result = $usersService->createSystemSchedule($feature, $startDate, $endDate, $status, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger'
        );
    }

    if ($action === 'delete_registration_schedule') {
        $scheduleId = (int)($_POST['registration_schedule_id'] ?? 0);
        if ($scheduleId <= 0) {
            redirectWithFlash('Μη έγκυρο πρόγραμμα.', 'danger');
        }

        $result = $usersService->deleteSystemSchedule($scheduleId, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger'
        );
    }
}

$selectedSort = normalizeUserSort((string)($_GET['sort'] ?? 'pending_first'));
$users = $usersService->getAllUsersForAdmin($selectedSort);
$managedParentId = (int)($_GET['manage_children'] ?? 0);
$allUserIds = [];
$parentUserIds = [];
$totalUsers = count($users);
$adminCount = 0;
$parentCount = 0;
$pendingCount = 0;

foreach ($users as $user) {
    $allUserIds[] = (int)($user['user_id'] ?? 0);

    if (($user['role'] ?? '') === 'admin') {
        $adminCount++;
    } else {
        $parentCount++;
        $parentUserIds[] = (int)($user['user_id'] ?? 0);
    }

    if (($user['account_status'] ?? '') === 'pending') {
        $pendingCount++;
    }
}

$childrenByParentId = $usersService->getChildrenGroupedByUserIds($parentUserIds);
$ordersByUserId = $usersService->getOrdersGroupedByUserIds($allUserIds);
$paymentsByUserId = $usersService->getPaymentsGroupedByUserIds($allUserIds);
$registrationSchedules = $usersService->getSystemSchedules();

$allOrderIds = [];
foreach ($ordersByUserId as $userOrders) {
    foreach ($userOrders as $order) {
        $orderId = (int)($order['order_id'] ?? 0);
        if ($orderId > 0) {
            $allOrderIds[] = $orderId;
        }
    }
}
$orderItemsByOrderId = $usersService->getOrderItemsGroupedByOrderIds($allOrderIds);
$parentUsersForExport = array_values(array_filter($users, static function ($user) {
    return (string)($user['role'] ?? '') === 'parent';
}));

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="users-export-' . date('Y-m-d-H-i') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    ?>
    <html lang="el">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../assets/css/admin_css/users-export.css">
    </head>
    <body>
    <table>
        <thead>
        <tr>
            <th>User ID</th>
            <th>Όνομα</th>
            <th>Επώνυμο</th>
            <th>Email</th>
            <th>Τηλέφωνο</th>
            <th>Ρόλος</th>
            <th>Κατάσταση</th>
            <th>Ημ. Δημιουργίας</th>
            <th>Αριθμός Παιδιών</th>
            <th>Στοιχεία Παιδιών</th>
            <th>Ασφάλεια Παιδιών</th>
            <th>Υπάρχει Ολοκληρωμένη Ασφάλεια</th>
            <th>Πληρωμές Ασφάλειας</th>
            <th>Πληρωμές Συνδρομής</th>
            <th>Αγορές E-shop</th>
            <th>JCC IDs E-shop</th>
            <th>Όλες οι Πληρωμές</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($parentUsersForExport as $user): ?>
            <?php
            $userId = (int)($user['user_id'] ?? 0);
            $userChildren = $childrenByParentId[$userId] ?? [];
            $userOrders = $ordersByUserId[$userId] ?? [];
            $userPayments = $paymentsByUserId[$userId] ?? [];
            $insuredChildIds = $usersService->getCompletedInsuredChildIdsByUserId($userId);
            $hasCompletedInsurance = !empty(array_filter($userPayments, static function ($payment) {
                return (string)($payment['payment_type'] ?? '') === 'insurance'
                    && (string)($payment['payment_status'] ?? '') === 'completed';
            }));
            ?>
            <tr>
                <td><?php echo exportCellText((string)$userId); ?></td>
                <td><?php echo exportCellText((string)($user['name'] ?? '')); ?></td>
                <td><?php echo exportCellText((string)($user['surname'] ?? '')); ?></td>
                <td><?php echo exportCellText((string)($user['email'] ?? '')); ?></td>
                <td><?php echo exportCellText((string)($user['phone_number'] ?? '')); ?></td>
                <td><?php echo exportCellText(formatRoleLabel((string)($user['role'] ?? 'parent'))); ?></td>
                <td><?php echo exportCellText(formatStatusLabel((string)($user['account_status'] ?? 'pending'))); ?></td>
                <td><?php echo exportCellText(formatExportDate($user['created_at'] ?? null)); ?></td>
                <td><?php echo exportCellText((string)count($userChildren)); ?></td>
                <td><?php echo exportCellText(buildChildExportSummary($userChildren)); ?></td>
                <td><?php echo exportCellText(buildInsuredChildrenSummary($userChildren, $insuredChildIds, $hasCompletedInsurance)); ?></td>
                <td><?php echo exportCellText($hasCompletedInsurance ? 'Ναι' : 'Όχι'); ?></td>
                <td><?php echo exportCellText(buildPaymentSummary($userPayments, 'insurance')); ?></td>
                <td><?php echo exportCellText(buildPaymentSummary($userPayments, 'membership')); ?></td>
                <td><?php echo exportCellText(buildOrdersExportSummary($userOrders, $orderItemsByOrderId)); ?></td>
                <td><?php echo exportCellText(buildProductTransactionSummary($userPayments)); ?></td>
                <td><?php echo exportCellText(buildPaymentSummary($userPayments, 'insurance') . " \n" . buildPaymentSummary($userPayments, 'membership') . " \n" . buildPaymentSummary($userPayments, 'product')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_users.css">

    <title>Διαχείριση Χρηστών - Admin</title>
</head>
<body>
<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στην Αρχική
        </a>

        <div class="admin-header admin-page-header">
            <div>
                <h1><i class="fas fa-users me-2"></i>Διαχείριση Χρηστών</h1>
                <p class="text-muted mb-0">Προβολή, δημιουργία και διαχείριση όλων των λογαριασμών από ένα κεντρικό panel.</p>
            </div>
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createParentModal">
                <i class="fas fa-user-plus me-1"></i>Νέος Γονέας
            </button>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Κλείσιμο"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom user-stat-card h-100">
                    <div class="card-body">
                        <span class="stat-label">Σύνολο Χρηστών</span>
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom user-stat-card h-100">
                    <div class="card-body">
                        <span class="stat-label">Διαχειριστές</span>
                        <div class="stat-value"><?php echo $adminCount; ?></div>
                        <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom user-stat-card h-100">
                    <div class="card-body">
                        <span class="stat-label">Γονείς</span>
                        <div class="stat-value"><?php echo $parentCount; ?></div>
                        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom user-stat-card h-100">
                    <div class="card-body">
                        <span class="stat-label">Σε Αναμονή</span>
                        <div class="stat-value"><?php echo $pendingCount; ?></div>
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <div class="users-toolbar">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-table me-2"></i>Λίστα Χρηστών</h4>
                        <p class="text-muted mb-0">Ορισμένοι λογαριασμοί διαχειριστή προστατεύονται για λόγους ασφάλειας. Επίσης, δεν επιτρέπεται η διαγραφή του λογαριασμού που είναι αυτή τη στιγμή συνδεδεμένος.</p>
                    </div>
                    <div class="users-toolbar-actions">
                        <a href="users.php?sort=<?php echo urlencode($selectedSort); ?>&export=excel" class="btn btn-success users-export-btn">
                            <i class="fas fa-file-excel me-1"></i>Export to Excel
                        </a>
                        <div class="users-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" id="usersSearchInput" class="form-control" placeholder="Αναζήτηση με όνομα, email, ρόλο ή κατάσταση">
                        </div>
                        <form method="GET" class="users-sort-form">
                            <?php if ($managedParentId > 0): ?>
                                <input type="hidden" name="manage_children" value="<?php echo $managedParentId; ?>">
                            <?php endif; ?>
                            <label for="usersSortSelect" class="users-sort-label">Ταξινόμηση</label>
                            <select name="sort" id="usersSortSelect" class="form-select form-select-sm" data-auto-submit-on-change>
                                <option value="pending_first" <?php echo $selectedSort === 'pending_first' ? 'selected' : ''; ?>>Πρώτα σε αναμονή</option>
                                <option value="newest" <?php echo $selectedSort === 'newest' ? 'selected' : ''; ?>>Νεότεροι πρώτα</option>
                                <option value="oldest" <?php echo $selectedSort === 'oldest' ? 'selected' : ''; ?>>Παλαιότεροι πρώτα</option>
                                <option value="name_az" <?php echo $selectedSort === 'name_az' ? 'selected' : ''; ?>>Όνομα Α-Ω</option>
                                <option value="status_az" <?php echo $selectedSort === 'status_az' ? 'selected' : ''; ?>>Κατάσταση</option>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>Δεν υπάρχουν χρήστες</h3>
                        <p>Δημιούργησε τον πρώτο γονέα από το panel.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle admin-dashboard-table users-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Χρήστης</th>
                                    <th>Ρόλος</th>
                                    <th>Κατάσταση</th>
                                    <th>Παιδιά</th>
                                    <th>Ιστορικό</th>
                                    <th>Δημιουργία</th>
                                    <th class="text-end">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                        $userId = (int)($user['user_id'] ?? 0);
                                        $isCurrentUser = $userId === $currentAdminId;
                                        $isProtected = isPrimaryProtectedAdmin($userId);
                                        $isNewRegistration = (($user['role'] ?? '') === 'parent') && (($user['account_status'] ?? '') === 'pending');
                                        $hasHistory = ((int)($user['order_count'] ?? 0) > 0) || ((int)($user['payment_count'] ?? 0) > 0);
                                        $orderHistory = $ordersByUserId[$userId] ?? [];
                                        $paymentHistory = $paymentsByUserId[$userId] ?? [];
                                        $displayChildren = ($user['role'] ?? '') === 'parent' ? (int)($user['child_count'] ?? 0) : 0;
                                        $inlineChildren = $childrenByParentId[$userId] ?? [];
                                        $historyRowId = 'history-preview-' . $userId;
                                        $inlineRowId = 'children-preview-' . $userId;
                                    ?>
                                    <tr class="user-data-row <?php echo $isCurrentUser ? 'current-user-row ' : ''; ?><?php echo $isNewRegistration ? 'new-user-row' : ''; ?>" data-user-id="<?php echo $userId; ?>" data-is-new-registration="<?php echo $isNewRegistration ? '1' : '0'; ?>" data-search="<?php echo htmlspecialchars(strtolower(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? '') . ' ' . ($user['email'] ?? '') . ' ' . ($user['role'] ?? '') . ' ' . ($user['account_status'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>">
                                        <td>
                                            <div class="user-main-cell">
                                                <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr((string)($user['name'] ?? 'U'), 0, 1))); ?></div>
                                                <div class="user-info">
                                                    <div class="user-full-name">
                                                        <?php echo htmlspecialchars(trim((string)($user['name'] ?? '') . ' ' . (string)($user['surname'] ?? ''))); ?>
                                                    </div>
                                                    <div class="user-contact-list">
                                                        <span class="user-contact-item">
                                                            <i class="far fa-envelope"></i>
                                                            <?php echo htmlspecialchars((string)($user['email'] ?? '')); ?>
                                                        </span>
                                                        <span class="user-contact-item">
                                                            <i class="fas fa-phone-alt"></i>
                                                            <?php echo htmlspecialchars((string)($user['phone_number'] ?? '—')); ?>
                                                        </span>
                                                    </div>
                                                    <div class="user-flags">
                                                        <?php if ($isNewRegistration): ?>
                                                            <span class="status-pill status-pill--accent js-new-user-badge">Νέος</span>
                                                        <?php endif; ?>
                                                        <?php if ($isCurrentUser): ?>
                                                            <span class="status-pill status-pill--info">Εσύ</span>
                                                        <?php endif; ?>
                                                        <?php if ($isProtected): ?>
                                                            <span class="status-pill status-pill--locked"><i class="fas fa-lock"></i>Προστατευμένος</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?php echo htmlspecialchars(roleBadgeClass((string)($user['role'] ?? 'parent'))); ?>">
                                                <?php echo htmlspecialchars(formatRoleLabel((string)($user['role'] ?? 'parent'))); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="<?php echo htmlspecialchars(statusBadgeClass((string)($user['account_status'] ?? 'pending'))); ?>">
                                                <?php echo htmlspecialchars(formatStatusLabel((string)($user['account_status'] ?? 'pending'))); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (($user['role'] ?? '') === 'parent'): ?>
                                                <span class="status-pill status-pill--neutral users-count-pill"><?php echo $displayChildren; ?></span>
                                            <?php else: ?>
                                                <span class="users-empty-value">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hasHistory): ?>
                                                <div class="history-summary">
                                                    <span class="status-pill status-pill--warning"><?php echo (int)($user['order_count'] ?? 0); ?> Παραγγ.</span>
                                                    <span class="status-pill status-pill--info"><?php echo (int)($user['payment_count'] ?? 0); ?> Πληρωμές</span>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-info users-action-btn users-action-btn--compact js-toggle-history-preview"
                                                        data-target="<?php echo $historyRowId; ?>"
                                                        aria-expanded="false"
                                                    >
                                                        <i class="fas fa-receipt me-1"></i>Προβολή
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="users-empty-value">Καθαρό</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="users-date-value">
                                                <?php echo !empty($user['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$user['created_at']))) : '—'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-end users-table-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary users-action-btn js-open-edit-user"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                    data-user-id="<?php echo $userId; ?>"
                                                    data-user-name="<?php echo htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-user-surname="<?php echo htmlspecialchars((string)($user['surname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-user-email="<?php echo htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-user-phone="<?php echo htmlspecialchars((string)($user['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-user-role="<?php echo htmlspecialchars((string)($user['role'] ?? 'parent'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-user-status="<?php echo htmlspecialchars((string)($user['account_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-user-protected="<?php echo $isProtected ? '1' : '0'; ?>"
                                                    data-user-self="<?php echo $isCurrentUser ? '1' : '0'; ?>"
                                                >
                                                    <i class="fas fa-edit me-1"></i>Επεξεργασία
                                                </button>

                                                <?php if (($user['role'] ?? '') === 'parent'): ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary users-action-btn js-toggle-children-preview"
                                                        data-target="<?php echo $inlineRowId; ?>"
                                                        aria-expanded="<?php echo $managedParentId === $userId ? 'true' : 'false'; ?>"
                                                    >
                                                        <i class="fas fa-chevron-down me-1"></i>Προβολή Παιδιών
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($isProtected): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-dark users-action-btn" disabled>
                                                        <i class="fas fa-lock me-1"></i>Κλειδωμένο
                                                    </button>
                                                <?php elseif ($isCurrentUser): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary users-action-btn" disabled>
                                                        <i class="fas fa-user-lock me-1"></i>Δικός Σου
                                                    </button>
                                                <?php else: ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger users-action-btn js-open-delete-user"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteUserModal"
                                                        data-user-id="<?php echo $userId; ?>"
                                                        data-user-name="<?php echo htmlspecialchars(trim((string)($user['name'] ?? '') . ' ' . (string)($user['surname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-user-email="<?php echo htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-user-status="<?php echo htmlspecialchars((string)($user['account_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-user-status-label="<?php echo htmlspecialchars(formatStatusLabel((string)($user['account_status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-user-order-count="<?php echo (int)($user['order_count'] ?? 0); ?>"
                                                        data-user-payment-count="<?php echo (int)($user['payment_count'] ?? 0); ?>"
                                                    >
                                                        <i class="fas fa-trash-alt me-1"></i>Διαγραφή
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if ($hasHistory): ?>
                                        <tr id="<?php echo $historyRowId; ?>" class="history-preview-row user-preview-row d-none" data-preview-for="<?php echo $userId; ?>">
                                            <td colspan="7">
                                                <div class="history-preview-card">
                                                    <div class="history-preview-header">
                                                        <div>
                                                            <h6 class="mb-1"><i class="fas fa-history me-2"></i>Ιστορικό Χρήστη</h6>
                                                            <p class="text-muted mb-0">
                                                                Παραγγελίες και πληρωμές για τον/την
                                                                <strong><?php echo htmlspecialchars(trim((string)($user['name'] ?? '') . ' ' . (string)($user['surname'] ?? ''))); ?></strong>.
                                                            </p>
                                                        </div>
                                                        <div class="history-preview-totals">
                                                            <span class="badge bg-light text-dark border px-3 py-2"><?php echo count($orderHistory); ?> Παραγγελίες</span>
                                                            <span class="badge bg-light text-dark border px-3 py-2"><?php echo count($paymentHistory); ?> Πληρωμές</span>
                                                        </div>
                                                    </div>

                                                    <div class="history-preview-grid">
                                                        <section class="history-panel">
                                                            <div class="history-panel-title">
                                                                <i class="fas fa-shopping-bag"></i>
                                                                <span>Παραγγελίες</span>
                                                            </div>

                                                            <?php if (empty($orderHistory)): ?>
                                                                <div class="history-empty">Δεν υπάρχουν παραγγελίες για αυτόν τον χρήστη.</div>
                                                            <?php else: ?>
                                                                <div class="history-list">
                                                                    <?php foreach ($orderHistory as $order): ?>
                                                                        <article class="history-item">
                                                                            <div class="history-item-top">
                                                                                <strong>Παραγγελία #<?php echo (int)($order['order_id'] ?? 0); ?></strong>
                                                                                <span class="<?php echo htmlspecialchars(orderStatusBadgeClass((string)($order['order_status'] ?? 'pending'))); ?>">
                                                                                    <?php echo htmlspecialchars(formatOrderStatusLabel((string)($order['order_status'] ?? 'pending'))); ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="history-item-meta">
                                                                                <span><i class="far fa-calendar-alt me-1"></i><?php echo !empty($order['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$order['created_at']))) : '—'; ?></span>
                                                                                <span><i class="fas fa-euro-sign me-1"></i><?php echo number_format((float)($order['total_price'] ?? 0), 2); ?></span>
                                                                            </div>
                                                                        </article>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </section>

                                                        <section class="history-panel">
                                                            <div class="history-panel-title">
                                                                <i class="fas fa-credit-card"></i>
                                                                <span>Πληρωμές</span>
                                                            </div>

                                                            <?php if (empty($paymentHistory)): ?>
                                                                <div class="history-empty">Δεν υπάρχουν πληρωμές για αυτόν τον χρήστη.</div>
                                                            <?php else: ?>
                                                                <div class="history-list">
                                                                    <?php foreach ($paymentHistory as $payment): ?>
                                                                        <article class="history-item">
                                                                            <div class="history-item-top">
                                                                                <strong>Πληρωμή #<?php echo (int)($payment['payment_id'] ?? 0); ?></strong>
                                                                                <span class="<?php echo htmlspecialchars(paymentStatusBadgeClass((string)($payment['payment_status'] ?? 'pending'))); ?>">
                                                                                    <?php echo htmlspecialchars(formatPaymentStatusLabel((string)($payment['payment_status'] ?? 'pending'))); ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="history-item-meta">
                                                                                <span><i class="far fa-calendar-alt me-1"></i><?php echo !empty($payment['payment_date']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$payment['payment_date']))) : '—'; ?></span>
                                                                                <span><i class="fas fa-euro-sign me-1"></i><?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></span>
                                                                                <span><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars(formatPaymentTypeLabel((string)($payment['payment_type'] ?? 'product'))); ?></span>
                                                                            </div>
                                                                            <?php if (!empty($payment['transaction_id'])): ?>
                                                                                <div class="history-transaction">
                                                                                    Συναλλαγή: <?php echo htmlspecialchars((string)$payment['transaction_id']); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </article>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </section>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if (($user['role'] ?? '') === 'parent'): ?>
                                        <tr id="<?php echo $inlineRowId; ?>" class="children-preview-row user-preview-row <?php echo $managedParentId === $userId ? '' : 'd-none'; ?>" data-preview-for="<?php echo $userId; ?>">
                                            <td colspan="7">
                                                <div class="children-preview-card">
                                                    <div class="children-preview-header">
                                                        <div>
                                                            <h6 class="mb-1"><i class="fas fa-child me-2"></i>Παιδιά Γονέα</h6>
                                                            <p class="text-muted mb-0">
                                                                <?php if (!empty($inlineChildren)): ?>
                                                                    Βρέθηκαν <?php echo count($inlineChildren); ?> παιδί/ά για αυτόν τον γονέα.
                                                                <?php else: ?>
                                                                    Δεν υπάρχουν ακόμη καταχωρημένα παιδιά.
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-primary-custom users-action-btn js-open-create-child"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#childModal"
                                                                data-parent-id="<?php echo $userId; ?>"
                                                                data-parent-name="<?php echo htmlspecialchars(trim((string)($user['name'] ?? '') . ' ' . (string)($user['surname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                                            >
                                                                <i class="fas fa-plus me-1"></i>Προσθήκη
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($inlineChildren)): ?>
                                                        <div class="children-preview-grid">
                                                            <?php foreach ($inlineChildren as $child): ?>
                                                                <div class="child-preview-item">
                                                                    <div class="child-preview-main">
                                                                        <strong><?php echo htmlspecialchars(trim((string)($child['name'] ?? '') . ' ' . (string)($child['surname'] ?? ''))); ?></strong>
                                                                        <span><?php echo !empty($child['date_of_birth']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$child['date_of_birth']))) : '—'; ?></span>
                                                                        <span>Τάξη: <?php echo htmlspecialchars((string)($child['school_class'] ?? '—')); ?></span>
                                                                    </div>
                                                                    <div class="child-preview-actions">
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-outline-primary users-action-btn users-action-btn--compact js-open-edit-child"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#childModal"
                                                                            data-parent-id="<?php echo $userId; ?>"
                                                                            data-parent-name="<?php echo htmlspecialchars(trim((string)($user['name'] ?? '') . ' ' . (string)($user['surname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                                                            data-child-id="<?php echo (int)($child['child_id'] ?? 0); ?>"
                                                                            data-child-name="<?php echo htmlspecialchars((string)($child['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                            data-child-surname="<?php echo htmlspecialchars((string)($child['surname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                            data-child-dob="<?php echo htmlspecialchars((string)($child['date_of_birth'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                            data-child-class="<?php echo htmlspecialchars((string)($child['school_class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        >
                                                                            <i class="fas fa-edit me-1"></i>Επεξεργασία
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-outline-danger users-action-btn users-action-btn--compact js-open-delete-child"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteChildModal"
                                                                            data-parent-id="<?php echo $userId; ?>"
                                                                            data-child-id="<?php echo (int)($child['child_id'] ?? 0); ?>"
                                                                            data-child-name="<?php echo htmlspecialchars(trim((string)($child['name'] ?? '') . ' ' . (string)($child['surname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        >
                                                                            <i class="fas fa-trash-alt me-1"></i>Διαγραφή
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="createParentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="action" value="create_parent">

                <div class="modal-header modal-brand-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Δημιουργία Γονέα</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Όνομα *</strong></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Επώνυμο *</strong></label>
                            <input type="text" name="surname" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Email *</strong></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Τηλέφωνο</strong></label>
                            <input type="text" name="phone_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Κωδικός *</strong></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Κατάσταση</strong></label>
                            <select name="account_status" class="form-select">
                                <option value="active" selected>Ενεργός</option>
                                <option value="pending">Σε Αναμονή</option>
                                <option value="approved">Εγκεκριμένος</option>
                                <option value="waiting_payment">Αναμονή Πληρωμής</option>
                                <option value="rejected">Απορριφθείς</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary modal-cancel-btn" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Δημιουργία
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" id="editUserForm">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="modal-header modal-brand-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Επεξεργασία Χρήστη</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-dark d-none" id="editProtectedNotice">
                        Ο `admin 1` είναι προστατευμένος. Μπορείς να αλλάξεις μόνο βασικά στοιχεία και κωδικό, όχι ρόλο ή κατάσταση.
                    </div>
                    <div class="alert alert-info d-none" id="editSelfNotice">
                        Επεξεργάζεσαι τον δικό σου λογαριασμό. Ο ρόλος και η κατάσταση παραμένουν ασφαλισμένα σε `admin / active`.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Όνομα *</strong></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Επώνυμο *</strong></label>
                            <input type="text" name="surname" id="edit_surname" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Email *</strong></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Τηλέφωνο</strong></label>
                            <input type="text" name="phone_number" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Ρόλος</strong></label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="parent">Γονέας</option>
                                <option value="admin">Διαχειριστής</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Κατάσταση</strong></label>
                            <select name="account_status" id="edit_status" class="form-select">
                                <option value="pending">Σε Αναμονή</option>
                                <option value="approved">Εγκεκριμένος</option>
                                <option value="rejected">Απορριφθείς</option>
                                <option value="waiting_payment">Αναμονή Πληρωμής</option>
                                <option value="active">Ενεργός</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Νέος Κωδικός</strong></label>
                            <input type="password" name="password" id="edit_password" class="form-control" minlength="6" placeholder="Άφησέ το κενό αν δεν θέλεις αλλαγή">
                        </div>
                        <div class="col-12 d-none" id="rejectionMessageGroup">
                            <label class="form-label"><strong>Μήνυμα Απόρριψης *</strong></label>
                            <textarea name="rejection_message" id="edit_rejection_message" class="form-control" rows="4" placeholder="Γράψε το μήνυμα που θα σταλεί στον γονέα"></textarea>
                            <small class="text-muted">Το μήνυμα αποστέλλεται με email όταν η κατάσταση γίνει «Απορριφθείς».</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary modal-cancel-btn" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Αποθήκευση
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" id="deleteUserForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="delete_user_id">
                <input type="hidden" id="delete_user_status" value="">
                <input type="hidden" id="delete_user_status_label" value="">

                <div class="modal-header modal-brand-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση Διαγραφής</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body text-center">
                    <p class="mb-2 fw-semibold">Θέλεις σίγουρα να διαγράψεις αυτόν τον χρήστη;</p>
                    <p class="mb-1" id="delete_user_name">—</p>
                    <p class="text-muted mb-0" id="delete_user_email">—</p>
                    <div class="alert alert-warning mt-3 mb-0 d-none" id="deleteUserExtraWarning">
                        <div class="mb-1">
                            Ο χρήστης είναι σε κατάσταση <strong id="delete_user_warning_status">—</strong>.
                        </div>
                        <div id="delete_user_history_warning" class="d-none">
                            Έχει επίσης <strong id="delete_user_history_counts">0 παραγγελίες / 0 πληρωμές</strong>.
                        </div>
                        <div class="mt-2">
                            Αν συνεχίσεις, η διαγραφή θα είναι οριστική και θα αφαιρεθεί και το σχετικό ιστορικό του χρήστη.
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Όχι</button>
                    <button type="submit" class="btn btn-danger px-4">Ναι, διαγραφή</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteUserFinalConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-brand-header">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Οριστική Επιβεβαίωση</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-2 fw-semibold">Επιβεβαίωσε ότι θέλεις να συνεχίσεις.</p>
                <p class="mb-0 text-muted" id="deleteUserFinalConfirmMessage">—</p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4 modal-cancel-btn" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" class="btn btn-danger px-4" id="deleteUserFinalConfirmButton">Ναι, οριστική διαγραφή</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="childModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" id="childForm">
                <input type="hidden" name="action" id="child_form_action" value="create_child">
                <input type="hidden" name="parent_user_id" id="child_parent_user_id" value="">
                <input type="hidden" name="child_id" id="child_id" value="">

                <div class="modal-header modal-brand-header">
                    <h5 class="modal-title" id="childModalTitle"><i class="fas fa-child me-2"></i>Προσθήκη Παιδιού</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        Διαχειρίζεσαι παιδιά για τον γονέα: <strong id="child_parent_name_display">—</strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Όνομα *</strong></label>
                            <input type="text" name="child_name" id="child_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Επώνυμο *</strong></label>
                            <input type="text" name="child_surname" id="child_surname" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Ημερομηνία Γέννησης *</strong></label>
                            <input type="date" name="child_date_of_birth" id="child_date_of_birth" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Τάξη *</strong></label>
                            <input type="text" name="child_school_class" id="child_school_class" class="form-control" required placeholder="π.χ. 5A">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary modal-cancel-btn" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Αποθήκευση
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteChildModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="action" value="delete_child">
                <input type="hidden" name="parent_user_id" id="delete_child_parent_user_id">
                <input type="hidden" name="child_id" id="delete_child_id">

                <div class="modal-header modal-brand-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Διαγραφή Παιδιού</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body text-center">
                    <p class="mb-2 fw-semibold">Θέλεις σίγουρα να διαγράψεις αυτό το παιδί;</p>
                    <p class="mb-0 text-muted" id="delete_child_name">—</p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Όχι</button>
                    <button type="submit" class="btn btn-danger px-4">Ναι, διαγραφή</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-brand-header">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Διαγραφή Προγράμματος</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-2 fw-semibold">Θέλεις σίγουρα να διαγράψεις αυτό το πρόγραμμα;</p>
                <p class="mb-1" id="delete_schedule_feature">—</p>
                <p class="text-muted mb-0" id="delete_schedule_dates">—</p>
                <input type="hidden" id="delete_schedule_form_id" value="">
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" class="btn btn-danger px-4" id="confirmDeleteScheduleButton">Ναι, διαγραφή</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-users.js"></script>
</body>
</html>
