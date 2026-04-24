<?php

function product_sizes_file_path(): string
{
    return __DIR__ . '/../../storage/product_size_options.json';
}

function product_sizes_allowed_options(): array
{
    return [
        'x-small' => 'X-Small',
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        'x-large' => 'X-Large',
        'one-size' => 'One Size'
    ];
}

function product_sizes_read_all(): array
{
    $filePath = product_sizes_file_path();
    if (!file_exists($filePath)) {
        return [];
    }

    $content = @file_get_contents($filePath);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return [];
    }

    $result = [];
    foreach ($decoded as $productId => $meta) {
        $normalizedId = (int)$productId;
        if ($normalizedId <= 0 || !is_array($meta)) {
            continue;
        }

        $result[(string)$normalizedId] = [
            'has_sizes' => !empty($meta['has_sizes']),
            'size_options' => product_sizes_normalize_options($meta['size_options'] ?? [])
        ];
    }

    return $result;
}

function product_sizes_write_all(array $data): bool
{
    $filePath = product_sizes_file_path();
    $dir = dirname($filePath);

    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return false;
    }

    return @file_put_contents($filePath, $encoded . PHP_EOL, LOCK_EX) !== false;
}

function product_sizes_normalize_options($sizeOptions): array
{
    $allowed = array_keys(product_sizes_allowed_options());
    $normalized = [];

    if (!is_array($sizeOptions)) {
        return [];
    }

    foreach ($sizeOptions as $size) {
        $value = strtolower(trim((string)$size));
        if ($value !== '' && in_array($value, $allowed, true)) {
            $normalized[$value] = $value;
        }
    }

    return array_values($normalized);
}

function product_sizes_get_for_product(int $productId): array
{
    if ($productId <= 0) {
        return ['has_sizes' => false, 'size_options' => []];
    }

    $all = product_sizes_read_all();
    $key = (string)$productId;

    if (!isset($all[$key]) || !is_array($all[$key])) {
        return ['has_sizes' => false, 'size_options' => []];
    }

    $meta = $all[$key];
    $sizeOptions = product_sizes_normalize_options($meta['size_options'] ?? []);
    $hasSizes = !empty($meta['has_sizes']) && count($sizeOptions) > 0;

    return [
        'has_sizes' => $hasSizes,
        'size_options' => $hasSizes ? $sizeOptions : []
    ];
}

function product_sizes_set_for_product(int $productId, bool $hasSizes, array $sizeOptions): bool
{
    if ($productId <= 0) {
        return false;
    }

    $normalized = product_sizes_normalize_options($sizeOptions);
    $all = product_sizes_read_all();
    $key = (string)$productId;

    if (!$hasSizes || count($normalized) === 0) {
        unset($all[$key]);
        return product_sizes_write_all($all);
    }

    $all[$key] = [
        'has_sizes' => true,
        'size_options' => $normalized
    ];

    return product_sizes_write_all($all);
}

function product_sizes_remove_for_product(int $productId): bool
{
    if ($productId <= 0) {
        return false;
    }

    $all = product_sizes_read_all();
    unset($all[(string)$productId]);

    return product_sizes_write_all($all);
}
