<?php

require_once __DIR__ . '/../includes/db.php';

class ProductsService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    public function getAllProducts(): array
    {
        $sql = "SELECT * FROM Products ORDER BY product_id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}