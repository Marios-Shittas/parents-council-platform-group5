<?php
/**
 * Admin Applications Management Page
 * Create, update, delete applications and manage documents
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

require_once __DIR__ . '/../../app/services/ApplicationsService.php';
require_once __DIR__ . '/../../app/services/ApplicationTemplateService.php';
require_once __DIR__ . '/../../app/config/db.php';

// Initialize the services
$applicationsService = new ApplicationsService();
$templateService = new ApplicationTemplateService($conn);

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

$documentsUploadDir = __DIR__ . '/../assets/Applications_docs/';
if (!is_dir($documentsUploadDir)) {
    mkdir($documentsUploadDir, 0777, true);
}

function normalizeUploadedFiles(array $fileField): array {
    $files = [];

    if (!isset($fileField['name'])) {
        return $files;
    }

    if (is_array($fileField['name'])) {
        foreach ($fileField['name'] as $index => $name) {
            $error = $fileField['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if (($name === '' || $name === null) && $error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name' => $name,
                'type' => $fileField['type'][$index] ?? '',
                'tmp_name' => $fileField['tmp_name'][$index] ?? '',
                'error' => $error,
                'size' => $fileField['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    if (($fileField['name'] ?? '') === '' && ($fileField['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $files;
    }

    $files[] = $fileField;
    return $files;
}

function uploadApplicationFiles(ApplicationsService $applicationsService, int $applicationId, string $uploadDir): array {
    $definitions = [
        'application_image' => ['label' => 'εικόνα', 'extensions' => ['jpg', 'jpeg', 'png', 'webp']],
        'instruction_file' => ['label' => 'αρχείο οδηγιών', 'extensions' => ['pdf', 'doc', 'docx']],
        'required_documents' => ['label' => 'δικαιολογητικό', 'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']],
    ];
    $maxFilesByField = [
        'application_image' => 1,
        'instruction_file' => 4,
        'required_documents' => 4,
    ];

    $uploadedCount = 0;
    $errors = [];
    $uploadedFiles = [];
    $documentDisplayNames = loadApplicationDocumentDisplayNames();
    $displayNamesChanged = false;

    foreach ($definitions as $fieldName => $definition) {
        if (!isset($_FILES[$fieldName])) {
            continue;
        }

        $files = normalizeUploadedFiles($_FILES[$fieldName]);
        $maxFiles = (int)($maxFilesByField[$fieldName] ?? 1);
        if ($fieldName === 'instruction_file') {
            $existingInstructionFiles = 0;
            foreach ($applicationsService->getDocuments($applicationId) as $existingDoc) {
                $existingPath = (string)($existingDoc['file_path'] ?? '');
                if ($existingPath !== '' && strpos($existingPath, '_instruction_file_') !== false) {
                    $existingInstructionFiles++;
                }
            }

            $remainingSlots = max(0, $maxFiles - $existingInstructionFiles);
            if ($remainingSlots <= 0) {
                $errors[] = 'Υπάρχουν ήδη 4 αρχεία οδηγιών για αυτή την αίτηση.';
                continue;
            }

            if (count($files) > $remainingSlots) {
                $errors[] = 'Μπορείτε να ανεβάσετε μέχρι ' . $remainingSlots . ' ακόμη αρχεία οδηγιών.';
                $files = array_slice($files, 0, $remainingSlots);
            }
        } elseif ($maxFiles > 0 && count($files) > $maxFiles) {
            $errors[] = 'Επιτρέπονται έως ' . $maxFiles . ' αρχεία για ' . $definition['label'] . '.';
            $files = array_slice($files, 0, $maxFiles);
        }

        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'Αποτυχία ανεβάσματος για ' . $definition['label'] . '.';
                continue;
            }

            $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $definition['extensions'], true)) {
                $errors[] = 'Μη επιτρεπτός τύπος αρχείου για ' . $definition['label'] . ': ' . htmlspecialchars((string)$file['name']);
                continue;
            }

            $newFileName = 'application_' . $applicationId . '_' . $fieldName . '_' . uniqid('', true) . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            $dbPath = '/parents-council-platform-group5/public/assets/Applications_docs/' . $newFileName;

            if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
                $errors[] = 'Δεν ήταν δυνατή η αποθήκευση του αρχείου ' . htmlspecialchars((string)$file['name']) . '.';
                continue;
            }

            if (!$applicationsService->addDocument($applicationId, $dbPath)) {
                $errors[] = 'Το αρχείο αποθηκεύτηκε αλλά δεν συνδέθηκε με την αίτηση: ' . htmlspecialchars((string)$file['name']) . '.';
                continue;
            }

            if (!isset($uploadedFiles[$fieldName])) {
                $uploadedFiles[$fieldName] = [];
            }
            $uploadedFiles[$fieldName][] = [
                'path' => $dbPath,
                'name' => (string)$file['name'],
            ];

            $displayName = normalizeUploadedDocumentDisplayName((string)($file['name'] ?? ''));
            if ($displayName !== '') {
                $documentDisplayNames[$dbPath] = $displayName;
                $displayNamesChanged = true;
            }

            $uploadedCount++;
        }
    }

    if ($displayNamesChanged) {
        saveApplicationDocumentDisplayNames($documentDisplayNames);
    }

    return [
        'uploaded_count' => $uploadedCount,
        'errors' => $errors,
        'uploaded_files' => $uploadedFiles,
    ];
}

/**
 * Convert stored document path to a public URL that can be opened from /public/admin.
 */
function getDocumentPublicUrl(string $storedPath): string {
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return '#';
    }

    if (strpos($storedPath, '/parents-council-platform-group5/public/') === 0) {
        return $storedPath;
    }

    if (strpos($storedPath, 'storage/') === 0) {
        return '/parents-council-platform-group5/' . ltrim($storedPath, '/');
    }

    return $storedPath;
}

/**
 * Convert stored document path to local absolute filesystem path for delete.
 */
function getDocumentAbsolutePath(string $storedPath): string {
    $storedPath = trim($storedPath);
    $publicRoot = realpath(__DIR__ . '/..');
    $repoRoot = realpath(__DIR__ . '/../../');

    if ($storedPath === '') {
        return '';
    }

    if (strpos($storedPath, '/parents-council-platform-group5/public/') === 0 && $publicRoot !== false) {
        $relative = substr($storedPath, strlen('/parents-council-platform-group5/public/'));
        return $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relative, '/'));
    }

    if (strpos($storedPath, 'storage/') === 0 && $repoRoot !== false) {
        return $repoRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
    }

    if ($repoRoot !== false) {
        return $repoRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($storedPath, '/'));
    }

    return $storedPath;
}

function getApplicationDocumentDisplayNamesPath(): string {
    return __DIR__ . '/../../storage/application_document_display_names.json';
}

