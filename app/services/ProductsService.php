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
        $sql = "SELECT * FROM Products ORDER BY product_id DESC";
        $result = mysqli_query($this->conn, $sql);

        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }
        }

        return $products;
    }
}