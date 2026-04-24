<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");

include "../config/db.php";
require_once __DIR__ . '/EshopSettingsService.php';
require_once __DIR__ . '/../includes/ProductImageHelper.php';

$eshopSettingsService = new EshopSettingsService($conn);

if (!$eshopSettingsService->isShopVisible()) {
    echo json_encode([]);
    $conn->close();
    exit;
}

$sql = "SELECT product_id, product_name, product_description, price FROM Products";
$result = $conn->query($sql);

$products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['product_id']; 
        
        $img_sql = "SELECT image_path FROM ProductsImages WHERE product_id = $product_id";
        $img_result = $conn->query($img_sql);

        $images = [];
        if ($img_result) {
            while ($img_row = $img_result->fetch_assoc()) {
                $images[] = ProductImageHelper::resolveImagePath((string)($img_row['image_path'] ?? ''));
            }
        }

        if (empty($images)) {
            $images[] = ProductImageHelper::getDefaultImagePath();
        }

        $row['images'] = $images;
        $products[] = $row;      
    }
}

echo json_encode($products);

$conn->close();
?>
