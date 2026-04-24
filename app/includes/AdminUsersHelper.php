<?php

final class AdminUsersHelper
{
    // Checks whether a user is the protected primary admin account.
    public static function isPrimaryProtectedAdmin(int $userId): bool
    {
        return $userId === 1;
    }

    // Normalizes user roles to the supported admin or parent values.
    public static function normalizeUserRole(string $role): string
    {
        return in_array($role, ['admin', 'parent'], true) ? $role : 'parent';
    }

    // Normalizes the account status used by the users dashboard.
    public static function normalizeUserStatus(string $status): string
    {
        $allowed = ['pending', 'approved', 'rejected', 'waiting_payment', 'active'];
        return in_array($status, $allowed, true) ? $status : 'pending';
    }

    // Normalizes the users list sort key.
    public static function normalizeUserSort(string $sort): string
    {
        $allowed = ['pending_first', 'newest', 'oldest', 'name_az', 'status_az'];
        return in_array($sort, $allowed, true) ? $sort : 'pending_first';
    }

    // Normalizes the registration schedule status.
    public static function normalizeScheduleStatus(string $status): string
    {
        return in_array($status, ['active', 'inactive'], true) ? $status : 'inactive';
    }

    // Normalizes the schedule feature name.
    public static function normalizeScheduleFeature(string $feature): string
    {
        $normalized = trim((string)$feature);
        if ($normalized === 'cleanup_applications' || $normalized === 'cleanuo_submissions') {
            $normalized = 'cleanup_submissions';
        }

        $allowed = ['registration', 'delete_users', 'cleanup_submissions'];
        return in_array($normalized, $allowed, true) ? $normalized : 'registration';
    }

    // Converts a schedule feature key into a human-readable label.
    public static function scheduleFeatureLabel(string $feature): string
    {
        $map = [
            'registration' => 'Εγγραφές',
            'delete_users' => 'Διαγραφή Χρηστών',
            'cleanup_submissions' => 'Καθαρισμός Υποβολών',
        ];

        return $map[$feature] ?? $feature;
    }

    // Parses a local date-time value from form input.
    public static function normalizeDateTimeLocalInput(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $dateTime = DateTime::createFromFormat('d/m/Y H:i', $trimmed);

        if (!$dateTime instanceof DateTime) {
            // Keeps compatibility with older datetime-local submissions.
            $dateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $trimmed);
            if (!$dateTime instanceof DateTime) {
                return null;
            }
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    // Converts a stored date-time string to datetime-local input format.
    public static function toDateTimeLocalValue(?string $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d\\TH:i', $timestamp) : '';
    }

    // Stores a flash message and redirects back to the users page.
    public static function redirectWithFlash(string $message, string $type = 'info', int $manageChildrenUserId = 0): void
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

    // Returns the translated label for a user role.
    public static function formatRoleLabel(string $role): string
    {
        return $role === 'admin' ? 'Διαχειριστής' : 'Γονέας';
    }

    // Returns the translated label for an account status.
    public static function formatStatusLabel(string $status): string
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

    // Returns the CSS class for the role badge.
    public static function roleBadgeClass(string $role): string
    {
        return $role === 'admin'
            ? 'status-pill status-pill--accent'
            : 'status-pill status-pill--neutral';
    }

    // Returns the CSS class for the account status badge.
    public static function statusBadgeClass(string $status): string
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

    // Returns the translated label for an order status.
    public static function formatOrderStatusLabel(string $status): string
    {
        $map = [
            'pending' => 'Σε Αναμονή',
            'paid' => 'Πληρωμένη',
            'cancelled' => 'Ακυρωμένη',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    // Returns the CSS class for an order status badge.
    public static function orderStatusBadgeClass(string $status): string
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

    // Returns the translated label for a payment status.
    public static function formatPaymentStatusLabel(string $status): string
    {
        $map = [
            'pending' => 'Σε Αναμονή',
            'completed' => 'Ολοκληρωμένη',
            'failed' => 'Αποτυχημένη',
            'refunded' => 'Επιστροφή',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    // Returns the CSS class for a payment status badge.
    public static function paymentStatusBadgeClass(string $status): string
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

    // Returns the translated label for a payment type.
    public static function formatPaymentTypeLabel(string $type): string
    {
        $map = [
            'membership' => 'Συνδρομή',
            'insurance' => 'Ασφάλεια',
            'product' => 'Προϊόν',
        ];

        return $map[$type] ?? ucfirst($type);
    }

    // Escapes table cell content for Excel export.
    public static function exportCellText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    // Formats export dates for spreadsheet output.
    public static function formatExportDate(?string $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '—';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : (string)$value;
    }

    // Summarizes child records for the Excel export.
    public static function buildChildExportSummary(array $children): string
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

    // Summarizes which children have completed insurance.
    public static function buildInsuredChildrenSummary(array $children, array $insuredChildIds, bool $hasCompletedInsurance): string
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

    // Summarizes payments of a specific type for the export.
    public static function buildPaymentSummary(array $payments, string $paymentType): string
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
                self::formatPaymentTypeLabel((string)($payment['payment_type'] ?? '')),
                self::formatPaymentStatusLabel((string)($payment['payment_status'] ?? 'pending')),
                self::formatExportDate($payment['payment_date'] ?? null),
                number_format((float)($payment['amount'] ?? 0), 2),
                trim((string)($payment['transaction_id'] ?? '')) !== '' ? (string)$payment['transaction_id'] : '—'
            );
        }

        return implode(" \n", $parts);
    }

    // Summarizes product transactions with non-empty transaction IDs.
    public static function buildProductTransactionSummary(array $payments): string
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

    // Summarizes order items for the users export.
    public static function buildOrdersExportSummary(array $orders, array $orderItemsByOrderId): string
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
                self::formatOrderStatusLabel((string)($order['order_status'] ?? 'pending')),
                self::formatExportDate($order['created_at'] ?? null),
                number_format((float)($order['total_price'] ?? 0), 2),
                !empty($itemParts) ? implode(', ', $itemParts) : '—'
            );
        }

        if (empty($parts)) {
            return '—';
        }

        return implode(" \n", $parts);
    }
}
