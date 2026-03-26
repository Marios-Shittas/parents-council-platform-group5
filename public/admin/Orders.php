<?php
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
	<link rel="stylesheet" href="../assets/css/admin_css/admin_orders.css">
</head>
<body>
	<div class="admin-wrapper">
		<?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

		<main class="admin-content">
			<a href="home.php" class="back-link">
				<i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
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
	<script type="text/babel" src="../assets/js/admin-orders.jsx"></script>
</body>
</html>
