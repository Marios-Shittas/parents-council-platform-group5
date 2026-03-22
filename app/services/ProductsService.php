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

    public function deleteProduct($productId)
    {
        $productId = (int)$productId;

        // 1. Πάρε όλες τις εικόνες του προϊόντος
        $stmt = $this->conn->prepare("
            SELECT image_path FROM ProductsImages WHERE product_id = ?
        ");

        if ($stmt) {
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();

            // 2. Διέγραψε τα αρχεία εικόνων από τον φάκελο
            while ($row = $result->fetch_assoc()) {
                $imagePath = $row['image_path'];

                // Καθαρίζουμε το σχετικό path
                $imagePath = ltrim($imagePath, './');

                if (strpos($imagePath, '../') === 0) {
                    $imagePath = substr($imagePath, 3);
                }

                $filePath = __DIR__ . '/../../public/' . $imagePath;

                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        // 3. Διέγραψε εγγραφές από ProductsImages
        $stmtImages = $this->conn->prepare("
            DELETE FROM ProductsImages WHERE product_id = ?
        ");

        if ($stmtImages) {
            $stmtImages->bind_param("i", $productId);
            $stmtImages->execute();
        }

        // 4. Διέγραψε το προϊόν από Products
        $stmtProduct = $this->conn->prepare("
            DELETE FROM Products WHERE product_id = ?
        ");

        if (!$stmtProduct) {
            return false;
        }

        $stmtProduct->bind_param("i", $productId);

        return $stmtProduct->execute();
    }
}