function loadApplicationDocumentDisplayNames(): array {
    $path = getApplicationDocumentDisplayNamesPath();
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

function saveApplicationDocumentDisplayNames(array $displayNames): bool {
    $path = getApplicationDocumentDisplayNamesPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($displayNames, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

function normalizeUploadedDocumentDisplayName(string $fileName): string {
    $fileName = trim($fileName);
    if ($fileName === '') {
        return '';
    }

    return basename(str_replace('\\', '/', $fileName));
}

function getTemplateInstructionFilesMetaPath(): string {
    return __DIR__ . '/../../storage/template_instruction_files.json';
}

function loadTemplateInstructionFilesMeta(): array {
    $path = getTemplateInstructionFilesMetaPath();
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

function saveTemplateInstructionFilesMeta(array $meta): bool {
    $path = getTemplateInstructionFilesMetaPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

function uploadTemplateInstructionFiles(int $templateId, string $uploadDir): array {
    $result = [
        'uploaded_count' => 0,
        'errors' => [],
    ];

    if ($templateId <= 0 || !isset($_FILES['instruction_file'])) {
        return $result;
    }

    $files = normalizeUploadedFiles($_FILES['instruction_file']);
    if (count($files) === 0) {
        return $result;
    }

    $metadata = loadTemplateInstructionFilesMeta();
    $templateKey = (string)$templateId;
    $existingEntries = isset($metadata[$templateKey]) && is_array($metadata[$templateKey])
        ? $metadata[$templateKey]
        : [];

    $normalizedExistingEntries = [];
    foreach ($existingEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $entryPath = trim((string)($entry['path'] ?? ''));
        if ($entryPath === '') {
            continue;
        }

        $normalizedExistingEntries[] = [
            'path' => $entryPath,
            'name' => normalizeUploadedDocumentDisplayName((string)($entry['name'] ?? basename($entryPath))),
        ];
    }
    $existingEntries = $normalizedExistingEntries;

    $remainingSlots = max(0, 4 - count($existingEntries));
    if ($remainingSlots <= 0) {
        $result['errors'][] = 'Υπάρχουν ήδη 4 αρχεία οδηγιών αποθηκευμένα για αυτό το πρότυπο.';
        return $result;
    }

    if (count($files) > $remainingSlots) {
        $result['errors'][] = 'Μπορείτε να ανεβάσετε μέχρι ' . $remainingSlots . ' ακόμη αρχεία οδηγιών.';
        $files = array_slice($files, 0, $remainingSlots);
    }

    $metadataChanged = false;
    $allowedExtensions = ['pdf', 'doc', 'docx'];

    foreach ($files as $file) {
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $result['errors'][] = 'Αποτυχία ανεβάσματος αρχείου οδηγιών.';
            continue;
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $result['errors'][] = 'Επιτρεπόμενοι τύποι αρχείων οδηγιών: pdf, doc, docx.';
            continue;
        }

        $newFileName = 'template_' . $templateId . '_instruction_file_' . uniqid('', true) . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        $dbPath = '/parents-council-platform-group5/public/assets/Applications_docs/' . $newFileName;

        if (!move_uploaded_file((string)($file['tmp_name'] ?? ''), $targetPath)) {
            $result['errors'][] = 'Δεν ήταν δυνατή η αποθήκευση του αρχείου οδηγιών.';
            continue;
        }

        $existingEntries[] = [
            'path' => $dbPath,
            'name' => normalizeUploadedDocumentDisplayName((string)($file['name'] ?? basename($dbPath))),
        ];
        $result['uploaded_count']++;
        $metadataChanged = true;
    }

    if ($metadataChanged) {
        $metadata[$templateKey] = $existingEntries;
        if (!saveTemplateInstructionFilesMeta($metadata)) {
            $result['errors'][] = 'Τα αρχεία οδηγιών αποθηκεύτηκαν αλλά δεν ήταν δυνατή η ενημέρωση μεταδεδομένων προτύπου.';
        }
    }

    return $result;
}

function normalizeTemplateInstructionEntries(array $entries): array {
    $normalized = [];

    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $entryPath = trim((string)($entry['path'] ?? ''));
        if ($entryPath === '') {
            continue;
        }

        $entryName = normalizeUploadedDocumentDisplayName((string)($entry['name'] ?? basename($entryPath)));
        if ($entryName === '') {
            $entryName = basename($entryPath);
        }

        $normalized[] = [
            'path' => $entryPath,
            'name' => $entryName,
        ];
    }

    return $normalized;
}

function getTemplateInstructionFilesByTemplateId(int $templateId): array {
    if ($templateId <= 0) {
        return [];
    }

    $metadata = loadTemplateInstructionFilesMeta();
    $templateKey = (string)$templateId;
    $entries = isset($metadata[$templateKey]) && is_array($metadata[$templateKey])
        ? $metadata[$templateKey]
        : [];
    $normalizedEntries = normalizeTemplateInstructionEntries($entries);

    $files = [];
    foreach ($normalizedEntries as $entry) {
        $entryPath = (string)($entry['path'] ?? '');
        $files[] = [
            'path' => $entryPath,
            'name' => (string)($entry['name'] ?? basename($entryPath)),
            'url' => getDocumentPublicUrl($entryPath),
        ];
    }

    return $files;
}

function removeTemplateInstructionFilesByPaths(int $templateId, array $pathsToRemove): array {
    $result = [
        'removed_count' => 0,
        'errors' => [],
    ];

    if ($templateId <= 0 || empty($pathsToRemove)) {
        return $result;
    }

    $normalizedPaths = [];
    foreach ($pathsToRemove as $rawPath) {
        if (!is_string($rawPath)) {
            continue;
        }

        $path = trim($rawPath);
        if ($path === '') {
            continue;
        }

        $normalizedPaths[$path] = true;
    }

    if (empty($normalizedPaths)) {
        return $result;
    }

    $metadata = loadTemplateInstructionFilesMeta();
    $templateKey = (string)$templateId;
    $entries = isset($metadata[$templateKey]) && is_array($metadata[$templateKey])
        ? $metadata[$templateKey]
        : [];
    if (count($entries) === 0) {
        return $result;
    }

    $normalizedEntries = normalizeTemplateInstructionEntries($entries);
    $remainingEntries = [];
    $metadataChanged = false;

    foreach ($normalizedEntries as $entry) {
        $entryPath = (string)($entry['path'] ?? '');
        if ($entryPath !== '' && isset($normalizedPaths[$entryPath])) {
            $absolutePath = getDocumentAbsolutePath($entryPath);
            if ($absolutePath !== '' && is_file($absolutePath) && !@unlink($absolutePath)) {
                $result['errors'][] = 'Δεν ήταν δυνατή η διαγραφή ενός αρχείου οδηγιών από τον δίσκο.';
            }

            $result['removed_count']++;
            $metadataChanged = true;
            continue;
        }

        $remainingEntries[] = [
            'path' => $entryPath,
            'name' => (string)($entry['name'] ?? basename($entryPath)),
        ];
    }

    if ($metadataChanged) {
        if (empty($remainingEntries)) {
            unset($metadata[$templateKey]);
        } else {
            $metadata[$templateKey] = $remainingEntries;
        }

        if (!saveTemplateInstructionFilesMeta($metadata)) {
            $result['errors'][] = 'Δεν ήταν δυνατή η αποθήκευση των αλλαγών στα αρχεία οδηγιών του προτύπου.';
        }
    }

    return $result;
}

function cloneTemplateInstructionFilesToApplication(
    ApplicationsService $applicationsService,
    int $templateId,
    int $applicationId,
    string $uploadDir
): array {
    $result = [
        'uploaded_count' => 0,
        'errors' => [],
        'uploaded_files' => [],
    ];

    if ($templateId <= 0 || $applicationId <= 0 || $uploadDir === '') {
        return $result;
    }

    $metadata = loadTemplateInstructionFilesMeta();
    $templateKey = (string)$templateId;
    $entries = isset($metadata[$templateKey]) && is_array($metadata[$templateKey])
        ? $metadata[$templateKey]
        : [];

    if (count($entries) === 0) {
        return $result;
    }

    $allowedExtensions = ['pdf', 'doc', 'docx'];
    $documentDisplayNames = loadApplicationDocumentDisplayNames();
    $displayNamesChanged = false;

    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $sourcePath = trim((string)($entry['path'] ?? ''));
        if ($sourcePath === '') {
            continue;
        }

        $sourceAbsolutePath = getDocumentAbsolutePath($sourcePath);
        if ($sourceAbsolutePath === '' || !is_file($sourceAbsolutePath)) {
            $result['errors'][] = 'Ένα αποθηκευμένο αρχείο οδηγιών προτύπου δεν βρέθηκε στον δίσκο.';
            continue;
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $result['errors'][] = 'Παραλείφθηκε μη έγκυρο αποθηκευμένο αρχείο οδηγιών προτύπου.';
            continue;
        }

        $newFileName = 'application_' . $applicationId . '_instruction_file_' . uniqid('', true) . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        $dbPath = '/parents-council-platform-group5/public/assets/Applications_docs/' . $newFileName;

        if (!copy($sourceAbsolutePath, $targetPath)) {
            $result['errors'][] = 'Δεν ήταν δυνατή η αντιγραφή αποθηκευμένου αρχείου οδηγιών προτύπου.';
            continue;
        }

        if (!$applicationsService->addDocument($applicationId, $dbPath)) {
            if (is_file($targetPath)) {
                unlink($targetPath);
            }
            $result['errors'][] = 'Το αρχείο οδηγιών αντιγράφηκε αλλά δεν συνδέθηκε με τη νέα αίτηση.';
            continue;
        }

        $displayName = normalizeUploadedDocumentDisplayName((string)($entry['name'] ?? basename($sourcePath)));
        if ($displayName !== '') {
            $documentDisplayNames[$dbPath] = $displayName;
            $displayNamesChanged = true;
        }

        $result['uploaded_files'][] = [
            'path' => $dbPath,
            'name' => $displayName !== '' ? $displayName : basename($dbPath),
        ];
        $result['uploaded_count']++;
    }

    if ($displayNamesChanged) {
        saveApplicationDocumentDisplayNames($documentDisplayNames);
    }

    return $result;
}

function deleteTemplateInstructionFiles(int $templateId): void {
    if ($templateId <= 0) {
        return;
    }

    $metadata = loadTemplateInstructionFilesMeta();
    $templateKey = (string)$templateId;
    $entries = isset($metadata[$templateKey]) && is_array($metadata[$templateKey])
        ? $metadata[$templateKey]
        : [];

    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $storedPath = trim((string)($entry['path'] ?? ''));
        if ($storedPath === '') {
            continue;
        }

        $absolutePath = getDocumentAbsolutePath($storedPath);
        if ($absolutePath !== '' && is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    if (isset($metadata[$templateKey])) {
        unset($metadata[$templateKey]);
        saveTemplateInstructionFilesMeta($metadata);
    }
}

function normalizeSubmissionMode(string $mode): string {
    return in_array($mode, ['manual', 'upload'], true) ? $mode : '';
}

function normalizeSubmissionFieldKey(string $rawName, int $index = 0): string {
    $safeName = trim($rawName);
    $safeName = preg_replace('/\s+/u', '_', $safeName) ?? '';
    $safeName = preg_replace('/[^a-zA-Z0-9_]/u', '_', $safeName) ?? '';
    $safeName = trim($safeName, '_');

    if ($safeName === '') {
        $safeName = 'field_' . (string)($index + 1);
    }

    return $safeName;
}

function resolveSubmissionMode(array $formData, array $submissionFiles = []): string {
    $storedMode = normalizeSubmissionMode((string)($formData['_submission_mode'] ?? ''));
    if ($storedMode !== '') {
        return $storedMode;
    }

    return !empty($submissionFiles) ? 'upload' : 'manual';
}

function getSubmissionModeUi(string $mode): array {
    if (normalizeSubmissionMode($mode) === 'manual') {
        return [
            'label' => 'Online Συμπλήρωση',
            'class' => 'submission-mode-badge submission-mode-badge-manual',
        ];
    }

    return [
        'label' => 'Ανέβασμα Αρχείου',
        'class' => 'submission-mode-badge submission-mode-badge-upload',
    ];
}


function getApplicationUiMetaPath(): string {
    return __DIR__ . '/../../storage/application_ui_meta.json';
}

function loadApplicationUiMeta(): array {
    $path = getApplicationUiMetaPath();
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

function saveApplicationUiMeta(array $meta): bool {
    $path = getApplicationUiMetaPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

function normalizeApplicationStatus(string $status): string {
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

function normalizeSubmissionSort(string $sort): string {
    $sort = strtolower(trim($sort));
    return in_array($sort, ['newest', 'oldest'], true) ? $sort : 'newest';
}

function normalizeEditModalTab(string $tab): string {
    $tab = strtolower(trim($tab));
    if (in_array($tab, ['files', 'edit-files', 'edit-files-pane'], true)) {
        return 'files';
    }
    if (in_array($tab, ['form_fields', 'form-fields', 'fields', 'edit-form-fields', 'edit-form-fields-pane'], true)) {
        return 'form_fields';
    }

    return 'info';
}

function normalizeUiDate(string $date): string {
    $date = trim($date);
    if ($date === '') {
        return '';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if ($dt && $dt->format('Y-m-d') === $date) {
        return $date;
    }

    return '';
}

function getTodayUiDate(): string {
    try {
        $timezone = new DateTimeZone('Europe/Athens');
        return (new DateTime('now', $timezone))->format('Y-m-d');
    } catch (Throwable $e) {
        return date('Y-m-d');
    }
}

function normalizeEditableApplicationFieldType(string $type): string {
    $type = strtolower(trim($type));
    if ($type === 'phone') {
        $type = 'tel';
    }
    if ($type === 'file') {
        $type = 'file_upload';
    }

    $allowed = ['text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox', 'file_upload'];
    return in_array($type, $allowed, true) ? $type : 'text';
}

function mapTemplateFieldTypeToApplicationType(string $type): string {
    $type = strtolower(trim($type));
    if ($type === 'phone') {
        return 'tel';
    }
    if ($type === 'file') {
        return 'file_upload';
    }

    $allowed = ['text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox', 'signature', 'file_upload'];
    return in_array($type, $allowed, true) ? $type : 'text';
}

function extractTemplateFieldsFromSchema($schema): array {
    if (!is_array($schema)) {
        return [];
    }

    $fields = [];
    $pushField = function (array $field) use (&$fields): void {
        $label = trim((string)($field['label'] ?? ''));
        $name = trim((string)($field['name'] ?? ''));
        $fieldName = $label !== '' ? $label : $name;
        if ($fieldName === '') {
            return;
        }

        $fields[] = [
            'name' => $fieldName,
            'type' => mapTemplateFieldTypeToApplicationType((string)($field['type'] ?? 'text')),
            'required' => (bool)($field['required'] ?? false),
        ];
    };

    $isList = array_keys($schema) === range(0, count($schema) - 1);
    if ($isList) {
        foreach ($schema as $field) {
            if (is_array($field)) {
                $pushField($field);
            }
        }
    }

    if (isset($schema['fields']) && is_array($schema['fields'])) {
        foreach ($schema['fields'] as $field) {
            if (is_array($field)) {
                $pushField($field);
            }
        }
    }

    if (isset($schema['sections']) && is_array($schema['sections'])) {
        foreach ($schema['sections'] as $section) {
            if (!is_array($section) || !isset($section['fields']) || !is_array($section['fields'])) {
                continue;
            }

            foreach ($section['fields'] as $field) {
                if (is_array($field)) {
                    $pushField($field);
                }
            }
        }
    }

    return $fields;
}

function publishTemplateAsApplication(
    ApplicationsService $applicationsService,
    ApplicationTemplateService $templateService,
    int $templateId,
    string $uploadDir = '',
    ?array &$uploadResult = null
) {
    if ($templateId <= 0) {
        return false;
    }

    $template = $templateService->getTemplateById($templateId);
    if (!$template) {
        return false;
    }

    $title = trim((string)($template['name'] ?? ''));
    if ($title === '') {
        $title = 'Νέα Αίτηση';
    }
    $description = trim((string)($template['description'] ?? ''));

    $newApplicationId = $applicationsService->createApplication($title, $description);
    if (!$newApplicationId) {
        return false;
    }

    $schema = $template['form_schema'] ?? [];
    if (!is_array($schema)) {
        $decoded = json_decode((string)$schema, true);
        $schema = is_array($decoded) ? $decoded : [];
    }

    $fields = extractTemplateFieldsFromSchema($schema);
    foreach ($fields as $index => $field) {
        $applicationsService->addFormField(
            (int)$newApplicationId,
            (string)$field['name'],
            (string)$field['type'],
            (int)$index,
            (bool)$field['required']
        );
    }

    if ($uploadDir !== '') {
        $requestUploadResult = uploadApplicationFiles($applicationsService, (int)$newApplicationId, $uploadDir);
        $storedTemplateFilesResult = cloneTemplateInstructionFilesToApplication(
            $applicationsService,
            $templateId,
            (int)$newApplicationId,
            $uploadDir
        );

        $uploadResult = [
            'uploaded_count' => (int)($requestUploadResult['uploaded_count'] ?? 0) + (int)($storedTemplateFilesResult['uploaded_count'] ?? 0),
            'errors' => array_values(array_filter(array_merge(
                (array)($requestUploadResult['errors'] ?? []),
                (array)($storedTemplateFilesResult['errors'] ?? [])
            ))),
            'uploaded_files' => array_merge(
                (array)($requestUploadResult['uploaded_files'] ?? []),
                (array)($storedTemplateFilesResult['uploaded_files'] ?? [])
            ),
        ];
    }

    return (int)$newApplicationId;
}

/*
|--------------------------------------------------------------------------
| POST actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $applicationUiMeta = loadApplicationUiMeta();
    $returnViewSubmissions = (int)($_POST['return_view_submissions'] ?? 0);
    $returnSubmissionSort = normalizeSubmissionSort((string)($_POST['return_submission_sort'] ?? 'newest'));
    $returnScrollY = max(0, (int)($_POST['return_scroll_y'] ?? 0));
    $returnEditApplicationId = (int)($_POST['return_edit_application_id'] ?? 0);
    $returnEditTab = normalizeEditModalTab((string)($_POST['return_edit_tab'] ?? 'info'));
    $activeTab = '';
    $activeTemplateId = 0;

    if ($action === 'create') {
        $title = trim($_POST['application_title'] ?? '');
        $description = trim($_POST['application_description'] ?? '');
        $openDateUi = getTodayUiDate();
        $closeDateUi = normalizeUiDate((string)($_POST['application_close_date_ui'] ?? ''));
        $statusUi = normalizeApplicationStatus((string)($_POST['application_status_ui'] ?? 'active'));
        $formFieldsJson = $_POST['form_fields_json'] ?? '[]';

        if ($title === '') {
            $message = 'Ο τίτλος της αίτησης είναι υποχρεωτικός.';
            $messageType = 'danger';
        } elseif ($openDateUi !== '' && $closeDateUi !== '' && $openDateUi > $closeDateUi) {
            $message = 'Η ημερομηνία ανοίγματος δεν μπορεί να είναι μετά την ημερομηνία κλεισίματος.';
            $messageType = 'danger';
        } else {
            $newApplicationId = $applicationsService->createApplication($title, $description);
            if ($newApplicationId) {
                // Ανέβασμα αρχείων ανεξάρτητα από τη μέθοδο υποβολής
                $uploadResult = uploadApplicationFiles($applicationsService, (int)$newApplicationId, $documentsUploadDir);

                $newMeta = [
                    'open_date' => $openDateUi,
                    'close_date' => $closeDateUi,
                    'status' => $statusUi,
                ];
                $uploadedImagePath = $uploadResult['uploaded_files']['application_image'][0]['path'] ?? '';
                if ($uploadedImagePath !== '') {
                    $newMeta['image_path'] = (string)$uploadedImagePath;
                }
                $applicationUiMeta[(string)$newApplicationId] = $newMeta;
                saveApplicationUiMeta($applicationUiMeta);

                // Αποθήκευση των πεδίων φόρμας για online συμπλήρωση
                $formFields = json_decode($formFieldsJson, true);
                if (is_array($formFields) && !empty($formFields)) {
                    foreach ($formFields as $index => $field) {
                        $fieldName = trim((string)($field['name'] ?? ''));
                        $fieldType = normalizeEditableApplicationFieldType((string)($field['type'] ?? 'text'));
                        $isRequired = (bool)($field['required'] ?? true);
                        
                        if ($fieldName !== '') {
                            $applicationsService->addFormField(
                                (int)$newApplicationId,
                                $fieldName,
                                $fieldType,
                                (int)$index,
                                $isRequired
                            );
                        }
                    }
                }

                $message = 'Η αίτηση δημιουργήθηκε επιτυχώς!';
                if ($uploadResult['uploaded_count'] > 0) {
                    $message .= ' Ανέβηκαν ' . $uploadResult['uploaded_count'] . ' αρχεία.';
                }
                if (!empty($formFields) && is_array($formFields)) {
                    $message .= ' Δημιουργήθηκαν ' . count(array_filter($formFields, fn($f) => !empty($f['name']))) . ' πεδία φόρμας.';
                }
                if (!empty($uploadResult['errors'])) {
                    $message .= ' ' . implode(' ', $uploadResult['errors']);
                }
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία της αίτησης.';
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'update') {
        $application_id = (int)($_POST['application_id'] ?? 0);
        $title = trim($_POST['application_title'] ?? '');
        $description = trim($_POST['application_description'] ?? '');
        $formFieldsJson = (string)($_POST['form_fields_json'] ?? '');

        if ($application_id > 0 && $title !== '') {
            $existingMeta = $applicationUiMeta[(string)$application_id] ?? [];
            $openDateUi = (string)($existingMeta['open_date'] ?? '');
            $closeDateUi = array_key_exists('application_close_date_ui', $_POST)
                ? normalizeUiDate((string)($_POST['application_close_date_ui'] ?? ''))
                : (string)($existingMeta['close_date'] ?? ($existingMeta['deadline'] ?? ''));
            $statusUi = array_key_exists('application_status_ui', $_POST)
                ? normalizeApplicationStatus((string)($_POST['application_status_ui'] ?? 'active'))
                : normalizeApplicationStatus((string)($existingMeta['status'] ?? 'active'));

            if ($openDateUi !== '' && $closeDateUi !== '' && $openDateUi > $closeDateUi) {
                $message = 'Η ημερομηνία ανοίγματος δεν μπορεί να είναι μετά την ημερομηνία κλεισίματος.';
                $messageType = 'danger';
            } elseif ($applicationsService->updateApplication($application_id, $title, $description)) {
                $uploadResult = uploadApplicationFiles($applicationsService, $application_id, $documentsUploadDir);
                $updatedFormFieldCount = null;
                $formFieldsUpdateError = '';
                $isPublishedApplication = normalizeApplicationStatus((string)($existingMeta['status'] ?? 'active')) === 'active';

                if ($isPublishedApplication && $formFieldsJson !== '') {
                    $decodedFormFields = json_decode($formFieldsJson, true);
                    if (!is_array($decodedFormFields)) {
                        $decodedFormFields = [];
                    }

                    $updatedFormFieldCount = 0;
                    $replaceFailed = false;
                    $existingApplicationFields = $applicationsService->getApplicationFormFields($application_id);

                    foreach ($existingApplicationFields as $existingField) {
                        $fieldId = (int)($existingField['field_id'] ?? 0);
                        if ($fieldId <= 0) {
                            continue;
                        }

                        if (!$applicationsService->deleteFormField($fieldId)) {
                            $replaceFailed = true;
                            $formFieldsUpdateError = 'Δεν ήταν δυνατή η ενημέρωση των πεδίων φόρμας.';
                            break;
                        }
                    }

                    if (!$replaceFailed) {
                        foreach ($decodedFormFields as $index => $field) {
                            if (!is_array($field)) {
                                continue;
                            }

                            $fieldName = trim((string)($field['name'] ?? ''));
                            if ($fieldName === '') {
                                continue;
                            }

                            $fieldType = normalizeEditableApplicationFieldType((string)($field['type'] ?? 'text'));
                            $isRequired = (bool)($field['required'] ?? true);

                            if (!$applicationsService->addFormField($application_id, $fieldName, $fieldType, (int)$index, $isRequired)) {
                                $replaceFailed = true;
                                $formFieldsUpdateError = 'Δεν ήταν δυνατή η αποθήκευση όλων των πεδίων φόρμας.';
                                break;
                            }

                            $updatedFormFieldCount++;
                        }
                    }
                }

                $updatedMeta = [
                    'open_date' => $openDateUi,
                    'close_date' => $closeDateUi,
                    'status' => $statusUi,
                ];

                $uploadedImagePath = $uploadResult['uploaded_files']['application_image'][0]['path'] ?? '';
                if ($uploadedImagePath !== '') {
                    $updatedMeta['image_path'] = (string)$uploadedImagePath;
                } elseif (!empty($existingMeta['image_path'])) {
                    $updatedMeta['image_path'] = (string)$existingMeta['image_path'];
                }

                $applicationUiMeta[(string)$application_id] = $updatedMeta;
                saveApplicationUiMeta($applicationUiMeta);

                $message = 'Η αίτηση ενημερώθηκε επιτυχώς!';
                if ($uploadResult['uploaded_count'] > 0) {
                    $message .= ' Προστέθηκαν ' . $uploadResult['uploaded_count'] . ' νέα αρχεία.';
                }
                if ($updatedFormFieldCount !== null) {
                    $message .= ' Ενημερώθηκαν ' . $updatedFormFieldCount . ' πεδία φόρμας.';
                }
                if ($formFieldsUpdateError !== '') {
                    $message .= ' ' . $formFieldsUpdateError;
                }
                if (!empty($uploadResult['errors'])) {
                    $message .= ' ' . implode(' ', $uploadResult['errors']);
                }
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά την ενημέρωση της αίτησης.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Μη έγκυρα δεδομένα αίτησης.';
            $messageType = 'danger';
        }
    }

    if ($action === 'delete') {
        $application_id = (int)($_POST['application_id'] ?? 0);

        if ($application_id > 0) {
            $documentDisplayNames = loadApplicationDocumentDisplayNames();
            $displayNamesChanged = false;

            foreach ($applicationsService->getDocuments($application_id) as $document) {
                $storedPath = (string)($document['file_path'] ?? '');
                $filePath = getDocumentAbsolutePath((string)($document['file_path'] ?? ''));
                if ($filePath !== '' && file_exists($filePath)) {
                    unlink($filePath);
                }

                if ($storedPath !== '' && isset($documentDisplayNames[$storedPath])) {
                    unset($documentDisplayNames[$storedPath]);
                    $displayNamesChanged = true;
                }
            }

            if ($displayNamesChanged) {
                saveApplicationDocumentDisplayNames($documentDisplayNames);
            }

            if (isset($applicationUiMeta[(string)$application_id])) {
                unset($applicationUiMeta[(string)$application_id]);
                saveApplicationUiMeta($applicationUiMeta);
            }

            if ($applicationsService->deleteApplication($application_id)) {
                $message = 'Η αίτηση διαγράφηκε επιτυχώς!';
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά τη διαγραφή της αίτησης.';
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'upload_document') {
        $application_id = (int)($_POST['application_id'] ?? 0);

        if ($application_id <= 0) {
            $message = 'Μη έγκυρη αίτηση.';
            $messageType = 'danger';
        } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Παρακαλώ ανεβάστε έγκυρο αρχείο.';
            $messageType = 'danger';
        } else {
            $file = $_FILES['document_file'];
            $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                $message = 'Επιτρεπόμενοι τύποι: pdf, doc, docx, jpg, jpeg, png.';
                $messageType = 'danger';
            } else {
                $newFileName = 'application_doc_' . $application_id . '_' . time() . '.' . $ext;
                $targetPath = $documentsUploadDir . $newFileName;
                $dbPath = '/parents-council-platform-group5/public/assets/Applications_docs/' . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    if ($applicationsService->addDocument($application_id, $dbPath)) {
                        $documentDisplayNames = loadApplicationDocumentDisplayNames();
                        $documentDisplayNames[$dbPath] = normalizeUploadedDocumentDisplayName((string)($file['name'] ?? ''));
                        saveApplicationDocumentDisplayNames($documentDisplayNames);

                        $message = 'Το έγγραφο ανέβηκε επιτυχώς!';
                        $messageType = 'success';
                    } else {
                        $message = 'Σφάλμα αποθήκευσης του εγγράφου στη βάση.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Αποτυχία ανεβάσματος αρχείου.';
                    $messageType = 'danger';
                }
            }
        }
    }

    if ($action === 'add_instruction_files') {
        $application_id = (int)($_POST['application_id'] ?? 0);
        if ($application_id <= 0) {
            $message = 'Μη έγκυρη αίτηση.';
            $messageType = 'danger';
        } else {
            $uploadResult = uploadApplicationFiles($applicationsService, $application_id, $documentsUploadDir);
            if ($uploadResult['uploaded_count'] > 0) {
                $message = 'Τα αρχεία προστέθηκαν επιτυχώς!';
                if (!empty($uploadResult['errors'])) {
                    $message .= ' ' . implode(' ', $uploadResult['errors']);
                }
                $messageType = 'success';
            } elseif (!empty($uploadResult['errors'])) {
                $message = implode(' ', $uploadResult['errors']);
                $messageType = 'danger';
            } else {
                $message = 'Δεν επιλέχθηκαν αρχεία.';
                $messageType = 'warning';
            }
        }
    }

    if ($action === 'replace_document') {
        $doc_id = (int)($_POST['ap_document_id'] ?? 0);
        $application_id = (int)($_POST['application_id'] ?? 0);

        if ($doc_id <= 0 || $application_id <= 0) {
            $message = 'Μη έγκυρα στοιχεία αρχείου.';
            $messageType = 'danger';
        } elseif (!isset($_FILES['replacement_file']) || ($_FILES['replacement_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $message = 'Παρακαλώ επιλέξτε νέο αρχείο για αντικατάσταση.';
            $messageType = 'danger';
        } else {
            $document = $applicationsService->getDocumentById($doc_id);
            if (!$document || (int)($document['application_id'] ?? 0) !== $application_id) {
                $message = 'Το αρχείο δεν βρέθηκε.';
                $messageType = 'danger';
            } else {
                $replacementFile = $_FILES['replacement_file'];
                $currentPath = (string)($document['file_path'] ?? '');
                if (strpos($currentPath, '_instruction_file_') === false) {
                    $message = 'Μπορούν να αντικατασταθούν μόνο αρχεία οδηγιών.';
                    $messageType = 'danger';
                } else {
                    $extension = strtolower(pathinfo((string)($replacementFile['name'] ?? ''), PATHINFO_EXTENSION));
                    $allowedExtensions = ['pdf', 'doc', 'docx'];
                    if (!in_array($extension, $allowedExtensions, true)) {
                        $message = 'Επιτρεπόμενοι τύποι: pdf, doc, docx.';
                        $messageType = 'danger';
                    } else {
                        $fieldToken = strpos($currentPath, '_instruction_file_') !== false ? 'instruction_file' : 'document_file';
                        $newFileName = 'application_' . $application_id . '_' . $fieldToken . '_' . uniqid('', true) . '.' . $extension;
                        $targetPath = $documentsUploadDir . $newFileName;
                        $dbPath = '/parents-council-platform-group5/public/assets/Applications_docs/' . $newFileName;

                        if (!move_uploaded_file((string)$replacementFile['tmp_name'], $targetPath)) {
                            $message = 'Αποτυχία ανεβάσματος νέου αρχείου.';
                            $messageType = 'danger';
                        } elseif ($applicationsService->updateDocumentPath($doc_id, $dbPath)) {
                            $documentDisplayNames = loadApplicationDocumentDisplayNames();
                            if ($currentPath !== '' && isset($documentDisplayNames[$currentPath])) {
                                unset($documentDisplayNames[$currentPath]);
                            }
                            $documentDisplayNames[$dbPath] = normalizeUploadedDocumentDisplayName((string)($replacementFile['name'] ?? ''));
                            saveApplicationDocumentDisplayNames($documentDisplayNames);

                            $oldAbsPath = getDocumentAbsolutePath($currentPath);
                            if ($oldAbsPath !== '' && file_exists($oldAbsPath)) {
                                unlink($oldAbsPath);
                            }
                            $message = 'Το αρχείο αντικαταστάθηκε επιτυχώς!';
                            $messageType = 'success';
                        } else {
                            if (file_exists($targetPath)) {
                                unlink($targetPath);
                            }
                            $message = 'Σφάλμα κατά την αντικατάσταση του αρχείου.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }
    }

    if ($action === 'delete_document') {
        $doc_id = (int)($_POST['ap_document_id'] ?? 0);
        $isAjaxDeleteDocument = isset($_POST['ajax_delete_document']) && (string)$_POST['ajax_delete_document'] === '1';
        $deleteDocumentSuccess = false;
        $deleteDocumentMessage = 'Μη έγκυρα στοιχεία αρχείου.';

        if ($doc_id > 0) {
            $doc = $applicationsService->getDocumentById($doc_id);

            if ($doc) {
                $storedPath = (string)($doc['file_path'] ?? '');
                $filePath = getDocumentAbsolutePath((string)$doc['file_path']);
                if ($filePath !== '' && file_exists($filePath)) {
                    unlink($filePath);
                }

                if ($applicationsService->deleteDocument($doc_id)) {
                    if ($storedPath !== '') {
                        $documentDisplayNames = loadApplicationDocumentDisplayNames();
                        if (isset($documentDisplayNames[$storedPath])) {
                            unset($documentDisplayNames[$storedPath]);
                            saveApplicationDocumentDisplayNames($documentDisplayNames);
                        }
                    }

                    $deleteDocumentSuccess = true;
                    $deleteDocumentMessage = 'Το έγγραφο διαγράφηκε επιτυχώς!';
                } else {
                    $deleteDocumentMessage = 'Σφάλμα κατά τη διαγραφή του εγγράφου.';
                }
            } else {
                $deleteDocumentMessage = 'Το αρχείο δεν βρέθηκε.';
            }
        }

        if ($isAjaxDeleteDocument) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $deleteDocumentSuccess,
                'message' => $deleteDocumentMessage,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $message = $deleteDocumentMessage;
        $messageType = $deleteDocumentSuccess ? 'success' : 'danger';
    }

    if ($action === 'update_submission_status') {
        $application_id = (int)($_POST['application_id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);
        $sub_status = $_POST['sub_status'] ?? '';

        if ($application_id > 0 && $user_id > 0) {
            if ($sub_status === 'delete') {
                if ($applicationsService->deleteSubmission($application_id, $user_id)) {
                    $message = 'Η υποβολή διαγράφηκε επιτυχώς. Ο χρήστης μπορεί να υποβάλει ξανά αίτηση.';
                    $messageType = 'success';
                } else {
                    $message = 'Σφάλμα κατά τη διαγραφή της υποβολής.';
                    $messageType = 'danger';
                }
            }
        }
    }

    if ($action === 'mark_submission_seen') {
        $application_id = (int)($_POST['application_id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);
        $success = false;

        if ($application_id > 0 && $user_id > 0) {
            $success = $applicationsService->markSubmissionAsSeen($application_id, $user_id);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'application_id' => $application_id,
            'application_waiting_count' => $application_id > 0 ? (int)$applicationsService->getWaitingSubmissionCountByApplication($application_id) : 0,
            'global_waiting_count' => (int)$applicationsService->getWaitingSubmissionCount(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Handle creating application from template (NEW)
    if ($action === 'create_app_from_template') {
        $template_id = !empty($_POST['template_id']) && $_POST['template_id'] !== 'blank' ? (int)$_POST['template_id'] : null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $open_date = getTodayUiDate();
        $due_date = trim($_POST['due_date'] ?? null);
        $allow_online = isset($_POST['allow_online']) ? 1 : 0;
        $allow_file = isset($_POST['allow_file']) ? 1 : 0;
        $require_signature = isset($_POST['require_signature']) ? 1 : 0;

        if (empty($title)) {
            $message = 'Ο τίτλος της αίτησης είναι υποχρεωτικός';
            $messageType = 'danger';
        } else {
            // Also add to legacy field names
            $app_id = $applicationsService->createApplicationFromTemplate(
                $template_id,
                $title,
                $description,
                $academic_year,
                $open_date,
                $due_date,
                $allow_online,
                $allow_file,
                $require_signature,
                (int)$_SESSION['user_id']
            );

            if ($app_id) {
                $message = 'Η αίτηση δημιουργήθηκε επιτυχώς!';
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία της αίτησης';
                $messageType = 'danger';
            }
        }
    }

    // Handle publishing application (NEW)
    if ($action === 'publish_application') {
        $app_id = (int)($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            if ($applicationsService->publishApplication($app_id)) {
                $message = 'Η αίτηση δημοσιεύθηκε επιτυχώς!';
                $messageType = 'success';
            }
        }
    }

    // Handle closing application (NEW)
    if ($action === 'close_application') {
        $app_id = (int)($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            if ($applicationsService->closeApplication($app_id)) {
                $message = 'Η αίτηση κλείστηκε και δεν δέχεται υποβολές';
                $messageType = 'success';
            }
        }
    }

    // Handle creating template (NEW)
    if ($action === 'create_template') {
        $template_name = trim($_POST['template_name'] ?? '');
        $template_category = 'standard';
        $template_description = trim($_POST['description'] ?? '');
        $form_schema_raw = $_POST['form_schema'] ?? '[]';

        $form_schema = json_decode((string)$form_schema_raw, true);
        if (!is_array($form_schema)) {
            $form_schema = [];
        }

        if (empty($template_name)) {
            $message = 'Το όνομα του προτύπου είναι υποχρεωτικό';
            $messageType = 'danger';
            $activeTab = 'templates';
        } else {
            // Auto-generate template key from name (lowercase, replace spaces with underscores)
            $template_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $template_name));
            $template_key = trim($template_key, '_'); // Remove leading/trailing underscores
            if ($template_key === '') {
                $template_key = 'template';
            }
            $template_key .= '_' . time(); // Add timestamp to ensure uniqueness

            $createdTemplateId = $templateService->createTemplate(
                $template_key,
                $template_name,
                $template_description,
                $template_category,
                $form_schema,
                0
            );

            if ($createdTemplateId) {
                $templateUploadResult = uploadTemplateInstructionFiles((int)$createdTemplateId, $documentsUploadDir);
                $message = 'Το πρότυπο δημιουργήθηκε επιτυχώς!';
                if ((int)($templateUploadResult['uploaded_count'] ?? 0) > 0) {
                    $message .= ' Προστέθηκαν ' . (int)$templateUploadResult['uploaded_count'] . ' αρχεία οδηγιών.';
                }
                if (!empty($templateUploadResult['errors'])) {
                    $message .= ' ' . implode(' ', (array)$templateUploadResult['errors']);
                }
                $messageType = 'success';
                $activeTab = 'templates';
                $activeTemplateId = (int)$createdTemplateId;
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία του προτύπου';
                $messageType = 'danger';
                $activeTab = 'templates';
            }
        }
    }

    // Handle deleting template (NEW)
    if ($action === 'delete_template') {
        $template_id = (int)($_POST['template_id'] ?? 0);
        if ($template_id > 0) {
            if ($templateService->deleteTemplate($template_id)) {
                deleteTemplateInstructionFiles($template_id);
                $message = 'Το πρότυπο διαγράφηκε επιτυχώς!';
                $messageType = 'success';
                $activeTab = 'templates';
            } else {
                $message = 'Σφάλμα κατά τη διαγραφή του προτύπου';
                $messageType = 'danger';
                $activeTab = 'templates';
            }
        } else {
            $message = 'Μη έγκυρο πρότυπο για διαγραφή.';
            $messageType = 'danger';
            $activeTab = 'templates';
        }
    }

    // Handle updating template (NEW)
    if ($action === 'update_template') {
        $template_id = (int)($_POST['template_id'] ?? 0);
        $template_name = trim($_POST['template_name'] ?? '');
        $template_description = trim($_POST['description'] ?? '');
        $removedTemplateInstructionFilesRaw = $_POST['removed_instruction_files'] ?? '[]';
        $removedTemplateInstructionFiles = json_decode((string)$removedTemplateInstructionFilesRaw, true);
        if (!is_array($removedTemplateInstructionFiles)) {
            $removedTemplateInstructionFiles = [];
        }
        $normalizedRemovedTemplateInstructionFiles = [];
        foreach ($removedTemplateInstructionFiles as $rawTemplatePathToRemove) {
            if (!is_string($rawTemplatePathToRemove)) {
                continue;
            }

            $normalizedTemplatePathToRemove = trim($rawTemplatePathToRemove);
            if ($normalizedTemplatePathToRemove === '') {
                continue;
            }

            $normalizedRemovedTemplateInstructionFiles[] = $normalizedTemplatePathToRemove;
        }
        $shouldPublishTemplate = isset($_POST['publish']) && (string)$_POST['publish'] === '1';
        $existingTemplate = $template_id > 0 ? $templateService->getTemplateById($template_id) : null;
        $template_category = $existingTemplate['category'] ?? 'standard';
        $form_schema_raw = $_POST['form_schema'] ?? '';
        $decodedFormSchema = json_decode((string)$form_schema_raw, true);
        if (is_array($decodedFormSchema)) {
            $form_schema_json = json_encode($decodedFormSchema, JSON_UNESCAPED_UNICODE);
        } elseif ($existingTemplate && isset($existingTemplate['form_schema'])) {
            $existingSchema = $existingTemplate['form_schema'];
            $form_schema_json = is_array($existingSchema)
                ? json_encode($existingSchema, JSON_UNESCAPED_UNICODE)
                : (string)$existingSchema;
        } else {
            $form_schema_json = '[]';
        }
        $activeTemplateId = $template_id;

        if ($template_id > 0 && !empty($template_name)) {
            if ($templateService->updateTemplate($template_id, $template_name, $template_description, $template_category, $form_schema_json)) {
                $removedTemplateFilesResult = removeTemplateInstructionFilesByPaths($template_id, $normalizedRemovedTemplateInstructionFiles);
                $templateUploadResult = uploadTemplateInstructionFiles($template_id, $documentsUploadDir);

                $templateInstructionChanges = [];
                if ((int)($removedTemplateFilesResult['removed_count'] ?? 0) > 0) {
                    $templateInstructionChanges[] = 'Αφαιρέθηκαν ' . (int)$removedTemplateFilesResult['removed_count'] . ' αρχεία οδηγιών από το πρότυπο.';
                }
                if ((int)($templateUploadResult['uploaded_count'] ?? 0) > 0) {
                    $templateInstructionChanges[] = 'Προστέθηκαν ' . (int)$templateUploadResult['uploaded_count'] . ' νέα αρχεία οδηγιών στο πρότυπο.';
                }
                $templateInstructionErrors = array_values(array_filter(array_merge(
                    (array)($removedTemplateFilesResult['errors'] ?? []),
                    (array)($templateUploadResult['errors'] ?? [])
                )));

                if ($shouldPublishTemplate) {
                    $publishUploadResult = null;
                    $publishedApplicationId = publishTemplateAsApplication(
                        $applicationsService,
                        $templateService,
                        $template_id,
                        $documentsUploadDir,
                        $publishUploadResult
                    );
                    if ($publishedApplicationId) {
                        $publishedAppMeta = $applicationUiMeta[(string)$publishedApplicationId] ?? [];
                        $publishedAppMeta['open_date'] = getTodayUiDate();
                        if (!isset($publishedAppMeta['close_date'])) {
                            $publishedAppMeta['close_date'] = '';
                        }
                        $publishedAppMeta['status'] = 'active';
                        $applicationUiMeta[(string)$publishedApplicationId] = $publishedAppMeta;
                        saveApplicationUiMeta($applicationUiMeta);

                        $message = 'Το πρότυπο ενημερώθηκε και δημοσιεύθηκε ως αίτηση επιτυχώς!';
                        if (!empty($templateInstructionChanges)) {
                            $message .= ' ' . implode(' ', $templateInstructionChanges);
                        }
                        if (is_array($publishUploadResult) && (int)($publishUploadResult['uploaded_count'] ?? 0) > 0) {
                            $message .= ' Ανέβηκαν ' . (int)$publishUploadResult['uploaded_count'] . ' αρχεία οδηγιών.';
                        }
                        if (is_array($publishUploadResult) && !empty($publishUploadResult['errors'])) {
                            $message .= ' ' . implode(' ', (array)$publishUploadResult['errors']);
                        }
                        if (!empty($templateInstructionErrors)) {
                            $message .= ' ' . implode(' ', $templateInstructionErrors);
                        }
                        $messageType = 'success';
                    } else {
                        $message = 'Το πρότυπο ενημερώθηκε, αλλά απέτυχε η δημοσίευση ως αίτηση.';
                        if (!empty($templateInstructionChanges)) {
                            $message .= ' ' . implode(' ', $templateInstructionChanges);
                        }
                        if (!empty($templateInstructionErrors)) {
                            $message .= ' ' . implode(' ', $templateInstructionErrors);
                        }
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Το πρότυπο ενημερώθηκε επιτυχώς!';
                    if (!empty($templateInstructionChanges)) {
                        $message .= ' ' . implode(' ', $templateInstructionChanges);
                    }
                    if (!empty($templateInstructionErrors)) {
                        $message .= ' ' . implode(' ', $templateInstructionErrors);
                    }
                    $messageType = 'success';
                }
                $activeTab = 'templates';
            } else {
                $message = 'Σφάλμα κατά την ενημέρωση του προτύπου';
                $messageType = 'danger';
                $activeTab = 'templates';
            }
        } else {
            $message = 'Μη έγκυρα δεδομένα';
            $messageType = 'danger';
            $activeTab = 'templates';
        }
    }

    $redirectUrl = 'applications.php';
    if ($returnViewSubmissions > 0) {
        $redirectUrl .= '?view_submissions=' . $returnViewSubmissions;
        $redirectUrl .= '&submissions_sort=' . urlencode($returnSubmissionSort);
        if ($returnScrollY > 0) {
            $redirectUrl .= '&scroll_y=' . $returnScrollY;
        }
    } elseif ($returnScrollY > 0) {
        $redirectUrl .= '?scroll_y=' . $returnScrollY;
    }

    if ($activeTab !== '') {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'active_tab=' . urlencode($activeTab);
    }

    if ($activeTemplateId > 0) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'active_template_id=' . $activeTemplateId;
    }

    if ($returnEditApplicationId > 0) {
        if ($activeTab === '') {
            $activeTab = 'manage';
            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'active_tab=' . urlencode($activeTab);
        }

        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'active_edit_application_id=' . $returnEditApplicationId;
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'active_edit_tab=' . urlencode($returnEditTab);
    }

    $_SESSION['flash_message'] = $message ?: 'Η ενέργεια ολοκληρώθηκε.';
    $_SESSION['flash_message_type'] = $messageType ?: 'info';

    header('Location: ' . $redirectUrl);
    exit;
}

/*
|--------------------------------------------------------------------------
| Load data
|--------------------------------------------------------------------------
*/
$applications = $applicationsService->getAllApplications();
$documents = $applicationsService->getAllDocuments();
$submissions = $applicationsService->getAllSubmissions();
$templates = $templateService->getAllTemplates();
$applicationUiMeta = loadApplicationUiMeta();
$documentDisplayNamesByPath = loadApplicationDocumentDisplayNames();
$documentsByApplication = [];

foreach ($documents as $document) {
    $applicationIdForDocument = (int)($document['application_id'] ?? 0);
    if ($applicationIdForDocument <= 0) {
        continue;
    }
    if (!isset($documentsByApplication[$applicationIdForDocument])) {
        $documentsByApplication[$applicationIdForDocument] = [];
    }
    $documentsByApplication[$applicationIdForDocument][] = $document;
}

$submissionCountByApplication = [];
$newSubmissionCountByApplication = [];

foreach ($submissions as $submission) {
    $applicationId = (int)($submission['application_id'] ?? 0);
    if (!isset($submissionCountByApplication[$applicationId])) {
        $submissionCountByApplication[$applicationId] = 0;
    }
    $submissionCountByApplication[$applicationId]++;

    $isUnreadWaitingSubmission = (string)($submission['sub_status'] ?? '') === 'waiting'
        && trim((string)($submission['admin_seen_at'] ?? '')) === '';

    if ($isUnreadWaitingSubmission) {
        if (!isset($newSubmissionCountByApplication[$applicationId])) {
            $newSubmissionCountByApplication[$applicationId] = 0;
        }
        $newSubmissionCountByApplication[$applicationId]++;
    }
}

$selectedApplicationId = (int)($_GET['view_submissions'] ?? 0);
$selectedSubmissionSort = normalizeSubmissionSort((string)($_GET['submissions_sort'] ?? 'newest'));
$selectedApplication = $selectedApplicationId > 0 ? $applicationsService->getApplicationById($selectedApplicationId) : null;
$selectedSubmissions = [];

if ($selectedApplicationId > 0) {
    foreach ($submissions as $submission) {
        if ((int)$submission['application_id'] === $selectedApplicationId) {
            $selectedSubmissions[] = $submission;
        }
    }

    usort($selectedSubmissions, static function (array $a, array $b) use ($selectedSubmissionSort): int {
        $aTime = strtotime((string)($a['submitted_at'] ?? ''));
        $bTime = strtotime((string)($b['submitted_at'] ?? ''));
        $aTs = $aTime !== false ? $aTime : 0;
        $bTs = $bTime !== false ? $bTime : 0;

        if ($aTs === $bTs) {
            $aUser = (int)($a['user_id'] ?? 0);
            $bUser = (int)($b['user_id'] ?? 0);
            return $selectedSubmissionSort === 'oldest' ? ($aUser <=> $bUser) : ($bUser <=> $aUser);
        }

        return $selectedSubmissionSort === 'oldest' ? ($aTs <=> $bTs) : ($bTs <=> $aTs);
    });
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_applications.css">

    <title>Διαχείριση Αιτήσεων - Admin</title>
</head>
<body>


<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
        </a>

        <div class="admin-header admin-page-header">
            <div>
                <h1><i class="fas fa-file-alt me-2"></i>Διαχείριση Αιτήσεων</h1>
                <p class="text-muted mb-0">Οργάνωση αιτήσεων, συνημμένων και υποβολών γονέων σε ένα ενιαίο dashboard.</p>
            </div>
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createApplicationModal">
                <i class="fas fa-plus me-1"></i>Νέα Αίτηση
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Κλείσιμο"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 stats-grid mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom stat-card h-100">
                    <div class="card-body">
                        <span class="stat-label">Σύνολο Αιτήσεων</span>
                        <div class="stat-value"><?php echo count($applications); ?></div>
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom stat-card h-100">
                    <div class="card-body">
                        <span class="stat-label">Σύνολο Υποβολών</span>
                        <div class="stat-value"><?php echo count($submissions); ?></div>
                        <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS for new features -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-manage-link" data-bs-toggle="tab" href="#tab-manage" role="tab" aria-controls="tab-manage" aria-selected="true">
                    <i class="fas fa-cog me-2"></i> Διαχείριση Αιτήσεων
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-templates-link" data-bs-toggle="tab" href="#tab-templates" role="tab" aria-controls="tab-templates" aria-selected="false">
                    <i class="fas fa-file-alt me-2"></i> Πρότυπα Αιτήσεων
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: Manage Applications -->
            <div id="tab-manage" class="tab-pane fade show active" role="tabpanel" aria-labelledby="tab-manage-link">

        <div class="card card-custom mb-4">
            <div class="card-body">
                <div class="section-toolbar mb-3">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-table me-2"></i>Πίνακας Αιτήσεων</h4>
                        <p class="text-muted mb-0">Δημιουργία, επεξεργασία και προβολή των αιτήσεων που εμφανίζονται στο parent portal.</p>
                    </div>
                </div>

                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Δεν υπάρχουν αιτήσεις</h3>
                        <p>Δημιουργήστε μία νέα αίτηση για να ξεκινήσετε.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle admin-dashboard-table">
                            <thead>
                                <tr>
                                    <th>Τίτλος Αίτησης</th>
                                    <th>Υποβολές</th>
                                    <th>Ημερομηνία</th>
                                    <th class="text-end">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $application): ?>
                                    <?php
                                        $applicationId = (int)$application['application_id'];
                                        $submissionTotal = $submissionCountByApplication[$applicationId] ?? 0;
                                        $newSubmissionTotal = $newSubmissionCountByApplication[$applicationId] ?? 0;
                                        $appMeta = $applicationUiMeta[(string)$applicationId] ?? [];
                                        $applicationOpenDate = (string)($appMeta['open_date'] ?? '');
                                        $applicationStatusUi = normalizeApplicationStatus((string)($appMeta['status'] ?? 'active'));
                                        $isApplicationPublished = $applicationStatusUi === 'active';
                                        $applicationDateDisplay = '—';
                                        if ($applicationOpenDate !== '') {
                                            $applicationDateObj = DateTime::createFromFormat('Y-m-d', $applicationOpenDate);
                                            if ($applicationDateObj && $applicationDateObj->format('Y-m-d') === $applicationOpenDate) {
                                                $applicationDateDisplay = $applicationDateObj->format('d/m/Y');
                                            } else {
                                                $applicationDateDisplay = $applicationOpenDate;
                                            }
                                        }
                                        $applicationFilesForEdit = [];
                                        foreach (($documentsByApplication[$applicationId] ?? []) as $applicationDocument) {
                                            $docPath = (string)($applicationDocument['file_path'] ?? '');
                                            if ($docPath === '' || strpos($docPath, '_instruction_file_') === false) {
                                                continue;
                                            }

                                            $mappedDisplayName = isset($documentDisplayNamesByPath[$docPath])
                                                ? trim((string)$documentDisplayNamesByPath[$docPath])
                                                : '';
                                            $displayName = $mappedDisplayName !== '' ? $mappedDisplayName : basename($docPath);

                                            $applicationFilesForEdit[] = [
                                                'id' => (int)($applicationDocument['ap_document_id'] ?? 0),
                                                'name' => $displayName,
                                                'url' => getDocumentPublicUrl($docPath),
                                            ];
                                        }
                                        $applicationFilesForEditJson = json_encode($applicationFilesForEdit, JSON_UNESCAPED_UNICODE);

                                        $applicationFormFieldsForEdit = [];
                                        foreach ($applicationsService->getApplicationFormFields($applicationId) as $applicationFormField) {
                                            $applicationFormFieldsForEdit[] = [
                                                'name' => (string)($applicationFormField['field_name'] ?? ''),
                                                'type' => normalizeEditableApplicationFieldType((string)($applicationFormField['field_type'] ?? 'text')),
                                                'required' => (bool)($applicationFormField['is_required'] ?? false),
                                            ];
                                        }
                                        $applicationFormFieldsForEditJson = json_encode($applicationFormFieldsForEdit, JSON_UNESCAPED_UNICODE);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($application['application_title']); ?></div>
                                                <?php if ($newSubmissionTotal > 0): ?>
                                                    <span class="application-new-submission-badge" data-application-notification-badge="1" data-application-id="<?php echo $applicationId; ?>" aria-label="Νέες αιτήσεις προς έλεγχο: <?php echo (int)$newSubmissionTotal; ?>">
                                                        <?php echo $newSubmissionTotal > 9 ? '9+' : (int)$newSubmissionTotal; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted text-truncate-two-lines"><?php echo htmlspecialchars($application['application_description'] ?? 'Χωρίς περιγραφή.'); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $submissionTotal; ?></span></td>
                                        <td><span class="text-muted"><?php echo htmlspecialchars($applicationDateDisplay); ?></span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center gap-2 application-table-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary py-0 px-2 js-edit-application application-table-action-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editApplicationModal"
                                                    data-application-id="<?php echo $applicationId; ?>"
                                                    data-application-title="<?php echo htmlspecialchars($application['application_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-description="<?php echo htmlspecialchars($application['application_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-open-date="<?php echo htmlspecialchars($applicationOpenDate, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-documents="<?php echo htmlspecialchars($applicationFilesForEditJson ?: '[]', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-form-fields="<?php echo htmlspecialchars($applicationFormFieldsForEditJson ?: '[]', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-published="<?php echo $isApplicationPublished ? '1' : '0'; ?>"
                                                >
                                                    <i class="fas fa-edit me-1"></i>Επεξεργασία
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger py-0 px-2 js-open-delete-modal application-table-action-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteApplicationModal"
                                                    data-application-id="<?php echo $applicationId; ?>"
                                                    data-application-title="<?php echo htmlspecialchars($application['application_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                                >
                                                    <i class="fas fa-trash-alt me-1"></i>Διαγραφή
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <div class="section-toolbar mb-3">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-users-cog me-2"></i>Υποβολές Αιτήσεων</h4>
                        <p class="text-muted mb-0">Επιλέξτε μία αίτηση για να δείτε τις υποβολές και να τις αποδεχτείτε ή να τις απορρίψετε.</p>
                    </div>
                </div>

                <form method="GET" class="row g-3 align-items-end mb-4">
                    <div class="col-12 col-lg-6">
                        <label for="view_submissions" class="form-label"><strong>Επιλογή Αίτησης</strong></label>
                        <select id="view_submissions" name="view_submissions" class="form-select" required>
                            <option value="">Επιλέξτε αίτηση...</option>
                            <?php foreach ($applications as $application): ?>
                                <option value="<?php echo (int)$application['application_id']; ?>" <?php echo $selectedApplicationId === (int)$application['application_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($application['application_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="submissions_sort" class="form-label"><strong>Ταξινόμηση</strong></label>
                        <select id="submissions_sort" name="submissions_sort" class="form-select">
                            <option value="newest" <?php echo $selectedSubmissionSort === 'newest' ? 'selected' : ''; ?>>Πιο πρόσφατες υποβολές</option>
                            <option value="oldest" <?php echo $selectedSubmissionSort === 'oldest' ? 'selected' : ''; ?>>Πιο παλιές υποβολές</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-auto">
                        <button type="submit" class="btn btn-primary-custom">Προβολή Υποβολών</button>
                    </div>
                    <div class="col-12 col-lg-auto">
                        <a href="applications.php" class="btn btn-outline-secondary">Καθαρισμός</a>
                    </div>
                </form>

                <?php if (!$selectedApplication): ?>
                    <div class="empty-state small-empty">
                        <i class="fas fa-folder-open"></i>
                        <p>Επιλέξτε μια αίτηση για να εμφανιστούν οι υποβολές.</p>
                    </div>
                <?php elseif (empty($selectedSubmissions)): ?>
                    <div class="empty-state small-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Δεν υπάρχουν ακόμη υποβολές για την αίτηση «<?php echo htmlspecialchars($selectedApplication['application_title']); ?>».</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle admin-dashboard-table">
                            <thead>
                                <tr>
                                    <th>Γονέας</th>
                                    <th>Ημ. Υποβολής</th>
                                    <th>Τρόπος Υποβολής</th>
                                    <th>Συνημμένα</th>
                                    <th>Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $selectedApplicationFieldDefinitions = [];
                                    foreach ($applicationsService->getApplicationFormFields((int)$selectedApplicationId) as $fieldIndex => $selectedApplicationField) {
                                        $fieldLabel = trim((string)($selectedApplicationField['field_name'] ?? ''));
                                        if ($fieldLabel === '') {
                                            continue;
                                        }

                                        $selectedApplicationFieldDefinitions[] = [
                                            'key' => normalizeSubmissionFieldKey($fieldLabel, (int)$fieldIndex),
                                            'name' => $fieldLabel,
                                            'type' => normalizeEditableApplicationFieldType((string)($selectedApplicationField['field_type'] ?? 'text')),
                                        ];
                                    }
                                ?>
                                <?php foreach ($selectedSubmissions as $submission): ?>
                                    <?php
                                        $formData = json_decode($submission['submission_data'] ?? '{}', true);
                                        if (!is_array($formData)) {
                                            $formData = [];
                                        }
                                        $isNewSubmission = (string)($submission['sub_status'] ?? '') === 'waiting'
                                            && trim((string)($submission['admin_seen_at'] ?? '')) === '';
                                        $parentName = $formData['parent_name'] ?? trim(($submission['name'] ?? '') . ' ' . ($submission['surname'] ?? ''));
                                        $submittedAt = !empty($submission['submitted_at']) ? date('d/m/Y H:i', strtotime($submission['submitted_at'])) : '—';
                                        $parentAccountName = trim(($submission['name'] ?? '') . ' ' . ($submission['surname'] ?? ''));
                                        $submissionFiles = [];
                                        $uploadedFileNames = [];

                                        if (!empty($formData['_uploaded_file_names']) && is_array($formData['_uploaded_file_names'])) {
                                            foreach ($formData['_uploaded_file_names'] as $storedPath => $displayName) {
                                                $storedPath = trim((string)$storedPath);
                                                $displayName = trim((string)$displayName);
                                                if ($storedPath !== '' && $displayName !== '') {
                                                    $uploadedFileNames[$storedPath] = $displayName;
                                                }
                                            }
                                        }

                                        $legacyFilePath = trim((string)($submission['file_path'] ?? ''));
                                        if ($legacyFilePath !== '') {
                                            $submissionFiles[] = $legacyFilePath;
                                        }

                                        if (!empty($formData['_uploaded_files']) && is_array($formData['_uploaded_files'])) {
                                            foreach ($formData['_uploaded_files'] as $uploadedPath) {
                                                $uploadedPath = trim((string)$uploadedPath);
                                                if ($uploadedPath !== '') {
                                                    $submissionFiles[] = $uploadedPath;
                                                }
                                            }
                                        }

                                        $submissionFiles = array_values(array_unique($submissionFiles));
                                        $uploadedFileLinks = [];
                                        foreach ($submissionFiles as $submissionFilePath) {
                                            $uploadedFileLinks[] = [
                                                'name' => (string)($uploadedFileNames[$submissionFilePath] ?? basename($submissionFilePath)),
                                                'url' => getDocumentPublicUrl($submissionFilePath),
                                            ];
                                        }

                                        $submissionMode = resolveSubmissionMode(is_array($formData) ? $formData : [], $submissionFiles);
                                        $submissionModeUi = getSubmissionModeUi($submissionMode);
                                        $submissionDetailPayload = [
                                            'application_id' => (int)($submission['application_id'] ?? 0),
                                            'user_id' => (int)($submission['user_id'] ?? 0),
                                            'application_title' => (string)($submission['application_title'] ?? ($selectedApplication['application_title'] ?? '')),
                                            'parent_name' => (string)$parentName,
                                            'parent_account' => $parentAccountName !== '' ? $parentAccountName : '—',
                                            'submitted_at' => $submittedAt,
                                            'submission_mode' => $submissionMode,
                                            'submission_mode_label' => $submissionModeUi['label'],
                                            'submission_data' => $formData,
                                            'form_field_definitions' => $selectedApplicationFieldDefinitions,
                                            'uploaded_file_links' => $uploadedFileLinks,
                                        ];
                                        $submissionDetailJson = json_encode($submissionDetailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
                                    ?>
                                    <tr class="<?php echo $isNewSubmission ? 'submission-row-new' : ''; ?>" data-application-id="<?php echo (int)($submission['application_id'] ?? 0); ?>" data-user-id="<?php echo (int)($submission['user_id'] ?? 0); ?>" data-is-new="<?php echo $isNewSubmission ? '1' : '0'; ?>">
                                        <td>
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <span><?php echo htmlspecialchars((string)$parentName); ?></span>
                                                <?php if ($isNewSubmission): ?>
                                                    <span class="application-new-submission-badge submission-new-label" aria-label="Νέα υποβολή">Νέα</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?php echo htmlspecialchars((string)($submission['email'] ?? '')); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$submittedAt); ?></td>
                                        <td>
                                            <span class="<?php echo htmlspecialchars($submissionModeUi['class']); ?>">
                                                <?php echo htmlspecialchars($submissionModeUi['label']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($submissionFiles)): ?>
                                                <div class="d-flex flex-column gap-1">
                                                    <?php foreach ($submissionFiles as $submissionFilePath): ?>
                                                        <a href="<?php echo htmlspecialchars(getDocumentPublicUrl($submissionFilePath)); ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">
                                                            <?php echo htmlspecialchars($uploadedFileNames[$submissionFilePath] ?? basename($submissionFilePath)); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary js-view-submission"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#submissionDetailModal"
                                                    data-submission="<?php echo htmlspecialchars((string)$submissionDetailJson, ENT_QUOTES, 'UTF-8'); ?>"
                                                >
                                                    <i class="fas fa-eye me-1"></i>Προβολή
                                                </button>
                                                <form method="POST" class="d-flex align-items-center gap-2 js-submission-action-form m-0" data-parent-name="<?php echo htmlspecialchars((string)$parentName, ENT_QUOTES, 'UTF-8'); ?>" data-application-title="<?php echo htmlspecialchars((string)($submission['application_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="update_submission_status">
                                                    <input type="hidden" name="application_id" value="<?php echo (int)$submission['application_id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$submission['user_id']; ?>">
                                                    <input type="hidden" name="sub_status" value="delete">
                                                    <input type="hidden" name="return_view_submissions" value="<?php echo $selectedApplicationId; ?>">
                                                    <input type="hidden" name="return_submission_sort" value="<?php echo htmlspecialchars($selectedSubmissionSort, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="return_scroll_y" value="0">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash-alt me-1"></i>Διαγραφή
                                                    </button>
                                                </form>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            </div> <!-- END TAB 1 -->
            <!-- TAB 2: Templates -->
            <div id="tab-templates" class="tab-pane fade" role="tabpanel" aria-labelledby="tab-templates-link">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <div class="section-toolbar mb-3">
                            <div>
                                <h4 class="mb-1"><i class="fas fa-file-alt me-2"></i>Πρότυπα Αιτήσεων</h4>
                                <p class="text-muted mb-0">Επεξεργασία και δημοσίευση προτύπων αιτήσεων που θα χρησιμοποιήσουν οι γονείς.</p>
                            </div>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#create-template-collapse"
                                    aria-expanded="false"
                                    aria-controls="create-template-collapse"
                                >
                                    <i class="fas fa-plus me-1"></i>Δημιουργία νέου προτύπου
                                </button>
                            </div>
                        </div>

                        <div class="collapse mb-4" id="create-template-collapse">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Νέο Πρότυπο</h6>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            id="close_create_template_btn"
                                            aria-label="Κλείσιμο δημιουργίας προτύπου"
                                            title="Κλείσιμο"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <form method="POST" enctype="multipart/form-data" id="createTemplateForm">
                                        <input type="hidden" name="action" value="create_template">
                                        <input type="hidden" name="form_schema" id="create_template_schema" value="[]">

                                        <div class="row g-3 mb-3">
                                            <div class="col-12">
                                                <label class="form-label"><strong>Όνομα Προτύπου *</strong></label>
                                                <input type="text" name="template_name" class="form-control" placeholder="π.χ. Δήλωση Συμμετοχής Εκδήλωσης" required>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label"><strong>Περιγραφή</strong></label>
                                            <textarea name="description" class="form-control" rows="2" placeholder="Προαιρετική περιγραφή προτύπου"></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label"><strong>Αρχεία Οδηγιών (μέχρι 4)</strong></label>
                                            <input
                                                type="file"
                                                name="instruction_file[]"
                                                id="create_template_instruction_files"
                                                class="form-control"
                                                accept=".pdf,.doc,.docx"
                                                multiple
                                            >
                                            <div class="form-text">Τα αρχεία αποθηκεύονται στο πρότυπο και θα μεταφέρονται αυτόματα στη νέα αίτηση όταν πατήσετε «Δημοσίευση».</div>
                                            <div id="create_template_instruction_files_list" class="vstack gap-2 mt-2">
                                                <div class="text-muted small">Δεν έχουν επιλεγεί αρχεία.</div>
                                            </div>
                                        </div>

                                        <div class="card bg-white border mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Πεδία Φόρμας</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="create_template_fields_container" class="vstack gap-3 mb-3"></div>
                                                <button type="button" id="add_create_template_field_btn" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-plus me-1"></i>Προσθήκη Πεδίου
                                                </button>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-save me-1"></i>Αποθήκευση
                                            </button>
                                            <button type="reset" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-undo me-1"></i>Καθαρισμός
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Template Selection -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-8 col-lg-6">
                                <label class="form-label"><strong>Επιλέξτε Πρότυπο *</strong></label>
                                <select id="template_selector" class="form-select form-select-lg">
                                    <option value="">-- Επιλέξτε ένα πρότυπο --</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo htmlspecialchars($template['template_id']); ?>" data-key="<?php echo htmlspecialchars($template['template_key']); ?>">
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Template Editor (Hidden until template selected) -->
                        <div id="template_editor" style="display: none;">
                            <form method="POST" enctype="multipart/form-data" id="editTemplateForm">
                                <input type="hidden" name="action" value="update_template">
                                <input type="hidden" name="template_id" id="edit_template_id">
                                <input type="hidden" name="form_schema" id="edit_template_schema">
                                <input type="hidden" name="removed_instruction_files" id="edit_template_removed_instruction_files" value="[]">

                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label"><strong>Όνομα Προτύπου *</strong></label>
                                        <input type="text" name="template_name" id="edit_template_name" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label"><strong>Περιγραφή</strong></label>
                                    <textarea name="description" id="edit_template_description" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label"><strong>Αρχεία Οδηγιών (μέχρι 4)</strong></label>
                                    <input
                                        type="file"
                                        name="instruction_file[]"
                                        id="edit_template_instruction_files"
                                        class="form-control"
                                        accept=".pdf,.doc,.docx"
                                        multiple
                                    >
                                    <div class="form-text">Τα αρχεία θα συσχετιστούν με τη νέα αίτηση όταν πατήσετε «Δημοσίευση».</div>
                                    <div class="form-text">Μπορείτε να αφαιρέσετε υπάρχοντα αρχεία ή να προσθέσετε νέα. Οι αλλαγές αποθηκεύονται με «Αποθήκευση Αλλαγών» ή «Δημοσίευση».</div>
                                    <div id="edit_template_instruction_files_list" class="vstack gap-2 mt-2">
                                        <div class="text-muted small">Δεν υπάρχουν αρχεία οδηγιών.</div>
                                    </div>
                                </div>

                                <!-- Form Fields Editor -->
                                <div class="card bg-light mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Πεδία Φόρμας</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="template_fields_container" class="vstack gap-3 mb-3">
                                            <!-- Fields will be rendered here -->
                                        </div>
                                        <button type="button" id="add_template_field_btn" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-plus me-1"></i>Προσθήκη Πεδίου
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Αποθήκευση Αλλαγών
                                    </button>
                                    <button type="button" id="publish_template_btn" class="btn btn-success">
                                        <i class="fas fa-check-circle me-1"></i>Δημοσίευση
                                    </button>
                                    <button type="button" id="delete_template_btn" class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt me-1"></i>Διαγραφή Προτύπου
                                    </button>
                                    <button type="button" class="btn btn-secondary ms-auto" onclick="document.getElementById('template_selector').value = ''; document.getElementById('template_editor').style.display = 'none';">
                                        <i class="fas fa-times me-1"></i>Ακύρωση
                                    </button>
                                </div>
                            </form>

                            <form method="POST" id="deleteTemplateForm" class="d-none">
                                <input type="hidden" name="action" value="delete_template">
                                <input type="hidden" name="template_id" id="delete_template_id">
                            </form>
                        </div>

                        <!-- Empty State -->
                        <div id="template_empty_state" class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>Δεν επιλέγθηκε πρότυπο</h3>
                            <p>Επιλέξτε ένα πρότυπο από την παραπάνω λίστα για να το επεξεργαστείτε.</p>
                        </div>
                    </div>
                </div>
            </div> <!-- END TAB 2 -->

        </div> <!-- END tab-content -->
    </main>
</div>

<div class="modal fade" id="createApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#2f6fb3;">
                <h5 class="modal-title" style="color:#ffffff !important;">
                    <i class="fas fa-plus me-2" style="color:#ffffff !important;"></i>Νέα Αίτηση
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο"
                        style="filter: brightness(0) invert(1); opacity:1;">
                </button>
            </div>

            <div class="modal-body">
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <label class="form-label"><strong>Τίτλος Αίτησης *</strong></label>
                        <input type="text" id="create_application_title" class="form-control form-control-custom" placeholder="π.χ. Δήλωση Συμμετοχής" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><strong>Περιγραφή</strong></label>
                        <textarea id="create_application_description" class="form-control form-control-custom" rows="3" placeholder="Προαιρετικη περιγραφή της αίτησης"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><strong>Αρχεία Οδηγιών (μέχρι 4)</strong></label>
                        <input type="file" id="create_instruction_files" name="instruction_file[]" class="form-control" accept=".pdf,.doc,.docx" multiple>
                        <div class="form-text">Μέχρι 4 αρχεία PDF ή εγγράφων</div>
                        <div id="create_instruction_files_list" class="vstack gap-2 mt-2">
                            <div class="text-muted small">Δεν έχουν επιλεγεί αρχεία.</div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="online-fill-tab" data-bs-toggle="tab" data-bs-target="#online-fill-pane" type="button" role="tab" aria-controls="online-fill-pane" aria-selected="true">
                            <i class="fas fa-keyboard me-2"></i>Online Συμπλήρωση
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- TAB 1: Online Συμπλήρωση -->
                    <div class="tab-pane fade show active" id="online-fill-pane" role="tabpanel" aria-labelledby="online-fill-tab">
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="mb-3"><i class="fas fa-list me-2"></i>Πεδία Φόρμας</h6>
                                <div id="online_form_fields_container" class="vstack gap-2 mb-3">
                                    <!-- Τα πεδία θα προστεθούν δυναμικά εδώ -->
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="button" id="add_form_field_btn" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus me-1"></i>Προσθήκη Πεδίου
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" id="publish_application_btn" class="btn btn-success">
                    <i class="fas fa-bullhorn me-1"></i>Δημοσίευση Αίτησης
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#2f6fb3;">
                <h5 class="modal-title" style="color:#ffffff !important;">
                    <i class="fas fa-edit me-2" style="color:#ffffff !important;"></i>Επεξεργασία Αίτησης
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο"
                        style="filter: brightness(0) invert(1); opacity:1;">
                </button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs" id="editApplicationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="edit-info-tab" data-bs-toggle="tab" data-bs-target="#edit-info-pane" type="button" role="tab" aria-controls="edit-info-pane" aria-selected="true">Στοιχεία</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-form-fields-tab" data-bs-toggle="tab" data-bs-target="#edit-form-fields-pane" type="button" role="tab" aria-controls="edit-form-fields-pane" aria-selected="false">Πεδία Φόρμας</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-files-tab" data-bs-toggle="tab" data-bs-target="#edit-files-pane" type="button" role="tab" aria-controls="edit-files-pane" aria-selected="false">Αρχεία</button>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="edit-info-pane" role="tabpanel" aria-labelledby="edit-info-tab">
                        <form method="POST" enctype="multipart/form-data" id="edit_application_form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="application_id" id="edit_application_id">
                            <input type="hidden" name="form_fields_json" id="edit_application_form_fields_json" value="[]">
                            <input type="hidden" name="return_edit_application_id" id="edit_return_application_id" value="0">
                            <input type="hidden" name="return_edit_tab" id="edit_return_tab" value="info">

                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="vstack gap-2">
                                        <div>
                                            <label class="form-label"><strong>Τίτλος *</strong></label>
                                            <input type="text" name="application_title" id="edit_application_title" class="form-control form-control-sm form-control-custom" required>
                                        </div>

                                        <div>
                                            <label class="form-label"><strong>Περιγραφή</strong></label>
                                            <textarea name="application_description" id="edit_application_description" class="form-control form-control-sm form-control-custom" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="edit-form-fields-pane" role="tabpanel" aria-labelledby="edit-form-fields-tab">
                        <div class="card bg-light border-0 mb-2">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Πεδία Φόρμας Αίτησης</h6>
                                </div>

                                <div id="edit_application_form_fields_locked" class="alert alert-warning d-none mb-3" role="alert">
                                    Τα πεδία φόρμας επεξεργάζονται μόνο όταν η αίτηση είναι δημοσιευμένη.
                                </div>

                                <div id="edit_application_form_fields_container" class="vstack gap-2 mb-3"></div>

                                <button type="button" id="add_edit_application_form_field_btn" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus me-1"></i>Προσθήκη Πεδίου
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="edit-files-pane" role="tabpanel" aria-labelledby="edit-files-tab">
                        <div class="card bg-light border-0 mb-2">
                            <div class="card-body py-2">
                                <label class="form-label"><strong>Προσθήκη Νέων Αρχείων Οδηγιών</strong></label>
                                <input type="file" id="edit_instruction_files" name="instruction_file[]" form="edit_application_form" class="form-control form-control-sm mb-2" accept=".pdf,.doc,.docx" multiple>
                                <div class="form-text mb-2">Με την επιλογή αρχείων θα εμφανιστούν άμεσα στη λίστα. Η αποθήκευση γίνεται μόνο με «Αποθήκευση Αλλαγών».</div>
                                <div class="form-text">Μπορείτε να έχετε έως 4 αρχεία οδηγιών συνολικά ανά αίτηση.</div>
                            </div>
                        </div>

                        <div id="edit_application_files_list" class="vstack gap-2">
                            <div class="text-muted">Δεν υπάρχουν αρχεία.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="submit" form="edit_application_form" class="btn btn-primary-custom">
                    <i class="fas fa-save me-1"></i>Αποθήκευση Αλλαγών
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteDocumentConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#2f6fb3;">
                <h5 class="modal-title" style="color:#ffffff !important;">
                    <i class="fas fa-exclamation-triangle me-2" style="color:#ffffff !important;"></i>Επιβεβαίωση
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο"
                        style="filter: brightness(0) invert(1); opacity:1;">
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0 fw-semibold">Θέλετε σίγουρα να αφαιρέσετε αυτό το αρχείο;</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-danger" id="confirm_delete_document_btn">Ναι</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Όχι</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="submissionDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#2f6fb3;">
                <h5 class="modal-title" style="color:#ffffff !important;">
                    <i class="fas fa-id-card me-2" style="color:#ffffff !important;"></i>Λεπτομέρειες Υποβολής
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο"
                        style="filter: brightness(0) invert(1); opacity:1;">
                </button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><div class="detail-card"><span>Αίτηση</span><strong id="detail_application_title">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Γονέας</span><strong id="detail_parent_name">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Ημ. Υποβολής</span><strong id="detail_submitted_at">—</strong></div></div>
                </div>

                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <h6 class="mb-3">Στοιχεία Φόρμας</h6>
                        <div id="detail_submission_fields" class="detail-fields-grid"></div>
                    </div>
                </div>

                <div class="card border-0 bg-light mb-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <h6 class="mb-0">Εσωτερικές Σημειώσεις</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="save_submission_note_btn">
                                <i class="fas fa-save me-1"></i>Αποθήκευση Σημείωσης
                            </button>
                        </div>
                        <textarea id="submission_internal_note" class="form-control" rows="4" placeholder="Σημειώσεις για εσωτερική χρήση του admin panel..."></textarea>
                        <div class="form-text">Οι σημειώσεις αποθηκεύονται τοπικά στο browser του admin χωρίς αλλαγή του υπάρχοντος backend.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Κλείσιμο</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="application_id" id="delete_application_id">

                <div class="modal-header" style="background:#2f6fb3;">
                    <h5 class="modal-title" style="color:#ffffff !important;">
                        <i class="fas fa-exclamation-triangle me-2" style="color:#ffffff !important;"></i>Επιβεβαίωση Διαγραφής
                    </h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Κλείσιμο"
                            style="filter: brightness(0) invert(1); opacity:1;">
                    </button>
                </div>

                <div class="modal-body text-center">
                    <p class="mb-2 fw-semibold">Θέλετε σίγουρα να διαγράψετε αυτή την αίτηση;</p>
                    <p class="text-muted mb-0" id="delete_application_title_display">—</p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="submit" class="btn btn-danger px-4">Ναι</button>
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Όχι</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSubmissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered delete-submission-modal-dialog">
        <div class="modal-content border-0 shadow-lg delete-submission-modal-content">
            <form method="POST" id="delete_submission_confirm_form">
                <input type="hidden" name="action" value="update_submission_status">
                <input type="hidden" name="application_id" id="delete_submission_application_id">
                <input type="hidden" name="user_id" id="delete_submission_user_id">
                <input type="hidden" name="sub_status" value="delete">
                <input type="hidden" name="return_view_submissions" id="delete_submission_return_view_submissions" value="0">
                <input type="hidden" name="return_submission_sort" id="delete_submission_return_submission_sort" value="<?php echo htmlspecialchars($selectedSubmissionSort, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="return_scroll_y" id="delete_submission_return_scroll_y" value="0">

                <div class="modal-header delete-submission-modal-header">
                    <h5 class="modal-title delete-submission-modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση Διαγραφής Υποβολής
                    </h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Κλείσιμο">
                    </button>
                </div>

                <div class="modal-body delete-submission-modal-body text-center">
                    <p class="delete-submission-modal-question mb-2">Θέλετε σίγουρα να διαγράψετε αυτή την υποβολή;</p>
                    <p class="delete-submission-modal-note mb-0">Η ενέργεια αυτή θα επιτρέψει στον γονέα να υποβάλει ξανά αίτηση.</p>
                    <div class="delete-submission-modal-details" id="delete_submission_details">—</div>
                </div>

                <div class="modal-footer delete-submission-modal-footer justify-content-center">
                    <button type="submit" class="btn btn-danger delete-submission-confirm-btn">Ναι, διαγραφή</button>
                    <button type="button" class="btn btn-outline-secondary delete-submission-cancel-btn" data-bs-dismiss="modal">Όχι</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="closeCreateTemplateConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#2f6fb3;">
                <h5 class="modal-title" style="color:#ffffff !important;">
                    <i class="fas fa-question-circle me-2" style="color:#ffffff !important;"></i>Επιβεβαίωση
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο"
                        style="filter: brightness(0) invert(1); opacity:1;">
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0 fw-semibold">Είστε σίγουροι ότι θέλετε να κλείσετε τη δημιουργία νέου προτύπου;</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-danger" id="confirm_close_create_template_btn">Ναι, κλείσιμο</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Όχι</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteTemplateConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#2f6fb3;">
                <h5 class="modal-title" style="color:#ffffff !important;">
                    <i class="fas fa-exclamation-triangle me-2" style="color:#ffffff !important;"></i>Επιβεβαίωση Διαγραφής
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο"
                        style="filter: brightness(0) invert(1); opacity:1;">
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-2 fw-semibold">Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το πρότυπο;</p>
                <p class="text-muted mb-0" id="delete_template_name_preview">—</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-danger" id="confirm_delete_template_btn">Ναι, διαγραφή</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Όχι</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin_applications_fields.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var createInstructionFiles = document.getElementById('create_instruction_files');
    var createInstructionFilesList = document.getElementById('create_instruction_files_list');
    var createInstructionSelectedFiles = [];

    function syncCreateInstructionInputFiles(nextFiles) {
        if (!createInstructionFiles) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            if (!Array.isArray(nextFiles) || nextFiles.length === 0) {
                createInstructionFiles.value = '';
            }
            return;
        }

        var transfer = new DataTransfer();
        (Array.isArray(nextFiles) ? nextFiles : []).forEach(function (file) {
            if (!file) {
                return;
            }
            transfer.items.add(file);
        });
        createInstructionFiles.files = transfer.files;
    }

    function renderCreateInstructionFilesList() {
        if (!createInstructionFilesList) {
            return;
        }

        if (!Array.isArray(createInstructionSelectedFiles) || createInstructionSelectedFiles.length === 0) {
            createInstructionFilesList.innerHTML = '<div class="text-muted small">Δεν έχουν επιλεγεί αρχεία.</div>';
            return;
        }

        createInstructionFilesList.innerHTML = createInstructionSelectedFiles.map(function (file, index) {
            var fileName = file && file.name ? String(file.name) : 'Αρχείο';
            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(fileName) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-create-instruction-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Αφαίρεση' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        createInstructionFilesList.querySelectorAll('.js-remove-create-instruction-file').forEach(function (button) {
            button.addEventListener('click', function () {
                var fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= createInstructionSelectedFiles.length) {
                    return;
                }

                createInstructionSelectedFiles.splice(fileIndex, 1);
                syncCreateInstructionInputFiles(createInstructionSelectedFiles);
                renderCreateInstructionFilesList();
            });
        });
    }

    if (createInstructionFiles) {
        createInstructionFiles.addEventListener('change', function () {
            var selectedFiles = Array.from(createInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                createInstructionSelectedFiles = [];
                renderCreateInstructionFilesList();
                return;
            }

            if (selectedFiles.length > 4) {
                alert('Μπορείτε να επιλέξετε έως 4 αρχεία οδηγιών.');
                createInstructionFiles.value = '';
                createInstructionSelectedFiles = [];
                renderCreateInstructionFilesList();
                return;
            }

            var allowedExtensions = ['pdf', 'doc', 'docx'];
            var hasInvalidFile = selectedFiles.some(function (file) {
                var fileName = String((file && file.name) || '');
                var extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                return allowedExtensions.indexOf(extension) === -1;
            });

            if (hasInvalidFile) {
                alert('Επιτρεπόμενοι τύποι αρχείων οδηγιών: pdf, doc, docx.');
                createInstructionFiles.value = '';
                createInstructionSelectedFiles = [];
                renderCreateInstructionFilesList();
                return;
            }

            createInstructionSelectedFiles = selectedFiles;
            renderCreateInstructionFilesList();
        });

        renderCreateInstructionFilesList();
    }

    var createModalEl = document.getElementById('createApplicationModal');
    if (createModalEl) {
        createModalEl.addEventListener('hidden.bs.modal', function () {
            var createApplicationTitle = document.getElementById('create_application_title');
            var createApplicationDescription = document.getElementById('create_application_description');
            if (createApplicationTitle) {
                createApplicationTitle.value = '';
            }
            if (createApplicationDescription) {
                createApplicationDescription.value = '';
            }
            if (createInstructionFiles) {
                createInstructionFiles.value = '';
            }
            createInstructionSelectedFiles = [];
            renderCreateInstructionFilesList();

            var createStatusSelect = document.getElementById('create_application_status');
            if (createStatusSelect) {
                createStatusSelect.value = 'active';
            }
        });
    }

    var urlParams = new URLSearchParams(window.location.search);
    var scrollYParam = parseInt(urlParams.get('scroll_y') || '0', 10);
    if (!Number.isNaN(scrollYParam) && scrollYParam > 0) {
        window.scrollTo({ top: scrollYParam, behavior: 'auto' });
    }

    var activeTabParam = (urlParams.get('active_tab') || '').toLowerCase();
    var tabMap = {
        manage: 'tab-manage-link',
        templates: 'tab-templates-link'
    };
    var tabButtonId = tabMap[activeTabParam] || '';
    if (tabButtonId) {
        var tabButton = document.getElementById(tabButtonId);
        if (tabButton && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabButton).show();
        }
    }

    document.querySelectorAll('.js-create-publish-btn').forEach(function (createPublishButton) {
        createPublishButton.addEventListener('click', function () {
            var createStatusSelect = document.getElementById('create_application_status');
            if (createStatusSelect) {
                createStatusSelect.value = 'active';
            }
        });
    });

    var editModal = document.getElementById('editApplicationModal');
    var editForm = document.getElementById('edit_application_form');
    var editInstructionFiles = document.getElementById('edit_instruction_files');
    var editFilesList = document.getElementById('edit_application_files_list');
    var editFormFieldsTabButton = document.getElementById('edit-form-fields-tab');
    var editFormFieldsContainer = document.getElementById('edit_application_form_fields_container');
    var editFormFieldsLockedNotice = document.getElementById('edit_application_form_fields_locked');
    var addEditFormFieldBtn = document.getElementById('add_edit_application_form_field_btn');
    var editFormFieldsJsonInput = document.getElementById('edit_application_form_fields_json');
    var editReturnApplicationIdInput = document.getElementById('edit_return_application_id');
    var editReturnTabInput = document.getElementById('edit_return_tab');
    var deleteDocumentConfirmModalEl = document.getElementById('deleteDocumentConfirmModal');
    var confirmDeleteDocumentBtn = document.getElementById('confirm_delete_document_btn');
    var deleteDocumentConfirmModal = deleteDocumentConfirmModalEl ? new bootstrap.Modal(deleteDocumentConfirmModalEl) : null;
    var pendingDeleteDocumentForm = null;
    var editApplicationFormSchema = [];
    var editApplicationIsPublished = false;
    var editApplicationExistingFiles = [];

    var editApplicationFieldTypes = {
        text: 'Κείμενο',
        email: 'Email',
        phone: 'Τηλέφωνο',
        date: 'Ημερομηνία',
        textarea: 'Μεγάλο Κείμενο',
        checkbox: 'Tick Box',
        file_upload: 'Αρχείο'
    };

    function normalizeEditApplicationFieldType(type) {
        var normalized = String(type || 'text').toLowerCase();
        if (normalized === 'tel') {
            normalized = 'phone';
        }
        if (normalized === 'file') {
            normalized = 'file_upload';
        }
        return Object.prototype.hasOwnProperty.call(editApplicationFieldTypes, normalized) ? normalized : 'text';
    }

    function syncEditApplicationFormSchemaFromInputs() {
        if (!editFormFieldsContainer) {
            return;
        }

        var nextSchema = [];
        editFormFieldsContainer.querySelectorAll('.js-edit-app-field-card').forEach(function (card) {
            var nameInput = card.querySelector('.js-edit-app-field-name');
            var typeSelect = card.querySelector('.js-edit-app-field-type');
            var requiredCheck = card.querySelector('.js-edit-app-field-required');

            nextSchema.push({
                name: nameInput ? nameInput.value : '',
                type: normalizeEditApplicationFieldType(typeSelect ? typeSelect.value : 'text'),
                required: requiredCheck ? requiredCheck.checked : true
            });
        });

        editApplicationFormSchema = nextSchema;
    }

    function getCurrentEditTabKey() {
        var activeTabButton = document.querySelector('#editApplicationTabs .nav-link.active');
        var activeTabId = activeTabButton ? String(activeTabButton.id || '') : '';

        if (activeTabId === 'edit-files-tab') {
            return 'files';
        }
        if (activeTabId === 'edit-form-fields-tab') {
            return 'form_fields';
        }

        return 'info';
    }

    function normalizeEditTabKey(tabKey) {
        var normalizedTabKey = String(tabKey || '').toLowerCase();
        if (normalizedTabKey === 'files' || normalizedTabKey === 'edit-files' || normalizedTabKey === 'edit-files-pane') {
            return 'files';
        }
        if (normalizedTabKey === 'form_fields' || normalizedTabKey === 'form-fields' || normalizedTabKey === 'fields' || normalizedTabKey === 'edit-form-fields' || normalizedTabKey === 'edit-form-fields-pane') {
            return 'form_fields';
        }

        return 'info';
    }

    function showEditTabByKey(tabKey) {
        var normalizedTabKey = String(tabKey || '').toLowerCase();
        var targetTabId = 'edit-info-tab';

        if (normalizedTabKey === 'files') {
            targetTabId = 'edit-files-tab';
        } else if (normalizedTabKey === 'form_fields') {
            targetTabId = 'edit-form-fields-tab';
        }

        var targetTabButton = document.getElementById(targetTabId);
        if (!targetTabButton || targetTabButton.disabled || !(window.bootstrap && bootstrap.Tab)) {
            targetTabButton = document.getElementById('edit-info-tab');
        }

        if (targetTabButton && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(targetTabButton).show();
        }
    }

    function renderEditApplicationFormFields() {
        if (!editFormFieldsContainer) {
            return;
        }

        editFormFieldsContainer.innerHTML = '';

        if (!editApplicationIsPublished) {
            if (editFormFieldsLockedNotice) {
                editFormFieldsLockedNotice.classList.remove('d-none');
            }
            return;
        }

        if (editFormFieldsLockedNotice) {
            editFormFieldsLockedNotice.classList.add('d-none');
        }

        if (!Array.isArray(editApplicationFormSchema) || editApplicationFormSchema.length === 0) {
            editFormFieldsContainer.innerHTML = '<div class="text-muted">Δεν υπάρχουν ακόμη πεδία φόρμας.</div>';
            return;
        }

        editApplicationFormSchema.forEach(function (field, index) {
            var fieldType = normalizeEditApplicationFieldType(field.type || 'text');
            var fieldCard = document.createElement('div');
            fieldCard.className = 'card border-0 bg-white js-edit-app-field-card';

            var typeOptions = Object.keys(editApplicationFieldTypes).map(function (value) {
                var selected = fieldType === value ? ' selected' : '';
                return '<option value="' + escapeHtml(value) + '"' + selected + '>' + escapeHtml(editApplicationFieldTypes[value]) + '</option>';
            }).join('');

            fieldCard.innerHTML =
                '<div class="card-body py-2 px-3">' +
                    '<div class="row g-2 align-items-center">' +
                        '<div class="col-12 col-md-4">' +
                            '<input type="text" class="form-control form-control-sm js-edit-app-field-name" value="' + escapeHtml(field.name || '') + '" placeholder="π.χ. Ονοματεπώνυμο">' +
                        '</div>' +
                        '<div class="col-12 col-md-3">' +
                            '<select class="form-select form-select-sm js-edit-app-field-type">' + typeOptions + '</select>' +
                        '</div>' +
                        '<div class="col-12 col-md-3">' +
                            '<div class="form-check">' +
                                '<input type="checkbox" class="form-check-input js-edit-app-field-required" id="edit_app_required_' + String(index) + '"' + (field.required ? ' checked' : '') + '>' +
                                '<label class="form-check-label" for="edit_app_required_' + String(index) + '">Υποχρεωτικό</label>' +
                            '</div>' +
                        '</div>' +
                        '<div class="col-12 col-md-2 text-md-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-edit-app-field"><i class="fas fa-trash-alt"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            editFormFieldsContainer.appendChild(fieldCard);

            var nameInput = fieldCard.querySelector('.js-edit-app-field-name');
            var typeSelect = fieldCard.querySelector('.js-edit-app-field-type');
            var requiredCheck = fieldCard.querySelector('.js-edit-app-field-required');
            var removeBtn = fieldCard.querySelector('.js-remove-edit-app-field');

            var persistField = function () {
                editApplicationFormSchema[index] = {
                    name: nameInput ? nameInput.value : '',
                    type: normalizeEditApplicationFieldType(typeSelect ? typeSelect.value : 'text'),
                    required: requiredCheck ? requiredCheck.checked : true
                };
            };

            if (nameInput) {
                nameInput.addEventListener('input', persistField);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', persistField);
            }
            if (requiredCheck) {
                requiredCheck.addEventListener('change', persistField);
            }
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    syncEditApplicationFormSchemaFromInputs();
                    editApplicationFormSchema.splice(index, 1);
                    renderEditApplicationFormFields();
                });
            }
        });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function humanizeSubmissionDetailLabel(key) {
        var labels = {
            parent_name: 'Ονοματεπώνυμο Γονέα',
            parent_email: 'Email Επικοινωνίας',
            parent_phone: 'Τηλέφωνο Επικοινωνίας',
            student_name: 'Ονοματεπώνυμο Μαθητή/Μαθήτριας',
            student_class: 'Τμήμα / Τάξη',
            manual_application_text: 'Κείμενο Αίτησης',
            applied_at: 'Ημερομηνία Υποβολής',
            _submission_mode: 'Τρόπος Υποβολής'
        };

        return labels[key] || String(key || '').replace(/_/g, ' ');
    }

    function buildSubmissionFieldDefinitionsLookup(fieldDefinitions) {
        var lookup = {};
        if (!Array.isArray(fieldDefinitions)) {
            return lookup;
        }

        fieldDefinitions.forEach(function (fieldDefinition) {
            if (!fieldDefinition || typeof fieldDefinition !== 'object') {
                return;
            }

            var key = String(fieldDefinition.key || '').trim();
            if (!key) {
                return;
            }

            lookup[key] = {
                name: String(fieldDefinition.name || '').trim(),
                type: String(fieldDefinition.type || 'text').toLowerCase()
            };
        });

        return lookup;
    }

    function buildUploadedFileLinksLookup(uploadedFileLinks) {
        var lookup = {};
        if (!Array.isArray(uploadedFileLinks)) {
            return lookup;
        }

        uploadedFileLinks.forEach(function (uploadedFile) {
            if (!uploadedFile || typeof uploadedFile !== 'object') {
                return;
            }

            var name = String(uploadedFile.name || '').trim();
            var url = String(uploadedFile.url || '').trim();
            if (!name || !url) {
                return;
            }

            lookup[name] = url;
        });

        return lookup;
    }

    function formatSubmissionDetailValue(key, value, fieldMeta, uploadedFileLinksByName) {
        if (key === '_submission_mode') {
            return value === 'manual' ? 'Online Συμπλήρωση' : 'Ανέβασμα Αρχείου';
        }

        var raw = Array.isArray(value) ? value.join(', ') : String(value == null ? '' : value);
        raw = raw.trim();

        var normalizedType = fieldMeta && fieldMeta.type ? String(fieldMeta.type).toLowerCase() : '';
        if (normalizedType === 'checkbox') {
            var checked = ['1', 'true', 'on', 'yes'].indexOf(raw.toLowerCase()) !== -1;
            return '<input type="checkbox" class="detail-checkbox-input" disabled' + (checked ? ' checked' : '') + '>';
        }

        if (!raw) {
            return '—';
        }

        if (normalizedType === 'file_upload') {
            var fileUrl = uploadedFileLinksByName && uploadedFileLinksByName[raw] ? uploadedFileLinksByName[raw] : '';
            if (fileUrl) {
                return '<a href="' + escapeHtml(fileUrl) + '" target="_blank" rel="noopener noreferrer" class="detail-field-link">' + escapeHtml(raw) + '</a>';
            }
        }

        return escapeHtml(raw).replace(/\r?\n/g, '<br>');
    }

    function buildSubmissionDetailHtml(fields, fieldDefinitions, uploadedFileLinks) {
        if (!fields || typeof fields !== 'object') {
            return '<div class="text-muted">Δεν υπάρχουν διαθέσιμα στοιχεία φόρμας.</div>';
        }

        var fieldDefinitionsLookup = buildSubmissionFieldDefinitionsLookup(fieldDefinitions);
        var uploadedFileLinksByName = buildUploadedFileLinksLookup(uploadedFileLinks);
        var html = '';
        var renderedKeys = {};

        if (Array.isArray(fieldDefinitions)) {
            fieldDefinitions.forEach(function (fieldDefinition) {
                if (!fieldDefinition || typeof fieldDefinition !== 'object') {
                    return;
                }

                var key = String(fieldDefinition.key || '').trim();
                if (!key || renderedKeys[key]) {
                    return;
                }

                var fieldMeta = fieldDefinitionsLookup[key] || null;
                var label = fieldMeta && fieldMeta.name ? fieldMeta.name : humanizeSubmissionDetailLabel(key);
                var hasValue = Object.prototype.hasOwnProperty.call(fields, key);
                var value = hasValue ? fields[key] : '';
                var formattedValue = formatSubmissionDetailValue(key, value, fieldMeta, uploadedFileLinksByName);
                var isCheckbox = fieldMeta && fieldMeta.type === 'checkbox';

                if (formattedValue === '—' && !isCheckbox) {
                    return;
                }

                renderedKeys[key] = true;
                html += '<div class="detail-field-item"><span>' +
                    escapeHtml(label) +
                    '</span><strong>' + formattedValue + '</strong></div>';
            });
        }

        Object.keys(fields).forEach(function (key) {
            if (renderedKeys[key]) {
                return;
            }

            if (key === '_formType' || key === '_category' || key === '_uploaded_files' || key === '_uploaded_file_names') {
                return;
            }

            var value = fields[key];
            if (value == null) {
                return;
            }

            if (Array.isArray(value) && value.length === 0) {
                return;
            }

            var fieldMeta = fieldDefinitionsLookup[key] || null;
            var label = fieldMeta && fieldMeta.name ? fieldMeta.name : humanizeSubmissionDetailLabel(key);
            var formattedValue = formatSubmissionDetailValue(key, value, fieldMeta, uploadedFileLinksByName);
            if (formattedValue === '—') {
                return;
            }

            html += '<div class="detail-field-item"><span>' +
                escapeHtml(label) +
                '</span><strong>' + formattedValue + '</strong></div>';
        });

        return html || '<div class="text-muted">Δεν υπάρχουν διαθέσιμα στοιχεία φόρμας.</div>';
    }

    function renderEditApplicationFiles(documents, pendingFiles) {
        if (!editFilesList) {
            return;
        }

        var existingDocs = Array.isArray(documents) ? documents : [];
        var stagedFiles = Array.isArray(pendingFiles) ? pendingFiles : [];

        if (existingDocs.length === 0 && stagedFiles.length === 0) {
            editFilesList.innerHTML = '<div class="text-muted">Δεν υπάρχουν αρχεία για αυτή την αίτηση.</div>';
            return;
        }

        var existingHtml = existingDocs.map(function (doc) {
            var fileName = doc && doc.name ? doc.name : 'Αρχείο';
            var fileUrl = doc && doc.url ? doc.url : '#';
            var documentId = doc && doc.id ? String(doc.id) : '0';

            return '' +
                '<div class="card border-0 bg-light">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<a href="' + escapeHtml(fileUrl) + '" target="_blank" class="fw-semibold text-decoration-none">' +
                                '<i class="fas fa-file-alt me-1"></i>' + escapeHtml(fileName) +
                            '</a>' +
                            '<form method="POST" class="js-delete-doc-form m-0">' +
                                '<input type="hidden" name="action" value="delete_document">' +
                                '<input type="hidden" name="ap_document_id" value="' + escapeHtml(documentId) + '">' +
                                '<button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt me-1"></i>Αφαίρεση</button>' +
                            '</form>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        var stagedHtml = stagedFiles.map(function (file) {
            var stagedName = file && file.name ? String(file.name) : 'Νέο αρχείο';

            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(stagedName) +
                            '</div>' +
                            '<span class="badge bg-primary-subtle text-primary">Σε αναμονή αποθήκευσης</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        editFilesList.innerHTML = existingHtml + stagedHtml;
        editFilesList.querySelectorAll('.js-delete-doc-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!deleteDocumentConfirmModal) {
                    if (confirm('Θέλετε σίγουρα να αφαιρέσετε αυτό το αρχείο;')) {
                        return;
                    }
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                pendingDeleteDocumentForm = form;
                deleteDocumentConfirmModal.show();
            });
        });
    }

    function getCurrentStagedInstructionFiles() {
        if (!editInstructionFiles) {
            return [];
        }

        return Array.from(editInstructionFiles.files || []);
    }

    if (confirmDeleteDocumentBtn) {
        confirmDeleteDocumentBtn.addEventListener('click', function () {
            if (!pendingDeleteDocumentForm) {
                if (deleteDocumentConfirmModal) {
                    deleteDocumentConfirmModal.hide();
                }
                return;
            }

            var formToSubmit = pendingDeleteDocumentForm;
            pendingDeleteDocumentForm = null;

            if (deleteDocumentConfirmModal) {
                deleteDocumentConfirmModal.hide();
            }

            var formData = new FormData(formToSubmit);
            formData.append('ajax_delete_document', '1');

            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function (response) {
                return response.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (parseError) {
                        throw new Error(text ? text.slice(0, 260) : 'Empty response');
                    }
                });
            })
            .then(function (payload) {
                if (!payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Σφάλμα κατά τη διαγραφή του αρχείου.');
                }

                var docIdInput = formToSubmit.querySelector('input[name="ap_document_id"]');
                var deletedDocId = docIdInput ? String(docIdInput.value || '') : '';
                if (deletedDocId !== '') {
                    editApplicationExistingFiles = editApplicationExistingFiles.filter(function (doc) {
                        return String((doc && doc.id) || '') !== deletedDocId;
                    });
                }

                renderEditApplicationFiles(editApplicationExistingFiles, getCurrentStagedInstructionFiles());
            })
            .catch(function (error) {
                alert(error && error.message ? error.message : 'Σφάλμα κατά τη διαγραφή του αρχείου.');
            });
        });
    }

    if (deleteDocumentConfirmModalEl) {
        deleteDocumentConfirmModalEl.addEventListener('hidden.bs.modal', function () {
            pendingDeleteDocumentForm = null;
        });
    }

    if (editInstructionFiles) {
        editInstructionFiles.addEventListener('change', function () {
            var selectedFiles = Array.from(editInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                renderEditApplicationFiles(editApplicationExistingFiles, []);
                return;
            }

            var remainingSlots = Math.max(0, 4 - editApplicationExistingFiles.length);
            if (selectedFiles.length > remainingSlots) {
                alert('Μπορείτε να προσθέσετε έως ' + String(remainingSlots) + ' ακόμη αρχεία.');
                editInstructionFiles.value = '';
                renderEditApplicationFiles(editApplicationExistingFiles, []);
                return;
            }

            var allowedExtensions = ['pdf', 'doc', 'docx'];
            var hasInvalidFile = selectedFiles.some(function (file) {
                var name = String((file && file.name) || '');
                var ext = name.indexOf('.') !== -1 ? name.split('.').pop().toLowerCase() : '';
                return allowedExtensions.indexOf(ext) === -1;
            });

            if (hasInvalidFile) {
                alert('Επιτρεπόμενοι τύποι: pdf, doc, docx.');
                editInstructionFiles.value = '';
                renderEditApplicationFiles(editApplicationExistingFiles, []);
                return;
            }

            renderEditApplicationFiles(editApplicationExistingFiles, selectedFiles);
        });
    }

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var applicationId = button.getAttribute('data-application-id') || '';
            document.getElementById('edit_application_id').value = applicationId;
            document.getElementById('edit_application_title').value = button.getAttribute('data-application-title') || '';
            document.getElementById('edit_application_description').value = button.getAttribute('data-application-description') || '';

            if (editInstructionFiles) {
                editInstructionFiles.value = '';
            }

            var documentsRaw = button.getAttribute('data-application-documents') || '[]';
            var documents = [];
            try {
                documents = JSON.parse(documentsRaw);
            } catch (error) {
                documents = [];
            }
            editApplicationExistingFiles = Array.isArray(documents) ? documents : [];
            renderEditApplicationFiles(editApplicationExistingFiles, []);

            var formFieldsRaw = button.getAttribute('data-application-form-fields') || '[]';
            var formFields = [];
            try {
                formFields = JSON.parse(formFieldsRaw);
            } catch (error) {
                formFields = [];
            }

            editApplicationIsPublished = button.getAttribute('data-application-published') === '1';
            editApplicationFormSchema = Array.isArray(formFields)
                ? formFields.map(function (field) {
                    return {
                        name: String((field && field.name) || ''),
                        type: normalizeEditApplicationFieldType((field && field.type) || 'text'),
                        required: Boolean(field && field.required)
                    };
                })
                : [];

            if (addEditFormFieldBtn) {
                addEditFormFieldBtn.disabled = !editApplicationIsPublished;
            }
            if (editFormFieldsTabButton) {
                editFormFieldsTabButton.disabled = !editApplicationIsPublished;
                editFormFieldsTabButton.title = editApplicationIsPublished ? '' : 'Διαθέσιμο μόνο για δημοσιευμένες αιτήσεις';
            }

            renderEditApplicationFormFields();

            var infoTabButton = document.getElementById('edit-info-tab');
            if (infoTabButton && window.bootstrap && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(infoTabButton).show();
            }
        });
    }

    if (addEditFormFieldBtn) {
        addEditFormFieldBtn.addEventListener('click', function () {
            if (!editApplicationIsPublished) {
                return;
            }

            syncEditApplicationFormSchemaFromInputs();
            editApplicationFormSchema.push({
                name: '',
                type: 'text',
                required: true
            });
            renderEditApplicationFormFields();
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function (event) {
            if (editReturnApplicationIdInput) {
                var editApplicationIdInput = document.getElementById('edit_application_id');
                editReturnApplicationIdInput.value = editApplicationIdInput ? String(editApplicationIdInput.value || '0') : '0';
            }
            if (editReturnTabInput) {
                editReturnTabInput.value = getCurrentEditTabKey();
            }

            if (!editFormFieldsJsonInput) {
                return;
            }

            if (editInstructionFiles && editInstructionFiles.files && editInstructionFiles.files.length > 0) {
                var stagedFiles = Array.from(editInstructionFiles.files || []);
                var remainingSlots = Math.max(0, 4 - editApplicationExistingFiles.length);

                if (stagedFiles.length > remainingSlots) {
                    event.preventDefault();
                    alert('Μπορείτε να προσθέσετε έως ' + String(remainingSlots) + ' ακόμη αρχεία.');

                    var filesTabButton = document.getElementById('edit-files-tab');
                    if (filesTabButton && window.bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(filesTabButton).show();
                    }
                    return;
                }
            }

            if (!editApplicationIsPublished) {
                editFormFieldsJsonInput.value = '';
                return;
            }

            syncEditApplicationFormSchemaFromInputs();
            var fieldsForSave = editApplicationFormSchema
                .map(function (field) {
                    return {
                        name: String(field.name || '').trim(),
                        type: normalizeEditApplicationFieldType(field.type || 'text'),
                        required: Boolean(field.required)
                    };
                })
                .filter(function (field) {
                    return field.name !== '';
                });

            editFormFieldsJsonInput.value = JSON.stringify(fieldsForSave);
        });
    }

    var activeEditApplicationParam = parseInt(urlParams.get('active_edit_application_id') || '0', 10);
    var activeEditTabParam = normalizeEditTabKey(urlParams.get('active_edit_tab') || 'info');
    if (!Number.isNaN(activeEditApplicationParam) && activeEditApplicationParam > 0 && editModal) {
        var editTriggerButton = document.querySelector('.js-edit-application[data-application-id="' + String(activeEditApplicationParam) + '"]');
        if (editTriggerButton) {
            var onEditModalShown = function () {
                showEditTabByKey(activeEditTabParam);
                editModal.removeEventListener('shown.bs.modal', onEditModalShown);
            };

            editModal.addEventListener('shown.bs.modal', onEditModalShown);
            editTriggerButton.click();

            urlParams.delete('active_edit_application_id');
            urlParams.delete('active_edit_tab');
            var nextQuery = urlParams.toString();
            var nextUrl = window.location.pathname + (nextQuery ? '?' + nextQuery : '') + window.location.hash;
            window.history.replaceState({}, document.title, nextUrl);
        }
    }

    var deleteModal = document.getElementById('deleteApplicationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var applicationId = button.getAttribute('data-application-id') || '';
            var applicationTitle = button.getAttribute('data-application-title') || '—';

            var deleteIdInput = document.getElementById('delete_application_id');
            var deleteTitleDisplay = document.getElementById('delete_application_title_display');

            if (deleteIdInput) {
                deleteIdInput.value = applicationId;
            }

            if (deleteTitleDisplay) {
                deleteTitleDisplay.textContent = applicationTitle;
            }
        });
    }

    var deleteSubmissionModalEl = document.getElementById('deleteSubmissionModal');
    var deleteSubmissionModal = deleteSubmissionModalEl ? new bootstrap.Modal(deleteSubmissionModalEl) : null;
    var deleteSubmissionApplicationId = document.getElementById('delete_submission_application_id');
    var deleteSubmissionUserId = document.getElementById('delete_submission_user_id');
    var deleteSubmissionReturnView = document.getElementById('delete_submission_return_view_submissions');
    var deleteSubmissionReturnSort = document.getElementById('delete_submission_return_submission_sort');
    var deleteSubmissionReturnScroll = document.getElementById('delete_submission_return_scroll_y');
    var deleteSubmissionDetails = document.getElementById('delete_submission_details');

    document.querySelectorAll('.js-submission-action-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var scrollInput = form.querySelector('input[name="return_scroll_y"]');
            if (scrollInput) {
                scrollInput.value = String(window.scrollY || window.pageYOffset || 0);
            }

            var statusField = form.querySelector('select[name="sub_status"], input[name="sub_status"]');
            var selectedStatus = statusField ? String(statusField.value || '') : '';
            if (selectedStatus !== 'delete' || !deleteSubmissionModal) {
                return;
            }

            event.preventDefault();

            var applicationIdInput = form.querySelector('input[name="application_id"]');
            var userIdInput = form.querySelector('input[name="user_id"]');
            var returnViewInput = form.querySelector('input[name="return_view_submissions"]');
            var returnSortInput = form.querySelector('input[name="return_submission_sort"]');
            var applicationTitle = form.getAttribute('data-application-title') || '—';
            var parentName = form.getAttribute('data-parent-name') || '—';

            if (deleteSubmissionApplicationId) {
                deleteSubmissionApplicationId.value = applicationIdInput ? applicationIdInput.value : '';
            }
            if (deleteSubmissionUserId) {
                deleteSubmissionUserId.value = userIdInput ? userIdInput.value : '';
            }
            if (deleteSubmissionReturnView) {
                deleteSubmissionReturnView.value = returnViewInput ? returnViewInput.value : '0';
            }
            if (deleteSubmissionReturnSort) {
                deleteSubmissionReturnSort.value = returnSortInput ? returnSortInput.value : 'newest';
            }
            if (deleteSubmissionReturnScroll) {
                deleteSubmissionReturnScroll.value = String(window.scrollY || window.pageYOffset || 0);
            }
            if (deleteSubmissionDetails) {
                deleteSubmissionDetails.textContent = 'Αίτηση: ' + applicationTitle + ' | Γονέας: ' + parentName;
            }

            deleteSubmissionModal.show();
        });
    });

    var deleteSubmissionConfirmForm = document.getElementById('delete_submission_confirm_form');
    if (deleteSubmissionConfirmForm) {
        deleteSubmissionConfirmForm.addEventListener('submit', function () {
            if (deleteSubmissionReturnScroll) {
                deleteSubmissionReturnScroll.value = String(window.scrollY || window.pageYOffset || 0);
            }
        });
    }

    function formatApplicationNotificationCount(count) {
        var value = Number(count) || 0;
        if (value <= 0) {
            return '';
        }
        return value > 9 ? '9+' : String(value);
    }

    function formatSidebarNotificationCount(count) {
        var value = Number(count) || 0;
        if (value <= 0) {
            return '';
        }
        return value > 10 ? '10+' : String(value);
    }

    function removeElementSmooth(element, delayMs) {
        if (!element) {
            return;
        }

        if (element.getAttribute('data-removing') === '1') {
            return;
        }

        element.setAttribute('data-removing', '1');
        element.classList.add('notification-badge-fade-out');

        window.setTimeout(function () {
            if (element && element.parentNode) {
                element.remove();
            }
        }, delayMs || 280);
    }

    function updateApplicationNotificationBadge(applicationId, waitingCount) {
        var selector = '.application-new-submission-badge[data-application-notification-badge="1"][data-application-id="' + String(applicationId) + '"]';
        var badge = document.querySelector(selector);
        var badgeText = formatApplicationNotificationCount(waitingCount);

        if (badgeText === '') {
            if (badge) {
                removeElementSmooth(badge, 280);
            }
            return;
        }

        if (!badge) {
            var actionButton = document.querySelector('.js-edit-application[data-application-id="' + String(applicationId) + '"]');
            var titleWrap = actionButton ? actionButton.closest('tr').querySelector('td .d-flex.align-items-center.flex-wrap.gap-2') : null;
            if (!titleWrap) {
                return;
            }

            badge = document.createElement('span');
            badge.className = 'application-new-submission-badge';
            badge.setAttribute('data-application-notification-badge', '1');
            badge.setAttribute('data-application-id', String(applicationId));
            titleWrap.appendChild(badge);
        }

        badge.textContent = badgeText;
        badge.setAttribute('aria-label', 'Νέες αιτήσεις προς έλεγχο: ' + String(waitingCount));
    }

    function updateSidebarApplicationsBadge(waitingCount) {
        var applicationsNavLink = document.querySelector('#adminSidebar a[href="applications.php"]');
        if (!applicationsNavLink) {
            return;
        }

        var badge = applicationsNavLink.querySelector('.admin-notification-badge');
        var badgeText = formatSidebarNotificationCount(waitingCount);

        if (badgeText === '') {
            if (badge) {
                removeElementSmooth(badge, 280);
            }
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'admin-notification-badge';
            applicationsNavLink.appendChild(badge);
        }

        badge.textContent = badgeText;
        badge.setAttribute('aria-label', 'Νέες αιτήσεις προς έλεγχο: ' + String(waitingCount));
    }

    function markSubmissionAsSeenOnHover(row) {
        if (!row || row.getAttribute('data-is-new') !== '1' || row.getAttribute('data-seen-request-running') === '1') {
            return;
        }

        var applicationId = parseInt(row.getAttribute('data-application-id') || '0', 10);
        var userId = parseInt(row.getAttribute('data-user-id') || '0', 10);
        if (Number.isNaN(applicationId) || Number.isNaN(userId) || applicationId <= 0 || userId <= 0) {
            return;
        }

        row.setAttribute('data-seen-request-running', '1');

        var payload = new URLSearchParams();
        payload.set('action', 'mark_submission_seen');
        payload.set('application_id', String(applicationId));
        payload.set('user_id', String(userId));

        fetch('applications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: payload.toString()
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP error');
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || data.success !== true) {
                    throw new Error('Request failed');
                }

                row.setAttribute('data-is-new', '0');

                var newBadge = row.querySelector('.submission-new-label');
                if (newBadge) {
                    removeElementSmooth(newBadge, 300);
                    window.setTimeout(function () {
                        row.classList.remove('submission-row-new');
                    }, 300);
                } else {
                    row.classList.remove('submission-row-new');
                }

                updateApplicationNotificationBadge(applicationId, Number(data.application_waiting_count || 0));
                updateSidebarApplicationsBadge(Number(data.global_waiting_count || 0));
            })
            .catch(function () {
                row.setAttribute('data-seen-request-running', '0');
            });
    }

    document.querySelectorAll('tr.submission-row-new[data-is-new="1"]').forEach(function (row) {
        row.addEventListener('mouseenter', function handleHover() {
            markSubmissionAsSeenOnHover(row);
        }, { passive: true });
    });

    function getSubmissionNoteKey(applicationId, userId) {
        return 'admin_submission_note_' + String(applicationId) + '_' + String(userId);
    }

    var currentSubmissionNoteKey = '';
    var detailFields = document.getElementById('detail_submission_fields');
    var noteField = document.getElementById('submission_internal_note');
    var noteSaveButton = document.getElementById('save_submission_note_btn');

    document.querySelectorAll('.js-view-submission').forEach(function (button) {
        button.addEventListener('click', function () {
            var raw = button.getAttribute('data-submission');
            if (!raw) return;

            try {
                var data = JSON.parse(raw);
                document.getElementById('detail_application_title').textContent = data.application_title || '—';
                document.getElementById('detail_parent_name').textContent = data.parent_name || data.parent_account || '—';
                document.getElementById('detail_submitted_at').textContent = data.submitted_at || '—';

                var fields = data.submission_data || {};
                var fieldDefinitions = Array.isArray(data.form_field_definitions) ? data.form_field_definitions : [];
                var uploadedFileLinks = Array.isArray(data.uploaded_file_links) ? data.uploaded_file_links : [];
                var html = buildSubmissionDetailHtml(fields, fieldDefinitions, uploadedFileLinks);
                detailFields.innerHTML = html || '<div class="text-muted">Δεν υπάρχουν διαθέσιμα στοιχεία φόρμας.</div>';

                currentSubmissionNoteKey = getSubmissionNoteKey(data.application_id, data.user_id);
                noteField.value = localStorage.getItem(currentSubmissionNoteKey) || '';
            } catch (error) {
                detailFields.innerHTML = '<div class="text-danger">Δεν ήταν δυνατή η φόρτωση των λεπτομερειών.</div>';
            }
        });
    });

    if (noteSaveButton) {
        noteSaveButton.addEventListener('click', function () {
            if (!currentSubmissionNoteKey) return;
            localStorage.setItem(currentSubmissionNoteKey, noteField.value || '');
            noteSaveButton.classList.remove('btn-outline-primary');
            noteSaveButton.classList.add('btn-success');
            noteSaveButton.innerHTML = '<i class="fas fa-check me-1"></i>Αποθηκεύτηκε';
            window.setTimeout(function () {
                noteSaveButton.classList.remove('btn-success');
                noteSaveButton.classList.add('btn-outline-primary');
                noteSaveButton.innerHTML = '<i class="fas fa-save me-1"></i>Αποθήκευση Σημείωσης';
            }, 1800);
        });
    }
});

