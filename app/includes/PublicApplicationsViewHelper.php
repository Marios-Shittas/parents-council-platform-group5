<?php

final class PublicApplicationsViewHelper
{
    /**
     * Loads UI metadata for the public applications page.
     */
    public static function loadApplicationUiMeta(): array
    {
        $path = __DIR__ . '/../../storage/application_ui_meta.json';
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Formats an ISO date to d/m/Y for UI output.
     */
    public static function formatUiDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt && $dt->format('Y-m-d') === $date) {
            return $dt->format('d/m/Y');
        }

        return $date;
    }

    /**
     * Normalizes $_FILES input (single or multi) into a flat array.
     *
     * @param array<string, mixed> $files
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeUploadedSubmissionFiles(array $files): array
    {
        $normalized = [];

        if (!isset($files['name'])) {
            return $normalized;
        }

        if (is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $name = (string)($files['name'][$i] ?? '');
                $error = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($name === '' && $error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $normalized[] = [
                    'name' => $name,
                    'type' => (string)($files['type'][$i] ?? ''),
                    'tmp_name' => (string)($files['tmp_name'][$i] ?? ''),
                    'error' => $error,
                    'size' => (int)($files['size'][$i] ?? 0),
                ];
            }

            return $normalized;
        }

        $name = (string)($files['name'] ?? '');
        $error = (int)($files['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($name === '' && $error === UPLOAD_ERR_NO_FILE) {
            return $normalized;
        }

        $normalized[] = [
            'name' => $name,
            'type' => (string)($files['type'] ?? ''),
            'tmp_name' => (string)($files['tmp_name'] ?? ''),
            'error' => $error,
            'size' => (int)($files['size'] ?? 0),
        ];

        return $normalized;
    }

    /**
     * Returns a safe basename display value for an uploaded file path.
     */
    public static function getUploadedSubmissionDisplayName(string $fileName): string
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return '';
        }

        return basename(str_replace('\\', '/', $fileName));
    }

    /**
     * Returns storage path for application document display names.
     */
    public static function getApplicationDocumentDisplayNamesPath(): string
    {
        return __DIR__ . '/../../storage/application_document_display_names.json';
    }

    /**
     * Loads application document display-name mappings.
     */
    public static function loadApplicationDocumentDisplayNames(): array
    {
        $path = self::getApplicationDocumentDisplayNamesPath();
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Returns storage path for uploaded submission display names.
     */
    public static function getSubmissionFileDisplayNamesPath(): string
    {
        return __DIR__ . '/../../storage/submission_file_display_names.json';
    }

    /**
     * Loads submission display-name mappings.
     */
    public static function loadSubmissionFileDisplayNames(): array
    {
        $path = self::getSubmissionFileDisplayNamesPath();
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Persists submission display-name mappings.
     */
    public static function saveSubmissionFileDisplayNames(array $displayNames): bool
    {
        $path = self::getSubmissionFileDisplayNamesPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return file_put_contents($path, json_encode($displayNames, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Normalizes the configured submission mode.
     */
    public static function normalizeSubmissionMode($mode): string
    {
        $mode = trim((string)$mode);
        return in_array($mode, ['manual', 'upload'], true) ? $mode : 'upload';
    }

    /**
     * Builds a stable key for dynamically defined manual fields.
     */
    public static function normalizeManualSubmissionFieldKey(string $rawName, int $index = 0): string
    {
        $safeName = trim($rawName);
        $safeName = preg_replace('/\s+/u', '_', $safeName) ?? '';
        $safeName = preg_replace('/[^a-zA-Z0-9_]/u', '_', $safeName) ?? '';
        $safeName = trim($safeName, '_');

        if ($safeName === '') {
            $safeName = 'field_' . (string)($index + 1);
        }

        return $safeName;
    }

    /**
     * Validates manual submission payload against dynamic field metadata.
     */
    public static function validateManualSubmissionPayload(array $payload, array $applicationFields = []): string
    {
        if (!empty($applicationFields)) {
            foreach (array_values($applicationFields) as $index => $field) {
                if (!is_array($field)) {
                    continue;
                }

                $rawFieldName = trim((string)($field['field_name'] ?? ''));
                $fieldType = strtolower(trim((string)($field['field_type'] ?? 'text')));
                if ($fieldType === 'phone') {
                    $fieldType = 'tel';
                }

                $isRequired = (bool)($field['is_required'] ?? false);
                $fieldKey = self::normalizeManualSubmissionFieldKey($rawFieldName, (int)$index);
                $fieldValue = trim((string)($payload[$fieldKey] ?? ''));
                $fieldLabel = $rawFieldName !== '' ? $rawFieldName : ('Πεδίο ' . (string)($index + 1));

                if ($isRequired) {
                    if ($fieldType === 'checkbox') {
                        $normalizedCheckboxValue = strtolower($fieldValue);
                        $isChecked = in_array($normalizedCheckboxValue, ['1', 'true', 'on', 'yes'], true);
                        if (!$isChecked) {
                            return 'Παρακαλώ συμπληρώστε το πεδίο: ' . $fieldLabel . '.';
                        }
                    } elseif ($fieldValue === '') {
                        return 'Παρακαλώ συμπληρώστε το πεδίο: ' . $fieldLabel . '.';
                    }
                }

                if ($fieldType === 'email' && $fieldValue !== '' && !filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                    return 'Παρακαλώ εισάγετε έγκυρο email στο πεδίο: ' . $fieldLabel . '.';
                }

                if ($fieldType === 'tel' && $fieldValue !== '' && !preg_match('/^[0-9]{6,15}$/', $fieldValue)) {
                    return 'Παρακαλώ εισάγετε έγκυρο τηλέφωνο (μόνο αριθμούς) στο πεδίο: ' . $fieldLabel . '.';
                }

                if ($fieldType === 'date' && $fieldValue !== '') {
                    $dateValue = DateTime::createFromFormat('Y-m-d', $fieldValue);
                    if (!$dateValue || $dateValue->format('Y-m-d') !== $fieldValue) {
                        return 'Παρακαλώ εισάγετε έγκυρη ημερομηνία (YYYY-MM-DD) στο πεδίο: ' . $fieldLabel . '.';
                    }
                }

                if ($fieldType === 'number' && $fieldValue !== '' && !preg_match('/^-?(?:\d+|\d*\.\d+)$/', $fieldValue)) {
                    return 'Παρακαλώ εισάγετε έγκυρο αριθμό στο πεδίο: ' . $fieldLabel . '.';
                }
            }

            return '';
        }

        return '';
    }

    /**
     * Ensures an internal guest identity exists for unauthenticated submissions.
     */
    public static function ensurePublicGuestSubmissionIdentity(): array
    {
        global $conn;

        if (!($conn instanceof mysqli)) {
            return ['user_id' => 0, 'email' => ''];
        }

        $sharedGuestEmail = 'public_guest@guest.local';
        $selectStmt = $conn->prepare('SELECT user_id FROM Users WHERE email = ? LIMIT 1');
        if ($selectStmt) {
            $selectStmt->bind_param('s', $sharedGuestEmail);
            if ($selectStmt->execute()) {
                $existingResult = $selectStmt->get_result();
                if ($existingResult && $existingResult->num_rows > 0) {
                    $existingRow = $existingResult->fetch_assoc();
                    return ['user_id' => (int)($existingRow['user_id'] ?? 0), 'email' => ''];
                }
            }
            $selectStmt->close();
        }

        try {
            $guestPasswordSeed = bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            $guestPasswordSeed = hash('sha256', microtime(true) . (string)mt_rand());
        }

        $guestPassword = password_hash($guestPasswordSeed, PASSWORD_DEFAULT);
        $guestName = 'Public';
        $guestSurname = 'Guest';
        $guestPhone = '';
        $guestChildren = 0;
        $guestRole = 'parent';
        $guestStatus = 'approved';

        $insertStmt = $conn->prepare(
            'INSERT INTO Users (name, surname, email, password, phone_number, number_of_children, role, account_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if ($insertStmt) {
            $insertStmt->bind_param(
                'sssssiss',
                $guestName,
                $guestSurname,
                $sharedGuestEmail,
                $guestPassword,
                $guestPhone,
                $guestChildren,
                $guestRole,
                $guestStatus
            );

            if ($insertStmt->execute()) {
                $insertStmt->close();
                return ['user_id' => (int)$conn->insert_id, 'email' => ''];
            }
            $insertStmt->close();

            if ((int)$conn->errno === 1062) {
                $retrySelectStmt = $conn->prepare('SELECT user_id FROM Users WHERE email = ? LIMIT 1');
                if ($retrySelectStmt) {
                    $retrySelectStmt->bind_param('s', $sharedGuestEmail);
                    if ($retrySelectStmt->execute()) {
                        $retryResult = $retrySelectStmt->get_result();
                        if ($retryResult && $retryResult->num_rows > 0) {
                            $retryRow = $retryResult->fetch_assoc();
                            $retrySelectStmt->close();
                            return ['user_id' => (int)($retryRow['user_id'] ?? 0), 'email' => ''];
                        }
                    }
                    $retrySelectStmt->close();
                }
            }
        }

        return ['user_id' => 0, 'email' => ''];
    }
}
