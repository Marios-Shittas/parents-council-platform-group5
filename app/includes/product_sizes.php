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

function product_sizes_get_connection()
{
    global $conn;

    return (isset($conn) && $conn instanceof mysqli) ? $conn : null;
}

function product_sizes_ensure_table(): bool
{
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }

    $conn = product_sizes_get_connection();
    if (!$conn) {
        $ready = false;
        return false;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS ProductSizeOptions (
            size_option_id INT(11) NOT NULL AUTO_INCREMENT,
            product_id INT(11) NOT NULL,
            size_value VARCHAR(100) NOT NULL,
            size_label VARCHAR(100) NOT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (size_option_id),
            UNIQUE KEY uq_product_size_value (product_id, size_value),
            KEY idx_product_size_product (product_id),
            CONSTRAINT fk_product_size_product
                FOREIGN KEY (product_id)
                REFERENCES Products (product_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $ready = $conn->query($sql) === true;

    if ($ready) {
        product_sizes_migrate_file_to_db();
    }

    return $ready;
}

function product_sizes_migrate_file_to_db(): void
{
    static $migrated = false;

    if ($migrated) {
        return;
    }

    $migrated = true;
    $conn = product_sizes_get_connection();

    if (!$conn) {
        return;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM ProductSizeOptions");
    if ($result && ($row = $result->fetch_assoc()) && (int)$row['total'] > 0) {
        return;
    }

    $fileData = product_sizes_read_file_all();
    foreach ($fileData as $productId => $meta) {
        product_sizes_set_for_product((int)$productId, !empty($meta['has_sizes']), $meta['size_options'] ?? []);
    }
}

function product_sizes_read_file_all(): array
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

        $rows = product_sizes_normalize_option_rows($meta['size_options'] ?? []);
        $result[(string)$normalizedId] = product_sizes_build_meta($rows);
    }

    return $result;
}

function product_sizes_read_all(): array
{
    if (!product_sizes_ensure_table()) {
        return product_sizes_read_file_all();
    }

    $conn = product_sizes_get_connection();
    if (!$conn) {
        return product_sizes_read_file_all();
    }

    $sql = "
        SELECT product_id, size_value, size_label
        FROM ProductSizeOptions
        ORDER BY product_id ASC, sort_order ASC, size_option_id ASC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        return product_sizes_read_file_all();
    }

    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $productId = (string)((int)$row['product_id']);
        if (!isset($grouped[$productId])) {
            $grouped[$productId] = [];
        }

        $grouped[$productId][] = [
            'value' => (string)$row['size_value'],
            'label' => (string)$row['size_label']
        ];
    }

    $all = [];
    foreach ($grouped as $productId => $rows) {
        $all[$productId] = product_sizes_build_meta($rows);
    }

    return $all;
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

function product_sizes_clean_text($value): string
{
    $value = trim(strip_tags((string)$value));
    $value = preg_replace('/\s+/u', ' ', $value);
    $value = $value === null ? '' : trim($value);

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, 100, 'UTF-8');
    }

    return substr($value, 0, 100);
}

function product_sizes_split_custom_options(string $customSizeOptions): array
{
    $customSizeOptions = trim($customSizeOptions);
    if ($customSizeOptions === '') {
        return [];
    }

    $parts = preg_split('/[\r\n,;]+/u', $customSizeOptions);
    if (!is_array($parts)) {
        return [];
    }

    return $parts;
}

function product_sizes_option_row_from_value($rawValue): array
{
    if (is_array($rawValue)) {
        $value = product_sizes_clean_text($rawValue['value'] ?? '');
        $label = product_sizes_clean_text($rawValue['label'] ?? $value);
    } else {
        $value = product_sizes_clean_text($rawValue);
        $label = $value;
    }

    if ($value === '') {
        return [];
    }

    $allowed = product_sizes_allowed_options();
    $candidateKey = strtolower(str_replace([' ', '_'], '-', $value));

    if (isset($allowed[$candidateKey])) {
        return [
            'value' => $candidateKey,
            'label' => $allowed[$candidateKey]
        ];
    }

    foreach ($allowed as $allowedValue => $allowedLabel) {
        if (strcasecmp($value, $allowedLabel) === 0 || strcasecmp($label, $allowedLabel) === 0) {
            return [
                'value' => $allowedValue,
                'label' => $allowedLabel
            ];
        }
    }

    return [
        'value' => $value,
        'label' => $label !== '' ? $label : $value
    ];
}

