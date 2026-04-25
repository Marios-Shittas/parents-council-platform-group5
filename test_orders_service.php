<?php
// Test the OrdersService logic directly
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/app/config/db.php';

// Manually test the queries that OrdersService uses
echo "=== Testing OrdersService Queries ===\n\n";

// Get paid orders
$sql = "
    SELECT
        o.order_id,
        o.created_at,
        o.total_price,
        o.customer_type,
        o.portal_context,
        o.customer_name,
        o.customer_surname,
        o.customer_email,
        o.customer_phone,
        o.student_name,
        o.student_class,
        COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM Orders o
    LEFT JOIN Users u ON u.user_id = o.user_id
    LEFT JOIN OrderItems oi ON oi.order_id = o.order_id
    WHERE o.order_status = 'paid'
    GROUP BY
        o.order_id,
        o.created_at,
        o.total_price,
        o.customer_type,
        o.portal_context,
        o.customer_name,
        o.customer_surname,
        o.customer_email,
        o.customer_phone,
        o.student_name,
        o.student_class
    ORDER BY o.created_at DESC
";

$result = $conn->query($sql);
if (!$result) {
    echo "✗ Orders query error: " . $conn->error . "\n";
} else {
    echo "✓ Orders query successful\n";
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    echo "- Found " . count($orders) . " paid orders\n";
    
    if (!empty($orders)) {
        $orderIds = array_map(fn($o) => (int)$o['order_id'], $orders);
        
        // Test getItemsByOrderIds
        echo "\n✓ Testing order items query...\n";
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $types = str_repeat('i', count($orderIds));
        
        echo "- Order IDs: " . implode(',', $orderIds) . "\n";
        echo "- Types string: '$types' (length: " . strlen($types) . ")\n";
        echo "- Number of orderIds: " . count($orderIds) . "\n";
        
        $itemsSql = "
            SELECT
                oi.order_id,
                oi.product_id,
                p.product_name,
                oi.quantity,
                oi.size,
                oi.price_at_purchase,
                (oi.quantity * oi.price_at_purchase) AS line_total
            FROM OrderItems oi
            INNER JOIN Products p ON p.product_id = oi.product_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.order_id DESC, p.product_name ASC
        ";
        
        $stmt = $conn->prepare($itemsSql);
        if ($stmt === false) {
            echo "✗ Prepare error: " . $conn->error . "\n";
        } else {
            echo "✓ Statement prepared\n";
            
            // Try to bind parameters
            $result2 = $stmt->bind_param($types, ...$orderIds);
            if (!$result2) {
                echo "✗ Bind error: " . $stmt->error . "\n";
            } else {
                echo "✓ Parameters bound\n";
                
                $stmt->execute();
                $itemsResult = $stmt->get_result();
                
                while ($item = $itemsResult->fetch_assoc()) {
                    echo "  - Product: " . $item['product_name'] . ", Qty: " . $item['quantity'] . "\n";
                }
                
                $stmt->close();
            }
        }
    }
}

$conn->close();
