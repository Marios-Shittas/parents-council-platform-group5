<?php

final class ProductImageHelper
{
    /**
     * Returns default product image path when no image is available.
     */
    public static function getDefaultImagePath(): string
    {
        return '/parents-council-platform-group5/public/assets/Products_img/default-product.svg';
    }

    /**
     * Resolves a product image path to an accessible URL or returns default.
     */
    public static function resolveImagePath(string $imagePath): string
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return self::getDefaultImagePath();
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

        return file_exists($absolutePath) ? $imagePath : self::getDefaultImagePath();
    }
}
