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

    public function createProduct($name, $description, $price)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO Products (product_name, product_description, price)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("ssd", $name, $description, $price);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function addProductImage($productId, $imagePath)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO ProductsImages (product_id, image_path)
            VALUES (?, ?)
        ");

        $stmt->bind_param("is", $productId, $imagePath);
        return $stmt->execute();
    }
}