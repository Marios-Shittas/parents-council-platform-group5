<?php
// Arxeio: app\views\pages\applications.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
/**
 * Goneas Aitiseis Page
 * Displays ola aitiseis kai allows goneas xristes to submit them.
 */

require_once __DIR__ . '/../../services/ApplicationsService.php';
require_once __DIR__ . '/../../includes/site_context.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Arxikopoiei to ypiresia kai fortonei tis vasikes eksartiseis.
$applicationsService = new ApplicationsService();

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function loadApplicationUiMetaPublic(): array {
    $path = __DIR__ . '/../../../storage/application_ui_meta.json';
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function formatUiDatePublic(string $date): string {
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
 * Normalize $_FILES input (ena i multiple) mesa se a flat arxeia pinakas.
 *
 * @param array<string, mixed - > $arxeia
 * @return array<int, - pinakas<string, mixed>>
 */
function normalizeUploadedSubmissionFiles(array $files): array {
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getUploadedSubmissionDisplayName(string $fileName): string {
    $fileName = trim($fileName);
    if ($fileName === '') {
        return '';
    }

    return basename(str_replace('\\', '/', $fileName));
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getApplicationDocumentDisplayNamesPathPublic(): string {
    return __DIR__ . '/../../../storage/application_document_display_names.json';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function loadApplicationDocumentDisplayNamesPublic(): array {
    $path = getApplicationDocumentDisplayNamesPathPublic();
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getSubmissionFileDisplayNamesPathPublic(): string {
    return __DIR__ . '/../../../storage/submission_file_display_names.json';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function loadSubmissionFileDisplayNamesPublic(): array {
    $path = getSubmissionFileDisplayNamesPathPublic();
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function saveSubmissionFileDisplayNamesPublic(array $displayNames): bool {
    $path = getSubmissionFileDisplayNamesPathPublic();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($displayNames, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function normalizeSubmissionMode($mode): string {
    $mode = trim((string)$mode);
    return in_array($mode, ['manual', 'upload'], true) ? $mode : 'upload';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function normalizeManualSubmissionFieldKey(string $rawName, int $index = 0): string {
    $safeName = trim($rawName);
    $safeName = preg_replace('/\s+/u', '_', $safeName) ?? '';
    $safeName = preg_replace('/[^a-zA-Z0-9_]/u', '_', $safeName) ?? '';
    $safeName = trim($safeName, '_');

    if ($safeName === '') {
        $safeName = 'field_' . (string)($index + 1);
    }

    return $safeName;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function validateManualSubmissionPayload(array $payload, array $applicationFields = []): string {
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
            $fieldKey = normalizeManualSubmissionFieldKey($rawFieldName, (int)$index);
            $fieldValue = trim((string)($payload[$fieldKey] ?? ''));
            $fieldLabel = $rawFieldName !== '' ? $rawFieldName : ('Î ÎµÎ´Î¯Î¿ ' . (string)($index + 1));

            if ($isRequired) {
                if ($fieldType === 'checkbox') {
                    $normalizedCheckboxValue = strtolower($fieldValue);
                    $isChecked = in_array($normalizedCheckboxValue, ['1', 'true', 'on', 'yes'], true);
                    if (!$isChecked) {
                        return 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ ÏƒÏ…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ Ï„Î¿ Ï€ÎµÎ´Î¯Î¿: ' . $fieldLabel . '.';
                    }
                } elseif ($fieldValue === '') {
                    return 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ ÏƒÏ…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ Ï„Î¿ Ï€ÎµÎ´Î¯Î¿: ' . $fieldLabel . '.';
                }
            }

            if ($fieldType === 'email' && $fieldValue !== '' && !filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                return 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ ÎµÎ¹ÏƒÎ¬Î³ÎµÏ„Îµ Î­Î³ÎºÏ…ÏÎ¿ email ÏƒÏ„Î¿ Ï€ÎµÎ´Î¯Î¿: ' . $fieldLabel . '.';
            }

            if ($fieldType === 'tel' && $fieldValue !== '' && !preg_match('/^[0-9]{6,15}$/', $fieldValue)) {
                return 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ ÎµÎ¹ÏƒÎ¬Î³ÎµÏ„Îµ Î­Î³ÎºÏ…ÏÎ¿ Ï„Î·Î»Î­Ï†Ï‰Î½Î¿ (Î¼ÏŒÎ½Î¿ Î±ÏÎ¹Î¸Î¼Î¿ÏÏ‚) ÏƒÏ„Î¿ Ï€ÎµÎ´Î¯Î¿: ' . $fieldLabel . '.';
            }

            if ($fieldType === 'date' && $fieldValue !== '') {
                $dateValue = DateTime::createFromFormat('Y-m-d', $fieldValue);
                if (!$dateValue || $dateValue->format('Y-m-d') !== $fieldValue) {
                    return 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ ÎµÎ¹ÏƒÎ¬Î³ÎµÏ„Îµ Î­Î³ÎºÏ…ÏÎ· Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± (YYYY-MM-DD) ÏƒÏ„Î¿ Ï€ÎµÎ´Î¯Î¿: ' . $fieldLabel . '.';
                }
            }

            if ($fieldType === 'number' && $fieldValue !== '' && !preg_match('/^-?(?:\d+|\d*\.\d+)$/', $fieldValue)) {
                return 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ ÎµÎ¹ÏƒÎ¬Î³ÎµÏ„Îµ Î­Î³ÎºÏ…ÏÎ¿ Î±ÏÎ¹Î¸Î¼ÏŒ ÏƒÏ„Î¿ Ï€ÎµÎ´Î¯Î¿: ' . $fieldLabel . '.';
            }
        }

        return '';
    }

    return '';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function ensurePublicGuestSubmissionIdentity(): array {
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
            return ['user_id' => (int)$conn->insert_id, 'email' => ''];
        }

        if ((int)$conn->errno === 1062) {
            $retrySelectStmt = $conn->prepare('SELECT user_id FROM Users WHERE email = ? LIMIT 1');
            if ($retrySelectStmt) {
                $retrySelectStmt->bind_param('s', $sharedGuestEmail);
                if ($retrySelectStmt->execute()) {
                    $retryResult = $retrySelectStmt->get_result();
                    if ($retryResult && $retryResult->num_rows > 0) {
                        $retryRow = $retryResult->fetch_assoc();
                        return ['user_id' => (int)($retryRow['user_id'] ?? 0), 'email' => ''];
                    }
                }
            }
        }
    }

    return ['user_id' => 0, 'email' => ''];
}

$isAuthenticatedParent = isset($_SESSION['user_id'], $_SESSION['role'])
    && (int)$_SESSION['user_id'] > 0
    && (string)$_SESSION['role'] === 'parent';

$guestIdentity = ['user_id' => 0, 'email' => ''];
if (!$isAuthenticatedParent) {
    $guestIdentity = ensurePublicGuestSubmissionIdentity();
}

$user_id = $isAuthenticatedParent ? (int)$_SESSION['user_id'] : (int)($guestIdentity['user_id'] ?? 0);
$canSubmitApplications = $user_id > 0;
$canShowSubmissions = $isAuthenticatedParent && $user_id > 0;
$currentUserEmail = $isAuthenticatedParent
    ? trim((string)($_SESSION['email'] ?? ''))
    : trim((string)($guestIdentity['email'] ?? ''));
$showGuestRegistrationReminder = !$isAuthenticatedParent;
$loginUrl = site_login_url();
$message = '';
$messageType = 'info';

$uploadDir = __DIR__ . '/../../../storage/uploads/submissions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/*
 |------------------------------------------------------------
 | AJAX: Get custom form fields for application
 |------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_form_fields'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $application_id = (int)($_GET['application_id'] ?? 0);
        if ($application_id <= 0) {
            echo json_encode(['success' => false, 'fields' => []]);
            exit;
        }

        $customFields = $applicationsService->getApplicationFormFields($application_id);
        if (empty($customFields)) {
            echo json_encode(['success' => true, 'fields' => [], 'hasCustomFields' => false]);
            exit;
        }

        $fields = [];
        foreach ($customFields as $field) {
            $fields[] = [
                'name' => (string)($field['field_name'] ?? ''),
                'label' => (string)($field['field_name'] ?? ''),
                'type' => (string)($field['field_type'] ?? 'text'),
                'required' => (bool)($field['is_required'] ?? false),
                'icon' => 'fa-keyboard'
            ];
        }

        echo json_encode(['success' => true, 'fields' => $fields, 'hasCustomFields' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'fields' => []]);
    }
    exit;
}

/*
 |------------------------------------------------------------
 | AJAX: Submit application with form data + up to 4 files
 |------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_submit_v2'])) {
    ob_start();
    try {
        if (!$canSubmitApplications) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Î”ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î´Ï…Î½Î±Ï„Î® Î· Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î± Ï€ÏÎ¿Ï†Î¯Î» Ï…Ï€Î¿Î²Î¿Î»Î®Ï‚ Î±Ï…Ï„Î® Ï„Î· ÏƒÏ„Î¹Î³Î¼Î®. Î Î±ÏÎ±ÎºÎ±Î»ÏŽ Î±Î½Î±Î½ÎµÏŽÏƒÏ„Îµ Ï„Î· ÏƒÎµÎ»Î¯Î´Î± ÎºÎ±Î¹ Î´Î¿ÎºÎ¹Î¼Î¬ÏƒÏ„Îµ Î¾Î±Î½Î¬.',
            ]);
            exit;
        }

        $application_id = (int)($_POST['application_id'] ?? 0);
        $raw = $_POST['submission_data'] ?? '';

        if ($application_id <= 0) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid application.']);
            exit;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid submission payload.']);
            exit;
        }

        foreach ($decoded as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $decoded[$key] = trim((string)$value);
            }
        }

        if ($isAuthenticatedParent && $applicationsService->hasUserSubmitted($application_id, $user_id)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ÎˆÏ‡ÎµÏ„Îµ Î®Î´Î· Ï…Ï€Î¿Î²Î¬Î»ÎµÎ¹ Î±Ï…Ï„Î® Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ·.']);
            exit;
        }

        $submissionMode = normalizeSubmissionMode($decoded['_submission_mode'] ?? ($_POST['submission_mode'] ?? 'upload'));
        $decoded['_submission_mode'] = $submissionMode;

        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $maxSubmissionFiles = 4;
        $incomingFiles = [];

        if (isset($_FILES['submission_files'])) {
            $incomingFiles = array_merge($incomingFiles, normalizeUploadedSubmissionFiles((array)$_FILES['submission_files']));
        }
        if (isset($_FILES['submission_file'])) {
            $incomingFiles = array_merge($incomingFiles, normalizeUploadedSubmissionFiles((array)$_FILES['submission_file']));
        }

        if ($submissionMode === 'upload' && count($incomingFiles) === 0) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Î Î±ÏÎ±ÎºÎ±Î»ÏŽ Î±Î½ÎµÎ²Î¬ÏƒÏ„Îµ Ï„Î¿Ï…Î»Î¬Ï‡Î¹ÏƒÏ„Î¿Î½ Î­Î½Î± Î±ÏÏ‡ÎµÎ¯Î¿ Î³Î¹Î± Ï„Î·Î½ Ï…Ï€Î¿Î²Î¿Î»Î® Ï„Î·Ï‚ Î±Î¯Ï„Î·ÏƒÎ·Ï‚.']);
            exit;
        }

        if ($submissionMode === 'manual') {
            $applicationManualFields = $applicationsService->getApplicationFormFields($application_id);
            $manualValidationMessage = validateManualSubmissionPayload($decoded, is_array($applicationManualFields) ? $applicationManualFields : []);
            if ($manualValidationMessage !== '') {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $manualValidationMessage]);
                exit;
            }
        }

        if (count($incomingFiles) > $maxSubmissionFiles) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Î±Î½ÎµÎ²Î¬ÏƒÎµÏ„Îµ Î­Ï‰Ï‚ 4 Î±ÏÏ‡ÎµÎ¯Î±.']);
            exit;
        }

        $uploadedDbPaths = [];
        $uploadedAbsPaths = [];
        $uploadedOriginalNames = [];

        foreach ($incomingFiles as $file) {
            $fileError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($fileError === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK) {
                foreach ($uploadedAbsPaths as $path) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Î£Ï†Î¬Î»Î¼Î± Î±Î½ÎµÎ²Î¬ÏƒÎ¼Î±Ï„Î¿Ï‚ Î±ÏÏ‡ÎµÎ¯Î¿Ï….']);
                exit;
            }

            $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                foreach ($uploadedAbsPaths as $path) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹ Î±ÏÏ‡ÎµÎ¯Ï‰Î½: pdf, doc, docx, jpg, jpeg, png.']);
                exit;
            }

            $newFileName = 'submission_' . $user_id . '_' . $application_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $uploadedAbsPath = $uploadDir . $newFileName;
            $uploadedDbPath = 'storage/uploads/submissions/' . $newFileName;
            $originalDisplayName = getUploadedSubmissionDisplayName((string)($file['name'] ?? ''));

            if (!move_uploaded_file((string)($file['tmp_name'] ?? ''), $uploadedAbsPath)) {
                foreach ($uploadedAbsPaths as $path) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Î— Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ· Ï„Î¿Ï… Î±ÏÏ‡ÎµÎ¯Î¿Ï… Î±Ï€Î­Ï„Ï…Ï‡Îµ.']);
                exit;
            }

            $uploadedAbsPaths[] = $uploadedAbsPath;
            $uploadedDbPaths[] = $uploadedDbPath;
            $uploadedOriginalNames[$uploadedDbPath] = $originalDisplayName !== '' ? $originalDisplayName : basename($uploadedDbPath);
        }

        if (!empty($uploadedDbPaths)) {
            $decoded['_uploaded_files'] = $uploadedDbPaths;
            $decoded['_uploaded_file_names'] = $uploadedOriginalNames;

            $submissionDisplayNames = loadSubmissionFileDisplayNamesPublic();
            $submissionDisplayNamesChanged = false;
            foreach ($uploadedOriginalNames as $uploadedDbPath => $uploadedDisplayName) {
                $uploadedDbPath = trim((string)$uploadedDbPath);
                $uploadedDisplayName = getUploadedSubmissionDisplayName((string)$uploadedDisplayName);
                if ($uploadedDbPath === '' || $uploadedDisplayName === '') {
                    continue;
                }

                if (!isset($submissionDisplayNames[$uploadedDbPath]) || (string)$submissionDisplayNames[$uploadedDbPath] !== $uploadedDisplayName) {
                    $submissionDisplayNames[$uploadedDbPath] = $uploadedDisplayName;
                    $submissionDisplayNamesChanged = true;
                }
            }

            if ($submissionDisplayNamesChanged) {
                saveSubmissionFileDisplayNamesPublic($submissionDisplayNames);
            }
        }

        $submissionPayloadJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        if (!is_string($submissionPayloadJson) || $submissionPayloadJson === '') {
            foreach ($uploadedAbsPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Could not prepare submission payload.']);
            exit;
        }

        $primaryUploadedDbPath = $uploadedDbPaths[0] ?? null;

        if ($applicationsService->createSubmissionWithDataAndFile($application_id, $user_id, $submissionPayloadJson, $primaryUploadedDbPath)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            $uploadedFileLinks = [];
            foreach ($uploadedDbPaths as $uploadedDbPath) {
                $uploadedFileLinks[] = [
                    'name' => $uploadedOriginalNames[$uploadedDbPath] ?? basename($uploadedDbPath),
                    'url' => site_resolve_content_url($uploadedDbPath),
                ];
            }
            echo json_encode([
                'success' => true,
                'message' => 'Î— Î±Î¯Ï„Î·ÏƒÎ· Ï…Ï€Î¿Î²Î»Î®Î¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.',
                'submission_mode' => $submissionMode,
                'uploaded_files' => array_values($uploadedOriginalNames),
                'uploaded_file_links' => $uploadedFileLinks,
            ]);
        } else {
            foreach ($uploadedAbsPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Î£Ï†Î¬Î»Î¼Î± Î²Î¬ÏƒÎ·Ï‚ Î´ÎµÎ´Î¿Î¼Î­Î½Ï‰Î½. Î”Î¿ÎºÎ¹Î¼Î¬ÏƒÏ„Îµ Î¾Î±Î½Î¬.']);
        }
    } catch (Throwable $e) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }

    exit;
}

/*
 |------------------------------------------------------------
 | AJAX: Submit application with form data (no file upload)
 |------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_submit'])) {
    ob_start();                                          // suppress any stray output
    try {
        if (!$canSubmitApplications) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Î”ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î´Ï…Î½Î±Ï„Î® Î· Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î± Ï€ÏÎ¿Ï†Î¯Î» Ï…Ï€Î¿Î²Î¿Î»Î®Ï‚ Î±Ï…Ï„Î® Ï„Î· ÏƒÏ„Î¹Î³Î¼Î®. Î Î±ÏÎ±ÎºÎ±Î»ÏŽ Î±Î½Î±Î½ÎµÏŽÏƒÏ„Îµ Ï„Î· ÏƒÎµÎ»Î¯Î´Î± ÎºÎ±Î¹ Î´Î¿ÎºÎ¹Î¼Î¬ÏƒÏ„Îµ Î¾Î±Î½Î¬.',
            ]);
            exit;
        }

        $application_id = (int)($_POST['application_id'] ?? 0);
        $raw            = $_POST['submission_data'] ?? '';

        if ($application_id <= 0) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ· Î±Î¯Ï„Î·ÏƒÎ·.']);
            exit;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) && !is_object(json_decode($raw))) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ± Î´ÎµÎ´Î¿Î¼Î­Î½Î± Ï†ÏŒÏÎ¼Î±Ï‚.']);
            exit;
        }

        if ($isAuthenticatedParent && $applicationsService->hasUserSubmitted($application_id, $user_id)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ÎˆÏ‡ÎµÏ„Îµ Î®Î´Î· Ï…Ï€Î¿Î²Î¬Î»ÎµÎ¹ Î±Ï…Ï„Î® Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ·.']);
            exit;
        }

        $uploadedDbPath = null;
        $uploadedAbsPath = null;
        $uploadedDisplayName = '';

        if (isset($_FILES['submission_file']) && ($_FILES['submission_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['submission_file'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Î£Ï†Î¬Î»Î¼Î± Î±Î½ÎµÎ²Î¬ÏƒÎ¼Î±Ï„Î¿Ï‚ Î±ÏÏ‡ÎµÎ¯Î¿Ï….']);
                exit;
            }

            $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹ Î±ÏÏ‡ÎµÎ¯Ï‰Î½: pdf, doc, docx, jpg, jpeg, png.']);
                exit;
            }

            $newFileName = 'submission_' . $user_id . '_' . $application_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $uploadedAbsPath = $uploadDir . $newFileName;
            $uploadedDbPath = 'storage/uploads/submissions/' . $newFileName;
            $uploadedDisplayName = getUploadedSubmissionDisplayName((string)$file['name']);

            if (!move_uploaded_file((string)$file['tmp_name'], $uploadedAbsPath)) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Î— Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ· Ï„Î¿Ï… Î±ÏÏ‡ÎµÎ¯Î¿Ï… Î±Ï€Î­Ï„Ï…Ï‡Îµ.']);
                exit;
            }
        }

        if ($applicationsService->createSubmissionWithDataAndFile($application_id, $user_id, $raw, $uploadedDbPath)) {
            if ($uploadedDbPath !== null && $uploadedDbPath !== '' && $uploadedDisplayName !== '') {
                $submissionDisplayNames = loadSubmissionFileDisplayNamesPublic();
                $submissionDisplayNames[(string)$uploadedDbPath] = $uploadedDisplayName;
                saveSubmissionFileDisplayNamesPublic($submissionDisplayNames);
            }

            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Î— Î±Î¯Ï„Î·ÏƒÎ· Ï…Ï€Î¿Î²Î»Î®Î¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.']);
        } else {
            if ($uploadedAbsPath && file_exists($uploadedAbsPath)) {
                unlink($uploadedAbsPath);
            }
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Î£Ï†Î¬Î»Î¼Î± Î²Î¬ÏƒÎ·Ï‚ Î´ÎµÎ´Î¿Î¼Î­Î½Ï‰Î½. Î”Î¿ÎºÎ¹Î¼Î¬ÏƒÏ„Îµ Î¾Î±Î½Î¬.']);
        }
    } catch (Throwable $e) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Î£Ï†Î¬Î»Î¼Î± Î´Î¹Î±ÎºÎ¿Î¼Î¹ÏƒÏ„Î®: ' . $e->getMessage()]);
    }
    exit;
}

/*
 |------------------------------------------------------------
 | Submit application
 |------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    if (!$canSubmitApplications) {
        $message = 'Î”ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î´Ï…Î½Î±Ï„Î® Î· Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î± Ï€ÏÎ¿Ï†Î¯Î» Ï…Ï€Î¿Î²Î¿Î»Î®Ï‚ Î±Ï…Ï„Î® Ï„Î· ÏƒÏ„Î¹Î³Î¼Î®. Î Î±ÏÎ±ÎºÎ±Î»ÏŽ Î±Î½Î±Î½ÎµÏŽÏƒÏ„Îµ Ï„Î· ÏƒÎµÎ»Î¯Î´Î± ÎºÎ±Î¹ Î´Î¿ÎºÎ¹Î¼Î¬ÏƒÏ„Îµ Î¾Î±Î½Î¬.';
        $messageType = 'warning';
    } else {
        $application_id = (int) ($_POST['application_id'] ?? 0);

        if ($application_id <= 0) {
            $message = 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ· Î±Î¯Ï„Î·ÏƒÎ·.';
            $messageType = 'warning';
        } else {
            // Elegxei an o xristis exei idi ypobalei afti tin aitisi.
            if ($isAuthenticatedParent && $applicationsService->hasUserSubmitted($application_id, $user_id)) {
                $message = 'ÎˆÏ‡ÎµÏ„Îµ Î®Î´Î· Ï…Ï€Î¿Î²Î¬Î»ÎµÎ¹ Î±Ï…Ï„Î® Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ·.';
                $messageType = 'warning';
            } else {
                if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
                    $message = 'Î Î±ÏÎ±ÎºÎ±Î»Î¿ÏÎ¼Îµ Î±Î½ÎµÎ²Î¬ÏƒÏ„Îµ Î­Î½Î± Î­Î³ÎºÏ…ÏÎ¿ Î±ÏÏ‡ÎµÎ¯Î¿.';
                    $messageType = 'danger';
                } else {
                    $file = $_FILES['submission_file'];
                    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $message = 'Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹ Î±ÏÏ‡ÎµÎ¯Ï‰Î½: pdf, doc, docx, jpg, jpeg, png.';
                        $messageType = 'danger';
                    } else {
                        $newFileName = 'submission_' . $user_id . '_' . $application_id . '_' . time() . '.' . $extension;
                        $targetPath = $uploadDir . $newFileName;
                        $dbPath = 'storage/uploads/submissions/' . $newFileName;
                        $uploadedDisplayName = getUploadedSubmissionDisplayName((string)$file['name']);

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            if ($applicationsService->createSubmission($application_id, $user_id, $dbPath)) {
                                if ($uploadedDisplayName !== '') {
                                    $submissionDisplayNames = loadSubmissionFileDisplayNamesPublic();
                                    $submissionDisplayNames[$dbPath] = $uploadedDisplayName;
                                    saveSubmissionFileDisplayNamesPublic($submissionDisplayNames);
                                }

                                $message = 'Î— Î±Î¯Ï„Î·ÏƒÎ· Ï…Ï€Î¿Î²Î»Î®Î¸Î·ÎºÎµ Î¼Îµ ÎµÏ€Î¹Ï„Ï…Ï‡Î¯Î±.';
                                $messageType = 'success';
                            } else {
                                $message = 'Î£Ï†Î¬Î»Î¼Î± Î²Î¬ÏƒÎ·Ï‚ Î´ÎµÎ´Î¿Î¼Î­Î½Ï‰Î½ ÎºÎ±Ï„Î¬ Ï„Î·Î½ Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ· Ï„Î·Ï‚ Ï…Ï€Î¿Î²Î¿Î»Î®Ï‚.';
                                $messageType = 'danger';
                            }
                        } else {
                            $message = 'Î— Î¼ÎµÏ„Î±Ï†ÏŒÏÏ„Ï‰ÏƒÎ· Ï„Î¿Ï… Î±ÏÏ‡ÎµÎ¯Î¿Ï… Î±Ï€Î­Ï„Ï…Ï‡Îµ.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }
    }
}

/*
 |------------------------------------------------------------
 | Get applications
 |------------------------------------------------------------
*/
$applications = $applicationsService->getAllApplications();
$applicationUiMeta = loadApplicationUiMetaPublic();

/*
 |------------------------------------------------------------
 | Get documents grouped by application
 |------------------------------------------------------------
*/
$documentsByApplication = $applicationsService->getDocumentsByApplication();
$applicationDocumentDisplayNamesByPath = loadApplicationDocumentDisplayNamesPublic();

/*
 |------------------------------------------------------------
 | My submissions
 |------------------------------------------------------------
*/
$mySubmissions = $canShowSubmissions ? $applicationsService->getUserSubmissions($user_id) : [];
$submissionFileDisplayNamesByPath = loadSubmissionFileDisplayNamesPublic();
$appliedIds = array_map('intval', array_column($mySubmissions, 'application_id'));
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo site_asset_url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/applications.css'); ?>?v=<?php echo (int)(@filemtime(__DIR__ . '/../../../public/assets/css/user_css/applications.css') ?: time()); ?>">

    

    <title>Î‘Î¹Ï„Î®ÏƒÎµÎ¹Ï‚ - Î“Ï…Î¼Î½Î¬ÏƒÎ¹Î¿ Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï…</title>
</head>
<body data-applications-can-submit="<?php echo $canSubmitApplications ? '1' : '0'; ?>" data-applications-login-url="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>">

<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Î‘Î¹Ï„Î®ÏƒÎµÎ¹Ï‚';
$pageHeaderSubtitle = 'Î¥Ï€Î¿Î²Î¬Î»Î»ÎµÏ„Îµ Î±Î¹Ï„Î®ÏƒÎµÎ¹Ï‚ ÎºÎ±Î¹ Ï€Î±ÏÎ±ÎºÎ¿Î»Î¿Ï…Î¸Î®ÏƒÏ„Îµ ÎµÏÎºÎ¿Î»Î± Ï„Î·Î½ Ï€Î¿ÏÎµÎ¯Î± Ï„Î¿Ï…Ï‚.';
$pageHeaderIcon = 'fas fa-file-alt';
$pageHeaderEyebrow = 'Î¥Ï€Î¿Î²Î¿Î»Î­Ï‚ ÎšÎ±Î¹ ÎˆÎ³Î³ÏÎ±Ï†Î±';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<div class="container py-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="ÎšÎ»ÎµÎ¯ÏƒÎ¹Î¼Î¿">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <div class="card applications-card mb-4">
                <div class="card-body p-4">
                    <h3 class="section-title">Î”Î¹Î±Î¸Î­ÏƒÎ¹Î¼ÎµÏ‚ Î‘Î¹Ï„Î®ÏƒÎµÎ¹Ï‚</h3>

                    <?php if (empty($applications)): ?>
                        <div class="alert alert-info mb-0">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼ÎµÏ‚ Î±Î¹Ï„Î®ÏƒÎµÎ¹Ï‚.</div>
                    <?php else: ?>
                        <div class="row" id="applications-grid">
                            <?php $appIndex = 0; foreach ($applications as $application):
                                $appId = (int)$application['application_id'];
                                $isDbApplied = in_array($appId, $appliedIds);
                                $appMeta = $applicationUiMeta[(string)$appId] ?? [];
                                $openDateRaw = (string)($appMeta['open_date'] ?? ($application['open_date'] ?? ''));
                                $closeDateRaw = (string)($appMeta['close_date'] ?? ($appMeta['deadline'] ?? ($application['due_date'] ?? '')));
                                $openDateFormatted = formatUiDatePublic($openDateRaw);
                                $closeDateFormatted = formatUiDatePublic($closeDateRaw);
                                $fullDescription = (string)($application['application_description'] ?? 'Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î· Ï€ÎµÏÎ¹Î³ÏÎ±Ï†Î®.');
                                $postImageSrc = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
                                $allDocs = $documentsByApplication[$application['application_id']] ?? [];
                                $instructionDocs = [];
                                $otherDocs = [];

                                foreach ($allDocs as $doc) {
                                    $docPath = (string)($doc['file_path'] ?? '');
                                    if ($docPath === '') {
                                        continue;
                                    }

                                    if (strpos($docPath, '_application_image_') !== false) {
                                        continue;
                                    }

                                    if (strpos($docPath, '_instruction_file_') !== false) {
                                        $instructionDocs[] = $doc;
                                        continue;
                                    }

                                    $otherDocs[] = $doc;
                                }

                                $docsForModal = [];
                                foreach (array_values(array_merge($instructionDocs, $otherDocs)) as $docForModal) {
                                    $docPathForModal = (string)($docForModal['file_path'] ?? '');
                                    if ($docPathForModal === '') {
                                        continue;
                                    }

                                    $mappedDisplayName = trim((string)($applicationDocumentDisplayNamesByPath[$docPathForModal] ?? ''));
                                    $docForModal['display_name'] = $mappedDisplayName !== ''
                                        ? $mappedDisplayName
                                        : basename($docPathForModal);
                                    $docsForModal[] = $docForModal;
                                }

                                $attachmentsJson = json_encode($docsForModal, JSON_UNESCAPED_UNICODE);
                                $primaryInstructionPath = (string)($instructionDocs[0]['file_path'] ?? '');
                                $primaryInstructionUrl = $primaryInstructionPath !== '' ? site_resolve_content_url($primaryInstructionPath) : '';
                                $primaryInstructionName = '';
                                if ($primaryInstructionPath !== '') {
                                    $primaryMappedName = trim((string)($applicationDocumentDisplayNamesByPath[$primaryInstructionPath] ?? ''));
                                    $primaryInstructionName = $primaryMappedName !== '' ? $primaryMappedName : basename($primaryInstructionPath);
                                }
                                if ($openDateFormatted !== '' && $closeDateFormatted !== '') {
                                    $applicationDateDisplay = $openDateFormatted . ' - ' . $closeDateFormatted;
                                } elseif ($openDateFormatted !== '') {
                                    $applicationDateDisplay = $openDateFormatted;
                                } elseif ($closeDateFormatted !== '') {
                                    $applicationDateDisplay = $closeDateFormatted;
                                } else {
                                    $applicationDateDisplay = 'â€”';
                                }
                            ?>
                                <div class="col-12 mb-4">
                                    <article class="application-item h-100 app-card-wrapper post-card post-open-trigger"
                                         id="app-card-<?php echo $appId; ?>"
                                         data-app-id="<?php echo $appId; ?>"
                                         data-app-index="<?php echo $appIndex; ?>"
                                         data-db-applied="<?php echo $isDbApplied ? 'true' : 'false'; ?>"
                                         data-application-title="<?php echo htmlspecialchars($application['application_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                         data-application-description="<?php echo htmlspecialchars($fullDescription, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-app-open-date="<?php echo htmlspecialchars($openDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-app-close-date="<?php echo htmlspecialchars($closeDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-parent-email="<?php echo htmlspecialchars($currentUserEmail, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-instruction-url="<?php echo htmlspecialchars($primaryInstructionUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-instruction-name="<?php echo htmlspecialchars($primaryInstructionName, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-application-documents="<?php echo htmlspecialchars($attachmentsJson ?: '[]', ENT_QUOTES, 'UTF-8'); ?>">

                                        <div class="post-image-wrap">
                                            <img src="<?php echo htmlspecialchars($postImageSrc); ?>" alt="Î•Î¹ÎºÏŒÎ½Î± Î±Î¯Ï„Î·ÏƒÎ·Ï‚">
                                        </div>

                                        <div class="post-content d-flex flex-column h-100">

                                        <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
                                        <div class="app-meta-top d-flex justify-content-between align-items-center mb-2">
                                            <span class="js-status-placeholder"></span>
                                            <span class="js-category-placeholder"></span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h5 class="application-title">
                                                <?php echo htmlspecialchars($application['application_title']); ?>
                                            </h5>
                                        </div>

                                        <div class="application-instruction-links">
                                            <a class="application-instruction-link js-open-submit-link" href="#" role="button">
                                                <span>Î”ÎµÎ¯Ï„Îµ ÎµÎ´ÏŽ</span><i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>

                                        <div class="application-date-bottom">
                                            <span><?php echo htmlspecialchars($applicationDateDisplay); ?></span>
                                        </div>

                                        <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
                                        <div class="js-dates-placeholder mb-3"></div>

                                        <button
                                            class="btn btn-primary submit-btn mt-auto"
                                            data-toggle="modal"
                                            data-target="#submitModal"
                                            data-application-id="<?php echo $appId; ?>"
                                            data-application-title="<?php echo htmlspecialchars($application['application_title']); ?>"
                                            data-application-description="<?php echo htmlspecialchars($application['application_description'] ?? ''); ?>"
                                            data-app-index="<?php echo $appIndex; ?>"
                                            data-app-open-date="<?php echo htmlspecialchars($openDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-app-close-date="<?php echo htmlspecialchars($closeDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-parent-email="<?php echo htmlspecialchars($currentUserEmail, ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <i class="fas fa-paper-plane mr-1"></i> Î¥Ï€Î¿Î²Î¿Î»Î® Î‘Î¯Ï„Î·ÏƒÎ·Ï‚
                                        </button>
                                        </div>
                                    </article>
                                </div>
                            <?php $appIndex++; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card applications-card">
                <div class="card-body p-4">
                    <h3 class="section-title">ÎŸÎ¹ Î¥Ï€Î¿Î²Î¿Î»Î­Ï‚ ÎœÎ¿Ï…</h3>

                    <?php if (!$canShowSubmissions): ?>
                        <div class="alert alert-light border mb-0" role="alert">
                            <?php if ($isAuthenticatedParent): ?>
                                Î”ÎµÎ½ Î®Ï„Î±Î½ Î´Ï…Î½Î±Ï„Î® Î· Ï†ÏŒÏÏ„Ï‰ÏƒÎ· ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Ï‰Î½ Ï…Ï€Î¿Î²Î¿Î»ÏŽÎ½. Î Î±ÏÎ±ÎºÎ±Î»Î¿ÏÎ¼Îµ Î´Î¿ÎºÎ¹Î¼Î¬ÏƒÏ„Îµ Î¾Î±Î½Î¬ Î±Ï€ÏŒ
                                <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="alert-link">Ï„Î· ÏƒÎµÎ»Î¯Î´Î± ÎµÎ¹ÏƒÏŒÎ´Î¿Ï…</a>.
                            <?php else: ?>
                                ÎŸÎ¹ Ï…Ï€Î¿Î²Î¿Î»Î­Ï‚ Î¼Î¿Ï… ÎµÎ¯Î½Î±Î¹ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼ÎµÏ‚ Î¼ÏŒÎ½Î¿ Î¼ÎµÏ„Î¬ Î±Ï€ÏŒ ÎµÎ¯ÏƒÎ¿Î´Î¿ Î¼Îµ Î»Î¿Î³Î±ÏÎ¹Î±ÏƒÎ¼ÏŒ Î³Î¿Î½Î­Î±.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-3" id="no-submissions-msg"<?php echo (!empty($mySubmissions)) ? ' hidden' : ''; ?>>
                            <i class="fas fa-inbox mr-2"></i>Î”ÎµÎ½ Î­Ï‡ÎµÏ„Îµ Ï…Ï€Î¿Î²Î¬Î»ÎµÎ¹ Î±ÎºÏŒÎ¼Î· ÎºÎ±Î¼Î¯Î± Î±Î¯Ï„Î·ÏƒÎ·.
                        </div>

                        <div class="table-responsive">
                            <table class="table submissions-table" id="submissions-table"<?php echo empty($mySubmissions) ? ' hidden' : ''; ?>>
                                <thead>
                                    <tr>
                                        <th>Î‘Î¯Ï„Î·ÏƒÎ·</th>
                                        <th>Î—Î¼. Î¥Ï€Î¿Î²Î¿Î»Î®Ï‚</th>
                                    </tr>
                                </thead>
                                <tbody id="submissions-tbody">
                                    <?php foreach ($mySubmissions as $submission):
                                        $formData = json_decode($submission['submission_data'] ?? '{}', true);
                                        if (!is_array($formData)) {
                                            $formData = [];
                                        }

                                        $submissionFiles = [];
                                        $uploadedFileNames = [];
                                        if (!empty($formData['_uploaded_file_names']) && is_array($formData['_uploaded_file_names'])) {
                                            foreach ($formData['_uploaded_file_names'] as $storedPath => $displayName) {
                                                $storedPath = (string)$storedPath;
                                                $displayName = getUploadedSubmissionDisplayName((string)$displayName);
                                                if ($storedPath !== '' && $displayName !== '') {
                                                    $uploadedFileNames[$storedPath] = $displayName;
                                                }
                                            }
                                        }
                                        $legacyFilePath = (string)($submission['file_path'] ?? '');
                                        if ($legacyFilePath !== '') {
                                            $submissionFiles[] = $legacyFilePath;
                                        }

                                        if (!empty($formData['_uploaded_files']) && is_array($formData['_uploaded_files'])) {
                                            foreach ($formData['_uploaded_files'] as $uploadedPath) {
                                                $uploadedPath = (string)$uploadedPath;
                                                if ($uploadedPath !== '') {
                                                    $submissionFiles[] = $uploadedPath;
                                                }
                                            }
                                        }

                                        $submissionFiles = array_values(array_unique($submissionFiles));
                                        $submissionMode = ($formData['_submission_mode'] ?? 'upload') === 'manual' ? 'Online Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎ·' : 'Î‘Î½Î­Î²Î±ÏƒÎ¼Î± Î‘ÏÏ‡ÎµÎ¯Î¿Ï…';
                                        $submittedDate = !empty($submission['submitted_at'])
                                            ? date('d/m/Y', strtotime($submission['submitted_at']))
                                            : 'â€”';
                                    ?>
                                        <tr data-db-row="1">
                                            <td>
                                                <strong><?php echo htmlspecialchars((string)$submission['application_title']); ?></strong>
                                                <div class="small text-muted mt-1"><?php echo htmlspecialchars($submissionMode); ?></div>
                                                <?php if (!empty($submissionFiles)): ?>
                                                    <div class="mt-2">
                                                        <?php foreach ($submissionFiles as $submissionFilePath): ?>
                                                            <?php
                                                                $linkDisplayName = trim((string)($uploadedFileNames[$submissionFilePath] ?? ''));
                                                                if ($linkDisplayName === '') {
                                                                    $linkDisplayName = trim((string)($submissionFileDisplayNamesByPath[$submissionFilePath] ?? ''));
                                                                }
                                                                if ($linkDisplayName === '') {
                                                                    $linkDisplayName = basename($submissionFilePath);
                                                                }
                                                            ?>
                                                            <a href="<?php echo htmlspecialchars(site_resolve_content_url($submissionFilePath)); ?>" target="_blank" rel="noopener noreferrer" class="submission-file-link d-block small mb-1">
                                                                <i class="fas fa-download mr-1"></i><?php echo htmlspecialchars($linkDisplayName); ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)$submittedDate); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="applicationViewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="application-view-title">Î›ÎµÏ€Ï„Î¿Î¼Î­ÏÎµÎ¹ÎµÏ‚ Î‘Î¯Ï„Î·ÏƒÎ·Ï‚</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="ÎšÎ»ÎµÎ¯ÏƒÎ¹Î¼Î¿">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="application-view-description"></p>
                <div class="application-view-files-panel application-view-attachments">
                    <h6 class="mb-2">Î£Ï…Î½Î·Î¼Î¼Î­Î½Î± Î‘ÏÏ‡ÎµÎ¯Î±</h6>
                    <ul id="application-view-attachments-list"></ul>
                </div>
                <div class="application-submit-methods" id="application-submit-methods">
                    <button type="button" class="application-submit-option" data-submit-mode="manual" aria-pressed="false">
                        <span class="application-submit-option__check"><i class="fas fa-check"></i></span>
                        <span class="application-submit-option__icon"><i class="fas fa-keyboard"></i></span>
                        <span>
                            <span class="application-submit-option__title">Online Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎ·</span>
                            <span class="application-submit-option__text">Î£Ï…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ· Î±Ï€ÎµÏ…Î¸ÎµÎ¯Î±Ï‚ ÎµÎ´ÏŽ, Ï‡Ï‰ÏÎ¯Ï‚ download, ÎµÎºÏ„ÏÏ€Ï‰ÏƒÎ· Î® Î½Î­Î¿ upload.</span>
                        </span>
                    </button>

                    <button type="button" class="application-submit-option" data-submit-mode="upload" aria-pressed="false">
                        <span class="application-submit-option__check"><i class="fas fa-check"></i></span>
                        <span class="application-submit-option__icon"><i class="fas fa-upload"></i></span>
                        <span>
                            <span class="application-submit-option__title">Î‘Î½Î­Î²Î±ÏƒÎ¼Î± Î‘ÏÏ‡ÎµÎ¯Î¿Ï…</span>
                            <span class="application-submit-option__text">ÎšÎ±Ï„ÎµÎ²Î¬ÏƒÏ„Îµ Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ·, ÏƒÏ…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ Ï„Î·Î½ ÎºÎ±Î¹ Î±Î½ÎµÎ²Î¬ÏƒÏ„Îµ ÎµÎ´ÏŽ Î­Ï‰Ï‚ 4 Î±ÏÏ‡ÎµÎ¯Î± Î³Î¹Î± Ï…Ï€Î¿Î²Î¿Î»Î®.</span>
                        </span>
                    </button>
                </div>

                <div class="application-submit-panel" id="application-view-manual-panel">
                    <div class="application-view-files-panel mb-0">
                        <h6 class="mb-2">Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎ· Î‘Î¯Ï„Î·ÏƒÎ·Ï‚ Online</h6>
                        <p class="application-submit-helper mb-3">Î£Ï…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ Ï„Î± Ï€Î±ÏÎ±ÎºÎ¬Ï„Ï‰ ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± ÎºÎ±Î¹ Î³ÏÎ¬ÏˆÏ„Îµ Ï„Î¿ Î±Î¯Ï„Î·Î¼Î¬ ÏƒÎ±Ï‚ ÏƒÏ„Î¿ Ï€ÎµÎ´Î¯Î¿ ÎºÎµÎ¹Î¼Î­Î½Î¿Ï….</p>
                        <form id="application-view-manual-form" novalidate>
                            <div id="application-view-manual-fields" class="application-view-manual-fields"></div>
                        </form>
                    </div>
                </div>

                <div class="application-submit-panel" id="application-view-upload-panel">
                    <div id="application-view-upload">
                    <label class="upload-title" for="application-view-file-input">
                        <i class="fas fa-paperclip"></i>
                        <span>Upload Î‘Î¯Ï„Î·ÏƒÎ·Ï‚ (Î­Ï‰Ï‚ 4 Î±ÏÏ‡ÎµÎ¯Î±)</span>
                    </label>
                    <input
                        type="file"
                        id="application-view-file-input"
                        class="form-control"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        multiple
                    >
                    <small class="application-view-upload-note">Î‘Ï€Î±Î¹Ï„ÎµÎ¯Ï„Î±Î¹ Ï„Î¿Ï…Î»Î¬Ï‡Î¹ÏƒÏ„Î¿Î½ 1 Î±ÏÏ‡ÎµÎ¯Î¿. Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹: <strong>pdf, doc, docx, jpg, jpeg, png</strong>.</small>
                    <ul id="application-view-selected-files"></ul>
                </div>
                </div>

                <?php if ($showGuestRegistrationReminder): ?>
                    <div class="application-registration-reminder" role="note">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        Î‘Ï†Î¿Ï ÏƒÏ…Î¼Ï€Î»Î·ÏÏŽÏƒÎµÏ„Îµ Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ·, Î¸Î± Ï‡ÏÎµÎ¹Î±ÏƒÏ„ÎµÎ¯ Î½Î± ÎºÎ¬Î½ÎµÏ„Îµ ÎµÎ³Î³ÏÎ±Ï†Î®.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ÎšÎ»ÎµÎ¯ÏƒÎ¹Î¼Î¿</button>
                <button type="button" class="btn btn-primary" id="application-view-submit-btn">
                    <i class="fas fa-paper-plane mr-1"></i>Î¥Ï€Î¿Î²Î¿Î»Î® Î‘Î¯Ï„Î·ÏƒÎ·Ï‚
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
<div class="modal fade" id="submitModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-group">
                    <h5 class="modal-title" id="modal-title">Î¥Ï€Î¿Î²Î¿Î»Î® Î‘Î¯Ï„Î·ÏƒÎ·Ï‚</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="ÎšÎ»ÎµÎ¯ÏƒÎ¹Î¼Î¿">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="modal-description" class="text-muted small mb-3"></p>
                <hr class="my-2">
                <div id="modal-dynamic-fields">
                    <!-- Rendered apo JavaScript -->
                </div>
                <div class="form-group mt-3 mb-0">
                    <label class="form-label-custom mb-2">
                        <i class="fas fa-paperclip text-primary mr-1"></i>Î ÏÎ¿Î±Î¹ÏÎµÏ„Î¹ÎºÏŒ Î±ÏÏ‡ÎµÎ¯Î¿ Ï…Ï€Î¿Î²Î¿Î»Î®Ï‚
                    </label>
                    <input type="file" id="modal-submission-file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="text-muted d-block mt-1">Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹: pdf, doc, docx, jpg, jpeg, png.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Î‘ÎºÏÏÏ‰ÏƒÎ·
                </button>
                <button type="button" id="modal-submit-btn" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-1"></i> Î¥Ï€Î¿Î²Î¿Î»Î®
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt mr-2 text-primary"></i>Î›ÎµÏ€Ï„Î¿Î¼Î­ÏÎµÎ¹ÎµÏ‚ Î‘Î¯Ï„Î·ÏƒÎ·Ï‚</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="view-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ÎšÎ»ÎµÎ¯ÏƒÎ¹Î¼Î¿</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="application-unavailable-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p class="mb-0" id="application-unavailable-message">Î— Î±Î¯Ï„Î·ÏƒÎ· Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î±Î½Î¿Î¯Î¾ÎµÎ¹ Î±ÎºÏŒÎ¼Î±.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary btn-sm px-4" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
<div id="submission-toast" class="position-fixed submission-toast" hidden>
    <div class="alert alert-success shadow py-3 px-4 mb-0">
        <i class="fas fa-check-circle mr-2"></i> Î— Î±Î¯Ï„Î·ÏƒÎ® ÏƒÎ±Ï‚ Ï…Ï€Î¿Î²Î»Î®Î¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚!
    </div>
</div>

<div id="application-notice-backdrop" class="application-notice-backdrop"></div>
<div id="application-notice-box" role="alertdialog" aria-modal="true" aria-labelledby="application-notice-title">
    <div class="application-notice-card">
        <div class="application-notice-header" id="application-notice-title">Î•Î¹Î´Î¿Ï€Î¿Î¯Î·ÏƒÎ·</div>
        <div class="application-notice-body" id="application-notice-message">â€”</div>
        <div class="application-notice-footer">
            <button type="button" class="btn btn-primary application-notice-btn" id="application-notice-close">OK</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo site_asset_url('js/applications.js'); ?>?v=<?php echo (int)(@filemtime(__DIR__ . '/../../../public/assets/js/applications.js') ?: time()); ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
