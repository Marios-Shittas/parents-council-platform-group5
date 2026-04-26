<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/services/CartService.php';
require_once __DIR__ . '/../app/services/EshopSettingsService.php';
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_role('parent', [
    'mode' => 'json',
    'message' => 'Μόνο λογαριασμοί γονέα μπορούν να χρησιμοποιήσουν το καλάθι.'
]);

$userId = (int)$_SESSION['user_id'];
$cartService = new CartService();
$eshopSettingsService = new EshopSettingsService();

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

if (!$eshopSettingsService->isShopVisible()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Το κατάστημα είναι προσωρινά μη διαθέσιμο. Coming soon.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'get':
        $cart = $cartService->getCart($userId);

        if ($cart === false) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Σφάλμα κατά την ανάκτηση του καλαθιού.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Το καλάθι φορτώθηκε επιτυχώς.',
            'cart' => $cart
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $size = trim((string)($_POST['size'] ?? ''));

        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Μη έγκυρο προϊόν.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $added = $cartService->addToCart($userId, $productId, $quantity, $size);

        if (!$added) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Σφάλμα κατά την προσθήκη στο καλάθι.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cart = $cartService->getCart($userId);

        echo json_encode([
            'success' => true,
            'message' => 'Το προϊόν προστέθηκε στο καλάθι.',
            'cart' => $cart
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $size = trim((string)($_POST['size'] ?? ''));
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Μη έγκυρο προϊόν καλαθιού.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $updated = $cartService->updateCartItem($userId, $productId, $size, $quantity);

        if (!$updated) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Σφάλμα κατά την ενημέρωση του καλαθιού.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cart = $cartService->getCart($userId);

        echo json_encode([
            'success' => true,
            'message' => 'Το καλάθι ενημερώθηκε επιτυχώς.',
            'cart' => $cart
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        $size = trim((string)($_POST['size'] ?? ''));

        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Μη έγκυρο προϊόν καλαθιού.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $removed = $cartService->removeFromCart($userId, $productId, $size);

        if (!$removed) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Σφάλμα κατά την αφαίρεση από το καλάθι.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cart = $cartService->getCart($userId);

        echo json_encode([
            'success' => true,
            'message' => 'Το προϊόν αφαιρέθηκε από το καλάθι.',
            'cart' => $cart
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'clear':
        $cleared = $cartService->clearCart($userId);

        if (!$cleared) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Σφάλμα κατά το άδειασμα του καλαθιού.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cart = $cartService->getCart($userId);

        echo json_encode([
            'success' => true,
            'message' => 'Το καλάθι άδειασε επιτυχώς.',
            'cart' => $cart
        ], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Μη έγκυρη ενέργεια.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
}
