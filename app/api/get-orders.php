<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.',
    ]);
    exit;
}

// Get paid orders
$sql = "
    SELECT
        o.order_id,
        o.created_at,
        o.total_price,
        o.customer_type,
        o.portal_context,
        TRIM(
            COALESCE(
                NULLIF(CONCAT(COALESCE(o.customer_name, ''), ' ', COALESCE(o.customer_surname, '')), ' '),
                CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.surname, ''))
            )
        ) AS customer_name,
        COALESCE(NULLIF(o.customer_email, ''), u.email, '') AS customer_email,
        COALESCE(NULLIF(o.customer_phone, ''), u.phone_number, '') AS customer_phone,
        o.student_name,
        o.student_class,
        COALESCE(SUM(oi.quantity), 0) AS total_items,
        o.admin_seen_at,
        CASE WHEN o.admin_seen_at IS NULL THEN 1 ELSE 0 END as is_unseen
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
        o.student_class,
        o.admin_seen_at,
        u.name,
        u.surname,
        u.email,
        u.phone_number
    ORDER BY o.created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error,
    ]);
    exit;
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get order items for each order
$ordersPayload = [];
foreach ($orders as $order) {
    $orderId = (int)$order['order_id'];
    
    // Get items for this order
    $itemsSql = "
        SELECT
            oi.product_id,
            oi.quantity,
            oi.price_at_purchase,
            oi.size,
            p.product_name
        FROM OrderItems oi
        LEFT JOIN Products p ON p.product_id = oi.product_id
        WHERE oi.order_id = ?
    ";
    
    $itemsStmt = $conn->prepare($itemsSql);
    if ($itemsStmt) {
        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = [
                'product_name' => $item['product_name'] ?? 'Unknown',
                'quantity' => (int)$item['quantity'],
                'size' => $item['size'] ?? null,
                'price_at_purchase' => (float)$item['price_at_purchase'],
                'line_total' => (float)($item['quantity'] * $item['price_at_purchase']),
            ];
        }
        $itemsStmt->close();
    } else {
        $items = [];
    }
    
    $ordersPayload[] = [
        'order_id' => $orderId,
        'created_at' => $order['created_at'],
        'customer_type' => (string)($order['customer_type'] ?? 'parent'),
        'portal_context' => (string)($order['portal_context'] ?? 'parent'),
        'customer_name' => $order['customer_name'] ?? '',
        'customer_email' => $order['customer_email'] ?? '',
        'customer_phone' => $order['customer_phone'] ?? '',
        'student_name' => $order['student_name'] ?? '',
        'student_class' => $order['student_class'] ?? '',
        'total_price' => (float)$order['total_price'],
        'total_items' => (int)$order['total_items'],
        'items' => $items,
        'is_unseen' => (bool)$order['is_unseen'],
    ];
}

// Get totals by product
$totalsSql = "
    SELECT
        p.product_id,
        p.product_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.price_at_purchase) as total_revenue
    FROM OrderItems oi
    LEFT JOIN Products p ON p.product_id = oi.product_id
    LEFT JOIN Orders o ON o.order_id = oi.order_id
    WHERE o.order_status = 'paid'
    GROUP BY p.product_id, p.product_name
    ORDER BY total_quantity DESC
";

$totalsResult = $conn->query($totalsSql);
$totalsByProduct = [];
if ($totalsResult) {
    while ($row = $totalsResult->fetch_assoc()) {
        $totalsByProduct[] = [
            'product_name' => $row['product_name'] ?? 'Unknown',
            'total_quantity' => (int)($row['total_quantity'] ?? 0),
            'total_revenue' => (float)($row['total_revenue'] ?? 0),
        ];
    }
}

$summary = [
    'paid_orders_count' => count($ordersPayload),
    'unseen_count' => count(array_filter($ordersPayload, fn($o) => $o['is_unseen'])),
    'total_revenue' => array_reduce(
        $ordersPayload,
        fn($carry, $order) => $carry + (float)$order['total_price'],
        0.0
    ),
    'total_items_count' => array_reduce(
        $ordersPayload,
        fn($carry, $order) => $carry + (int)$order['total_items'],
        0
    ),
];

http_response_code(200);
echo json_encode([
    'success' => true,
    'summary' => $summary,
    'totals_by_product' => $totalsByProduct,
    'orders' => $ordersPayload,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
