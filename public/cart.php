<?php
// Arxeio: public\cart.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.
// Public route gia to kalathi agoron.
// Leitourgei san JSON API endpoint gia get/add/upimerominia/remove/clear sto kalathi.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ola ta responses einai JSON gia na ta diavazei to frontend xoris HTML parsing.
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/services/CartService.php';
require_once __DIR__ . '/../app/services/EshopSettingsService.php';
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_role('parent', [
    'mode' => 'json',
    'message' => 'Μόνο λογαριασμοί γονέα μπορούν να χρησιμοποιήσουν το καλάθι.'
]);

// Kratame ton xristis apo to session gia na min mporei kapoios na peiraksei allo kalathi.
$userId = (int)$_SESSION['user_id'];
$cartService = new CartService();
$eshopSettingsService = new EshopSettingsService();

// To action mporei na erthei apo POST i GET, me default tin anagnosi tou kalathi.
$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

// An to shop einai kleisto apo settings, stamataei kathe kalathi action.
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
        // Epistrefei tin trexousa katastasi tou kalathiou.
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
        // Prosthetei proion sto kalathi, mazi me megethos an yparxei.
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
        // Allazei posotita gia ena idi yparxon item tou kalathi.
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
        // Aferei ena sygkekrimeno proion/size apo to kalathi.
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
        // Katharizei olo to kalathi tou syndedemenou gonea.
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
