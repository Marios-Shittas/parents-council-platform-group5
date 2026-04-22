<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");

include "../config/db.php";
require_once __DIR__ . '/EshopSettingsService.php';

$eshopSettingsService = new EshopSettingsService($conn);

if (!$eshopSettingsService->isShopVisible()) {
    echo json_encode([]);
    $conn->close();
    exit;
}

function getDefaultProductImagePath(): string
{
    return '/parents-council-platform-group5/public/assets/Products_img/default-product.svg';
}

function resolveProductImagePath(string $imagePath): string
{
    $imagePath = trim($imagePath);
    if ($imagePath === '') {
        return getDefaultProductImagePath();
    }

    $normalized = str_replace('\\', '/', $imagePath);
    $projectRoot = dirname(__DIR__, 2);
    $absolutePath = '';

    $publicPosition = strpos($normalized, '/public/');
    if ($publicPosition !== false) {
        $absolutePath = $projectRoot . substr($normalized, $publicPosition);
    } elseif (strpos($normalized, '/assets/') === 0) {
        $absolutePath = $projectRoot . '/public' . $normalized;
    } else {
        $absolutePath = $projectRoot . '/public/' . ltrim($normalized, '/');
    }

    return file_exists($absolutePath) ? $imagePath : getDefaultProductImagePath();
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
                $images[] = resolveProductImagePath((string)($img_row['image_path'] ?? ''));
            }
        }

        if (empty($images)) {
            $images[] = getDefaultProductImagePath();
        }

        $row['images'] = $images;
        $products[] = $row;      
    }
}

echo json_encode($products);

$conn->close();
?>
