<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/product_sizes.php';

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

        if ($method === 'POST' && $action === 'clear_product_order_history') {
            $this->handleClearProductOrderHistory();
            return;
        }

        if ($method === 'POST' && $action === 'delete_product_order_history') {
            $this->handleDeleteProductOrderHistory();
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

    private function handleClearProductOrderHistory(): void
    {
        try {
            $stats = $this->clearProductOrderHistory();
            $this->respond(200, [
                'success' => true,
                'message' => 'Το ιστορικό παραγγελιών e-shop καθαρίστηκε.',
                'stats' => $stats,
                'pending_paid_orders_count' => $this->getPendingPaidOrdersCount(),
            ]);
        } catch (Throwable $e) {
            $this->respond(500, [
                'success' => false,
                'message' => 'Δεν ήταν δυνατός ο καθαρισμός του ιστορικού παραγγελιών.',
            ]);
            error_log('Clear product order history error: ' . $e->getMessage());
        }
    }

    private function handleDeleteProductOrderHistory(): void
    {
        $orderId = (int)($_POST['order_id'] ?? 0);

        if ($orderId <= 0) {
            $this->respond(400, [
                'success' => false,
                'message' => 'Μη έγκυρη παραγγελία.',
            ]);
            return;
        }

        try {
            $stats = $this->deleteProductOrderHistory($orderId);
            $this->respond(200, [
                'success' => true,
                'message' => 'Η παραγγελία e-shop διαγράφηκε.',
                'stats' => $stats,
                'pending_paid_orders_count' => $this->getPendingPaidOrdersCount(),
            ]);
        } catch (Throwable $e) {
            $this->respond(500, [
                'success' => false,
                'message' => 'Δεν ήταν δυνατή η διαγραφή της παραγγελίας.',
            ]);
            error_log('Delete product order history error: ' . $e->getMessage());
        }
    }

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

            $paymentIds = $this->findProductPaymentIdsForOrder($orderId);
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

            $sizeValue = $row['size'] === null ? '' : (string) $row['size'];
            $sizeMeta = product_sizes_get_for_product((int) $row['product_id']);

            $itemsByOrderId[$orderId][] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => (string) $row['product_name'],
                'quantity' => (int) $row['quantity'],
                'size' => $sizeValue === '' ? null : $sizeValue,
                'size_label' => product_sizes_label_for_value($sizeValue, $sizeMeta),
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

    private function getPaidOrderForDeletion(int $orderId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT order_id
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

    private function findProductPaymentIdsForOrder(int $orderId): array
    {
        $stmt = $this->conn->prepare("
            SELECT payment_id
            FROM Payments
            WHERE order_id = ?
              AND payment_type = 'product'
            ORDER BY payment_id DESC
        ");

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare product payment lookup.');
        }

        $stmt->bind_param('i', $orderId);
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

    private function countRows(string $sql): int
    {
        $result = $this->conn->query($sql);
        if ($result === false) {
            throw new RuntimeException('Failed to count rows.');
        }

        $row = $result->fetch_assoc();

        return (int)($row['total'] ?? 0);
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
