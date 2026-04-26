<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/TokenValidator.php';

class InsertPaymentService
{
    private mysqli $conn;
    private TokenValidator $tokenValidator;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->tokenValidator = new TokenValidator($conn);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function handleRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respond(405, [
                'success' => false,
                'message' => 'Method not allowed.',
            ]);
            return;
        }

        $request = $this->parseJsonBody();
        $token = trim((string) ($request['token'] ?? ''));
        $includeInsurance = isset($request['includeInsurance']) ? (bool) $request['includeInsurance'] : true;

        if ($token === '') {
            $this->respond(400, [
                'success' => false,
                'message' => 'Missing approval token.',
            ]);
            return;
        }

        $userId = $this->getUserIdFromToken($token);
        if ($userId === null) {
            $this->respond(401, [
                'success' => false,
                'message' => 'Invalid or expired approval token.',
            ]);
            return;
        }

        try {
            $this->conn->begin_transaction();

            $childrenCount = $this->getChildrenCount($userId);
            $pricing = $this->getPricing();
            $customerEmail = $this->getUserEmail($userId);

            if ($pricing === null) {
                throw new RuntimeException('Pricing settings are missing.');
            }

            $subscriptionPrice = (float) $pricing['subscription_price'];
            $insurancePrice = (float) $pricing['insurance_price'];
            $insuranceTotal = $includeInsurance ? ($insurancePrice * $childrenCount) : 0.0;
            $totalAmount = $subscriptionPrice + $insuranceTotal;

            $membershipPaymentId = $this->insertPayment($userId, $subscriptionPrice, 'membership');

            $insurancePaymentId = null;
            if ($includeInsurance) {
                $insurancePaymentId = $this->insertPayment($userId, $insuranceTotal, 'insurance');
            }

            $returnUrl = $this->buildJccReturnUrl($token, $membershipPaymentId, $insurancePaymentId, false);
            $failUrl = $this->buildJccReturnUrl($token, $membershipPaymentId, $insurancePaymentId, true);
            $orderNumber = $this->generateOrderNumber($userId);

            $jccResponse = $this->registerJccOrder(
                $orderNumber,
                $totalAmount,
                $returnUrl,
                $failUrl,
                $customerEmail
            );

            $logDescription = "User created membership payment (ID: {$membershipPaymentId})";
            if ($includeInsurance && $insurancePaymentId !== null) {
                $logDescription .= " and insurance payment (ID: {$insurancePaymentId})";
            }
            $logDescription .= "; JCC orderId: " . $jccResponse['orderId'];

            $this->insertLog($userId, $logDescription);
            $this->conn->commit();

            $this->respond(200, [
                'success' => true,
                'membership_payment_id' => $membershipPaymentId,
                'insurance_payment_id' => $insurancePaymentId,
                'order_id' => $jccResponse['orderId'],
                'redirect_url' => $jccResponse['formUrl'],
            ]);
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->respond(500, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getUserIdFromToken(string $token): ?int
    {
        return $this->tokenValidator->getUserIdByToken($token, 'parent', 'waiting_payment', true);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getChildrenCount(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS children_count FROM Children WHERE user_id = ?');
        if ($stmt === false) {
            throw new RuntimeException('Failed to count children.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return (int) ($row['children_count'] ?? 0);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getPricing(): ?array
    {
        $res = $this->conn->query('SELECT subscription_price, insurance_price FROM PricingSettings LIMIT 1');
        if ($res === false) {
            throw new RuntimeException('Failed to read pricing settings.');
        }

        $row = $res->fetch_assoc();
        return $row ?: null;
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

        return (string) ($row['email'] ?? '');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildJccReturnUrl(string $token, int $membershipPaymentId, ?int $insurancePaymentId, bool $failed): string
    {
        $base = $failed ? JCC_FAIL_URL : JCC_RETURN_URL;
        $separator = strpos($base, '?') === false ? '?' : '&';

        $params = [
            'token' => $token,
            'mp' => (string) $membershipPaymentId,
            'ip' => $insurancePaymentId !== null ? (string) $insurancePaymentId : '',
        ];

        return $base . $separator . http_build_query($params);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function generateOrderNumber(int $userId): string
    {
        $randomPart = bin2hex(random_bytes(5));
        return sprintf('SUB-%d-%d-%s', $userId, time(), $randomPart);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
            'description' => 'Subscription payment',
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

        $orderId = (string) ($response['orderId'] ?? '');
        $formUrl = (string) ($response['formUrl'] ?? '');

        if ($httpCode >= 400 || $orderId === '' || $formUrl === '') {
            throw new RuntimeException('JCC did not return a valid checkout URL.');
        }

        return [
            'orderId' => $orderId,
            'formUrl' => $formUrl,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertLog(int $userId, string $description): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO Logs (user_id, action, description) VALUES (?, 'PAYMENT_CREATED', ?)"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare log insert.');
        }

        $stmt->bind_param('is', $userId, $description);
        $stmt->execute();
        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

$service = new InsertPaymentService($conn);
$service->handleRequest();
$conn->close();
