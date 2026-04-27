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
require_once __DIR__ . '/PublicCartService.php';

class EshopJccService
{
    private mysqli $conn;
    private EshopSettingsService $eshopSettingsService;
    private PublicCartService $publicCartService;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->eshopSettingsService = new EshopSettingsService($conn);
        $this->publicCartService = new PublicCartService($conn);
    }

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

    private function handleCheckoutRegistration(bool $respondWithJson): void
    {
        $portalContext = $this->resolveRequestedPortalContext();

        if (!$this->eshopSettingsService->isShopVisible()) {
            $this->respondCheckoutError(
                $respondWithJson,
                'Το κατάστημα είναι προσωρινά μη διαθέσιμο. Coming soon.',
                403,
                $portalContext
            );
            return;
        }

        if ($this->isAuthenticatedParent()) {
            $this->handleParentCheckoutRegistration($respondWithJson, $portalContext);
            return;
        }

        $this->handlePublicCheckoutRegistration($respondWithJson, $portalContext);
    }

    private function handleParentCheckoutRegistration(bool $respondWithJson, string $portalContext): void
    {
        auth_require_role('parent', [
            'mode' => $respondWithJson ? 'json' : 'redirect',
            'message' => 'Μόνο λογαριασμοί γονέα μπορούν να ολοκληρώσουν αγορές.',
            'redirect_to' => APP_BASE_URL . '/public/parent/eshop.php',
        ]);

        $userId = auth_user_id();
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

            $customer = $this->loadParentCustomerSnapshot($userId);
            $this->syncOrderCustomerSnapshotForParent($orderId, $userId, $portalContext, $customer);

            $paymentId = $this->insertPayment($userId, $orderId, $totalPrice, 'product');
            $this->insertPaymentDetailsFromOrder($paymentId, $orderId);

            $orderNumber = $this->generateOrderNumber('parent', $userId, $orderId, $paymentId);
            $returnUrl = $this->buildCallbackUrl($paymentId, $orderId, false, $portalContext);
            $failUrl = $this->buildCallbackUrl($paymentId, $orderId, true, $portalContext);

            $jccResponse = $this->registerJccOrder(
                $orderNumber,
                $totalPrice,
                $returnUrl,
                $failUrl,
                $customer['email']
            );

            $this->attachGatewayOrderId($paymentId, (string) $jccResponse['orderId']);
            $this->insertLog(
                $userId,
                'PAYMENT_CREATED',
                'Parent created eshop payment (ID: ' . $paymentId . ') for order ID: ' . $orderId . '; JCC orderId: ' . $jccResponse['orderId']
            );

            $this->storeCheckoutContext(
                (string) $jccResponse['orderId'],
                $userId,
                $orderId,
                $paymentId,
                $portalContext,
                'parent'
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

            $this->redirectToExternalUrl((string) $jccResponse['formUrl']);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->conn->rollback();
            }

            $this->respondCheckoutError($respondWithJson, $e->getMessage(), 500, $portalContext);
        }
    }

    private function handlePublicCheckoutRegistration(bool $respondWithJson, string $portalContext): void
    {
        $transactionStarted = false;

        try {
            $customer = $this->validatePublicCheckoutPayload($_POST);
            $cart = $this->publicCartService->getCart();
            $items = array_values($cart['items'] ?? []);
            $totalPrice = (float) ($cart['total'] ?? 0);

            if (empty($items) || $totalPrice <= 0) {
                throw new RuntimeException('Το καλάθι είναι κενό ή το ποσό πληρωμής δεν είναι έγκυρο.');
            }

            $this->conn->begin_transaction();
            $transactionStarted = true;

            $orderId = $this->createPublicOrderFromCart($customer, $portalContext, $items, $totalPrice);
            $paymentId = $this->insertPayment(null, $orderId, $totalPrice, 'product');
            $this->insertPaymentDetailsFromOrder($paymentId, $orderId);

            $orderNumber = $this->generateOrderNumber('public', 0, $orderId, $paymentId);
            $returnUrl = $this->buildCallbackUrl($paymentId, $orderId, false, $portalContext);
            $failUrl = $this->buildCallbackUrl($paymentId, $orderId, true, $portalContext);

            $jccResponse = $this->registerJccOrder(
                $orderNumber,
                $totalPrice,
                $returnUrl,
                $failUrl,
                $customer['email']
            );

            $this->attachGatewayOrderId($paymentId, (string) $jccResponse['orderId']);
            $this->insertLog(
                null,
                'PAYMENT_CREATED',
                'Public customer created eshop payment (ID: ' . $paymentId . ') for order ID: ' . $orderId . '; JCC orderId: ' . $jccResponse['orderId']
            );

            $this->storeCheckoutContext(
                (string) $jccResponse['orderId'],
                null,
                $orderId,
                $paymentId,
                $portalContext,
                'public'
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

            $this->redirectToExternalUrl((string) $jccResponse['formUrl']);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->conn->rollback();
            }

            $this->respondCheckoutError($respondWithJson, $e->getMessage(), 500, $portalContext);
        }
    }

    private function handleCallback(): void
    {
        $gatewayOrderId = trim((string) ($_GET['orderId'] ?? $_GET['mdOrder'] ?? ''));
        $paymentId = (int) ($_GET['pid'] ?? 0);
        $orderId = (int) ($_GET['oid'] ?? 0);
        $portalContext = $this->sanitizePortalContext((string) ($_GET['ctx'] ?? ''));

        $storedContext = null;
        if ($gatewayOrderId !== '') {
            $storedContext = $this->getStoredCheckoutContext($gatewayOrderId);
            if (($paymentId <= 0 || $orderId <= 0) && $storedContext !== null) {
                $paymentId = (int) ($storedContext['payment_id'] ?? 0);
                $orderId = (int) ($storedContext['order_id'] ?? 0);
            }

            if (($paymentId <= 0 || $orderId <= 0)) {
                $databaseContext = $this->getCheckoutContextByGatewayOrderId($gatewayOrderId);
                if ($databaseContext !== null) {
                    $paymentId = (int) ($databaseContext['payment_id'] ?? 0);
                    $orderId = (int) ($databaseContext['order_id'] ?? 0);
                    if ($portalContext === '') {
                        $portalContext = $this->sanitizePortalContext((string) ($databaseContext['portal_context'] ?? ''));
                    }
                }
            }
        }

        if ($portalContext === '') {
            $portalContext = $this->sanitizePortalContext((string) ($storedContext['portal_context'] ?? ''));
        }

        if ($paymentId <= 0 || $orderId <= 0) {
            $this->redirectToEshop('failed', 'Λείπουν απαραίτητα στοιχεία πληρωμής.', $portalContext ?: 'public');
            return;
        }

        $transactionStarted = false;

        try {
            $paymentContext = $this->getPaymentContext($paymentId, $orderId);
            if ($paymentContext === null) {
                throw new RuntimeException('Δεν βρέθηκε η πληρωμή ή η παραγγελία.');
            }

            if ($portalContext === '') {
                $portalContext = $this->sanitizePortalContext((string) ($paymentContext['portal_context'] ?? ''));
            }

            $customerType = (string) ($paymentContext['customer_type'] ?? 'parent');
            $userId = (int) ($paymentContext['user_id'] ?? 0);

            $statusResponse = $this->getJccOrderStatus($gatewayOrderId !== '' ? $gatewayOrderId : (string) ($paymentContext['gateway_order_id'] ?? ''));
            $orderStatusCode = (int) ($statusResponse['orderStatus'] ?? -1);
            $paymentStatus = $this->mapOrderStatusToPaymentStatus($orderStatusCode);
            $paymentStatus = $this->resolveFinalPaymentStatus(
                (string) ($paymentContext['payment_status'] ?? 'pending'),
                $paymentStatus
            );
            $transactionId = $this->resolveTransactionId($statusResponse, $gatewayOrderId);
            $nextOrderStatus = $this->resolveFinalOrderStatus(
                (string) ($paymentContext['order_status'] ?? 'pending'),
                $paymentStatus,
                $customerType
            );

            $this->conn->begin_transaction();
            $transactionStarted = true;

            $this->updatePaymentStatus($paymentId, $paymentStatus, $transactionId);
            $this->updateOrderStatus($orderId, $nextOrderStatus);

            if ($paymentStatus === 'completed') {
                $this->insertLog(
                    $userId > 0 ? $userId : null,
                    'PAYMENT_COMPLETED',
                    'Eshop JCC payment completed. Order ID: ' . $orderId . ', Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
                );

                if ($customerType === 'parent' && $userId > 0) {
                    $newPendingOrderId = $this->createPendingOrderIfMissing($userId);
                    if ($newPendingOrderId !== null) {
                        $this->insertLog(
                            $userId,
                            'ORDER_CREATED',
                            'Created new pending eshop order ID: ' . $newPendingOrderId . ' after payment for order ID: ' . $orderId
                        );
                    }
                }
            } elseif ($paymentStatus === 'pending') {
                $this->insertLog(
                    $userId > 0 ? $userId : null,
                    'PAYMENT_PENDING',
                    'Eshop JCC payment pending. Order ID: ' . $orderId . ', Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
                );
            } else {
                $this->insertLog(
                    $userId > 0 ? $userId : null,
                    'PAYMENT_FAILED',
                    'Eshop JCC payment failed. Order ID: ' . $orderId . ', Payment ID: ' . $paymentId . ', Transaction ID: ' . $transactionId
                );
            }

            $this->conn->commit();
            $this->clearStoredCheckoutContext($gatewayOrderId);

            if ($paymentStatus === 'completed') {
                if ($customerType === 'public') {
                    $this->publicCartService->clearCart();
                }

                try {
                    $receiptService = new PaymentReceiptService($this->conn);
                    $receiptSummary = $userId > 0
                        ? $receiptService->sendReceiptForPayments($userId, [$paymentId], 'JCC e-shop')
                        : $receiptService->sendReceiptForProductOrderPayment($paymentId, 'JCC e-shop');

                    $this->insertLog(
                        $userId > 0 ? $userId : null,
                        'PAYMENT_RECEIPT_SENT',
                        'Receipt email sent for payment ID: ' . $paymentId . ' to ' . $receiptSummary['email']
                    );
                } catch (Throwable $receiptError) {
                    $this->insertLog(
                        $userId > 0 ? $userId : null,
                        'PAYMENT_RECEIPT_FAILED',
                        'Receipt email failed for payment ID: ' . $paymentId . '. Error: ' . $receiptError->getMessage()
                    );
                }
            }

            $message = $this->buildRedirectMessage($paymentStatus);
            $this->redirectToEshop($paymentStatus, $message, $portalContext);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->conn->rollback();
            }

            $this->redirectToEshop('failed', $e->getMessage(), $portalContext ?: 'public');
        }
    }

    private function isAuthenticatedParent(): bool
    {
        return auth_user_id() > 0 && auth_user_role() === 'parent';
    }

    private function resolveRequestedPortalContext(): string
    {
        $requested = $this->sanitizePortalContext((string) ($_POST['return_context'] ?? $_GET['return_context'] ?? ''));
        if ($requested !== '') {
            return $requested;
        }

        return $this->isAuthenticatedParent() ? 'parent' : 'public';
    }

    private function sanitizePortalContext(string $portalContext): string
    {
        $normalized = strtolower(trim($portalContext));
        if ($normalized === 'parent') {
            return 'parent';
        }

        if ($normalized === 'public') {
            return 'public';
        }

        return '';
    }

    private function validatePublicCheckoutPayload(array $payload): array
    {
        $name = $this->normalizeTextField((string) ($payload['customer_name'] ?? ''));
        $surname = $this->normalizeTextField((string) ($payload['customer_surname'] ?? ''));
        $studentName = $this->normalizeTextField((string) ($payload['student_name'] ?? ''));
        $studentClass = $this->normalizeTextField((string) ($payload['student_class'] ?? ''));
        $email = trim((string) ($payload['customer_email'] ?? ''));
        $phone = $this->normalizeTextField((string) ($payload['customer_phone'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Συμπληρώστε το όνομα πελάτη.');
        }

        if ($surname === '') {
            throw new RuntimeException('Συμπληρώστε το επώνυμο πελάτη.');
        }

        if ($studentName === '') {
            throw new RuntimeException('Συμπληρώστε το ονοματεπώνυμο μαθητή/τριας.');
        }

        if ($studentClass === '') {
            throw new RuntimeException('Συμπληρώστε το τμήμα του/της μαθητή/τριας.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Συμπληρώστε έγκυρο email επικοινωνίας.');
        }

        if ($phone === '') {
            throw new RuntimeException('Συμπληρώστε τηλέφωνο επικοινωνίας.');
        }

        return [
            'name' => $name,
            'surname' => $surname,
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'email' => $email,
            'phone' => $phone,
        ];
    }

    private function normalizeTextField(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));
        if (!is_string($normalized)) {
            return '';
        }

        return substr($normalized, 0, 150);
    }

    private function loadParentCustomerSnapshot(int $userId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT name, surname, email, phone_number
             FROM Users
             WHERE user_id = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to load parent customer details.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!is_array($row)) {
            throw new RuntimeException('Δεν βρέθηκαν τα στοιχεία του λογαριασμού γονέα.');
        }

        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Ο λογαριασμός γονέα δεν έχει έγκυρο email για την πληρωμή.');
        }

        return [
            'name' => trim((string) ($row['name'] ?? '')),
            'surname' => trim((string) ($row['surname'] ?? '')),
            'email' => $email,
            'phone' => trim((string) ($row['phone_number'] ?? '')),
        ];
    }

    private function syncOrderCustomerSnapshotForParent(int $orderId, int $userId, string $portalContext, array $customer): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Orders
             SET user_id = ?,
                 customer_type = ?,
                 customer_name = ?,
                 customer_surname = ?,
                 customer_email = ?,
                 customer_phone = ?,
                 portal_context = ?
             WHERE order_id = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to update parent order customer snapshot.');
        }

        $customerType = 'parent';
        $stmt->bind_param(
            'issssssi',
            $userId,
            $customerType,
            $customer['name'],
            $customer['surname'],
            $customer['email'],
            $customer['phone'],
            $portalContext,
            $orderId
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to save parent checkout details.');
        }
        $stmt->close();
    }

    private function createPublicOrderFromCart(array $customer, string $portalContext, array $items, float $totalPrice): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO Orders (
                user_id,
                total_price,
                created_at,
                order_status,
                customer_type,
                customer_name,
                customer_surname,
                customer_email,
                customer_phone,
                student_name,
                student_class,
                portal_context
            )
            VALUES (
                NULL,
                ?,
                NOW(),
                'pending',
                'public',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to create public order.');
        }

        $stmt->bind_param(
            'dsssssss',
            $totalPrice,
            $customer['name'],
            $customer['surname'],
            $customer['email'],
            $customer['phone'],
            $customer['student_name'],
            $customer['student_class'],
            $portalContext
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to insert public order.');
        }
        $orderId = (int) $stmt->insert_id;
        $stmt->close();

        if ($orderId <= 0) {
            throw new RuntimeException('Δεν ήταν δυνατή η δημιουργία παραγγελίας.');
        }

        $this->insertOrderItemsForOrder($orderId, $items);
        return $orderId;
    }

    private function insertOrderItemsForOrder(int $orderId, array $items): void
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO OrderItems (order_id, product_id, price_at_purchase, quantity, size)
             VALUES (?, ?, ?, ?, ?)'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare public order items insert.');
        }

        $insertedAny = false;

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $priceAtPurchase = (float) ($item['price_at_purchase'] ?? 0);
            $size = trim((string) ($item['size'] ?? ''));

            if ($productId <= 0 || $quantity <= 0 || $priceAtPurchase <= 0) {
                continue;
            }

            $stmt->bind_param('iidis', $orderId, $productId, $priceAtPurchase, $quantity, $size);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('Failed to insert public order item.');
            }
            $insertedAny = true;
        }

        $stmt->close();

        if (!$insertedAny) {
            throw new RuntimeException('Δεν βρέθηκαν έγκυρα προϊόντα για την παραγγελία.');
        }
    }

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

    private function insertPayment(?int $userId, ?int $orderId, float $amount, string $paymentType): int
    {
        if ($userId !== null && $userId > 0) {
            $stmt = $this->conn->prepare(
                "INSERT INTO Payments (user_id, order_id, amount, payment_status, payment_type)
                 VALUES (?, ?, ?, 'pending', ?)"
            );

            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare payment insert.');
            }

            $stmt->bind_param('iids', $userId, $orderId, $amount, $paymentType);
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO Payments (user_id, order_id, amount, payment_status, payment_type)
                 VALUES (NULL, ?, ?, 'pending', ?)"
            );

            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare public payment insert.');
            }

            $stmt->bind_param('ids', $orderId, $amount, $paymentType);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to insert payment.');
        }
        $paymentId = (int) $stmt->insert_id;
        $stmt->close();

        if ($paymentId <= 0) {
            throw new RuntimeException('Δεν ήταν δυνατή η δημιουργία πληρωμής.');
        }

        return $paymentId;
    }

    private function attachGatewayOrderId(int $paymentId, string $gatewayOrderId): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Payments
             SET gateway_order_id = ?
             WHERE payment_id = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to update payment gateway order ID.');
        }

        $stmt->bind_param('si', $gatewayOrderId, $paymentId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to store JCC gateway order ID.');
        }
        $stmt->close();
    }

    private function getPaymentContext(int $paymentId, int $orderId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT
                p.payment_id,
                p.user_id,
                p.order_id,
                p.payment_status,
                p.gateway_order_id,
                o.order_status,
                o.customer_type,
                o.portal_context,
                o.customer_email
             FROM Payments p
             INNER JOIN Orders o ON o.order_id = p.order_id
             WHERE p.payment_id = ?
               AND o.order_id = ?
               AND p.payment_type = 'product'
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

    private function getCheckoutContextByGatewayOrderId(string $gatewayOrderId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT
                p.payment_id,
                p.order_id,
                o.portal_context,
                o.customer_type
             FROM Payments p
             INNER JOIN Orders o ON o.order_id = p.order_id
             WHERE p.gateway_order_id = ?
               AND p.payment_type = 'product'
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to load payment by gateway order ID.');
        }

        $stmt->bind_param('s', $gatewayOrderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

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
            $size = trim((string) ($row['size'] ?? ''));

            if ($productId <= 0 || $quantity <= 0 || $priceAtPurchase <= 0) {
                continue;
            }

            $insertStmt->bind_param('iiids', $paymentId, $productId, $quantity, $priceAtPurchase, $size);
            if (!$insertStmt->execute()) {
                $insertStmt->close();
                $itemsStmt->close();
                throw new RuntimeException('Failed to insert payment detail item.');
            }
        }

        $insertStmt->close();
        $itemsStmt->close();

        if (!$hasRows) {
            throw new RuntimeException('No order items found for payment details.');
        }
    }

    private function updatePaymentStatus(int $paymentId, string $status, string $transactionId): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Payments
             SET payment_status = ?, transaction_id = ?
             WHERE payment_id = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare payment update.');
        }

        $stmt->bind_param('ssi', $status, $transactionId, $paymentId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to update payment status.');
        }

        if ($stmt->affected_rows === 0 && !$this->paymentExists($paymentId)) {
            $stmt->close();
            throw new RuntimeException('Payment not found for update.');
        }

        $stmt->close();
    }

    private function paymentExists(int $paymentId): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM Payments WHERE payment_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to verify payment existence.');
        }

        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = (bool) ($result && $result->fetch_assoc());
        $stmt->close();

        return $exists;
    }

    private function updateOrderStatus(int $orderId, string $status): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Orders
             SET order_status = ?
             WHERE order_id = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare order update.');
        }

        $stmt->bind_param('si', $status, $orderId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to update order status.');
        }

        if ($stmt->affected_rows === 0 && !$this->orderExists($orderId)) {
            $stmt->close();
            throw new RuntimeException('Order not found for update.');
        }

        $stmt->close();
    }

    private function orderExists(int $orderId): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM Orders WHERE order_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to verify order existence.');
        }

        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = (bool) ($result && $result->fetch_assoc());
        $stmt->close();

        return $exists;
    }

    private function buildCallbackUrl(int $paymentId, int $orderId, bool $failed, string $portalContext): string
    {
        $base = APP_BASE_URL . '/app/services/EshopJCC.php';
        $safePortalContext = $this->sanitizePortalContext($portalContext) ?: 'public';
        $params = [
            'pid' => (string) $paymentId,
            'oid' => (string) $orderId,
            'ctx' => $safePortalContext,
        ];

        if ($failed) {
            $params['status'] = 'fail';
        }

        return $base . '?' . http_build_query($params);
    }

    private function storeCheckoutContext(
        string $gatewayOrderId,
        ?int $userId,
        int $orderId,
        int $paymentId,
        string $portalContext,
        string $customerType
    ): void {
        if (!isset($_SESSION['eshop_checkout_context']) || !is_array($_SESSION['eshop_checkout_context'])) {
            $_SESSION['eshop_checkout_context'] = [];
        }

        $_SESSION['eshop_checkout_context'][$gatewayOrderId] = [
            'user_id' => $userId,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'portal_context' => $portalContext,
            'customer_type' => $customerType,
            'stored_at' => time(),
        ];
    }

    private function getStoredCheckoutContext(string $gatewayOrderId): ?array
    {
        $context = $_SESSION['eshop_checkout_context'][$gatewayOrderId] ?? null;
        return is_array($context) ? $context : null;
    }

    private function clearStoredCheckoutContext(string $gatewayOrderId): void
    {
        if ($gatewayOrderId !== '' && isset($_SESSION['eshop_checkout_context'][$gatewayOrderId])) {
            unset($_SESSION['eshop_checkout_context'][$gatewayOrderId]);
        }
    }

    private function generateOrderNumber(string $customerType, int $userId, int $orderId, int $paymentId): string
    {
        $randomPart = bin2hex(random_bytes(5));
        $prefix = $customerType === 'public' ? 'ESHOP-PUB' : 'ESHOP-PAR';
        return sprintf('%s-%d-%d-%d-%s', $prefix, $userId, $orderId, $paymentId, $randomPart);
    }

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

    private function getJccOrderStatus(string $gatewayOrderId): array
    {
        if ($gatewayOrderId === '') {
            throw new RuntimeException('Missing JCC order ID.');
        }

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
            "INSERT INTO Orders (user_id, total_price, created_at, order_status, customer_type, portal_context)
             VALUES (?, 0.00, NOW(), 'pending', 'parent', 'parent')"
        );

        if ($insertStmt === false) {
            throw new RuntimeException('Failed to create new pending order after payment.');
        }

        $insertStmt->bind_param('i', $userId);
        if (!$insertStmt->execute()) {
            $insertStmt->close();
            throw new RuntimeException('Failed to create the next pending order.');
        }
        $newOrderId = (int) $insertStmt->insert_id;
        $insertStmt->close();

        return $newOrderId > 0 ? $newOrderId : null;
    }

    private function resolveFinalPaymentStatus(string $currentStatus, string $incomingStatus): string
    {
        if ($currentStatus === 'completed' && $incomingStatus !== 'refunded') {
            return 'completed';
        }

        return $incomingStatus;
    }

    private function resolveFinalOrderStatus(string $currentStatus, string $paymentStatus, string $customerType): string
    {
        if ($currentStatus === 'paid' && $paymentStatus !== 'refunded') {
            return 'paid';
        }

        if ($paymentStatus === 'completed') {
            return 'paid';
        }

        if ($paymentStatus === 'refunded') {
            return 'cancelled';
        }

        if ($paymentStatus === 'failed' && $customerType === 'public') {
            return 'cancelled';
        }

        return 'pending';
    }

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

    private function insertLog(?int $userId, string $action, string $description): void
    {
        if ($userId !== null && $userId > 0) {
            $stmt = $this->conn->prepare('INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)');
            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare log insert.');
            }

            $stmt->bind_param('iss', $userId, $action, $description);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('Failed to write user log entry.');
            }
            $stmt->close();
            return;
        }

        $stmt = $this->conn->prepare('INSERT INTO Logs (user_id, action, description) VALUES (NULL, ?, ?)');
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare public log insert.');
        }

        $stmt->bind_param('ss', $action, $description);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to write public log entry.');
        }
        $stmt->close();
    }

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

    private function redirectToEshop(string $paymentStatus, string $message, string $portalContext): void
    {
        $target = $portalContext === 'parent'
            ? APP_BASE_URL . '/public/parent/eshop.php'
            : APP_BASE_URL . '/public/eshop.php';

        $url = $target . '?' . http_build_query([
            'payment_status' => $paymentStatus,
            'payment_message' => $message,
        ]);

        header('Location: ' . $url);
        exit;
    }

    private function redirectToExternalUrl(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function respondCheckoutError(bool $respondWithJson, string $message, int $statusCode, string $portalContext): void
    {
        if ($respondWithJson) {
            $this->respond($statusCode, [
                'success' => false,
                'message' => $message,
            ]);
            return;
        }

        $this->redirectToEshop('failed', $message, $portalContext !== '' ? $portalContext : 'public');
    }

    private function wantsJsonResponse(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
    }

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
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
