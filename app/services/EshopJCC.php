<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/EshopSettingsService.php';
require_once __DIR__ . '/PaymentReceiptService.php';

class EshopJccService
{
    private mysqli $conn;
    private EshopSettingsService $eshopSettingsService;

    // Leitourgia __construct: xeirizetai to antistoixo kommati tis selidas i tou service.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->eshopSettingsService = new EshopSettingsService($conn);
    }

    // Leitourgia handleRequest: xeirizetai to antistoixo kommati tis selidas i tou service.
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

        if ($method === 'POST') {
            $this->handleCheckoutRegistration($this->wantsJsonResponse());
            return;
        }

        if ($method === 'GET' && $action === 'checkout') {
            $this->handleCheckoutRegistration(false);
            return;
        }

        if ($method === 'GET') {
            $this->handleCallback();
            return;
        }

        $this->respond(405, [
            'success' => false,
            'message' => 'Method not allowed.',
        ]);
    }

    // Leitourgia handleCheckoutRegistration: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function handleCheckoutRegistration(bool $respondWithJson): void
    {
        auth_require_role('parent', [
            'mode' => $respondWithJson ? 'json' : 'redirect',
            'message' => 'Μόνο λογαριασμοί γονέα μπορούν να ολοκληρώσουν αγορές.',
        ]);

        if (!$this->eshopSettingsService->isShopVisible()) {
            $this->respondCheckoutError($respondWithJson, 'Το κατάστημα είναι προσωρινά μη διαθέσιμο. Coming soon.', 403);
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $transactionStarted = false;

        try {
            $this->conn->begin_transaction();
            $transactionStarted = true;

            $order = $this->getPendingOrderForCheckout($userId);
            if ($order === null) {
                throw new RuntimeException('Δεν βρέθηκε ενεργή παραγγελία για πληρωμή.');
            }

            $orderId = (int) $order['order_id'];
            $totalPrice = (float) $order['total_price'];
            $itemsCount = (int) $order['items_count'];

            if ($itemsCount <= 0 || $totalPrice <= 0) {
                throw new RuntimeException('Το καλάθι είναι κενό ή το ποσό πληρωμής δεν είναι έγκυρο.');
            }

            $customerEmail = $this->getUserEmail($userId);
            $paymentId = $this->insertPayment($userId, $totalPrice, 'product');
            $this->insertPaymentDetailsFromOrder($paymentId, $orderId);
            $orderNumber = $this->generateOrderNumber($userId, $orderId, $paymentId);
            $returnUrl = $this->buildCallbackUrl($paymentId, $orderId, false);
            $failUrl = $this->buildCallbackUrl($paymentId, $orderId, true);

            $jccResponse = $this->registerJccOrder(
                $orderNumber,
                $totalPrice,
                $returnUrl,
                $failUrl,
                $customerEmail
            );

            $this->insertLog(
                $userId,
                'PAYMENT_CREATED',
                'User created eshop payment (ID: ' . $paymentId . ') for order ID: ' . $orderId . '; JCC orderId: ' . $jccResponse['orderId']
            );

            $this->storeCheckoutContext(
                (string) $jccResponse['orderId'],
                $userId,
                $orderId,
                $paymentId
            );

            $this->conn->commit();

            if ($respondWithJson) {
                $this->respond(200, [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'redirect_url' => $jccResponse['formUrl'],
                ]);
                return;
            }

            $this->redirectToExternalUrl($jccResponse['formUrl']);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->conn->rollback();
            }

            $this->respondCheckoutError($respondWithJson, $e->getMessage(), 500);
        }
    }

    // Leitourgia handleCallback: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function handleCallback(): void
    {
        $gatewayOrderId = trim((string) ($_GET['orderId'] ?? $_GET['mdOrder'] ?? ''));
        $paymentId = (int) ($_GET['pid'] ?? 0);
        $orderId = (int) ($_GET['oid'] ?? 0);

        if ($gatewayOrderId !== '' && ($paymentId <= 0 || $orderId <= 0)) {
            $storedContext = $this->getStoredCheckoutContext($gatewayOrderId);
            if ($storedContext !== null) {
                $paymentId = (int) ($storedContext['payment_id'] ?? 0);
                $orderId = (int) ($storedContext['order_id'] ?? 0);
            }
        }

        if ($gatewayOrderId === '' || $paymentId <= 0 || $orderId <= 0) {
            $this->redirectToEshop('failed', 'Λείπουν απαραίτητα στοιχεία πληρωμής.');
            return;
        }

        $transactionStarted = false;

        try {
            $paymentContext = $this->getPaymentContext($paymentId, $orderId);
            if ($paymentContext === null) {
                throw new RuntimeException('Δεν βρέθηκε η πληρωμή ή η παραγγελία.');
            }

            $userId = (int) $paymentContext['user_id'];
            $statusResponse = $this->getJccOrderStatus($gatewayOrderId);
            $orderStatusCode = (int) ($statusResponse['orderStatus'] ?? -1);
            $paymentStatus = $this->mapOrderStatusToPaymentStatus($orderStatusCode);
            $paymentStatus = $this->resolveFinalPaymentStatus(
                (string) ($paymentContext['payment_status'] ?? 'pending'),
                $paymentStatus
            );
            $transactionId = $this->resolveTransactionId($statusResponse, $gatewayOrderId);
            $nextOrderStatus = $this->resolveFinalOrderStatus(
                (string) ($paymentContext['order_status'] ?? 'pending'),
                $paymentStatus
            );

            $this->conn->begin_transaction();
            $transactionStarted = true;

            $this->updatePaymentStatus($paymentId, $userId, $paymentStatus, $transactionId);
            $this->updateOrderStatus($orderId, $userId, $nextOrderStatus);

            if ($paymentStatus === 'completed') {
                $this->insertLog(
                    $userId,
                    'PAYMENT_COMPLETED',
                    'Eshop JCC payment completed. Order ID: ' . $orderId . ', Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
                );

                $newPendingOrderId = $this->createPendingOrderIfMissing($userId);
                if ($newPendingOrderId !== null) {
                    $this->insertLog(
                        $userId,
                        'ORDER_CREATED',
                        'Created new pending eshop order ID: ' . $newPendingOrderId . ' after payment for order ID: ' . $orderId
                    );
                }
            } elseif ($paymentStatus === 'pending') {
                $this->insertLog(
                    $userId,
                    'PAYMENT_PENDING',
                    'Eshop JCC payment pending. Order ID: ' . $orderId . ', Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
                );
            } else {
                $this->insertLog(
                    $userId,
                    'PAYMENT_FAILED',
                    'Eshop JCC payment failed. Order ID: ' . $orderId . ', Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
                );
            }

            $this->conn->commit();
            $this->clearStoredCheckoutContext($gatewayOrderId);

            if ($paymentStatus === 'completed') {
                try {
                    $receiptSummary = (new PaymentReceiptService($this->conn))
                        ->sendReceiptForPayments($userId, [$paymentId], 'JCC e-shop');

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

            $message = $this->buildRedirectMessage($paymentStatus);
            $this->redirectToEshop($paymentStatus, $message);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->conn->rollback();
            }

            $this->redirectToEshop('failed', $e->getMessage());
        }
    }

    // Leitourgia getPendingOrderForCheckout: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function getPendingOrderForCheckout(int $userId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT o.order_id, o.total_price, COALESCE(SUM(oi.quantity), 0) AS items_count
             FROM Orders o
             LEFT JOIN OrderItems oi ON oi.order_id = o.order_id
             WHERE o.user_id = ? AND o.order_status = 'pending'
             GROUP BY o.order_id, o.total_price
             ORDER BY o.order_id DESC
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to load pending order.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    // Leitourgia getUserEmail: xeirizetai to antistoixo kommati tis selidas i tou service.
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

    // Leitourgia insertPayment: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function insertPayment(int $userId, float $amount, string $paymentType): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO Payments (user_id, amount, payment_status, payment_type)
             VALUES (?, ?, 'pending', ?)"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare payment insert.');
        }

        $stmt->bind_param('ids', $userId, $amount, $paymentType);
        $stmt->execute();
        $paymentId = (int) $stmt->insert_id;
        $stmt->close();

        return $paymentId;
    }

    // Leitourgia getPaymentContext: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function getPaymentContext(int $paymentId, int $orderId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.payment_id, p.user_id, p.payment_status, o.order_id, o.order_status
             FROM Payments p
             INNER JOIN Orders o ON o.user_id = p.user_id
             WHERE p.payment_id = ? AND o.order_id = ? AND p.payment_type = 'product'
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to verify payment context.');
        }

        $stmt->bind_param('ii', $paymentId, $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    // Leitourgia insertPaymentDetailsFromOrder: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function insertPaymentDetailsFromOrder(int $paymentId, int $orderId): void
    {
        $itemsStmt = $this->conn->prepare(
            "SELECT product_id, quantity, price_at_purchase, size
             FROM OrderItems
             WHERE order_id = ?"
        );

        if ($itemsStmt === false) {
            throw new RuntimeException('Failed to load order items for payment details.');
        }

        $itemsStmt->bind_param('i', $orderId);
        $itemsStmt->execute();
        $result = $itemsStmt->get_result();

        $insertStmt = $this->conn->prepare(
            "INSERT INTO PaymentsDetails (payment_id, product_id, quantity, price_at_purchase, size)
             VALUES (?, ?, ?, ?, ?)"
        );

        if ($insertStmt === false) {
            $itemsStmt->close();
            throw new RuntimeException('Failed to prepare payment details insert.');
        }

        $hasRows = false;

        while ($row = $result ? $result->fetch_assoc() : null) {
            if (!is_array($row)) {
                break;
            }

            $hasRows = true;
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);
            $priceAtPurchase = (float) ($row['price_at_purchase'] ?? 0);
            $size = isset($row['size']) ? (string) $row['size'] : null;

            if ($productId <= 0 || $quantity <= 0 || $priceAtPurchase <= 0) {
                continue;
            }

            $insertStmt->bind_param('iiids', $paymentId, $productId, $quantity, $priceAtPurchase, $size);
            $insertStmt->execute();
        }

        $insertStmt->close();
        $itemsStmt->close();

        if (!$hasRows) {
            throw new RuntimeException('No order items found for payment details.');
        }
    }

    // Leitourgia updatePaymentStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
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

        if ($stmt->affected_rows === 0 && !$this->paymentExistsForUser($paymentId, $userId)) {
            $stmt->close();
            throw new RuntimeException('Payment not found for update.');
        }

        $stmt->close();
    }

    // Leitourgia paymentExistsForUser: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function paymentExistsForUser(int $paymentId, int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM Payments WHERE payment_id = ? AND user_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to verify payment existence.');
        }

        $stmt->bind_param('ii', $paymentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = (bool) ($result && $result->fetch_assoc());
        $stmt->close();

        return $exists;
    }

    // Leitourgia updateOrderStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function updateOrderStatus(int $orderId, int $userId, string $status): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Orders
             SET order_status = ?
             WHERE order_id = ? AND user_id = ? LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare order update.');
        }

        $stmt->bind_param('sii', $status, $orderId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows === 0 && !$this->orderExistsForUser($orderId, $userId)) {
            $stmt->close();
            throw new RuntimeException('Order not found for update.');
        }

        $stmt->close();
    }

    // Leitourgia orderExistsForUser: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function orderExistsForUser(int $orderId, int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM Orders WHERE order_id = ? AND user_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to verify order existence.');
        }

        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = (bool) ($result && $result->fetch_assoc());
        $stmt->close();

        return $exists;
    }

    // Leitourgia buildCallbackUrl: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function buildCallbackUrl(int $paymentId, int $orderId, bool $failed): string
    {
        $base = APP_BASE_URL . '/app/services/EshopJCC.php';
        $params = [
            'pid' => (string) $paymentId,
            'oid' => (string) $orderId,
        ];

        if ($failed) {
            $params['status'] = 'fail';
        }

        return $base . '?' . http_build_query($params);
    }

    // Leitourgia storeCheckoutContext: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function storeCheckoutContext(string $gatewayOrderId, int $userId, int $orderId, int $paymentId): void
    {
        if (!isset($_SESSION['eshop_checkout_context']) || !is_array($_SESSION['eshop_checkout_context'])) {
            $_SESSION['eshop_checkout_context'] = [];
        }

        $_SESSION['eshop_checkout_context'][$gatewayOrderId] = [
            'user_id' => $userId,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'stored_at' => time(),
        ];
    }

    // Leitourgia getStoredCheckoutContext: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function getStoredCheckoutContext(string $gatewayOrderId): ?array
    {
        $context = $_SESSION['eshop_checkout_context'][$gatewayOrderId] ?? null;
        return is_array($context) ? $context : null;
    }

    // Leitourgia clearStoredCheckoutContext: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function clearStoredCheckoutContext(string $gatewayOrderId): void
    {
        if (isset($_SESSION['eshop_checkout_context'][$gatewayOrderId])) {
            unset($_SESSION['eshop_checkout_context'][$gatewayOrderId]);
        }
    }

    // Leitourgia generateOrderNumber: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function generateOrderNumber(int $userId, int $orderId, int $paymentId): string
    {
        $randomPart = bin2hex(random_bytes(5));
        return sprintf('ESHOP-%d-%d-%d-%s', $userId, $orderId, $paymentId, $randomPart);
    }

    // Leitourgia registerJccOrder: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function registerJccOrder(
        string $orderNumber,
        float $amount,
        string $returnUrl,
        string $failUrl,
        string $email
    ): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for JCC integration.');
        }

        $amountMinorUnits = (int) round($amount * 100);
        if ($amountMinorUnits <= 0) {
            throw new RuntimeException('Invalid payment amount.');
        }

        $requestFields = [
            'userName' => JCC_API_LOGIN,
            'password' => JCC_API_PASSWORD,
            'orderNumber' => $orderNumber,
            'amount' => (string) $amountMinorUnits,
            'currency' => '978',
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            'language' => 'el',
            'description' => 'Eshop payment',
        ];

        if ($email !== '') {
            $requestFields['email'] = $email;
        }

        $ch = curl_init(JCC_REGISTER_URL);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize JCC request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($requestFields),
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

        $rawResponse = ltrim($rawResponse, "\xEF\xBB\xBF");
        $response = json_decode($rawResponse, true);
        if (!is_array($response)) {
            $snippet = substr(trim($rawResponse), 0, 220);
            throw new RuntimeException('Invalid response from JCC gateway (HTTP ' . $httpCode . '): ' . $snippet);
        }

        if (($response['errorCode'] ?? '0') !== '0' && ($response['errorCode'] ?? 0) !== 0) {
            $errorMessage = (string) ($response['errorMessage'] ?? 'Unknown JCC error.');
            throw new RuntimeException('JCC register error: ' . $errorMessage);
        }

        $gatewayOrderId = (string) ($response['orderId'] ?? '');
        $formUrl = (string) ($response['formUrl'] ?? '');

        if ($httpCode >= 400 || $gatewayOrderId === '' || $formUrl === '') {
            throw new RuntimeException('JCC did not return a valid checkout URL.');
        }

        return [
            'orderId' => $gatewayOrderId,
            'formUrl' => $formUrl,
        ];
    }

    // Leitourgia getJccOrderStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function getJccOrderStatus(string $gatewayOrderId): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for JCC integration.');
        }

        $requestFields = [
            'userName' => JCC_API_LOGIN,
            'password' => JCC_API_PASSWORD,
            'orderId' => $gatewayOrderId,
        ];

        $ch = curl_init(JCC_ORDER_STATUS_URL);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize JCC status request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($requestFields),
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

        $rawResponse = ltrim($rawResponse, "\xEF\xBB\xBF");
        $response = json_decode($rawResponse, true);
        if (!is_array($response)) {
            $snippet = substr(trim($rawResponse), 0, 220);
            throw new RuntimeException('Invalid response from JCC status API (HTTP ' . $httpCode . '): ' . $snippet);
        }

        if (($response['errorCode'] ?? '0') !== '0' && ($response['errorCode'] ?? 0) !== 0) {
            $errorMessage = (string) ($response['errorMessage'] ?? 'Unknown JCC status error.');
            throw new RuntimeException('JCC status error: ' . $errorMessage);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('JCC status request failed with HTTP ' . $httpCode . '.');
        }

        return $response;
    }

    // Leitourgia mapOrderStatusToPaymentStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
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

    // Leitourgia mapPaymentStatusToOrderStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function mapPaymentStatusToOrderStatus(string $paymentStatus): string
    {
        if ($paymentStatus === 'completed') {
            return 'paid';
        }

        if ($paymentStatus === 'refunded') {
            return 'cancelled';
        }

        return 'pending';
    }

    // Leitourgia createPendingOrderIfMissing: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function createPendingOrderIfMissing(int $userId): ?int
    {
        $existingStmt = $this->conn->prepare(
            "SELECT order_id
             FROM Orders
             WHERE user_id = ? AND order_status = 'pending'
             ORDER BY order_id DESC
             LIMIT 1"
        );

        if ($existingStmt === false) {
            throw new RuntimeException('Failed to check pending order after payment.');
        }

        $existingStmt->bind_param('i', $userId);
        $existingStmt->execute();
        $result = $existingStmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $existingStmt->close();

        if ($row && isset($row['order_id'])) {
            return null;
        }

        $insertStmt = $this->conn->prepare(
            "INSERT INTO Orders (user_id, total_price, created_at, order_status)
             VALUES (?, 0.00, NOW(), 'pending')"
        );

        if ($insertStmt === false) {
            throw new RuntimeException('Failed to create new pending order after payment.');
        }

        $insertStmt->bind_param('i', $userId);
        $insertStmt->execute();
        $newOrderId = (int) $insertStmt->insert_id;
        $insertStmt->close();

        return $newOrderId > 0 ? $newOrderId : null;
    }

    // Leitourgia resolveFinalPaymentStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function resolveFinalPaymentStatus(string $currentStatus, string $incomingStatus): string
    {
        if ($currentStatus === 'completed' && $incomingStatus !== 'refunded') {
            return 'completed';
        }

        return $incomingStatus;
    }

    // Leitourgia resolveFinalOrderStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function resolveFinalOrderStatus(string $currentStatus, string $paymentStatus): string
    {
        if ($currentStatus === 'paid' && $paymentStatus !== 'refunded') {
            return 'paid';
        }

        return $this->mapPaymentStatusToOrderStatus($paymentStatus);
    }

    // Leitourgia resolveTransactionId: xeirizetai to antistoixo kommati tis selidas i tou service.
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

        if (isset($statusResponse['attributes']) && is_array($statusResponse['attributes'])) {
            foreach ($statusResponse['attributes'] as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $name = strtolower((string) ($attribute['name'] ?? ''));
                $value = trim((string) ($attribute['value'] ?? ''));
                if ($name === 'mdorder' && $value !== '') {
                    return $value;
                }
            }
        }

        return $fallbackOrderId;
    }

    // Leitourgia insertLog: xeirizetai to antistoixo kommati tis selidas i tou service.
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

    // Leitourgia buildRedirectMessage: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function buildRedirectMessage(string $paymentStatus): string
    {
        if ($paymentStatus === 'completed') {
            return 'Η πληρωμή σας ολοκληρώθηκε με επιτυχία.';
        }

        if ($paymentStatus === 'pending') {
            return 'Η πληρωμή σας παραμένει σε αναμονή επιβεβαίωσης.';
        }

        if ($paymentStatus === 'refunded') {
            return 'Η πληρωμή σας σημειώθηκε ως επιστροφή.';
        }

        return 'Η πληρωμή σας δεν ολοκληρώθηκε.';
    }

    // Leitourgia redirectToEshop: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function redirectToEshop(string $paymentStatus, string $message): void
    {
        $url = APP_BASE_URL . '/public/parent/eshop.php?' . http_build_query([
            'payment_status' => $paymentStatus,
            'payment_message' => $message,
        ]);

        header('Location: ' . $url);
        exit;
    }

    // Leitourgia redirectToExternalUrl: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function redirectToExternalUrl(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    // Leitourgia respondCheckoutError: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function respondCheckoutError(bool $respondWithJson, string $message, int $statusCode): void
    {
        if ($respondWithJson) {
            $this->respond($statusCode, [
                'success' => false,
                'message' => $message,
            ]);
            return;
        }

        $this->redirectToEshop('failed', $message);
    }

    // Leitourgia wantsJsonResponse: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function wantsJsonResponse(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
    }

    // Leitourgia respond: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function respond(int $statusCode, array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

try {
    $service = new EshopJccService($conn);
    $service->handleRequest();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
