<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/TokenValidator.php';

class SubscriptionService
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
        $token = trim((string) ($_GET['token'] ?? ''));
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

        $childrenCount = $this->getChildrenCount($userId);
        $pricing = $this->getPricing();

        if ($pricing === null) {
            $this->respond(500, [
                'success' => false,
                'message' => 'Pricing settings are missing.',
            ]);
            return;
        }

        $subscriptionPrice = (float) $pricing['subscription_price'];
        $insurancePrice = (float) $pricing['insurance_price'];

        $insuranceTotal = $insurancePrice * $childrenCount;
        $total = $subscriptionPrice + $insuranceTotal;

        $this->respond(200, [
            'success' => true,
            'data' => [
                'subscription_price' => $subscriptionPrice,
                'insurance_price' => $insurancePrice,
                'children_count' => $childrenCount,
                'insurance_total' => $insuranceTotal,
                'total' => $total,
            ],
        ]);
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
        $sql = 'SELECT subscription_price, insurance_price FROM PricingSettings LIMIT 1';
        $res = $this->conn->query($sql);
        if ($res === false) {
            throw new RuntimeException('Failed to read pricing settings.');
        }

        $row = $res->fetch_assoc();
        return $row ?: null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

try {
    $service = new SubscriptionService($conn);
    $service->handleRequest();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
