<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class OrdersService
{
    private mysqli $conn;
    private ?bool $hasAdminSeenAtColumn = null;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
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

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));

        if ($method === 'POST' && $action === 'mark_order_seen') {
            $this->handleMarkOrderSeen();
            return;
        }

        $paidOrders = $this->getPaidOrders();
        $orderIds = array_map(static fn(array $order): int => (int) $order['order_id'], $paidOrders);
        $itemsByOrderId = $this->getItemsByOrderIds($orderIds);

        $ordersPayload = [];
        foreach ($paidOrders as $order) {
            $orderId = (int) $order['order_id'];
            $items = $itemsByOrderId[$orderId] ?? [];
            $isUnseen = !isset($order['admin_seen_at']) || $order['admin_seen_at'] === null || $order['admin_seen_at'] === '';
            $ordersPayload[] = [
                'order_id' => $orderId,
                'created_at' => $order['created_at'],
                'customer_type' => (string) ($order['customer_type'] ?? 'parent'),
                'portal_context' => (string) ($order['portal_context'] ?? 'parent'),
                'customer_name' => trim((string) ($order['customer_name'] ?? '')),
                'customer_email' => (string) ($order['customer_email'] ?? ''),
                'customer_phone' => (string) ($order['customer_phone'] ?? ''),
                'student_name' => trim((string) ($order['student_name'] ?? '')),
                'student_class' => trim((string) ($order['student_class'] ?? '')),
                'parent_name' => trim((string) ($order['customer_name'] ?? '')),
                'parent_email' => (string) ($order['customer_email'] ?? ''),
                'total_price' => (float) $order['total_price'],
                'total_items' => (int) $order['total_items'],
                'items' => $items,
                'is_unseen' => $isUnseen,
            ];
        }

        $totalsByProduct = $this->getTotalsByProduct();
        $pendingPaidOrdersCount = $this->getPendingPaidOrdersCount();

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
            'pending_paid_orders_count' => $pendingPaidOrdersCount,
            'orders' => $ordersPayload,
        ]);
    }

    private function handleMarkOrderSeen(): void
    {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            $this->respond(400, [
                'success' => false,
                'message' => 'Missing or invalid order ID.',
            ]);
            return;
        }

        $updated = $this->markOrderAsSeen($orderId);
        if (!$updated) {
            $this->respond(500, [
                'success' => false,
                'message' => 'Failed to mark order as seen.',
            ]);
            return;
        }

        $this->respond(200, [
            'success' => true,
            'pending_paid_orders_count' => $this->getPendingPaidOrdersCount(),
        ]);
    }

    public function getPendingPaidOrdersCount(): int
    {
        if (!$this->ensureAdminSeenAtColumn()) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS count FROM Orders WHERE order_status = 'paid' AND admin_seen_at IS NULL";
        $result = $this->conn->query($sql);

        if ($result === false) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int) ($row['count'] ?? 0);
    }

    public function markOrderAsSeen(int $orderId): bool
    {
        if (!$this->ensureAdminSeenAtColumn()) {
            return true;
        }

        $stmt = $this->conn->prepare(
            "UPDATE Orders
             SET admin_seen_at = NOW()
             WHERE order_id = ?
               AND order_status = 'paid'
               AND admin_seen_at IS NULL"
        );

        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param('i', $orderId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    private function getPaidOrders(): array
    {
        $adminSeenSelect = $this->ensureAdminSeenAtColumn() ? 'o.admin_seen_at' : 'NULL AS admin_seen_at';
        $adminSeenGroupBy = $this->ensureAdminSeenAtColumn() ? "                o.admin_seen_at,\n" : '';

        $sql = "
            SELECT
                o.order_id,
                o.created_at,
                o.total_price,
                {$adminSeenSelect},
                o.customer_type,
                o.portal_context,
                TRIM(
                    COALESCE(
                        NULLIF(CONCAT(COALESCE(o.customer_name, ''), ' ', COALESCE(o.customer_surname, '')), ' '),
                        CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.surname, ''))
                    )
                ) AS customer_name,
                COALESCE(NULLIF(o.customer_email, ''), u.email, '') AS customer_email,
                COALESCE(NULLIF(o.customer_phone, ''), u.phone_number, '') AS customer_phone,
                o.student_name,
                o.student_class,
                COALESCE(SUM(oi.quantity), 0) AS total_items
            FROM Orders o
            LEFT JOIN Users u ON u.user_id = o.user_id
            LEFT JOIN OrderItems oi ON oi.order_id = o.order_id
            WHERE o.order_status = 'paid'
            GROUP BY
                o.order_id,
                o.created_at,
                o.total_price,
{$adminSeenGroupBy}
                o.customer_type,
                o.portal_context,
                o.customer_name,
                o.customer_surname,
                o.customer_email,
                o.customer_phone,
                o.student_name,
                o.student_class,
                u.name,
                u.surname,
                u.email,
                u.phone_number
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

    private function ensureAdminSeenAtColumn(): bool
    {
        if ($this->hasAdminSeenAtColumn !== null) {
            return $this->hasAdminSeenAtColumn;
        }

        $check = $this->conn->query("SHOW COLUMNS FROM Orders LIKE 'admin_seen_at'");
        if ($check && (int) $check->num_rows > 0) {
            $this->hasAdminSeenAtColumn = true;
            return true;
        }

        $alterSql = "ALTER TABLE Orders ADD COLUMN admin_seen_at DATETIME NULL DEFAULT NULL AFTER order_status";
        if (!$this->conn->query($alterSql)) {
            $this->hasAdminSeenAtColumn = false;
            return false;
        }

        $this->hasAdminSeenAtColumn = true;
        return true;
    }

    private function respond(int $statusCode, array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    try {
        $service = new OrdersService($conn);
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
}
