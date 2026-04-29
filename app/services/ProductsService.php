<?php
// Arxeio: app\services\ProductsService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.

require_once __DIR__ . '/../includes/db.php';

class ProductsService
{
    private $conn;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getAllProducts()
    {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.product_description,
                p.price,
                (
                    SELECT pi.image_path
                    FROM ProductsImages pi
                    WHERE pi.product_id = p.product_id
                    ORDER BY pi.pro_image_id DESC
                    LIMIT 1
                ) AS product_image
            FROM Products p
            ORDER BY p.product_id DESC
        ";

        $result = mysqli_query($this->conn, $sql);

        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $row['product_image'] = $this->resolveProductImagePath((string)($row['product_image'] ?? ''));
                $products[] = $row;
            }
        }

        return $products;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getProductById($id)
    {
        $id = (int)$id;

        $stmt = $this->conn->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.product_description,
                p.price,
                (
                    SELECT pi.image_path
                    FROM ProductsImages pi
                    WHERE pi.product_id = p.product_id
                    ORDER BY pi.pro_image_id DESC
                    LIMIT 1
                ) AS product_image
            FROM Products p
            WHERE p.product_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            if (is_array($product)) {
                $product['product_image'] = $this->resolveProductImagePath((string)($product['product_image'] ?? ''));
            }

            return $product;
        }

        return false;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function createProduct($name, $description, $price)
    {
        $name = trim($name);
        $description = trim($description);
        $price = (float)$price;

        $stmt = $this->conn->prepare("
            INSERT INTO Products (product_name, product_description, price)
            VALUES (?, ?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ssd", $name, $description, $price);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return false;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateProduct($id, $name, $description, $price)
    {
        $id = (int)$id;
        $name = trim($name);
        $description = trim($description);
        $price = (float)$price;

        $stmt = $this->conn->prepare("
            UPDATE Products
            SET product_name = ?, product_description = ?, price = ?
            WHERE product_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ssdi", $name, $description, $price, $id);

        return $stmt->execute();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function addProductImage($productId, $imagePath)
    {
        $productId = (int)$productId;
        $imagePath = trim($imagePath);

        $stmt = $this->conn->prepare("
            INSERT INTO ProductsImages (product_id, image_path)
            VALUES (?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("is", $productId, $imagePath);

        return $stmt->execute();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function replaceProductImage($productId, $imagePath)
    {
        $productId = (int)$productId;
        $imagePath = trim($imagePath);

        if ($productId <= 0 || $imagePath === '') {
            return false;
        }

        mysqli_begin_transaction($this->conn);

        try {
            $oldImagePaths = $this->getProductImagePaths($productId);

            $stmtDelete = $this->conn->prepare("
                DELETE FROM ProductsImages
                WHERE product_id = ?
            ");

            if (!$stmtDelete) {
                throw new Exception('Prepare delete images failed: ' . $this->conn->error);
            }

            $stmtDelete->bind_param("i", $productId);

            if (!$stmtDelete->execute()) {
                throw new Exception('Delete images failed: ' . $stmtDelete->error);
            }

            $stmtDelete->close();

            $stmtInsert = $this->conn->prepare("
                INSERT INTO ProductsImages (product_id, image_path)
                VALUES (?, ?)
            ");

            if (!$stmtInsert) {
                throw new Exception('Prepare insert image failed: ' . $this->conn->error);
            }

            $stmtInsert->bind_param("is", $productId, $imagePath);

            if (!$stmtInsert->execute()) {
                throw new Exception('Insert image failed: ' . $stmtInsert->error);
            }

            $stmtInsert->close();

            mysqli_commit($this->conn);
            $this->deleteImageFiles($oldImagePaths);

            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            error_log('replaceProductImage error: ' . $e->getMessage());
            return false;
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteProductImages($productId)
    {
        $productId = (int)$productId;

        $stmt = $this->conn->prepare("
            DELETE FROM ProductsImages
            WHERE product_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $productId);

        return $stmt->execute();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function productExistsInOrders($productId)
    {
        $productId = (int)$productId;

        $stmt = $this->conn->prepare("
            SELECT
                (
                    SELECT COUNT(*)
                    FROM OrderItems oi
                    INNER JOIN Orders o ON o.order_id = oi.order_id
                    WHERE oi.product_id = ?
                      AND o.order_status = 'paid'
                ) +
                (
                    SELECT COUNT(*)
                    FROM PaymentsDetails pd
                    INNER JOIN Payments p ON p.payment_id = pd.payment_id
                    WHERE pd.product_id = ?
                      AND p.payment_type = 'product'
                      AND p.payment_status IN ('completed', 'refunded')
                ) AS total
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $productId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return ((int)$row['total']) > 0;
        }

        return false;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteProduct($productId)
    {
        $productId = (int)$productId;

        if ($productId <= 0) {
            return false;
        }

        mysqli_begin_transaction($this->conn);

        try {
            if ($this->productExistsInOrders($productId)) {
                throw new Exception('Product has completed order or payment history.');
            }

            $this->cleanupDraftProductReferences($productId);

            $imagePaths = $this->getProductImagePaths($productId);

            $stmtDeleteSizes = $this->conn->prepare("
                DELETE FROM ProductSizeOptions
                WHERE product_id = ?
            ");

            if ($stmtDeleteSizes) {
                $stmtDeleteSizes->bind_param("i", $productId);

                if (!$stmtDeleteSizes->execute()) {
                    throw new Exception('Delete product sizes failed: ' . $stmtDeleteSizes->error);
                }

                $stmtDeleteSizes->close();
            }

            $stmtDeleteImages = $this->conn->prepare("
                DELETE FROM ProductsImages
                WHERE product_id = ?
            ");

            if (!$stmtDeleteImages) {
                throw new Exception('Prepare delete images failed: ' . $this->conn->error);
            }

            $stmtDeleteImages->bind_param("i", $productId);

            if (!$stmtDeleteImages->execute()) {
                throw new Exception('Delete images failed: ' . $stmtDeleteImages->error);
            }

            $stmtDeleteImages->close();

            $stmtDeleteProduct = $this->conn->prepare("
                DELETE FROM Products
                WHERE product_id = ?
            ");

            if (!$stmtDeleteProduct) {
                throw new Exception('Prepare delete product failed: ' . $this->conn->error);
            }

            $stmtDeleteProduct->bind_param("i", $productId);

            if (!$stmtDeleteProduct->execute()) {
                throw new Exception('Delete product failed: ' . $stmtDeleteProduct->error);
            }

            if ($stmtDeleteProduct->affected_rows <= 0) {
                throw new Exception('No product deleted.');
            }

            $stmtDeleteProduct->close();

            mysqli_commit($this->conn);

            $this->deleteImageFiles($imagePaths);

            return true;

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            error_log('deleteProduct error: ' . $e->getMessage());
            return false;
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function cleanupDraftProductReferences($productId): void
    {
        $productId = (int)$productId;

        $paymentIds = $this->getDraftProductPaymentIds($productId);
        if (!empty($paymentIds)) {
            $paymentIdList = implode(',', array_map('intval', $paymentIds));

            if (!$this->conn->query("DELETE FROM Payments WHERE payment_id IN ({$paymentIdList})")) {
                throw new Exception('Delete draft product payments failed: ' . $this->conn->error);
            }
        }

        $affectedOrderIds = $this->getDraftOrderIdsForProduct($productId);

        $stmtDeleteItems = $this->conn->prepare("
            DELETE oi
            FROM OrderItems oi
            INNER JOIN Orders o ON o.order_id = oi.order_id
            WHERE oi.product_id = ?
              AND o.order_status <> 'paid'
        ");

        if (!$stmtDeleteItems) {
            throw new Exception('Prepare delete draft order items failed: ' . $this->conn->error);
        }

        $stmtDeleteItems->bind_param("i", $productId);

        if (!$stmtDeleteItems->execute()) {
            throw new Exception('Delete draft order items failed: ' . $stmtDeleteItems->error);
        }

        $stmtDeleteItems->close();

        foreach ($affectedOrderIds as $orderId) {
            $this->refreshOrderTotal((int)$orderId);
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getDraftProductPaymentIds($productId): array
    {
        $productId = (int)$productId;
        $paymentIds = [];

        $stmt = $this->conn->prepare("
            SELECT DISTINCT p.payment_id
            FROM Payments p
            INNER JOIN PaymentsDetails pd ON pd.payment_id = p.payment_id
            WHERE pd.product_id = ?
              AND p.payment_type = 'product'
              AND p.payment_status IN ('pending', 'failed')
        ");

        if (!$stmt) {
            throw new Exception('Prepare draft product payment lookup failed: ' . $this->conn->error);
        }

        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($result && $row = $result->fetch_assoc()) {
            $paymentId = (int)($row['payment_id'] ?? 0);
            if ($paymentId > 0) {
                $paymentIds[] = $paymentId;
            }
        }

        $stmt->close();

        return array_values(array_unique($paymentIds));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getDraftOrderIdsForProduct($productId): array
    {
        $productId = (int)$productId;
        $orderIds = [];

        $stmt = $this->conn->prepare("
            SELECT DISTINCT o.order_id
            FROM Orders o
            INNER JOIN OrderItems oi ON oi.order_id = o.order_id
            WHERE oi.product_id = ?
              AND o.order_status <> 'paid'
        ");

        if (!$stmt) {
            throw new Exception('Prepare draft order lookup failed: ' . $this->conn->error);
        }

        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($result && $row = $result->fetch_assoc()) {
            $orderId = (int)($row['order_id'] ?? 0);
            if ($orderId > 0) {
                $orderIds[] = $orderId;
            }
        }

        $stmt->close();

        return array_values(array_unique($orderIds));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function refreshOrderTotal($orderId): void
    {
        $orderId = (int)$orderId;

        $stmt = $this->conn->prepare("
            UPDATE Orders
            SET total_price = (
                SELECT COALESCE(SUM(oi.price_at_purchase * oi.quantity), 0)
                FROM OrderItems oi
                WHERE oi.order_id = ?
            )
            WHERE order_id = ?
        ");

        if (!$stmt) {
            throw new Exception('Prepare order total refresh failed: ' . $this->conn->error);
        }

        $stmt->bind_param("ii", $orderId, $orderId);

        if (!$stmt->execute()) {
            throw new Exception('Order total refresh failed: ' . $stmt->error);
        }

        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getProductImagePaths($productId)
    {
        $productId = (int)$productId;
        $imagePaths = [];

        $stmt = $this->conn->prepare("
            SELECT image_path
            FROM ProductsImages
            WHERE product_id = ?
        ");

        if (!$stmt) {
            return $imagePaths;
        }

        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($result && $row = $result->fetch_assoc()) {
            $imagePaths[] = $row['image_path'];
        }

        $stmt->close();

        return $imagePaths;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function deleteImageFiles(array $imagePaths)
    {
        $publicRoot = realpath(__DIR__ . '/../../public');
        if ($publicRoot === false) {
            return;
        }

        foreach ($imagePaths as $imagePath) {
            $imagePath = trim((string)$imagePath);

            if ($imagePath === '') {
                continue;
            }

            $absolutePath = $this->resolveProductImageAbsolutePath($imagePath);
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resolveProductImagePath(string $imagePath): string
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return $this->getDefaultProductImagePath();
        }

        $absolutePath = $this->resolveProductImageAbsolutePath($imagePath);

        return file_exists($absolutePath)
            ? $this->resolveProductImagePublicUrl($imagePath)
            : $this->getDefaultProductImagePath();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resolveProductImageAbsolutePath(string $imagePath): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $publicRelativePath = $this->resolveProductImagePublicRelativePath($imagePath);

        return $publicRelativePath === '' ? '' : $projectRoot . '/public/' . $publicRelativePath;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resolveProductImagePublicUrl(string $imagePath): string
    {
        $publicRelativePath = $this->resolveProductImagePublicRelativePath($imagePath);

        return $publicRelativePath === ''
            ? $this->getDefaultProductImagePath()
            : '/parents-council-platform-group5/public/' . $publicRelativePath;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resolveProductImagePublicRelativePath(string $imagePath): string
    {
        $normalized = trim(str_replace('\\', '/', $imagePath));
        if ($normalized === '') {
            return '';
        }

        $publicPosition = strpos($normalized, '/public/');
        if ($publicPosition !== false) {
            return ltrim(substr($normalized, $publicPosition + strlen('/public/')), '/');
        }

        if (strpos($normalized, '../assets/') === 0) {
            return substr($normalized, 3);
        }

        if (strpos($normalized, '/assets/') === 0) {
            return ltrim($normalized, '/');
        }

        if (strpos($normalized, 'assets/') === 0) {
            return $normalized;
        }

        if (strpos($normalized, 'public/') === 0) {
            return substr($normalized, strlen('public/'));
        }

        return ltrim($normalized, '/');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getDefaultProductImagePath(): string
    {
        return '/parents-council-platform-group5/public/assets/Products_img/default-product.svg';
    }
}
