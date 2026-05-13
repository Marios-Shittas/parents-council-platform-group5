<?php
// Arxeio: public\admin\Orders.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
	header('Location: /parents-council-platform-group5/public/login.php');
	exit;
}

// Xeirizetai AJAX request gia fortosi orders apo tin idia selida, oste na douleuei se localhost kai deployed paths.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_orders') {
	require_once __DIR__ . '/../../app/config/db.php';
	require_once __DIR__ . '/../../app/services/OrdersService.php';

	header('Content-Type: application/json; charset=utf-8');

	try {
		$testResult = $conn->query("SHOW COLUMNS FROM Orders LIKE 'admin_seen_at'");
		if (!$testResult || $testResult->num_rows === 0) {
			if (!$conn->query("ALTER TABLE Orders ADD COLUMN admin_seen_at datetime DEFAULT NULL")) {
				throw new RuntimeException('Failed to create admin_seen_at column: ' . $conn->error);
			}
		}

		$ordersService = new OrdersService($conn);
		$ordersService->handleRequest();
	} catch (Throwable $e) {
		http_response_code(500);
		echo json_encode([
			'success' => false,
			'message' => $e->getMessage(),
		], JSON_UNESCAPED_UNICODE);
		error_log('Orders page API error: ' . $e->getMessage());
	}

	if (isset($conn) && $conn instanceof mysqli) {
		$conn->close();
	}

	exit;
}

// Xeirizetai AJAX requests gia mark_order_seen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	$action = $_POST['action'];
	
	if ($action === 'mark_order_seen') {
		require_once __DIR__ . '/../../app/config/db.php';
		require_once __DIR__ . '/../../app/services/OrdersService.php';
		
		$order_id = (int)($_POST['order_id'] ?? 0);
		$success = false;

		if ($order_id > 0) {
			$ordersService = new OrdersService($conn);
			$success = $ordersService->markOrderAsSeen($order_id);
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => $success,
			'order_id' => $order_id,
			'pending_paid_orders_count' => (int)(new OrdersService($conn))->getPendingPaidOrdersCount(),
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	if ($action === 'clear_product_order_history') {
		require_once __DIR__ . '/../../app/config/db.php';
		require_once __DIR__ . '/../../app/services/OrdersService.php';

		header('Content-Type: application/json; charset=utf-8');

		try {
			$ordersService = new OrdersService($conn);
			$stats = $ordersService->clearProductOrderHistory();

			echo json_encode([
				'success' => true,
				'message' => 'Το ιστορικό παραγγελιών e-shop καθαρίστηκε.',
				'stats' => $stats,
				'pending_paid_orders_count' => (int)$ordersService->getPendingPaidOrdersCount(),
			], JSON_UNESCAPED_UNICODE);
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Δεν ήταν δυνατός ο καθαρισμός του ιστορικού παραγγελιών.',
			], JSON_UNESCAPED_UNICODE);
			error_log('Clear product order history error: ' . $e->getMessage());
		}

		exit;
	}

	if ($action === 'delete_product_order_history') {
		require_once __DIR__ . '/../../app/config/db.php';
		require_once __DIR__ . '/../../app/services/OrdersService.php';

		header('Content-Type: application/json; charset=utf-8');

		try {
			$orderId = (int)($_POST['order_id'] ?? 0);
			$ordersService = new OrdersService($conn);
			$stats = $ordersService->deleteProductOrderHistory($orderId);

			echo json_encode([
				'success' => true,
				'message' => 'Η παραγγελία e-shop διαγράφηκε.',
				'stats' => $stats,
				'pending_paid_orders_count' => (int)$ordersService->getPendingPaidOrdersCount(),
			], JSON_UNESCAPED_UNICODE);
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Δεν ήταν δυνατή η διαγραφή της παραγγελίας.',
			], JSON_UNESCAPED_UNICODE);
			error_log('Delete product order history error: ' . $e->getMessage());
		}

		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Πληρωμένες Παραγγελίες - Admin</title>

	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

	<link rel="stylesheet" href="../assets/css/main.css">
	<link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
	<link rel="stylesheet" href="../assets/css/admin_css/admin_orders.css?v=9">
</head>
<body>
	<div class="admin-wrapper">
		<?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

		<main class="admin-content">
			<a href="home.php" class="back-link">
				<i class="fas fa-arrow-left"></i> Πίσω στην Αρχική
			</a>

			<div class="admin-header">
				<h1><i class="fas fa-cash-register mr-2"></i>Πληρωμένες Παραγγελίες</h1>
			</div>

			<div id="orders-root"></div>
		</main>
	</div>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
	<script type="text/babel" src="../assets/js/admin-orders.jsx?v=9"></script>
</body>
</html>
