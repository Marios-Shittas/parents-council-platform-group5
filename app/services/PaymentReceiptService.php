<?php
declare(strict_types=1);

require_once __DIR__ . '/ApprovalMailer.php';

class PaymentReceiptService
{
    private mysqli $conn;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

            $mailer->sendTextEmail($recipient['email'], $subject, $textBody);
        } catch (Throwable $smtpError) {
            $fallbackBody = str_replace("\r\n", "\n", $textBody);

            $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
            if (!mail($recipient['email'], $subject, $fallbackBody, $headers)) {
                throw new RuntimeException('Failed to send payment receipt email: ' . $smtpError->getMessage());
            }
        }

        return [
            'email' => $recipient['email'],
            'payment_count' => count($payments),
            'total_amount' => $totalAmount,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

            $lines[] =
                'Payment ID: #' . $paymentId . "\r\n" .
                'Περιγραφή: ' . $description . "\r\n" .
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function loadProductItems(int $paymentId): array
    {
        if ($paymentId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT pd.quantity, pd.size, p.product_name ' .
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
            $quantity = (int) ($row['quantity'] ?? 1);
            $size = trim((string) ($row['size'] ?? ''));

            $label = $productName . ' x' . max($quantity, 1);
            if ($size !== '') {
                $label .= ' (Size: ' . $size . ')';
            }

            $items[] = $label;
        }

        $stmt->close();

        return $items;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
