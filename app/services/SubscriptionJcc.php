<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/TokenValidator.php';

class SubscriptionJccService
{
    private mysqli $conn;
    private TokenValidator $tokenValidator;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->tokenValidator = new TokenValidator($conn);
    }

    public function handleRequest(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $orderId = trim((string) ($_GET['orderId'] ?? $_GET['mdOrder'] ?? ''));
        $membershipPaymentId = (int) ($_GET['mp'] ?? 0);
        $insurancePaymentId = (int) ($_GET['ip'] ?? 0);

        if ($token === '' || $orderId === '' || $membershipPaymentId <= 0) {
            $this->respond(400, [
                'success' => false,
                'message' => 'Missing required callback data.',
            ]);
            return;
        }

        $userId = $this->tokenValidator->getUserIdByToken($token, 'parent', 'waiting_payment', true);
        if ($userId === null) {
            $this->respond(401, [
                'success' => false,
                'message' => 'Invalid or expired token.',
            ]);
            return;
        }

        try {
            $statusResponse = $this->getJccOrderStatus($orderId);
            $orderStatus = (int) ($statusResponse['orderStatus'] ?? -1);
            $paymentStatus = $this->mapOrderStatusToPaymentStatus($orderStatus);
            $transactionId = $this->resolveTransactionId($statusResponse, $orderId);

            $this->conn->begin_transaction();

            $this->updatePaymentStatus($membershipPaymentId, $userId, $paymentStatus, $transactionId);
            if ($insurancePaymentId > 0) {
                // A single JCC checkout can cover both rows, so only the primary payment stores the gateway transaction id.
                $this->updatePaymentStatus($insurancePaymentId, $userId, $paymentStatus, null);
            }

            if ($paymentStatus === 'completed') {
                if ($insurancePaymentId > 0) {
                    $this->insertInsuranceChildrenPayments($userId, $insurancePaymentId);
                }

                $this->activateUser($userId);
                $this->insertLog(
                    $userId,
                    'PAYMENT_COMPLETED',
                    'JCC payment completed. Order ID: ' . $orderId . ', Transaction ID: ' . $transactionId
                );
            } elseif ($paymentStatus === 'pending') {
                $this->insertLog(
                    $userId,
                    'PAYMENT_PENDING',
                    'JCC payment pending. Order ID: ' . $orderId . ', Transaction ID: ' . $transactionId
                );
            } else {
                $this->insertLog(
                    $userId,
                    'PAYMENT_FAILED',
                    'JCC payment failed. Order ID: ' . $orderId . ', Transaction ID: ' . $transactionId
                );
            }

            $this->conn->commit();

            $this->respond(200, [
                'success' => true,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'transaction_id' => $transactionId,
            ]);
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->respond(500, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getJccOrderStatus(string $orderId): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for JCC integration.');
        }

        $requestFields = [
            'userName' => JCC_API_LOGIN,
            'password' => JCC_API_PASSWORD,
            'orderId' => $orderId,
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

                $name = (string) ($attribute['name'] ?? '');
                $value = trim((string) ($attribute['value'] ?? ''));
                if (strtolower($name) === 'mdorder' && $value !== '') {
                    return $value;
                }
            }
        }

        return $fallbackOrderId;
    }

    private function updatePaymentStatus(
        int $paymentId,
        int $userId,
        string $status,
        ?string $transactionId
    ): void
    {
        if ($transactionId !== null && $transactionId !== '') {
            $stmt = $this->conn->prepare(
                'UPDATE Payments
                 SET payment_status = ?, transaction_id = ?
                 WHERE payment_id = ? AND user_id = ? LIMIT 1'
            );

            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare payment status update.');
            }

            $stmt->bind_param('ssii', $status, $transactionId, $paymentId, $userId);
        } else {
            $stmt = $this->conn->prepare(
                'UPDATE Payments SET payment_status = ? WHERE payment_id = ? AND user_id = ? LIMIT 1'
            );

            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare payment status update.');
            }

            $stmt->bind_param('sii', $status, $paymentId, $userId);
        }

        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();

            if (!$this->paymentExistsForUser($paymentId, $userId)) {
                throw new RuntimeException('Payment not found for callback update.');
            }

            return;
        }

        $stmt->close();
    }

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

    private function insertInsuranceChildrenPayments(int $userId, int $insurancePaymentId): void
    {
        $childrenStmt = $this->conn->prepare('SELECT child_id FROM Children WHERE user_id = ?');
        if ($childrenStmt === false) {
            throw new RuntimeException('Failed to load children for insurance payment.');
        }

        $childrenStmt->bind_param('i', $userId);
        $childrenStmt->execute();
        $result = $childrenStmt->get_result();

        $insertStmt = $this->conn->prepare(
            'INSERT INTO InsurancePayments (payment_id, child_id)
             SELECT ?, ?
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1 FROM InsurancePayments WHERE payment_id = ? AND child_id = ?
             )'
        );

        if ($insertStmt === false) {
            $childrenStmt->close();
            throw new RuntimeException('Failed to prepare insurance payment insert.');
        }

        while ($row = $result->fetch_assoc()) {
            $childId = (int) ($row['child_id'] ?? 0);
            if ($childId <= 0) {
                continue;
            }

            $insertStmt->bind_param('iiii', $insurancePaymentId, $childId, $insurancePaymentId, $childId);
            $insertStmt->execute();
        }

        $insertStmt->close();
        $childrenStmt->close();
    }

    private function activateUser(int $userId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE Users
             SET account_status = 'active', token = NULL, token_expiry = NULL
             WHERE user_id = ? AND account_status = 'waiting_payment'
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare user activation.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    private function insertLog(int $userId, string $action, string $description): void
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare callback log insert.');
        }

        $stmt->bind_param('iss', $userId, $action, $description);
        $stmt->execute();
        $stmt->close();
    }

    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

$service = new SubscriptionJccService($conn);
$service->handleRequest();
$conn->close();
