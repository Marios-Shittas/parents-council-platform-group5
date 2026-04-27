<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . '/EshopSettingsService.php';
require_once __DIR__ . '/../includes/product_sizes.php';

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

    $absolutePath = resolveProductImageAbsolutePath($imagePath);

    return file_exists($absolutePath)
        ? resolveProductImagePublicUrl($imagePath)
        : getDefaultProductImagePath();
}

function resolveProductImageAbsolutePath(string $imagePath): string
{
    $projectRoot = dirname(__DIR__, 2);
    $publicRelativePath = resolveProductImagePublicRelativePath($imagePath);

    return $publicRelativePath === '' ? '' : $projectRoot . '/public/' . $publicRelativePath;
}

function resolveProductImagePublicUrl(string $imagePath): string
{
    $publicRelativePath = resolveProductImagePublicRelativePath($imagePath);

    return $publicRelativePath === ''
        ? getDefaultProductImagePath()
        : '/parents-council-platform-group5/public/' . $publicRelativePath;
}

function resolveProductImagePublicRelativePath(string $imagePath): string
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

$sql = "SELECT product_id, product_name, product_description, price FROM Products";
$result = $conn->query($sql);

$products = [];

if ($result && $result->num_rows > 0) {
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

        $sizeMeta = product_sizes_get_for_product((int)$product_id);
        $row['images'] = $images;
        $row['has_sizes'] = $sizeMeta['has_sizes'];
        $row['size_options'] = $sizeMeta['size_options_with_labels'];
        $row['size_labels'] = $sizeMeta['size_labels'];
        $products[] = $row;      
    }
}

echo json_encode($products);

$conn->close();
?>
