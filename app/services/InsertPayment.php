<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/TokenValidator.php';

class InsertPaymentService
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

            if ($pricing === null) {
                throw new RuntimeException('Pricing settings are missing.');
            }

            $subscriptionPrice = (float) $pricing['subscription_price'];
            $insurancePrice = (float) $pricing['insurance_price'];

            $membershipPaymentId = $this->insertPayment($userId, $subscriptionPrice, 'membership');

            $insurancePaymentId = null;
            if ($includeInsurance) {
                $insuranceTotal = $insurancePrice * $childrenCount;
                $insurancePaymentId = $this->insertPayment($userId, $insuranceTotal, 'insurance');
            }

            $logDescription = "User created membership payment (ID: {$membershipPaymentId})";
            if ($includeInsurance && $insurancePaymentId !== null) {
                $logDescription .= " and insurance payment (ID: {$insurancePaymentId})";
            }

            $this->insertLog($userId, $logDescription);
            $this->conn->commit();

            $this->respond(200, [
                'success' => true,
                'membership_payment_id' => $membershipPaymentId,
                'insurance_payment_id' => $insurancePaymentId,
            ]);
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->respond(500, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function getUserIdFromToken(string $token): ?int
    {
        return $this->tokenValidator->getUserIdByToken($token, 'parent', 'waiting_payment', true);
    }

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

    private function getPricing(): ?array
    {
        $res = $this->conn->query('SELECT subscription_price, insurance_price FROM PricingSettings LIMIT 1');
        if ($res === false) {
            throw new RuntimeException('Failed to read pricing settings.');
        }

        $row = $res->fetch_assoc();
        return $row ?: null;
    }

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

    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

$service = new InsertPaymentService($conn);
$service->handleRequest();
$conn->close();