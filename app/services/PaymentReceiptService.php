<?php
declare(strict_types=1);

require_once __DIR__ . '/ApprovalMailer.php';
require_once __DIR__ . '/../includes/product_sizes.php';

class PaymentReceiptService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function sendReceiptForPayments(int $userId, array $paymentIds, string $sourceLabel = 'JCC'): array
    {
        $normalizedPaymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds), static function (int $id): bool {
            return $id > 0;
        })));

        if ($userId <= 0 || empty($normalizedPaymentIds)) {
            throw new InvalidArgumentException('Receipt requires a valid user and payment IDs.');
        }

        $recipient = $this->getReceiptRecipient($userId);
        $payments = $this->getPayments($userId, $normalizedPaymentIds);
        if (empty($payments)) {
            throw new RuntimeException('No payments found to include in receipt email.');
        }

        $totalAmount = array_reduce($payments, static function (float $carry, array $payment): float {
            return $carry + (float) ($payment['amount'] ?? 0);
        }, 0.0);

        $subject = 'Απόδειξη πληρωμής - Parents Council';
        $textBody = $this->buildReceiptTextBody($recipient, $payments, $sourceLabel, $totalAmount);
        $this->dispatchReceiptEmail($recipient['email'], $subject, $textBody);

        return [
            'email' => $recipient['email'],
            'payment_count' => count($payments),
            'total_amount' => $totalAmount,
        ];
    }

    public function sendReceiptForProductOrderPayment(int $paymentId, string $sourceLabel = 'JCC'): array
    {
        $paymentId = (int) $paymentId;
        if ($paymentId <= 0) {
            throw new InvalidArgumentException('Receipt requires a valid payment ID.');
        }

        $payment = $this->getProductOrderPayment($paymentId);
        if ($payment === null) {
            throw new RuntimeException('No product payment found to include in receipt email.');
        }

        $recipient = $this->resolveOrderPaymentRecipient($payment);
        $amount = (float) ($payment['amount'] ?? 0);
        $subject = 'Απόδειξη πληρωμής - Parents Council';
        $textBody = $this->buildReceiptTextBody($recipient, [$payment], $sourceLabel, $amount);
        $this->dispatchReceiptEmail($recipient['email'], $subject, $textBody);

        return [
            'email' => $recipient['email'],
            'payment_count' => 1,
            'total_amount' => $amount,
        ];
    }

    private function getReceiptRecipient(int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT name, surname, email FROM Users WHERE user_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare receipt recipient query.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!is_array($row)) {
            throw new RuntimeException('Receipt recipient was not found.');
        }

        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Receipt recipient email is invalid.');
        }

        return [
            'name' => trim((string) ($row['name'] ?? '')),
            'surname' => trim((string) ($row['surname'] ?? '')),
            'email' => $email,
        ];
    }

    private function getPayments(int $userId, array $paymentIds): array
    {
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
        $types = 'i' . str_repeat('i', count($paymentIds));
        $params = array_merge([$userId], $paymentIds);

        $sql =
            'SELECT payment_id, amount, payment_date, payment_status, payment_type, transaction_id ' .
            'FROM Payments WHERE user_id = ? AND payment_id IN (' . $placeholders . ') ORDER BY payment_date ASC, payment_id ASC';

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare payments query for receipt.');
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $payments[] = [
                'payment_id' => (int) ($row['payment_id'] ?? 0),
                'amount' => (float) ($row['amount'] ?? 0),
                'payment_date' => (string) ($row['payment_date'] ?? ''),
                'payment_status' => (string) ($row['payment_status'] ?? ''),
                'payment_type' => (string) ($row['payment_type'] ?? ''),
                'transaction_id' => trim((string) ($row['transaction_id'] ?? '')),
            ];
        }

        $stmt->close();

        return $payments;
    }

    private function getProductOrderPayment(int $paymentId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT
                p.payment_id,
                p.user_id,
                p.order_id,
                p.amount,
                p.payment_date,
                p.payment_status,
                p.payment_type,
                p.transaction_id,
                o.customer_name,
                o.customer_surname,
                o.customer_email,
                o.customer_phone,
                o.student_name,
                o.student_class
             FROM Payments p
             LEFT JOIN Orders o ON o.order_id = p.order_id
             WHERE p.payment_id = ?
               AND p.payment_type = ? 
             LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare product payment receipt query.');
        }

        $paymentType = 'product';
        $stmt->bind_param('is', $paymentId, $paymentType);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!is_array($row)) {
            return null;
        }

        return [
            'payment_id' => (int) ($row['payment_id'] ?? 0),
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : 0,
            'order_id' => isset($row['order_id']) ? (int) $row['order_id'] : 0,
            'amount' => (float) ($row['amount'] ?? 0),
            'payment_date' => (string) ($row['payment_date'] ?? ''),
            'payment_status' => (string) ($row['payment_status'] ?? ''),
            'payment_type' => (string) ($row['payment_type'] ?? ''),
            'transaction_id' => trim((string) ($row['transaction_id'] ?? '')),
            'customer_name' => trim((string) ($row['customer_name'] ?? '')),
            'customer_surname' => trim((string) ($row['customer_surname'] ?? '')),
            'customer_email' => trim((string) ($row['customer_email'] ?? '')),
            'customer_phone' => trim((string) ($row['customer_phone'] ?? '')),
            'student_name' => trim((string) ($row['student_name'] ?? '')),
            'student_class' => trim((string) ($row['student_class'] ?? '')),
        ];
    }

    private function resolveOrderPaymentRecipient(array $payment): array
    {
        $email = trim((string) ($payment['customer_email'] ?? ''));
        $name = trim((string) ($payment['customer_name'] ?? ''));
        $surname = trim((string) ($payment['customer_surname'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
            ];
        }

        $userId = (int) ($payment['user_id'] ?? 0);
        if ($userId > 0) {
            return $this->getReceiptRecipient($userId);
        }

        throw new RuntimeException('Receipt recipient email is invalid.');
    }

    private function dispatchReceiptEmail(string $recipientEmail, string $subject, string $textBody): void
    {
        try {
            $mailer = new ApprovalMailer([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);

            $mailer->sendTextEmail($recipientEmail, $subject, $textBody);
        } catch (Throwable $smtpError) {
            $fallbackBody = str_replace("\r\n", "\n", $textBody);

            $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
            if (!mail($recipientEmail, $subject, $fallbackBody, $headers)) {
                throw new RuntimeException('Failed to send payment receipt email: ' . $smtpError->getMessage());
            }
        }
    }

    private function buildReceiptTextBody(array $recipient, array $payments, string $sourceLabel, float $totalAmount): string
    {
        $fullName = trim(($recipient['name'] ?? '') . ' ' . ($recipient['surname'] ?? ''));
        $displayName = $fullName !== '' ? $fullName : $recipient['email'];

        $lines = [];
        foreach ($payments as $payment) {
            $paymentId = (int) ($payment['payment_id'] ?? 0);
            $amount = (float) ($payment['amount'] ?? 0);
            $transactionId = trim((string) ($payment['transaction_id'] ?? ''));
            $paymentDate = (string) ($payment['payment_date'] ?? '');
            $description = $this->buildPaymentDescription($payment);
            $studentName = trim((string) ($payment['student_name'] ?? ''));
            $studentClass = trim((string) ($payment['student_class'] ?? ''));

            $studentLines = '';
            if ($studentName !== '') {
                $studentLines .= "\r\n" . 'Μαθητής/τρια: ' . $studentName;
            }
            if ($studentClass !== '') {
                $studentLines .= "\r\n" . 'Τμήμα: ' . $studentClass;
            }

            $lines[] =
                'Payment ID: #' . $paymentId . "\r\n" .
                'Περιγραφή: ' . $description . "\r\n" .
                ltrim($studentLines . "\r\n", "\r\n") .
                'Ποσό: €' . number_format($amount, 2) . "\r\n" .
                'Ημερομηνία: ' . $this->formatDate($paymentDate) . "\r\n" .
                'Transaction ID: ' . ($transactionId !== '' ? $transactionId : 'N/A');
        }

        return
            "Απόδειξη Πληρωμής\r\n" .
            "\r\n" .
            'Γεια σας ' . $displayName . ",\r\n" .
            'Η πληρωμή σας ολοκληρώθηκε επιτυχώς μέσω ' . $sourceLabel . ".\r\n" .
            "\r\n" .
            implode("\r\n\r\n", $lines) .
            "\r\n\r\n" .
            'Σύνολο: €' . number_format($totalAmount, 2) . "\r\n\r\n" .
            'Αυτό είναι αυτοματοποιημένο μήνυμα. Για απορίες, απαντήστε σε αυτό το email.';
    }

    private function buildPaymentDescription(array $payment): string
    {
        $paymentType = (string) ($payment['payment_type'] ?? '');
        $paymentId = (int) ($payment['payment_id'] ?? 0);

        if ($paymentType === 'membership') {
            return 'Ετήσια συνδρομή μέλους';
        }

        if ($paymentType === 'insurance') {
            $childrenCount = $this->countCoveredChildren($paymentId);
            if ($childrenCount > 0) {
                return 'Ασφάλεια παιδιών (' . $childrenCount . ' παιδιά)';
            }

            return 'Ασφάλεια παιδιών';
        }

        if ($paymentType === 'product') {
            $items = $this->loadProductItems($paymentId);
            if (empty($items)) {
                return 'Αγορά προϊόντων από e-shop';
            }

            return 'Αγορά από e-shop: ' . implode(', ', $items);
        }

        return 'Πληρωμή';
    }

    private function countCoveredChildren(int $paymentId): int
    {
        if ($paymentId <= 0) {
            return 0;
        }

        $stmt = $this->conn->prepare('SELECT COUNT(*) AS children_count FROM InsurancePayments WHERE payment_id = ?');
        if ($stmt === false) {
            return 0;
        }

        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return (int) ($row['children_count'] ?? 0);
    }

    private function loadProductItems(int $paymentId): array
    {
        if ($paymentId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT pd.product_id, pd.quantity, pd.size, p.product_name ' .
            'FROM PaymentsDetails pd ' .
            'INNER JOIN Products p ON p.product_id = pd.product_id ' .
            'WHERE pd.payment_id = ? ' .
            'ORDER BY p.product_name ASC'
        );

        if ($stmt === false) {
            return [];
        }

        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $productName = trim((string) ($row['product_name'] ?? 'Προϊόν'));
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 1);
            $size = trim((string) ($row['size'] ?? ''));

            $label = $productName . ' x' . max($quantity, 1);
            if ($size !== '') {
                $sizeMeta = product_sizes_get_for_product($productId);
                $label .= ' (Size: ' . product_sizes_label_for_value($size, $sizeMeta) . ')';
            }

            $items[] = $label;
        }

        $stmt->close();

        return $items;
    }

    private function formatDate(string $dateTime): string
    {
        if ($dateTime === '') {
            return 'N/A';
        }

        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return $dateTime;
        }

        return date('d/m/Y H:i', $timestamp);
    }
}
