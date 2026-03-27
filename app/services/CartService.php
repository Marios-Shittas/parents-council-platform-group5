<?php

require_once __DIR__ . '/../includes/db.php';

class CartService
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getOrCreateCart($userId)
    {
        $userId = (int)$userId;

        $stmt = $this->conn->prepare("
            SELECT order_id
            FROM Orders
            WHERE user_id = ? AND order_status = 'pending'
            ORDER BY order_id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['order_id'];
        }

        $stmt = $this->conn->prepare("
            INSERT INTO Orders (user_id, total_price, created_at, order_status)
            VALUES (?, 0.00, NOW(), 'pending')
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            return (int)$this->conn->insert_id;
        }

        return false;
    }

    public function getCart($userId)
    {
        $orderId = $this->getOrCreateCart($userId);

        if (!$orderId) {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT 
                oi.order_id,
                oi.product_id,
                oi.price_at_purchase,
                oi.quantity,
                oi.size,
                p.product_name,
                p.product_description,
                p.price,
                (
                    SELECT MIN(pi.image_path)
                    FROM ProductsImages pi
                    WHERE pi.product_id = p.product_id
                ) AS product_image
            FROM OrderItems oi
            INNER JOIN Products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.product_id DESC
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        $total = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['quantity'] = (int)$row['quantity'];
                $row['price'] = (float)$row['price'];
                $row['price_at_purchase'] = (float)$row['price_at_purchase'];
                $row['line_total'] = $row['quantity'] * $row['price_at_purchase'];
                $total += $row['line_total'];
                $items[] = $row;
            }
        }

        $this->updateOrderTotal($orderId, $total);

        return [
            'order_id' => $orderId,
            'items' => $items,
            'total' => $total
        ];
    }

    public function addToCart($userId, $productId, $quantity = 1, $size = '')
    {
        $userId = (int)$userId;
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        $size = trim((string)$size);

        if ($userId <= 0 || $productId <= 0 || $quantity <= 0) {
            return false;
        }

        $orderId = $this->getOrCreateCart($userId);

        if (!$orderId) {
            return false;
        }

        $stmtPrice = $this->conn->prepare("
            SELECT price
            FROM Products
            WHERE product_id = ?
            LIMIT 1
        ");

        if (!$stmtPrice) {
            return false;
        }

        $stmtPrice->bind_param("i", $productId);
        $stmtPrice->execute();
        $priceResult = $stmtPrice->get_result();

        if (!$priceResult || !($priceRow = $priceResult->fetch_assoc())) {
            return false;
        }

        $priceAtPurchase = (float)$priceRow['price'];

        $stmtCheck = $this->conn->prepare("
            SELECT quantity
            FROM OrderItems
            WHERE order_id = ?
              AND product_id = ?
              AND (
                    (size = ?)
                    OR (size IS NULL AND ? = '')
                  )
            LIMIT 1
        ");

        if (!$stmtCheck) {
            return false;
        }

        $stmtCheck->bind_param("iiss", $orderId, $productId, $size, $size);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        if ($result && $result->fetch_assoc()) {
            $stmtUpdate = $this->conn->prepare("
                UPDATE OrderItems
                SET quantity = quantity + ?
                WHERE order_id = ?
                  AND product_id = ?
                  AND (
                        (size = ?)
                        OR (size IS NULL AND ? = '')
                      )
            ");

            if (!$stmtUpdate) {
                return false;
            }

            $stmtUpdate->bind_param("iiiss", $quantity, $orderId, $productId, $size, $size);

            if (!$stmtUpdate->execute()) {
                return false;
            }
        } else {
            $stmtInsert = $this->conn->prepare("
                INSERT INTO OrderItems (order_id, product_id, price_at_purchase, quantity, size)
                VALUES (?, ?, ?, ?, ?)
            ");

            if (!$stmtInsert) {
                return false;
            }

            $stmtInsert->bind_param("iidis", $orderId, $productId, $priceAtPurchase, $quantity, $size);

            if (!$stmtInsert->execute()) {
                return false;
            }
        }

        return $this->refreshOrderTotal($orderId);
    }

    public function updateCartItem($userId, $productId, $size, $quantity)
    {
        $userId = (int)$userId;
        $productId = (int)$productId;
        $size = trim((string)$size);
        $quantity = (int)$quantity;

        if ($userId <= 0 || $productId <= 0) {
            return false;
        }

        $orderId = $this->getOrCreateCart($userId);

        if (!$orderId) {
            return false;
        }

        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId, $size);
        }

        $stmt = $this->conn->prepare("
            UPDATE OrderItems
            SET quantity = ?
            WHERE order_id = ?
              AND product_id = ?
              AND (
                    (size = ?)
                    OR (size IS NULL AND ? = '')
                  )
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iiiss", $quantity, $orderId, $productId, $size, $size);

        if (!$stmt->execute()) {
            return false;
        }

        return $this->refreshOrderTotal($orderId);
    }

    public function removeFromCart($userId, $productId, $size)
    {
        $userId = (int)$userId;
        $productId = (int)$productId;
        $size = trim((string)$size);

        if ($userId <= 0 || $productId <= 0) {
            return false;
        }

        $orderId = $this->getOrCreateCart($userId);

        if (!$orderId) {
            return false;
        }

        $stmt = $this->conn->prepare("
            DELETE FROM OrderItems
            WHERE order_id = ?
              AND product_id = ?
              AND (
                    (size = ?)
                    OR (size IS NULL AND ? = '')
                  )
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iiss", $orderId, $productId, $size, $size);

        if (!$stmt->execute()) {
            return false;
        }

        return $this->refreshOrderTotal($orderId);
    }

    public function clearCart($userId)
    {
        $userId = (int)$userId;

        $orderId = $this->getOrCreateCart($userId);

        if (!$orderId) {
            return false;
        }

        $stmt = $this->conn->prepare("
            DELETE FROM OrderItems
            WHERE order_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $orderId);

        if (!$stmt->execute()) {
            return false;
        }

        return $this->updateOrderTotal($orderId, 0);
    }

    private function refreshOrderTotal($orderId)
    {
        $orderId = (int)$orderId;

        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(price_at_purchase * quantity), 0) AS total
            FROM OrderItems
            WHERE order_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $total = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $total = (float)$row['total'];
        }

        return $this->updateOrderTotal($orderId, $total);
    }

    private function updateOrderTotal($orderId, $total)
    {
        $orderId = (int)$orderId;
        $total = (float)$total;

        $stmt = $this->conn->prepare("
            UPDATE Orders
            SET total_price = ?
            WHERE order_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("di", $total, $orderId);
        return $stmt->execute();
    }
}