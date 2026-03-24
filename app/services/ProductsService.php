<?php

require_once __DIR__ . '/../includes/db.php';

class ProductsService
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getAllProducts()
    {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.product_description,
                p.price,
                MIN(pi.image_path) AS product_image
            FROM Products p
            LEFT JOIN ProductsImages pi ON p.product_id = pi.product_id
            GROUP BY p.product_id, p.product_name, p.product_description, p.price
            ORDER BY p.product_id DESC
        ";

        $result = mysqli_query($this->conn, $sql);

        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }
        }

        return $products;
    }

    public function getProductById($id)
    {
        $id = (int)$id;

        $stmt = $this->conn->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.product_description,
                p.price,
                MIN(pi.image_path) AS product_image
            FROM Products p
            LEFT JOIN ProductsImages pi ON p.product_id = pi.product_id
            WHERE p.product_id = ?
            GROUP BY p.product_id, p.product_name, p.product_description, p.price
            LIMIT 1
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return false;
    }

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

    public function productExistsInOrders($productId)
    {
        $productId = (int)$productId;

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM OrderItems
            WHERE product_id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return ((int)$row['total']) > 0;
        }

        return false;
    }

    public function deleteProduct($productId)
    {
        $productId = (int)$productId;

        if ($productId <= 0) {
            return false;
        }

        mysqli_begin_transaction($this->conn);

        try {
            $imagePaths = [];

            $stmtSelect = $this->conn->prepare("
                SELECT image_path
                FROM ProductsImages
                WHERE product_id = ?
            ");

            if (!$stmtSelect) {
                throw new Exception('Prepare select failed: ' . $this->conn->error);
            }

            $stmtSelect->bind_param("i", $productId);
            $stmtSelect->execute();

            $result = $stmtSelect->get_result();
            while ($row = $result->fetch_assoc()) {
                $imagePaths[] = $row['image_path'];
            }
            $stmtSelect->close();

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

            foreach ($imagePaths as $imagePath) {
                $imagePath = trim((string)$imagePath);

                if ($imagePath === '') {
                    continue;
                }

                $cleanPath = str_replace('\\', '/', $imagePath);
                $cleanPath = preg_replace('#^\.\./#', '', $cleanPath);
                $cleanPath = preg_replace('#^/+#', '', $cleanPath);

                $publicRoot = realpath(__DIR__ . '/../../public');
                if ($publicRoot !== false) {
                    $absolutePath = $publicRoot . '/' . $cleanPath;
                    if (file_exists($absolutePath)) {
                        @unlink($absolutePath);
                    }
                }
            }

            return true;

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            error_log('deleteProduct error: ' . $e->getMessage());
            return false;
        }
    }
}