// Template Editor Handler
document.addEventListener('DOMContentLoaded', function() {
    const templateSelector = document.getElementById('template_selector');
    const templateEditor = document.getElementById('template_editor');
    const templateEmptyState = document.getElementById('template_empty_state');
    const templateFieldsContainer = document.getElementById('template_fields_container');
    const addTemplateFieldBtn = document.getElementById('add_template_field_btn');
    const editTemplateForm = document.getElementById('editTemplateForm');
    const editTemplateId = document.getElementById('edit_template_id');
    const editTemplateName = document.getElementById('edit_template_name');
    const editTemplateDescription = document.getElementById('edit_template_description');
    const editTemplateInstructionFiles = document.getElementById('edit_template_instruction_files');
    const editTemplateInstructionFilesList = document.getElementById('edit_template_instruction_files_list');
    const editTemplateRemovedInstructionFiles = document.getElementById('edit_template_removed_instruction_files');
    const editTemplateSchema = document.getElementById('edit_template_schema');
    const publishTemplateBtn = document.getElementById('publish_template_btn');
    const deleteTemplateForm = document.getElementById('deleteTemplateForm');
    const deleteTemplateId = document.getElementById('delete_template_id');
    const deleteTemplateBtn = document.getElementById('delete_template_btn');
    const createTemplateForm = document.getElementById('createTemplateForm');
    const createTemplateSchema = document.getElementById('create_template_schema');
    const createTemplateInstructionFiles = document.getElementById('create_template_instruction_files');
    const createTemplateInstructionFilesList = document.getElementById('create_template_instruction_files_list');
    const createTemplateFieldsContainer = document.getElementById('create_template_fields_container');
    const addCreateTemplateFieldBtn = document.getElementById('add_create_template_field_btn');
    const closeCreateTemplateBtn = document.getElementById('close_create_template_btn');
    const createTemplateCollapse = document.getElementById('create-template-collapse');
    const closeCreateTemplateConfirmModalEl = document.getElementById('closeCreateTemplateConfirmModal');
    const confirmCloseCreateTemplateBtn = document.getElementById('confirm_close_create_template_btn');
    const closeCreateTemplateConfirmModal = closeCreateTemplateConfirmModalEl ? new bootstrap.Modal(closeCreateTemplateConfirmModalEl) : null;
    const deleteTemplateConfirmModalEl = document.getElementById('deleteTemplateConfirmModal');
    const confirmDeleteTemplateBtn = document.getElementById('confirm_delete_template_btn');
    const deleteTemplateNamePreview = document.getElementById('delete_template_name_preview');
    const deleteTemplateConfirmModal = deleteTemplateConfirmModalEl ? new bootstrap.Modal(deleteTemplateConfirmModalEl) : null;

    // Template data (passed from PHP)
    const templatesData = <?php echo json_encode(array_map(function($t) {
        $templateId = (int)($t['template_id'] ?? 0);
        return [
            'id' => $templateId,
            'name' => $t['name'] ?? '',
            'description' => $t['description'] ?? '',
            'form_schema' => is_array($t['form_schema']) ? $t['form_schema'] : json_decode($t['form_schema'] ?? '[]', true),
            'instruction_files' => getTemplateInstructionFilesByTemplateId($templateId)
        ];
    }, $templates), JSON_UNESCAPED_UNICODE); ?>;

    const fieldTypes = {
        'text': 'Κείμενο',
        'email': 'Email',
        'phone': 'Τηλέφωνο',
        'date': 'Ημερομηνία',
        'checkbox': 'Tick Box',
        'textarea': 'Μεγάλο Κείμενο',
        'file_upload': 'Αρχείο'
    };

    let currentFormSchema = [];
    let createFormSchema = [];
    let createTemplateSelectedFiles = [];
    let editTemplateInitialInstructionFiles = [];
    let editTemplateExistingInstructionFiles = [];
    let editTemplateSelectedFiles = [];

    function escapeHtml(value) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return String(value == null ? '' : value).replace(/[&<>"']/g, (char) => map[char]);
    }

    function syncCreateTemplateInputFiles(nextFiles) {
        if (!createTemplateInstructionFiles) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            if (!Array.isArray(nextFiles) || nextFiles.length === 0) {
                createTemplateInstructionFiles.value = '';
            }
            return;
        }

        const transfer = new DataTransfer();
        (Array.isArray(nextFiles) ? nextFiles : []).forEach((file) => {
            if (!file) {
                return;
            }
            transfer.items.add(file);
        });
        createTemplateInstructionFiles.files = transfer.files;
    }

    function renderCreateTemplateInstructionFilesList() {
        if (!createTemplateInstructionFilesList) {
            return;
        }

        if (!Array.isArray(createTemplateSelectedFiles) || createTemplateSelectedFiles.length === 0) {
            createTemplateInstructionFilesList.innerHTML = '<div class="text-muted small">Δεν έχουν επιλεγεί αρχεία.</div>';
            return;
        }

        createTemplateInstructionFilesList.innerHTML = createTemplateSelectedFiles.map((file, index) => {
            const fileName = file && file.name ? String(file.name) : 'Αρχείο';
            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(fileName) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-create-template-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Αφαίρεση' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        createTemplateInstructionFilesList.querySelectorAll('.js-remove-create-template-file').forEach((button) => {
            button.addEventListener('click', () => {
                const fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= createTemplateSelectedFiles.length) {
                    return;
                }

                createTemplateSelectedFiles.splice(fileIndex, 1);
                syncCreateTemplateInputFiles(createTemplateSelectedFiles);
                renderCreateTemplateInstructionFilesList();
            });
        });
    }

    function syncEditTemplateInputFiles(nextFiles) {
        if (!editTemplateInstructionFiles) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            if (!Array.isArray(nextFiles) || nextFiles.length === 0) {
                editTemplateInstructionFiles.value = '';
            }
            return;
        }

        const transfer = new DataTransfer();
        (Array.isArray(nextFiles) ? nextFiles : []).forEach((file) => {
            if (!file) {
                return;
            }
            transfer.items.add(file);
        });
        editTemplateInstructionFiles.files = transfer.files;
    }

    function getEditTemplateRemovedInstructionPaths() {
        const activePaths = new Set(
            (Array.isArray(editTemplateExistingInstructionFiles) ? editTemplateExistingInstructionFiles : [])
                .map((file) => String((file && file.path) || '').trim())
                .filter((path) => path !== '')
        );

        return (Array.isArray(editTemplateInitialInstructionFiles) ? editTemplateInitialInstructionFiles : [])
            .map((file) => String((file && file.path) || '').trim())
            .filter((path) => path !== '' && !activePaths.has(path));
    }

    function renderEditTemplateInstructionFilesList() {
        if (!editTemplateInstructionFilesList) {
            return;
        }

        const existingFiles = Array.isArray(editTemplateExistingInstructionFiles) ? editTemplateExistingInstructionFiles : [];
        const stagedFiles = Array.isArray(editTemplateSelectedFiles) ? editTemplateSelectedFiles : [];

        if (existingFiles.length === 0 && stagedFiles.length === 0) {
            editTemplateInstructionFilesList.innerHTML = '<div class="text-muted small">Δεν υπάρχουν αρχεία οδηγιών.</div>';
            if (editTemplateRemovedInstructionFiles) {
                editTemplateRemovedInstructionFiles.value = '[]';
            }
            return;
        }

        const existingHtml = existingFiles.map((file, index) => {
            const fileName = file && file.name ? String(file.name) : 'Αρχείο';
            const fileUrl = file && file.url ? String(file.url) : '#';
            return '' +
                '<div class="card border-0 bg-light">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<a href="' + escapeHtml(fileUrl) + '" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none">' +
                                '<i class="fas fa-file-alt me-1"></i>' + escapeHtml(fileName) +
                            '</a>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-edit-template-existing-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Αφαίρεση' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        const stagedHtml = stagedFiles.map((file, index) => {
            const fileName = file && file.name ? String(file.name) : 'Νέο αρχείο';
            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(fileName) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-edit-template-staged-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Αφαίρεση' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        const slotsLeft = Math.max(0, 4 - existingFiles.length);
        const slotsInfo = '<div class="text-muted small">Διαθέσιμες θέσεις για νέα αρχεία: ' + String(slotsLeft) + ' / 4</div>';
        editTemplateInstructionFilesList.innerHTML = existingHtml + stagedHtml + slotsInfo;

        editTemplateInstructionFilesList.querySelectorAll('.js-remove-edit-template-existing-file').forEach((button) => {
            button.addEventListener('click', () => {
                const fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= editTemplateExistingInstructionFiles.length) {
                    return;
                }

                editTemplateExistingInstructionFiles.splice(fileIndex, 1);
                const maxNewFiles = Math.max(0, 4 - editTemplateExistingInstructionFiles.length);
                if (editTemplateSelectedFiles.length > maxNewFiles) {
                    editTemplateSelectedFiles = editTemplateSelectedFiles.slice(0, maxNewFiles);
                    syncEditTemplateInputFiles(editTemplateSelectedFiles);
                }
                renderEditTemplateInstructionFilesList();
            });
        });

        editTemplateInstructionFilesList.querySelectorAll('.js-remove-edit-template-staged-file').forEach((button) => {
            button.addEventListener('click', () => {
                const fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= editTemplateSelectedFiles.length) {
                    return;
                }

                editTemplateSelectedFiles.splice(fileIndex, 1);
                syncEditTemplateInputFiles(editTemplateSelectedFiles);
                renderEditTemplateInstructionFilesList();
            });
        });

        if (editTemplateRemovedInstructionFiles) {
            editTemplateRemovedInstructionFiles.value = JSON.stringify(getEditTemplateRemovedInstructionPaths());
        }
    }

    function syncCurrentFormSchemaFromInputs() {
        if (!templateFieldsContainer) {
            return;
        }

        const nextSchema = [];
        templateFieldsContainer.querySelectorAll('.card').forEach((card) => {
            const nameInput = card.querySelector('.field-name');
            const typeSelect = card.querySelector('.field-type');
            const requiredCheck = card.querySelector('.field-required');

            nextSchema.push({
                name: nameInput ? nameInput.value : '',
                type: typeSelect ? typeSelect.value : 'text',
                required: requiredCheck ? requiredCheck.checked : true
            });
        });

        currentFormSchema = nextSchema;
    }

    function syncCreateFormSchemaFromInputs() {
        if (!createTemplateFieldsContainer) {
            return;
        }

        const nextSchema = [];
        createTemplateFieldsContainer.querySelectorAll('.card').forEach((card) => {
            const nameInput = card.querySelector('.create-field-name');
            const typeSelect = card.querySelector('.create-field-type');
            const requiredCheck = card.querySelector('.create-field-required');

            nextSchema.push({
                name: nameInput ? nameInput.value : '',
                type: typeSelect ? typeSelect.value : 'text',
                required: requiredCheck ? requiredCheck.checked : true
            });
        });

        createFormSchema = nextSchema;
    }

    function renderFormFields() {
        templateFieldsContainer.innerHTML = '';
        currentFormSchema.forEach((field, index) => {
            const fieldEl = document.createElement('div');
            fieldEl.className = 'card border-0 bg-white mb-2';
            
            fieldEl.innerHTML = `
                <div class="card-body py-2 px-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <input type="text" class="form-control form-control-sm field-name" value="${field.name || ''}" placeholder="π.χ. όνομα πεδίου">
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm field-type">
                                ${Object.entries(fieldTypes).map(([val, label]) => 
                                    `<option value="${val}" ${field.type === val ? 'selected' : ''}>${label}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input field-required" id="required_${index}" ${field.required ? 'checked' : ''}>
                                <label class="form-check-label" for="required_${index}">Υποχρεωτικό</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 text-md-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-field">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            templateFieldsContainer.appendChild(fieldEl);

            const nameInput = fieldEl.querySelector('.field-name');
            const typeSelect = fieldEl.querySelector('.field-type');
            const requiredCheck = fieldEl.querySelector('.field-required');

            const persistFieldValues = () => {
                currentFormSchema[index] = {
                    name: nameInput ? nameInput.value : '',
                    type: typeSelect ? typeSelect.value : 'text',
                    required: requiredCheck ? requiredCheck.checked : true
                };
            };

            if (nameInput) {
                nameInput.addEventListener('input', persistFieldValues);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', persistFieldValues);
            }
            if (requiredCheck) {
                requiredCheck.addEventListener('change', persistFieldValues);
            }
            
            fieldEl.querySelector('.remove-field').addEventListener('click', () => {
                syncCurrentFormSchemaFromInputs();
                currentFormSchema.splice(index, 1);
                renderFormFields();
            });
        });
    }

    function renderCreateTemplateFields() {
        if (!createTemplateFieldsContainer) {
            return;
        }

        createTemplateFieldsContainer.innerHTML = '';
        createFormSchema.forEach((field, index) => {
            const fieldEl = document.createElement('div');
            fieldEl.className = 'card border-0 bg-white mb-2';
            fieldEl.innerHTML = `
                <div class="card-body py-2 px-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <input type="text" class="form-control form-control-sm create-field-name" value="${field.name || ''}" placeholder="π.χ. Ονοματεπώνυμο Μαθητή">
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm create-field-type">
                                ${Object.entries(fieldTypes).map(([val, label]) => `<option value="${val}" ${field.type === val ? 'selected' : ''}>${label}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input create-field-required" id="create_required_${index}" ${field.required ? 'checked' : ''}>
                                <label class="form-check-label" for="create_required_${index}">Υποχρεωτικό</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 text-md-end">
                            <button type="button" class="btn btn-sm btn-outline-danger create-remove-field">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            createTemplateFieldsContainer.appendChild(fieldEl);

            const nameInput = fieldEl.querySelector('.create-field-name');
            const typeSelect = fieldEl.querySelector('.create-field-type');
            const requiredCheck = fieldEl.querySelector('.create-field-required');

            const persistFieldValues = () => {
                createFormSchema[index] = {
                    name: nameInput ? nameInput.value : '',
                    type: typeSelect ? typeSelect.value : 'text',
                    required: requiredCheck ? requiredCheck.checked : true
                };
            };

            if (nameInput) {
                nameInput.addEventListener('input', persistFieldValues);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', persistFieldValues);
            }
            if (requiredCheck) {
                requiredCheck.addEventListener('change', persistFieldValues);
            }

            fieldEl.querySelector('.create-remove-field').addEventListener('click', () => {
                syncCreateFormSchemaFromInputs();
                createFormSchema.splice(index, 1);
                renderCreateTemplateFields();
            });
        });
    }

    if (templateSelector) {
        templateSelector.addEventListener('change', function() {
            const selectedId = this.value;
            
            if (!selectedId) {
                templateEditor.style.display = 'none';
                templateEmptyState.style.display = 'block';
                if (deleteTemplateId) {
                    deleteTemplateId.value = '';
                }
                if (editTemplateInstructionFiles) {
                    editTemplateInstructionFiles.value = '';
                }
                editTemplateInitialInstructionFiles = [];
                editTemplateExistingInstructionFiles = [];
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }
            
            const template = templatesData.find(t => t.id == selectedId);
            if (!template) return;
            
            editTemplateId.value = selectedId;
            if (deleteTemplateId) {
                deleteTemplateId.value = selectedId;
            }
            editTemplateName.value = template.name;
            editTemplateDescription.value = template.description;
            currentFormSchema = template.form_schema || [];
            editTemplateInitialInstructionFiles = Array.isArray(template.instruction_files)
                ? template.instruction_files.map((file) => ({
                    path: String((file && file.path) || ''),
                    name: String((file && file.name) || ''),
                    url: String((file && file.url) || '#')
                }))
                : [];
            editTemplateExistingInstructionFiles = editTemplateInitialInstructionFiles.map((file) => ({ ...file }));
            editTemplateSelectedFiles = [];
            if (editTemplateInstructionFiles) {
                editTemplateInstructionFiles.value = '';
            }
            
            renderFormFields();
            renderEditTemplateInstructionFilesList();
            
            templateEmptyState.style.display = 'none';
            templateEditor.style.display = 'block';
        });

        const templateParams = new URLSearchParams(window.location.search);
        const activeTemplateIdParam = templateParams.get('active_template_id') || '';
        if (activeTemplateIdParam !== '') {
            templateSelector.value = activeTemplateIdParam;
            templateSelector.dispatchEvent(new Event('change'));
        }
    }

    if (addTemplateFieldBtn) {
        addTemplateFieldBtn.addEventListener('click', () => {
            syncCurrentFormSchemaFromInputs();
            currentFormSchema.push({
                name: '',
                type: 'text',
                required: true
            });
            renderFormFields();
        });
    }

    if (editTemplateInstructionFiles) {
        editTemplateInstructionFiles.addEventListener('change', function () {
            const selectedFiles = Array.from(editTemplateInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }

            const remainingSlots = Math.max(0, 4 - editTemplateExistingInstructionFiles.length);
            if (selectedFiles.length > remainingSlots) {
                alert('Μπορείτε να προσθέσετε έως ' + String(remainingSlots) + ' ακόμη αρχεία οδηγιών.');
                editTemplateInstructionFiles.value = '';
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }

            const allowedExtensions = ['pdf', 'doc', 'docx'];
            const hasInvalidFile = selectedFiles.some((file) => {
                const fileName = String((file && file.name) || '');
                const extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                return !allowedExtensions.includes(extension);
            });

            if (hasInvalidFile) {
                alert('Επιτρεπόμενοι τύποι αρχείων οδηγιών: pdf, doc, docx.');
                editTemplateInstructionFiles.value = '';
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }

            editTemplateSelectedFiles = selectedFiles;
            renderEditTemplateInstructionFilesList();
        });

        renderEditTemplateInstructionFilesList();
    }

    if (createTemplateInstructionFiles) {
        createTemplateInstructionFiles.addEventListener('change', function () {
            const selectedFiles = Array.from(createTemplateInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                return;
            }

            if (selectedFiles.length > 4) {
                alert('Μπορείτε να επιλέξετε έως 4 αρχεία οδηγιών.');
                createTemplateInstructionFiles.value = '';
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                return;
            }

            const allowedExtensions = ['pdf', 'doc', 'docx'];
            const hasInvalidFile = selectedFiles.some((file) => {
                const fileName = String((file && file.name) || '');
                const extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                return !allowedExtensions.includes(extension);
            });

            if (hasInvalidFile) {
                alert('Επιτρεπόμενοι τύποι αρχείων οδηγιών: pdf, doc, docx.');
                createTemplateInstructionFiles.value = '';
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                return;
            }

            createTemplateSelectedFiles = selectedFiles;
            renderCreateTemplateInstructionFilesList();
        });

        renderCreateTemplateInstructionFilesList();
    }

    if (editTemplateForm) {
        editTemplateForm.addEventListener('submit', function(e) {
            e.preventDefault();

            syncCurrentFormSchemaFromInputs();
            const fields = currentFormSchema
                .map((field) => ({
                    name: String(field.name || '').trim(),
                    type: field.type || 'text',
                    required: Boolean(field.required)
                }))
                .filter((field) => field.name !== '');

            if (editTemplateInstructionFiles && editTemplateSelectedFiles.length !== Array.from(editTemplateInstructionFiles.files || []).length) {
                syncEditTemplateInputFiles(editTemplateSelectedFiles);
            }

            const remainingSlots = Math.max(0, 4 - editTemplateExistingInstructionFiles.length);
            if (editTemplateSelectedFiles.length > remainingSlots) {
                alert('Μπορείτε να προσθέσετε έως ' + String(remainingSlots) + ' ακόμη αρχεία οδηγιών.');
                return;
            }

            if (editTemplateRemovedInstructionFiles) {
                editTemplateRemovedInstructionFiles.value = JSON.stringify(getEditTemplateRemovedInstructionPaths());
            }

            editTemplateSchema.value = JSON.stringify(fields);
            this.submit();
        });
    }

    if (publishTemplateBtn) {
        publishTemplateBtn.addEventListener('click', function() {
            if (editTemplateForm) {
                let publishInput = editTemplateForm.querySelector('input[name="publish"]');
                if (!publishInput) {
                    publishInput = document.createElement('input');
                    publishInput.type = 'hidden';
                    publishInput.name = 'publish';
                    editTemplateForm.appendChild(publishInput);
                }
                publishInput.value = '1';

                if (typeof editTemplateForm.requestSubmit === 'function') {
                    editTemplateForm.requestSubmit();
                } else {
                    const submitBtn = editTemplateForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    } else {
                        editTemplateForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
            }
        });
    }

    if (deleteTemplateBtn) {
        deleteTemplateBtn.addEventListener('click', function () {
            if (!deleteTemplateId || !deleteTemplateId.value) {
                return;
            }

            if (deleteTemplateNamePreview) {
                deleteTemplateNamePreview.textContent = editTemplateName && editTemplateName.value
                    ? editTemplateName.value
                    : 'Επιλεγμένο πρότυπο';
            }

            if (deleteTemplateConfirmModal) {
                deleteTemplateConfirmModal.show();
            }
        });
    }

    if (confirmDeleteTemplateBtn) {
        confirmDeleteTemplateBtn.addEventListener('click', function () {
            if (deleteTemplateConfirmModal) {
                deleteTemplateConfirmModal.hide();
            }

            if (deleteTemplateForm) {
                deleteTemplateForm.submit();
            }
        });
    }

    if (addCreateTemplateFieldBtn) {
        addCreateTemplateFieldBtn.addEventListener('click', function () {
            syncCreateFormSchemaFromInputs();
            createFormSchema.push({
                name: '',
                type: 'text',
                required: true
            });
            renderCreateTemplateFields();
        });
    }

    if (createTemplateForm) {
        createTemplateForm.addEventListener('submit', function () {
            syncCreateFormSchemaFromInputs();

            if (createTemplateInstructionFiles && createTemplateSelectedFiles.length !== Array.from(createTemplateInstructionFiles.files || []).length) {
                syncCreateTemplateInputFiles(createTemplateSelectedFiles);
            }

            const fields = createFormSchema
                .map((field) => ({
                    name: String(field.name || '').trim(),
                    type: field.type || 'text',
                    required: Boolean(field.required)
                }))
                .filter((field) => field.name !== '');

            createTemplateSchema.value = JSON.stringify(fields);
        });

        createTemplateForm.addEventListener('reset', function () {
            createFormSchema = [];
            if (createTemplateInstructionFiles) {
                createTemplateInstructionFiles.value = '';
            }
            createTemplateSelectedFiles = [];
            renderCreateTemplateInstructionFilesList();
            window.setTimeout(renderCreateTemplateFields, 0);
        });
    }

    if (createTemplateFieldsContainer) {
        createFormSchema = [
            { name: '', type: 'text', required: true }
        ];
        renderCreateTemplateFields();
    }

    if (closeCreateTemplateBtn) {
        closeCreateTemplateBtn.addEventListener('click', function () {
            if (closeCreateTemplateConfirmModal) {
                closeCreateTemplateConfirmModal.show();
            }
        });
    }

    if (confirmCloseCreateTemplateBtn) {
        confirmCloseCreateTemplateBtn.addEventListener('click', function () {
            if (createTemplateCollapse) {
                bootstrap.Collapse.getOrCreateInstance(createTemplateCollapse).hide();
            }

            if (createTemplateForm) {
                createTemplateForm.reset();
                if (createTemplateInstructionFiles) {
                    createTemplateInstructionFiles.value = '';
                }
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                createFormSchema = [{ name: '', type: 'text', required: true }];
                renderCreateTemplateFields();
            }

            if (closeCreateTemplateConfirmModal) {
                closeCreateTemplateConfirmModal.hide();
            }
        });
    }
});
</script>
</body>
</html>
