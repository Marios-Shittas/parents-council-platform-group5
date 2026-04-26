<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class OrdersService
{
    private mysqli $conn;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function clearProductOrderHistory(): array
    {
        $stats = [
            'orders_deleted' => 0,
            'order_items_deleted' => 0,
            'product_payments_deleted' => 0,
            'payment_details_deleted' => 0,
        ];

        $this->conn->begin_transaction();

        try {
            $stats['orders_deleted'] = $this->countRows("SELECT COUNT(*) AS total FROM Orders WHERE order_status = 'paid'");
            $stats['order_items_deleted'] = $this->countRows("
                SELECT COUNT(*) AS total
                FROM OrderItems oi
                INNER JOIN Orders o ON o.order_id = oi.order_id
                WHERE o.order_status = 'paid'
            ");
            $stats['product_payments_deleted'] = $this->countRows("SELECT COUNT(*) AS total FROM Payments WHERE payment_type = 'product'");
            $stats['payment_details_deleted'] = $this->countRows("
                SELECT COUNT(*) AS total
                FROM PaymentsDetails pd
                INNER JOIN Payments p ON p.payment_id = pd.payment_id
                WHERE p.payment_type = 'product'
            ");

            if (!$this->conn->query("
                DELETE pd
                FROM PaymentsDetails pd
                INNER JOIN Payments p ON p.payment_id = pd.payment_id
                WHERE p.payment_type = 'product'
            ")) {
                throw new RuntimeException('Failed to delete product payment details.');
            }

            if (!$this->conn->query("DELETE FROM Payments WHERE payment_type = 'product'")) {
                throw new RuntimeException('Failed to delete product payments.');
            }

            if (!$this->conn->query("
                DELETE oi
                FROM OrderItems oi
                INNER JOIN Orders o ON o.order_id = oi.order_id
                WHERE o.order_status = 'paid'
            ")) {
                throw new RuntimeException('Failed to delete paid order items.');
            }

            if (!$this->conn->query("DELETE FROM Orders WHERE order_status = 'paid'")) {
                throw new RuntimeException('Failed to delete paid orders.');
            }

            $this->conn->commit();

            return $stats;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteProductOrderHistory(int $orderId): array
    {
        if ($orderId <= 0) {
            throw new RuntimeException('Invalid order ID.');
        }

        $stats = [
            'order_id' => $orderId,
            'orders_deleted' => 0,
            'order_items_deleted' => 0,
            'product_payments_deleted' => 0,
            'payment_details_deleted' => 0,
        ];

        $this->conn->begin_transaction();

        try {
            $order = $this->getPaidOrderForDeletion($orderId);
            if ($order === null) {
                throw new RuntimeException('Paid order not found.');
            }

            $userId = (int)$order['user_id'];
            $totalPrice = (float)$order['total_price'];
            $paymentIds = $this->findProductPaymentIdsForOrder($orderId, $userId, $totalPrice);
            $stats['order_items_deleted'] = $this->countRows("
                SELECT COUNT(*) AS total
                FROM OrderItems
                WHERE order_id = {$orderId}
            ");

            if (!empty($paymentIds)) {
                $paymentIds = array_values(array_unique(array_map('intval', $paymentIds)));
                $paymentIdList = implode(',', $paymentIds);
                $stats['product_payments_deleted'] = count($paymentIds);
                $stats['payment_details_deleted'] = $this->countRows("
                    SELECT COUNT(*) AS total
                    FROM PaymentsDetails
                    WHERE payment_id IN ({$paymentIdList})
                ");

                if (!$this->conn->query("DELETE FROM PaymentsDetails WHERE payment_id IN ({$paymentIdList})")) {
                    throw new RuntimeException('Failed to delete payment details for order.');
                }

                if (!$this->conn->query("DELETE FROM Payments WHERE payment_id IN ({$paymentIdList})")) {
                    throw new RuntimeException('Failed to delete product payment for order.');
                }
            }

            $stmtDeleteItems = $this->conn->prepare("DELETE FROM OrderItems WHERE order_id = ?");
            if (!$stmtDeleteItems) {
                throw new RuntimeException('Failed to prepare order item deletion.');
            }

            $stmtDeleteItems->bind_param('i', $orderId);
            if (!$stmtDeleteItems->execute()) {
                throw new RuntimeException('Failed to delete order items.');
            }
            $stmtDeleteItems->close();

            $stmtDeleteOrder = $this->conn->prepare("DELETE FROM Orders WHERE order_id = ? AND order_status = 'paid'");
            if (!$stmtDeleteOrder) {
                throw new RuntimeException('Failed to prepare order deletion.');
            }

            $stmtDeleteOrder->bind_param('i', $orderId);
            if (!$stmtDeleteOrder->execute()) {
                throw new RuntimeException('Failed to delete order.');
            }
            $stats['orders_deleted'] = $stmtDeleteOrder->affected_rows;
            $stmtDeleteOrder->close();

            $this->conn->commit();

            return $stats;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getPaidOrderForDeletion(int $orderId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT order_id, user_id, total_price
            FROM Orders
            WHERE order_id = ?
              AND order_status = 'paid'
            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare order lookup.');
        }

        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function findProductPaymentIdsForOrder(int $orderId, int $userId, float $totalPrice): array
    {
        $idsFromLogs = $this->findProductPaymentIdsForOrderFromLogs($orderId, $userId);
        if (!empty($idsFromLogs)) {
            return $idsFromLogs;
        }

        $stmt = $this->conn->prepare("
            SELECT p.payment_id
            FROM Payments p
            WHERE p.user_id = ?
              AND p.payment_type = 'product'
              AND ABS(p.amount - ?) < 0.01
              AND (
                    SELECT COUNT(*)
                    FROM PaymentsDetails pd
                    WHERE pd.payment_id = p.payment_id
                  ) = (
                    SELECT COUNT(*)
                    FROM OrderItems oi
                    WHERE oi.order_id = ?
                  )
              AND NOT EXISTS (
                    SELECT 1
                    FROM PaymentsDetails pd
                    WHERE pd.payment_id = p.payment_id
                      AND NOT EXISTS (
                            SELECT 1
                            FROM OrderItems oi
                            WHERE oi.order_id = ?
                              AND oi.product_id = pd.product_id
                              AND oi.quantity = pd.quantity
                              AND ABS(oi.price_at_purchase - pd.price_at_purchase) < 0.01
                              AND COALESCE(oi.size, '') = COALESCE(pd.size, '')
                      )
                  )
              AND NOT EXISTS (
                    SELECT 1
                    FROM OrderItems oi
                    WHERE oi.order_id = ?
                      AND NOT EXISTS (
                            SELECT 1
                            FROM PaymentsDetails pd
                            WHERE pd.payment_id = p.payment_id
                              AND pd.product_id = oi.product_id
                              AND pd.quantity = oi.quantity
                              AND ABS(pd.price_at_purchase - oi.price_at_purchase) < 0.01
                              AND COALESCE(pd.size, '') = COALESCE(oi.size, '')
                      )
                  )
            ORDER BY p.payment_id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare product payment lookup.');
        }

        $stmt->bind_param('idiii', $userId, $totalPrice, $orderId, $orderId, $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $paymentIds = [];

        while ($result && ($row = $result->fetch_assoc())) {
            $paymentId = (int)($row['payment_id'] ?? 0);
            if ($paymentId > 0) {
                $paymentIds[] = $paymentId;
            }
        }

        $stmt->close();

        return $paymentIds;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function findProductPaymentIdsForOrderFromLogs(int $orderId, int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT p.payment_id
            FROM Payments p
            INNER JOIN Logs l ON l.user_id = p.user_id
            WHERE p.user_id = ?
              AND p.payment_type = 'product'
              AND (
                    l.description LIKE CONCAT('%Order ID: ', ?, ', Payment ID: ', p.payment_id, '%')
                    OR l.description LIKE CONCAT('%payment (ID: ', p.payment_id, ') for order ID: ', ?, ';%')
                  )
            ORDER BY p.payment_id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('iii', $userId, $orderId, $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $paymentIds = [];

        while ($result && ($row = $result->fetch_assoc())) {
            $paymentId = (int)($row['payment_id'] ?? 0);
            if ($paymentId > 0) {
                $paymentIds[] = $paymentId;
            }
        }

        $stmt->close();

        return $paymentIds;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function countRows(string $sql): int
    {
        $result = $this->conn->query($sql);
        if ($result === false) {
            throw new RuntimeException('Failed to count rows.');
        }

        $row = $result->fetch_assoc();

        return (int)($row['total'] ?? 0);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function respond(int $statusCode, array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
