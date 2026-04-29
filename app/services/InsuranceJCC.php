<?php
// Arxeio: app\services\InsuranceJCC.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora payment flow, opote kratame ta redirects/responses synexi me ton provider.
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/PaymentReceiptService.php';

class InsuranceJccService
{
    private mysqli $conn;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = trim((string) ($_GET['action'] ?? ''));

        if ($method === 'GET' && $action === 'checkout') {
            $this->startCheckout();
            return;
        }

        if ($method === 'GET') {
            $this->handleCallback();
            return;
        }

        $this->redirectToProfile('failed', 'Method not allowed.');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function startCheckout(): void
    {
        auth_require_role('parent', [
            'mode' => 'redirect',
            'redirect' => APP_BASE_URL . '/public/login.php',
        ]);

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->redirectToProfile('failed', 'Δεν βρέθηκε ενεργός λογαριασμός.');
            return;
        }

        try {
            $this->conn->begin_transaction();

            $childIds = $this->getPendingInsuranceChildrenIds($userId);
            if (empty($childIds)) {
                throw new RuntimeException('Η ασφάλεια έχει ήδη πληρωθεί για όλα τα παιδιά.');
            }

            $insurancePrice = $this->getInsurancePrice();
            if ($insurancePrice <= 0) {
                throw new RuntimeException('Δεν βρέθηκε έγκυρη τιμή ασφάλειας.');
            }

            $amount = $insurancePrice * count($childIds);
            $paymentId = $this->createInsurancePayment($userId, $amount);
            $orderNumber = sprintf('INS-%d-%d-%s', $userId, $paymentId, bin2hex(random_bytes(5)));

            $callbackBase = APP_BASE_URL . '/app/services/InsuranceJCC.php';
            $returnUrl = $callbackBase . '?' . http_build_query(['pid' => (string) $paymentId]);
            $failUrl = $callbackBase . '?' . http_build_query([
                'pid' => (string) $paymentId,
                'status' => 'fail',
            ]);

            $jccResponse = $this->registerJccOrder(
                $orderNumber,
                $amount,
                $returnUrl,
                $failUrl,
                $this->getUserEmail($userId)
            );

            $gatewayOrderId = (string) ($jccResponse['orderId'] ?? '');
            $this->storeCheckoutContext($gatewayOrderId, $paymentId, $childIds);
            $this->insertLog(
                $userId,
                'PAYMENT_CREATED',
                'User created insurance payment (ID: ' . $paymentId . ') for ' . count($childIds) . ' children; JCC orderId: ' . $gatewayOrderId
            );

            $this->conn->commit();
            $this->redirectToExternalUrl((string) ($jccResponse['formUrl'] ?? ''));
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->redirectToProfile('failed', $e->getMessage());
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function handleCallback(): void
    {
        $gatewayOrderId = trim((string) ($_GET['orderId'] ?? $_GET['mdOrder'] ?? ''));
        $paymentId = (int) ($_GET['pid'] ?? 0);

        $context = $gatewayOrderId !== '' ? $this->getStoredCheckoutContext($gatewayOrderId) : null;
        if ($paymentId <= 0 && is_array($context)) {
            $paymentId = (int) ($context['payment_id'] ?? 0);
        }

        if ($gatewayOrderId === '' || $paymentId <= 0) {
            $this->redirectToProfile('failed', 'Λείπουν απαραίτητα στοιχεία πληρωμής.');
            return;
        }

        try {
            $payment = $this->getInsurancePayment($paymentId);
            if ($payment === null) {
                throw new RuntimeException('Δεν βρέθηκε πληρωμή ασφάλειας.');
            }

            $userId = (int) ($payment['user_id'] ?? 0);
            $statusResponse = $this->getJccOrderStatus($gatewayOrderId);
            $orderStatus = (int) ($statusResponse['orderStatus'] ?? -1);
            $nextStatus = $this->mapOrderStatusToPaymentStatus($orderStatus);
            $finalStatus = $this->resolveFinalPaymentStatus((string) ($payment['payment_status'] ?? 'pending'), $nextStatus);
            $transactionId = $this->resolveTransactionId($statusResponse, $gatewayOrderId);

            $this->conn->begin_transaction();

            $this->updatePaymentStatus($paymentId, $userId, $finalStatus, $transactionId);

            if ($finalStatus === 'completed') {
                $childIds = [];
                if (is_array($context) && isset($context['child_ids']) && is_array($context['child_ids'])) {
                    $childIds = array_values(array_filter(array_map('intval', $context['child_ids']), static function (int $id): bool {
                        return $id > 0;
                    }));
                }

                if (empty($childIds)) {
                    $childIds = $this->getPendingInsuranceChildrenIds($userId);
                }

                $this->insertInsurancePaymentsForChildren($paymentId, $childIds);
            }

            $this->insertLog(
                $userId,
                $this->mapLogActionByPaymentStatus($finalStatus),
                'Insurance JCC payment ' . $finalStatus . '. Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
            );

            $this->conn->commit();
            $this->clearStoredCheckoutContext($gatewayOrderId);

            if ($finalStatus === 'completed') {
                try {
                    $receiptSummary = (new PaymentReceiptService($this->conn))
                        ->sendReceiptForPayments($userId, [$paymentId], 'JCC insurance');

                    $this->insertLog(
                        $userId,
                        'PAYMENT_RECEIPT_SENT',
                        'Receipt email sent for payment ID: ' . $paymentId . ' to ' . $receiptSummary['email']
                    );
                } catch (Throwable $receiptError) {
                    $this->insertLog(
                        $userId,
                        'PAYMENT_RECEIPT_FAILED',
                        'Receipt email failed for payment ID: ' . $paymentId . '. Error: ' . $receiptError->getMessage()
                    );
                }
            }

            $this->redirectToProfile($finalStatus, $this->buildRedirectMessage($finalStatus));
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->redirectToProfile('failed', $e->getMessage());
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getPendingInsuranceChildrenIds(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT c.child_id
             FROM Children c
             WHERE c.user_id = ?
               AND NOT EXISTS (
                   SELECT 1
                   FROM InsurancePayments ip
                   INNER JOIN Payments p ON p.payment_id = ip.payment_id
                   WHERE ip.child_id = c.child_id
                     AND p.user_id = c.user_id
                     AND p.payment_type = 'insurance'
                     AND p.payment_status = 'completed'
               )"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to load children without insurance payment.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $childIds = [];
        while ($result && $row = $result->fetch_assoc()) {
            $childId = (int) ($row['child_id'] ?? 0);
            if ($childId > 0) {
                $childIds[] = $childId;
            }
        }

        $stmt->close();
        return $childIds;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getInsurancePrice(): float
    {
        $result = $this->conn->query('SELECT insurance_price FROM PricingSettings LIMIT 1');
        if ($result === false) {
            throw new RuntimeException('Failed to load insurance price.');
        }

        $row = $result->fetch_assoc();
        return (float) ($row['insurance_price'] ?? 0.0);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getUserEmail(int $userId): string
    {
        $stmt = $this->conn->prepare('SELECT email FROM Users WHERE user_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to load user email.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return trim((string) ($row['email'] ?? ''));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function createInsurancePayment(int $userId, float $amount): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO Payments (user_id, amount, payment_status, payment_type)
             VALUES (?, ?, 'pending', 'insurance')"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare insurance payment insert.');
        }

        $stmt->bind_param('id', $userId, $amount);
        $stmt->execute();
        $paymentId = (int) $stmt->insert_id;
        $stmt->close();

        return $paymentId;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getInsurancePayment(int $paymentId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT payment_id, user_id, payment_status
             FROM Payments
             WHERE payment_id = ? AND payment_type = 'insurance'
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to verify insurance payment.');
        }

        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function updatePaymentStatus(int $paymentId, int $userId, string $status, string $transactionId): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Payments
             SET payment_status = ?, transaction_id = ?
             WHERE payment_id = ? AND user_id = ? LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare payment update.');
        }

        $stmt->bind_param('ssii', $status, $transactionId, $paymentId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();

            $existsStmt = $this->conn->prepare('SELECT 1 FROM Payments WHERE payment_id = ? AND user_id = ? LIMIT 1');
            if ($existsStmt === false) {
                throw new RuntimeException('Failed to verify payment existence.');
            }

            $existsStmt->bind_param('ii', $paymentId, $userId);
            $existsStmt->execute();
            $existsResult = $existsStmt->get_result();
            $exists = (bool) ($existsResult && $existsResult->fetch_assoc());
            $existsStmt->close();

            if (!$exists) {
                throw new RuntimeException('Payment not found for update.');
            }

            return;
        }

        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertInsurancePaymentsForChildren(int $paymentId, array $childIds): void
    {
        if (empty($childIds)) {
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO InsurancePayments (payment_id, child_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE child_id = VALUES(child_id)'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare insurance payment-children insert.');
        }

        foreach ($childIds as $childId) {
            $id = (int) $childId;
            if ($id <= 0) {
                continue;
            }

            $stmt->bind_param('ii', $paymentId, $id);
            $stmt->execute();
        }

        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function registerJccOrder(
        string $orderNumber,
        float $amount,
        string $returnUrl,
        string $failUrl,
        string $email
    ): array {
        $requestFields = [
            'userName' => JCC_API_LOGIN,
            'password' => JCC_API_PASSWORD,
            'orderNumber' => $orderNumber,
            'amount' => (string) ((int) round($amount * 100)),
            'currency' => '978',
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            'language' => 'el',
            'description' => 'Insurance payment',
        ];

        if ($email !== '') {
            $requestFields['email'] = $email;
        }

        return $this->callJccEndpoint(JCC_REGISTER_URL, $requestFields, 'register');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getJccOrderStatus(string $orderId): array
    {
        return $this->callJccEndpoint(JCC_ORDER_STATUS_URL, [
            'userName' => JCC_API_LOGIN,
            'password' => JCC_API_PASSWORD,
            'orderId' => $orderId,
        ], 'status');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function callJccEndpoint(string $url, array $fields, string $context): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for JCC integration.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize JCC ' . $context . ' request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false || $curlError !== '') {
            throw new RuntimeException('Failed to connect to JCC gateway: ' . $curlError);
        }

        $response = json_decode(ltrim((string) $rawResponse, "\xEF\xBB\xBF"), true);
        if (!is_array($response)) {
            $snippet = substr(trim((string) $rawResponse), 0, 220);
            throw new RuntimeException('Invalid response from JCC ' . $context . ' API (HTTP ' . $httpCode . '): ' . $snippet);
        }

        if (($response['errorCode'] ?? '0') !== '0' && ($response['errorCode'] ?? 0) !== 0) {
            $errorMessage = (string) ($response['errorMessage'] ?? 'Unknown JCC error.');
            throw new RuntimeException('JCC ' . $context . ' error: ' . $errorMessage);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('JCC ' . $context . ' request failed with HTTP ' . $httpCode . '.');
        }

        return $response;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function mapOrderStatusToPaymentStatus(int $orderStatus): string
    {
        if ($orderStatus === 2) {
            return 'completed';
        }

        if ($orderStatus === 0 || $orderStatus === 1 || $orderStatus === 5) {
            return 'pending';
        }

        if ($orderStatus === 4) {
            return 'refunded';
        }

        return 'failed';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resolveFinalPaymentStatus(string $currentStatus, string $nextStatus): string
    {
        if ($currentStatus === 'completed') {
            return 'completed';
        }

        if ($currentStatus === 'refunded' && $nextStatus !== 'completed') {
            return 'refunded';
        }

        return $nextStatus;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resolveTransactionId(array $statusResponse, string $fallbackOrderId): string
    {
        if (isset($statusResponse['transactionAttributes']) && is_array($statusResponse['transactionAttributes'])) {
            foreach ($statusResponse['transactionAttributes'] as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $name = strtolower((string) ($attribute['name'] ?? ''));
                $value = trim((string) ($attribute['value'] ?? ''));
                if ($name === 'paymentnetrefnum' && $value !== '') {
                    return $value;
                }
            }
        }

        $authRefNum = trim((string) ($statusResponse['authRefNum'] ?? ''));
        if ($authRefNum !== '') {
            return $authRefNum;
        }

        return $fallbackOrderId;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function mapLogActionByPaymentStatus(string $paymentStatus): string
    {
        if ($paymentStatus === 'completed') {
            return 'PAYMENT_COMPLETED';
        }

        if ($paymentStatus === 'pending') {
            return 'PAYMENT_PENDING';
        }

        return 'PAYMENT_FAILED';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertLog(int $userId, string $action, string $description): void
    {
        $stmt = $this->conn->prepare('INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)');
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare log insert.');
        }

        $stmt->bind_param('iss', $userId, $action, $description);
        $stmt->execute();
        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildRedirectMessage(string $paymentStatus): string
    {
        if ($paymentStatus === 'completed') {
            return 'Η πληρωμή ασφάλειας ολοκληρώθηκε επιτυχώς.';
        }

        if ($paymentStatus === 'pending') {
            return 'Η πληρωμή είναι σε αναμονή επιβεβαίωσης από την τράπεζα.';
        }

        return 'Η πληρωμή ασφάλειας δεν ολοκληρώθηκε.';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function redirectToProfile(string $status, string $message): void
    {
        $this->redirectToExternalUrl(
            APP_BASE_URL . '/public/parent/profile.php?' . http_build_query([
                'insurance_payment_status' => $status,
                'insurance_payment_message' => $message,
            ])
        );
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function redirectToExternalUrl(string $url): void
    {
        if ($url === '') {
            throw new RuntimeException('Redirect URL is empty.');
        }

        header('Location: ' . $url);
        exit;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function storeCheckoutContext(string $gatewayOrderId, int $paymentId, array $childIds): void
    {
        if ($gatewayOrderId === '') {
            return;
        }

        if (!isset($_SESSION['insurance_checkout_context']) || !is_array($_SESSION['insurance_checkout_context'])) {
            $_SESSION['insurance_checkout_context'] = [];
        }

        $_SESSION['insurance_checkout_context'][$gatewayOrderId] = [
            'payment_id' => $paymentId,
            'child_ids' => array_values(array_filter(array_map('intval', $childIds), static function (int $id): bool {
                return $id > 0;
            })),
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getStoredCheckoutContext(string $gatewayOrderId): ?array
    {
        $context = $_SESSION['insurance_checkout_context'][$gatewayOrderId] ?? null;
        return is_array($context) ? $context : null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function clearStoredCheckoutContext(string $gatewayOrderId): void
    {
        if (isset($_SESSION['insurance_checkout_context'][$gatewayOrderId])) {
            unset($_SESSION['insurance_checkout_context'][$gatewayOrderId]);
        }
    }
}

$service = new InsuranceJccService($conn);
$service->handleRequest();
$conn->close();
