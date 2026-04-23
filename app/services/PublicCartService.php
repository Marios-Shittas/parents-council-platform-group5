<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class PublicCartService
{
    private const SESSION_KEY = 'public_eshop_cart';

    private mysqli $conn;

    public function __construct(?mysqli $conn = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($conn instanceof mysqli) {
            $this->conn = $conn;
            return;
        }

        global $conn;
        $this->conn = $conn;
    }

    public function getCart(): array
    {
        $storedItems = $this->getStoredItems();
        if (empty($storedItems)) {
            return [
                'order_id' => null,
                'items' => [],
                'total' => 0.0,
            ];
        }

        $resolvedItems = [];
        $cleanItems = [];
        $total = 0.0;

        foreach ($storedItems as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $size = trim((string) ($item['size'] ?? ''));

            if ($productId <= 0) {
                continue;
            }

            $product = $this->loadProductSnapshot($productId);
            if ($product === null) {
                continue;
            }

            $priceAtPurchase = (float) ($item['price_at_purchase'] ?? 0);
            if ($priceAtPurchase <= 0) {
                $priceAtPurchase = (float) ($product['price'] ?? 0);
            }

            if ($priceAtPurchase <= 0) {
                continue;
            }

            $lineTotal = $priceAtPurchase * $quantity;

            $resolvedItems[] = [
                'order_id' => null,
                'product_id' => $productId,
                'price_at_purchase' => $priceAtPurchase,
                'quantity' => $quantity,
                'size' => $size,
                'product_name' => (string) ($product['product_name'] ?? 'Προϊόν'),
                'product_description' => (string) ($product['product_description'] ?? ''),
                'price' => (float) ($product['price'] ?? 0),
                'product_image' => (string) ($product['product_image'] ?? $this->getDefaultProductImagePath()),
                'line_total' => $lineTotal,
            ];

            $cleanItems[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'price_at_purchase' => $priceAtPurchase,
            ];

            $total += $lineTotal;
        }

        $this->setStoredItems($cleanItems);

        return [
            'order_id' => null,
            'items' => $resolvedItems,
            'total' => $total,
        ];
    }

    public function getCheckoutItems(): array
    {
        $cart = $this->getCart();
        return array_values($cart['items'] ?? []);
    }

    public function addToCart(int $productId, int $quantity = 1, string $size = ''): bool
    {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        $size = trim($size);

        if ($productId <= 0 || $quantity <= 0) {
            return false;
        }

        $product = $this->loadProductSnapshot($productId);
        if ($product === null) {
            return false;
        }

        $priceAtPurchase = (float) ($product['price'] ?? 0);
        if ($priceAtPurchase <= 0) {
            return false;
        }

        $items = $this->getStoredItems();
        $existingIndex = $this->findCartItemIndex($items, $productId, $size);

        if ($existingIndex !== null) {
            $items[$existingIndex]['quantity'] = max(1, (int) ($items[$existingIndex]['quantity'] ?? 1)) + $quantity;
            if (!isset($items[$existingIndex]['price_at_purchase']) || (float) $items[$existingIndex]['price_at_purchase'] <= 0) {
                $items[$existingIndex]['price_at_purchase'] = $priceAtPurchase;
            }
        } else {
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'price_at_purchase' => $priceAtPurchase,
            ];
        }

        $this->setStoredItems($items);
        return true;
    }

    public function updateCartItem(int $productId, string $size, int $quantity): bool
    {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        $size = trim($size);

        if ($productId <= 0) {
            return false;
        }

        if ($quantity <= 0) {
            return $this->removeFromCart($productId, $size);
        }

        $items = $this->getStoredItems();
        $existingIndex = $this->findCartItemIndex($items, $productId, $size);
        if ($existingIndex === null) {
            return false;
        }

        $items[$existingIndex]['quantity'] = $quantity;
        $this->setStoredItems($items);
        return true;
    }

    public function removeFromCart(int $productId, string $size): bool
    {
        $productId = (int) $productId;
        $size = trim($size);

        if ($productId <= 0) {
            return false;
        }

        $items = $this->getStoredItems();
        $existingIndex = $this->findCartItemIndex($items, $productId, $size);
        if ($existingIndex === null) {
            return false;
        }

        array_splice($items, $existingIndex, 1);
        $this->setStoredItems($items);
        return true;
    }

    public function clearCart(): bool
    {
        unset($_SESSION[self::SESSION_KEY]);
        return true;
    }

    private function getStoredItems(): array
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        return is_array($items) ? array_values($items) : [];
    }

    private function setStoredItems(array $items): void
    {
        $normalizedItems = array_values(array_filter($items, static function ($item): bool {
            return is_array($item) && (int) ($item['product_id'] ?? 0) > 0 && (int) ($item['quantity'] ?? 0) > 0;
        }));

        if (empty($normalizedItems)) {
            unset($_SESSION[self::SESSION_KEY]);
            return;
        }

        $_SESSION[self::SESSION_KEY] = $normalizedItems;
    }

    private function findCartItemIndex(array $items, int $productId, string $size): ?int
    {
        foreach ($items as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) !== $productId) {
                continue;
            }

            if (trim((string) ($item['size'] ?? '')) === $size) {
                return $index;
            }
        }

        return null;
    }

    private function loadProductSnapshot(int $productId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT
                p.product_id,
                p.product_name,
                p.product_description,
                p.price,
                (
                    SELECT pi.image_path
                    FROM ProductsImages pi
                    WHERE pi.product_id = p.product_id
                    ORDER BY pi.pro_image_id DESC
                    LIMIT 1
                ) AS product_image
             FROM Products p
             WHERE p.product_id = ?
             LIMIT 1"
        );

        if ($stmt === false) {
            return null;
        }

        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!is_array($row)) {
            return null;
        }

        $row['product_image'] = $this->resolveProductImagePath((string) ($row['product_image'] ?? ''));
        return $row;
    }

    private function getDefaultProductImagePath(): string
    {
        return '/parents-council-platform-group5/public/assets/Products_img/default-product.svg';
    }

    private function resolveProductImagePath(string $imagePath): string
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return $this->getDefaultProductImagePath();
        }

        $normalized = str_replace('\\', '/', $imagePath);
        $projectRoot = dirname(__DIR__, 2);

        if (strpos($normalized, '/public/') !== false) {
            $absolutePath = $projectRoot . substr($normalized, strpos($normalized, '/public/'));
        } elseif (strpos($normalized, '/assets/') === 0) {
            $absolutePath = $projectRoot . '/public' . $normalized;
        } else {
            $absolutePath = $projectRoot . '/public/' . ltrim($normalized, '/');
        }

        return file_exists($absolutePath) ? $imagePath : $this->getDefaultProductImagePath();
    }
}
