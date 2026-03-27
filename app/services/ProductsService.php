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
            $imagePaths = $this->getProductImagePaths($productId);

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

            $cleanPath = str_replace('\\', '/', $imagePath);
            $cleanPath = preg_replace('#^\.\./#', '', $cleanPath);
            $cleanPath = preg_replace('#^/+#', '', $cleanPath);

            $absolutePath = $publicRoot . '/' . $cleanPath;
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }
}