function product_sizes_normalize_option_rows($sizeOptions, string $customSizeOptions = ''): array
{
    $normalized = [];
    $seen = [];

    if (!is_array($sizeOptions)) {
        $sizeOptions = [];
    }

    foreach (array_merge($sizeOptions, product_sizes_split_custom_options($customSizeOptions)) as $size) {
        $row = product_sizes_option_row_from_value($size);
        if (empty($row['value']) || empty($row['label'])) {
            continue;
        }

        $seenKey = strtolower($row['value']);
        if (isset($seen[$seenKey])) {
            continue;
        }

        $seen[$seenKey] = true;
        $normalized[] = $row;
    }

    return $normalized;
}

function product_sizes_normalize_options($sizeOptions): array
{
    return array_map(static function (array $row): string {
        return $row['value'];
    }, product_sizes_normalize_option_rows($sizeOptions));
}

function product_sizes_build_meta(array $rows): array
{
    $normalizedRows = product_sizes_normalize_option_rows($rows);
    $values = [];
    $labels = [];
    $customLabels = [];

    foreach ($normalizedRows as $row) {
        $values[] = $row['value'];
        $labels[$row['value']] = $row['label'];

        if (!isset(product_sizes_allowed_options()[$row['value']])) {
            $customLabels[] = $row['label'];
        }
    }

    return [
        'has_sizes' => count($values) > 0,
        'size_options' => $values,
        'size_labels' => $labels,
        'size_options_with_labels' => $normalizedRows,
        'custom_size_options' => implode("\n", $customLabels)
    ];
}

function product_sizes_get_for_product(int $productId): array
{
    if ($productId <= 0) {
        return product_sizes_build_meta([]);
    }

    $all = product_sizes_read_all();
    $key = (string)$productId;

    if (!isset($all[$key]) || !is_array($all[$key])) {
        return product_sizes_build_meta([]);
    }

    return product_sizes_build_meta($all[$key]['size_options_with_labels'] ?? []);
}

function product_sizes_set_for_product(int $productId, bool $hasSizes, array $sizeOptions, string $customSizeOptions = ''): bool
{
    if ($productId <= 0) {
        return false;
    }

    $rows = product_sizes_normalize_option_rows($sizeOptions, $customSizeOptions);

    if (product_sizes_ensure_table()) {
        return product_sizes_replace_db_rows($productId, $hasSizes ? $rows : []);
    }

    $all = product_sizes_read_file_all();
    $key = (string)$productId;

    if (!$hasSizes || count($rows) === 0) {
        unset($all[$key]);
        return product_sizes_write_all($all);
    }

    $all[$key] = product_sizes_build_meta($rows);

    return product_sizes_write_all($all);
}

function product_sizes_replace_db_rows(int $productId, array $rows): bool
{
    $conn = product_sizes_get_connection();
    if (!$conn) {
        return false;
    }

    $startedTransaction = $conn->begin_transaction();

    $deleteStmt = $conn->prepare("DELETE FROM ProductSizeOptions WHERE product_id = ?");
    if (!$deleteStmt) {
        if ($startedTransaction) {
            $conn->rollback();
        }
        return false;
    }

    $deleteStmt->bind_param("i", $productId);
    if (!$deleteStmt->execute()) {
        $deleteStmt->close();
        if ($startedTransaction) {
            $conn->rollback();
        }
        return false;
    }
    $deleteStmt->close();

    if (!empty($rows)) {
        $insertStmt = $conn->prepare("
            INSERT INTO ProductSizeOptions (product_id, size_value, size_label, sort_order)
            VALUES (?, ?, ?, ?)
        ");

        if (!$insertStmt) {
            if ($startedTransaction) {
                $conn->rollback();
            }
            return false;
        }

        foreach ($rows as $index => $row) {
            $sortOrder = $index + 1;
            $value = (string)$row['value'];
            $label = (string)$row['label'];
            $insertStmt->bind_param("issi", $productId, $value, $label, $sortOrder);

            if (!$insertStmt->execute()) {
                $insertStmt->close();
                if ($startedTransaction) {
                    $conn->rollback();
                }
                return false;
            }
        }

        $insertStmt->close();
    }

    if ($startedTransaction) {
        return $conn->commit();
    }

    return true;
}

function product_sizes_remove_for_product(int $productId): bool
{
    if ($productId <= 0) {
        return false;
    }

    if (product_sizes_ensure_table()) {
        return product_sizes_replace_db_rows($productId, []);
    }

    $all = product_sizes_read_file_all();
    unset($all[(string)$productId]);

    return product_sizes_write_all($all);
}

function product_sizes_label_for_value(string $sizeValue, ?array $meta = null): string
{
    $sizeValue = product_sizes_clean_text($sizeValue);
    if ($sizeValue === '') {
        return '';
    }

    if ($meta !== null && !empty($meta['size_labels'][$sizeValue])) {
        return (string)$meta['size_labels'][$sizeValue];
    }

    $allowed = product_sizes_allowed_options();
    return $allowed[$sizeValue] ?? $sizeValue;
}
