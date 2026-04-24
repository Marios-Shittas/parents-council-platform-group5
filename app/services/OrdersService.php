<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class OrdersService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get count of paid orders not yet seen by admin
     * @return int Count of unseen paid orders
     */
    public function getPendingPaidOrdersCount(): int
    {
        $sql = "SELECT COUNT(*) AS count FROM Orders WHERE order_status = 'paid' AND admin_seen_at IS NULL";
        $result = $this->conn->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Mark an order as seen by admin
     * @param int $orderId Order ID
     * @return bool True when query executes successfully
     */
    public function markOrderAsSeen(int $orderId): bool
    {
        $sql = "UPDATE Orders SET admin_seen_at = NOW() WHERE order_id = ? AND order_status = 'paid' AND admin_seen_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $orderId);
        return $stmt->execute();
    }

    public function handleRequest(): void
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            $this->respond(403, [
                'success' => false,
                'message' => 'Unauthorized access.',
            ]);
            return;
        }

        $paidOrders = $this->getPaidOrders();
        $orderIds = array_map(static fn(array $order): int => (int) $order['order_id'], $paidOrders);
        $itemsByOrderId = $this->getItemsByOrderIds($orderIds);

        $ordersPayload = [];
        foreach ($paidOrders as $order) {
            $orderId = (int) $order['order_id'];
            $items = $itemsByOrderId[$orderId] ?? [];
            $isUnseen = $order['admin_seen_at'] === null || $order['admin_seen_at'] === '';
            
            $ordersPayload[] = [
                'order_id' => $orderId,
                'created_at' => $order['created_at'],
                'parent_name' => trim((string) ($order['parent_name'] ?? '')),
                'parent_email' => (string) ($order['parent_email'] ?? ''),
                'total_price' => (float) $order['total_price'],
                'total_items' => (int) $order['total_items'],
                'items' => $items,
                'is_unseen' => $isUnseen,
            ];
        }

        $totalsByProduct = $this->getTotalsByProduct();

        $summary = [
            'paid_orders_count' => count($ordersPayload),
            'total_revenue' => array_reduce(
                $ordersPayload,
                static fn(float $carry, array $order): float => $carry + (float) $order['total_price'],
                0.0
            ),
            'total_items_count' => array_reduce(
                $ordersPayload,
                static fn(int $carry, array $order): int => $carry + (int) $order['total_items'],
                0
            ),
        ];

        $this->respond(200, [
            'success' => true,
            'summary' => $summary,
            'totals_by_product' => $totalsByProduct,
            'orders' => $ordersPayload,
        ]);
    }

    private function getPaidOrders(): array
    {
        $sql = "
            SELECT
                o.order_id,
                o.created_at,
                o.total_price,
                o.admin_seen_at,
                CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.surname, '')) AS parent_name,
                u.email AS parent_email,
                COALESCE(SUM(oi.quantity), 0) AS total_items
            FROM Orders o
            INNER JOIN Users u ON u.user_id = o.user_id
            LEFT JOIN OrderItems oi ON oi.order_id = o.order_id
            WHERE o.order_status = 'paid'
            GROUP BY o.order_id, o.created_at, o.total_price, o.admin_seen_at, u.name, u.surname, u.email
            ORDER BY o.created_at DESC
        ";

        $result = $this->conn->query($sql);
        if ($result === false) {
            throw new RuntimeException('Failed to fetch paid orders.');
        }

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        return $orders;
    }

    private function getItemsByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $types = str_repeat('i', count($orderIds));

        $sql = "
            SELECT
                oi.order_id,
                oi.product_id,
                p.product_name,
                oi.quantity,
                oi.size,
                oi.price_at_purchase,
                (oi.quantity * oi.price_at_purchase) AS line_total
            FROM OrderItems oi
            INNER JOIN Products p ON p.product_id = oi.product_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.order_id DESC, p.product_name ASC
        ";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare paid order items query.');
        }

        $stmt->bind_param($types, ...$orderIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $itemsByOrderId = [];
        while ($row = $result->fetch_assoc()) {
            $orderId = (int) $row['order_id'];
            if (!isset($itemsByOrderId[$orderId])) {
                $itemsByOrderId[$orderId] = [];
            }

            $itemsByOrderId[$orderId][] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => (string) $row['product_name'],
                'quantity' => (int) $row['quantity'],
                'size' => $row['size'] === null ? null : (string) $row['size'],
                'price_at_purchase' => (float) $row['price_at_purchase'],
                'line_total' => (float) $row['line_total'],
            ];
        }

        $stmt->close();

        return $itemsByOrderId;
    }

    private function getTotalsByProduct(): array
    {
        $sql = "
            SELECT
                oi.product_id,
                p.product_name,
                SUM(oi.quantity) AS total_quantity,
                SUM(oi.quantity * oi.price_at_purchase) AS total_value
            FROM Orders o
            INNER JOIN OrderItems oi ON oi.order_id = o.order_id
            INNER JOIN Products p ON p.product_id = oi.product_id
            WHERE o.order_status = 'paid'
            GROUP BY oi.product_id, p.product_name
            ORDER BY total_quantity DESC, p.product_name ASC
        ";

        $result = $this->conn->query($sql);
        if ($result === false) {
            throw new RuntimeException('Failed to fetch product totals.');
        }

        $totals = [];
        while ($row = $result->fetch_assoc()) {
            $totals[] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => (string) $row['product_name'],
                'total_quantity' => (int) $row['total_quantity'],
                'total_value' => (float) $row['total_value'],
            ];
        }

        return $totals;
    }

    private function respond(int $statusCode, array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
