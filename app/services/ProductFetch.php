<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");

include "../config/db.php";

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
                $images[] = $img_row['image_path'];
            }
        }

        $row['images'] = $images;
        $products[] = $row;      
    }
}

echo json_encode($products);

$conn->close();
?>