<?php
// Arxeio: public\admin\applications.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
/**
 * Admin selida diaxeirisis aitiseon
 * Dimiourgei, enimeronei, diagrafei aitiseis kai diaxeirizetai documents
 * Edo o admin ftiaxnei aitiseis/templates kai diaxeirizetai ta arxeia pou synodevoun kathe aitisi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/includes/site_context.php';

// No-cache gia na min emfanizontai admin dedomena apo browser history meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Mono admin mporoun na allaksoun aitiseis/templates, alliws redirect sto login.
    header('Location: ' . site_login_url());
    exit;
}

require_once __DIR__ . '/../../app/services/ApplicationsService.php';
require_once __DIR__ . '/../../app/services/ApplicationTemplateService.php';
require_once __DIR__ . '/../../app/config/db.php';

// Arxikopoiei ta services
// ApplicationsService kanei CRUD stis aitiseis, ApplicationTemplateService xeirizetai ta reusable templates.
$applicationsService = new ApplicationsService();
$templateService = new ApplicationTemplateService($conn);

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

$documentsUploadDir = __DIR__ . '/../assets/Applications_docs/';
if (!is_dir($documentsUploadDir)) {
    // Dimiourgoume ton upload fakelo an leipei, gia na min apotyxei to upload sti mesi.
    mkdir($documentsUploadDir, 0777, true);
}

// Leitourgia normalizeUploadedFiles: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeUploadedFiles(array $fileField): array {
    // Kanonikopoiei to $_FILES gia ena kai multiple uploads sto idio pinakas morfi.
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

// Leitourgia uploadApplicationFiles: xeirizetai to antistoixo kommati tis selidas i tou service.
function uploadApplicationFiles(ApplicationsService $applicationsService, int $applicationId, string $uploadDir): array {
    // Elegxei types/oria, metaferei ta arxeia sto uploads dir kai ta syndeei me tin aitisi sti vasi.
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
            $dbPath = site_asset_url('Applications_docs/' . $newFileName);

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
 * Metatrepei to apothikevmeno document path se public URL gia anoigma apo /public/admin.
 */
function getDocumentPublicUrl(string $storedPath): string {
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return '#';
    }

    $publicPosition = strpos($storedPath, '/public/');
    if ($publicPosition !== false) {
        return site_public_url(substr($storedPath, $publicPosition + strlen('/public/')));
    }

    if (strpos($storedPath, 'storage/') === 0) {
        return site_project_url() . '/' . ltrim($storedPath, '/');
    }

    return $storedPath;
}

/**
 * Metatrepei to apothikevmeno document path se local absolute path gia diagrafi.
 */
function getDocumentAbsolutePath(string $storedPath): string {
    $storedPath = trim($storedPath);
    $publicRoot = realpath(__DIR__ . '/..');
    $repoRoot = realpath(__DIR__ . '/../../');

    if ($storedPath === '') {
        return '';
    }

    $publicUrlPath = rtrim((string)parse_url(site_public_url(), PHP_URL_PATH), '/') . '/';
    $storedUrlPath = (string)parse_url($storedPath, PHP_URL_PATH);

    if ($storedUrlPath !== '' && strpos($storedUrlPath, $publicUrlPath) === 0 && $publicRoot !== false) {
        $relative = substr($storedUrlPath, strlen($publicUrlPath));
        return $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relative, '/'));
    }

    $legacyPublicPosition = strpos($storedPath, '/public/');
    if ($legacyPublicPosition !== false && $publicRoot !== false) {
        $relative = substr($storedPath, $legacyPublicPosition + strlen('/public/'));
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

// Leitourgia getApplicationDocumentDisplayNamesPath: xeirizetai to antistoixo kommati tis selidas i tou service.
function getApplicationDocumentDisplayNamesPath(): string {
    return __DIR__ . '/../../storage/application_document_display_names.json';
}

// Leitourgia loadApplicationDocumentDisplayNames: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia saveApplicationDocumentDisplayNames: xeirizetai to antistoixo kommati tis selidas i tou service.
function saveApplicationDocumentDisplayNames(array $displayNames): bool {
    $path = getApplicationDocumentDisplayNamesPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($displayNames, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

// Leitourgia normalizeUploadedDocumentDisplayName: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeUploadedDocumentDisplayName(string $fileName): string {
    $fileName = trim($fileName);
    if ($fileName === '') {
        return '';
    }

    return basename(str_replace('\\', '/', $fileName));
}

// Leitourgia getTemplateInstructionFilesMetaPath: xeirizetai to antistoixo kommati tis selidas i tou service.
function getTemplateInstructionFilesMetaPath(): string {
    return __DIR__ . '/../../storage/template_instruction_files.json';
}

// Leitourgia loadTemplateInstructionFilesMeta: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia saveTemplateInstructionFilesMeta: xeirizetai to antistoixo kommati tis selidas i tou service.
function saveTemplateInstructionFilesMeta(array $meta): bool {
    $path = getTemplateInstructionFilesMetaPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

// Leitourgia uploadTemplateInstructionFiles: xeirizetai to antistoixo kommati tis selidas i tou service.
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
        $dbPath = site_asset_url('Applications_docs/' . $newFileName);

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

// Leitourgia normalizeTemplateInstructionEntries: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia getTemplateInstructionFilesByTemplateId: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia removeTemplateInstructionFilesByPaths: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia cloneTemplateInstructionFilesToApplication: xeirizetai to antistoixo kommati tis selidas i tou service.
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
        $dbPath = site_asset_url('Applications_docs/' . $newFileName);

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

// Leitourgia diagrafiTemplateInstructionFiles: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia normalizeSubmissionMode: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeSubmissionMode(string $mode): string {
    return in_array($mode, ['manual', 'upload'], true) ? $mode : '';
}

// Leitourgia normalizeSubmissionFieldKey: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia resolveSubmissionMode: xeirizetai to antistoixo kommati tis selidas i tou service.
function resolveSubmissionMode(array $formData, array $submissionFiles = []): string {
    $storedMode = normalizeSubmissionMode((string)($formData['_submission_mode'] ?? ''));
    if ($storedMode !== '') {
        return $storedMode;
    }

    return !empty($submissionFiles) ? 'upload' : 'manual';
}

// Leitourgia getSubmissionModeUi: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia normalizeSubmissionExportValue: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeSubmissionExportValue($value): string {
    if (is_array($value)) {
        $parts = [];
        foreach ($value as $item) {
            $normalizedItem = normalizeSubmissionExportValue($item);
            if ($normalizedItem !== '') {
                $parts[] = $normalizedItem;
            }
        }

        return implode(', ', $parts);
    }

    if (is_bool($value)) {
        return $value ? 'Ναι' : 'Όχι';
    }

    if ($value === null) {
        return '';
    }

    return trim((string)$value);
}

// Leitourgia normalizeCheckboxExportValue: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeCheckboxExportValue($value): string {
    if (is_array($value)) {
        foreach ($value as $item) {
            if (normalizeCheckboxExportValue($item) === 'Ναι') {
                return 'Ναι';
            }
        }

        return 'Όχι';
    }

    if (is_bool($value)) {
        return $value ? 'Ναι' : 'Όχι';
    }

    if ($value === null) {
        return 'Όχι';
    }

    $normalized = strtolower(trim((string)$value));
    if (in_array($normalized, ['1', 'true', 'on', 'yes', 'checked', 'ναι'], true)) {
        return 'Ναι';
    }

    return 'Όχι';
}

// Leitourgia sanitizeExcelCellValue: xeirizetai to antistoixo kommati tis selidas i tou service.
function sanitizeExcelCellValue(string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }

    return $value;
}

// Leitourgia exportApplicationSubmissionsExcel: xeirizetai to antistoixo kommati tis selidas i tou service.
function exportApplicationSubmissionsExcel(ApplicationsService $applicationsService, int $applicationId, string $sort = 'newest'): void {
    if ($applicationId <= 0) {
        http_response_code(400);
        echo 'Μη έγκυρη αίτηση για export.';
        exit;
    }

    $application = $applicationsService->getApplicationById($applicationId);
    if (!$application) {
        http_response_code(404);
        echo 'Η αίτηση δεν βρέθηκε.';
        exit;
    }

    $selectedSort = normalizeSubmissionSort($sort);
    $allSubmissions = $applicationsService->getAllSubmissions();
    $selectedSubmissions = [];
    foreach ($allSubmissions as $submission) {
        if ((int)($submission['application_id'] ?? 0) === $applicationId) {
            $selectedSubmissions[] = $submission;
        }
    }

    usort($selectedSubmissions, static function (array $a, array $b) use ($selectedSort): int {
        $aTime = strtotime((string)($a['submitted_at'] ?? ''));
        $bTime = strtotime((string)($b['submitted_at'] ?? ''));
        $aTs = $aTime !== false ? $aTime : 0;
        $bTs = $bTime !== false ? $bTime : 0;

        if ($aTs === $bTs) {
            $aUser = (int)($a['user_id'] ?? 0);
            $bUser = (int)($b['user_id'] ?? 0);
            return $selectedSort === 'oldest' ? ($aUser <=> $bUser) : ($bUser <=> $aUser);
        }

        return $selectedSort === 'oldest' ? ($aTs <=> $bTs) : ($bTs <=> $aTs);
    });

    $fieldDefinitions = [];
    foreach ($applicationsService->getApplicationFormFields($applicationId) as $fieldIndex => $field) {
        $fieldLabel = trim((string)($field['field_name'] ?? ''));
        if ($fieldLabel === '') {
            continue;
        }

        $fieldType = normalizeEditableApplicationFieldType((string)($field['field_type'] ?? 'text'));
        if ($fieldType === 'file_upload') {
            continue;
        }

        $fieldDefinitions[] = [
            'key' => normalizeSubmissionFieldKey($fieldLabel, (int)$fieldIndex),
            'label' => $fieldLabel,
            'type' => $fieldType,
        ];
    }

    $headers = ['Γονέας', 'Email', 'Ημ. Υποβολής'];
    foreach ($fieldDefinitions as $fieldDefinition) {
        $headers[] = (string)$fieldDefinition['label'];
    }

    $rows = [];
    foreach ($selectedSubmissions as $submission) {
        $formData = json_decode((string)($submission['submission_data'] ?? '{}'), true);
        if (!is_array($formData)) {
            $formData = [];
        }

        $parentName = trim((string)($formData['parent_name'] ?? (trim((string)($submission['name'] ?? '') . ' ' . (string)($submission['surname'] ?? '')))));
        $parentEmail = trim((string)($submission['email'] ?? ''));
        $submittedAt = trim((string)($submission['submitted_at'] ?? ''));
        $submittedAtDisplay = $submittedAt !== '' ? date('d/m/Y H:i', strtotime($submittedAt)) : '';

        $row = [
            sanitizeExcelCellValue($parentName),
            sanitizeExcelCellValue($parentEmail),
            sanitizeExcelCellValue($submittedAtDisplay),
        ];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldKey = (string)($fieldDefinition['key'] ?? '');
            $fieldValue = $fieldKey !== '' ? ($formData[$fieldKey] ?? '') : '';
            $fieldType = (string)($fieldDefinition['type'] ?? 'text');
            if ($fieldType === 'checkbox') {
                $row[] = sanitizeExcelCellValue(normalizeCheckboxExportValue($fieldValue));
            } else {
                $row[] = sanitizeExcelCellValue(normalizeSubmissionExportValue($fieldValue));
            }
        }

        $rows[] = $row;
    }

    $applicationTitleForFile = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)($application['application_title'] ?? 'application'));
    $applicationTitleForFile = trim((string)$applicationTitleForFile, '_');
    if ($applicationTitleForFile === '') {
        $applicationTitleForFile = 'application_' . $applicationId;
    }

    $fileName = 'submissions_' . $applicationTitleForFile . '_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: public');
    echo "\xEF\xBB\xBF";

    echo '<!DOCTYPE html><html lang="el"><head><meta charset="UTF-8"><title>Export Υποβολών</title></head><body>';
    echo '<table border="1">';
    echo '<thead><tr>';
    foreach ($headers as $headerCell) {
        echo '<th>' . htmlspecialchars($headerCell, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead><tbody>';

    if (empty($rows)) {
        echo '<tr><td colspan="' . count($headers) . '">Δεν υπάρχουν υποβολές για export.</td></tr>';
    } else {
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . nl2br(htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8')) . '</td>';
            }
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '</body></html>';
    exit;
}


// Leitourgia getApplicationUiMetaPath: xeirizetai to antistoixo kommati tis selidas i tou service.
function getApplicationUiMetaPath(): string {
    return __DIR__ . '/../../storage/application_ui_meta.json';
}

// Leitourgia loadApplicationUiMeta: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia saveApplicationUiMeta: xeirizetai to antistoixo kommati tis selidas i tou service.
function saveApplicationUiMeta(array $meta): bool {
    $path = getApplicationUiMetaPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

// Leitourgia getTemplateUiMetaPath: xeirizetai to antistoixo kommati tis selidas i tou service.
function getTemplateUiMetaPath(): string {
    return __DIR__ . '/../../storage/template_ui_meta.json';
}

// Leitourgia loadTemplateUiMeta: xeirizetai to antistoixo kommati tis selidas i tou service.
function loadTemplateUiMeta(): array {
    $path = getTemplateUiMetaPath();
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

// Leitourgia saveTemplateUiMeta: xeirizetai to antistoixo kommati tis selidas i tou service.
function saveTemplateUiMeta(array $meta): bool {
    $path = getTemplateUiMetaPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

// Leitourgia normalizeApplicationStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeApplicationStatus(string $status): string {
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

// Leitourgia normalizeSubmissionSort: xeirizetai to antistoixo kommati tis selidas i tou service.
function normalizeSubmissionSort(string $sort): string {
    $sort = strtolower(trim($sort));
    return in_array($sort, ['newest', 'oldest'], true) ? $sort : 'newest';
}

// Leitourgia normalizeEditModal parathyroTab: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia normalizeUiImerominia: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia getTodayUiImerominia: xeirizetai to antistoixo kommati tis selidas i tou service.
function getTodayUiDate(): string {
    try {
        $timezone = new DateTimeZone('Europe/Athens');
        return (new DateTime('now', $timezone))->format('Y-m-d');
    } catch (Throwable $e) {
        return date('Y-m-d');
    }
}

// Leitourgia normalizeEditableApplicationFieldType: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia mapTemplateFieldTypeToApplicationType: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia extractTemplateFieldsFromSchema: xeirizetai to antistoixo kommati tis selidas i tou service.
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

// Leitourgia publishTemplateAsApplication: xeirizetai to antistoixo kommati tis selidas i tou service.
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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export_submissions_excel'])) {
    $applicationIdForExport = (int)($_GET['view_submissions'] ?? $_GET['application_id'] ?? 0);
    $submissionSortForExport = normalizeSubmissionSort((string)($_GET['submissions_sort'] ?? 'newest'));

    if ($applicationIdForExport <= 0) {
        $_SESSION['flash_message'] = 'Επιλέξτε αίτηση για export υποβολών.';
        $_SESSION['flash_message_type'] = 'warning';
        header('Location: applications.php');
        exit;
    }

    exportApplicationSubmissionsExcel($applicationsService, $applicationIdForExport, $submissionSortForExport);
}

/*
|--------------------------------------------------------------------------
| POST actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $applicationUiMeta = loadApplicationUiMeta();
    $templateUiMeta = loadTemplateUiMeta();
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
        $openDateUi = normalizeUiDate((string)($_POST['application_open_date_ui'] ?? ''));
        if ($openDateUi === '') {
            $openDateUi = getTodayUiDate();
        }
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
                // Sxolio: voithitiko sxolio gia ton parakato kodika.
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

                // Sxolio: voithitiko sxolio gia ton parakato kodika.
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
            $openDateUi = array_key_exists('application_open_date_ui', $_POST)
                ? normalizeUiDate((string)($_POST['application_open_date_ui'] ?? ''))
                : (string)($existingMeta['open_date'] ?? '');
            if ($openDateUi === '') {
                $openDateUi = (string)($existingMeta['open_date'] ?? getTodayUiDate());
            }
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
                $dbPath = site_asset_url('Applications_docs/' . $newFileName);

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
                        $dbPath = site_asset_url('Applications_docs/' . $newFileName);

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

    // Xeirizetai creating aitisi apo template (NEW)
    if ($action === 'create_app_from_template') {
        $template_id = !empty($_POST['template_id']) && $_POST['template_id'] !== 'blank' ? (int)$_POST['template_id'] : null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $openDateUi = normalizeUiDate((string)($_POST['application_open_date_ui'] ?? $_POST['open_date'] ?? ''));
        if ($openDateUi === '') {
            $openDateUi = getTodayUiDate();
        }
        $closeDateUi = normalizeUiDate((string)($_POST['application_close_date_ui'] ?? $_POST['due_date'] ?? ''));
        $allow_online = isset($_POST['allow_online']) ? 1 : 0;
        $allow_file = isset($_POST['allow_file']) ? 1 : 0;
        $require_signature = isset($_POST['require_signature']) ? 1 : 0;

        if (empty($title)) {
            $message = 'Ο τίτλος της αίτησης είναι υποχρεωτικός';
            $messageType = 'danger';
        } elseif ($closeDateUi !== '' && $openDateUi > $closeDateUi) {
            $message = 'Η ημερομηνία κλεισίματος δεν μπορεί να είναι πριν από την ημερομηνία ανοίγματος.';
            $messageType = 'danger';
        } else {
            // Also add to legacy pedio names
            $app_id = $applicationsService->createApplicationFromTemplate(
                $template_id,
                $title,
                $description,
                $academic_year,
                $openDateUi,
                $closeDateUi,
                $allow_online,
                $allow_file,
                $require_signature,
                (int)$_SESSION['user_id']
            );

            if ($app_id) {
                $existingAppMeta = $applicationUiMeta[(string)$app_id] ?? [];
                $existingAppMeta['open_date'] = $openDateUi;
                $existingAppMeta['close_date'] = $closeDateUi;
                if (!isset($existingAppMeta['status'])) {
                    $existingAppMeta['status'] = 'active';
                }
                $applicationUiMeta[(string)$app_id] = $existingAppMeta;
                saveApplicationUiMeta($applicationUiMeta);

                $message = 'Η αίτηση δημιουργήθηκε επιτυχώς!';
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία της αίτησης';
                $messageType = 'danger';
            }
        }
    }

    // Xeirizetai publishing aitisi (NEW)
    if ($action === 'publish_application') {
        $app_id = (int)($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            if ($applicationsService->publishApplication($app_id)) {
                $message = 'Η αίτηση δημοσιεύθηκε επιτυχώς!';
                $messageType = 'success';
            }
        }
    }

    // Xeirizetai closing aitisi (NEW)
    if ($action === 'close_application') {
        $app_id = (int)($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            if ($applicationsService->closeApplication($app_id)) {
                $message = 'Η αίτηση κλείστηκε και δεν δέχεται υποβολές';
                $messageType = 'success';
            }
        }
    }

    // Xeirizetai creating template (NEW)
    if ($action === 'create_template') {
        $template_name = trim($_POST['template_name'] ?? '');
        $template_category = 'standard';
        $template_description = trim($_POST['description'] ?? '');
        $templateOpenDateUi = normalizeUiDate((string)($_POST['template_open_date_ui'] ?? ''));
        if ($templateOpenDateUi === '') {
            $templateOpenDateUi = getTodayUiDate();
        }
        $templateCloseDateUi = normalizeUiDate((string)($_POST['template_close_date_ui'] ?? ''));
        $form_schema_raw = $_POST['form_schema'] ?? '[]';

        $form_schema = json_decode((string)$form_schema_raw, true);
        if (!is_array($form_schema)) {
            $form_schema = [];
        }

        if (empty($template_name)) {
            $message = 'Το όνομα του προτύπου είναι υποχρεωτικό';
            $messageType = 'danger';
            $activeTab = 'templates';
        } elseif ($templateCloseDateUi !== '' && $templateOpenDateUi > $templateCloseDateUi) {
            $message = 'Η ημερομηνία κλεισίματος δεν μπορεί να είναι πριν από την ημερομηνία ανοίγματος.';
            $messageType = 'danger';
            $activeTab = 'templates';
        } else {
            // Auto-generate template key apo name (lowercase, replace spaces me underscores)
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
                $templateUiMeta[(string)$createdTemplateId] = [
                    'open_date' => $templateOpenDateUi,
                    'close_date' => $templateCloseDateUi,
                ];
                saveTemplateUiMeta($templateUiMeta);

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

    // Xeirizetai deleting template (NEW)
    if ($action === 'delete_template') {
        $template_id = (int)($_POST['template_id'] ?? 0);
        if ($template_id > 0) {
            if ($templateService->deleteTemplate($template_id)) {
                deleteTemplateInstructionFiles($template_id);
                if (isset($templateUiMeta[(string)$template_id])) {
                    unset($templateUiMeta[(string)$template_id]);
                    saveTemplateUiMeta($templateUiMeta);
                }
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

    // Xeirizetai updating template (NEW)
    if ($action === 'update_template') {
        $template_id = (int)($_POST['template_id'] ?? 0);
        $template_name = trim($_POST['template_name'] ?? '');
        $template_description = trim($_POST['description'] ?? '');
        $templateOpenDateUi = normalizeUiDate((string)($_POST['template_open_date_ui'] ?? ''));
        $templateCloseDateUi = normalizeUiDate((string)($_POST['template_close_date_ui'] ?? ''));
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

        if ($template_id <= 0 || empty($template_name)) {
            $message = 'Σφάλμα: Απαιτείται έγκυρο πρότυπο και όνομα.';
            $messageType = 'danger';
            $activeTab = 'templates';
        } elseif ($templateOpenDateUi === '') {
            $message = 'Η ημερομηνία ανοίγματος είναι υποχρεωτική.';
            $messageType = 'danger';
            $activeTab = 'templates';
        } elseif ($templateCloseDateUi !== '' && $templateOpenDateUi > $templateCloseDateUi) {
            $message = 'Η ημερομηνία κλεισίματος δεν μπορεί να είναι πριν από την ημερομηνία ανοίγματος.';
            $messageType = 'danger';
            $activeTab = 'templates';
        } else {
            if ($templateService->updateTemplate($template_id, $template_name, $template_description, $template_category, $form_schema_json)) {
                $templateUiMeta[(string)$template_id] = [
                    'open_date' => $templateOpenDateUi,
                    'close_date' => $templateCloseDateUi,
                ];
                saveTemplateUiMeta($templateUiMeta);

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
                        $publishedAppMeta['open_date'] = $templateOpenDateUi;
                        $publishedAppMeta['close_date'] = $templateCloseDateUi;
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
$templateUiMeta = loadTemplateUiMeta();
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
            <i class="fas fa-arrow-left"></i> Πίσω στην Αρχική
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

        <!-- TABS gia neo features -->
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
            <!-- TAB 1: Diaxeirizetai Aitiseis -->
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
                                    <th>Άνοιγμα</th>
                                    <th>Κλείσιμο</th>
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
                                        $applicationCloseDate = (string)($appMeta['close_date'] ?? ($appMeta['deadline'] ?? ''));
                                        $applicationStatusUi = normalizeApplicationStatus((string)($appMeta['status'] ?? 'active'));
                                        $isApplicationPublished = $applicationStatusUi === 'active';
                                        $applicationOpenDateDisplay = '—';
                                        if ($applicationOpenDate !== '') {
                                            $applicationDateObj = DateTime::createFromFormat('Y-m-d', $applicationOpenDate);
                                            if ($applicationDateObj && $applicationDateObj->format('Y-m-d') === $applicationOpenDate) {
                                                $applicationOpenDateDisplay = $applicationDateObj->format('d/m/Y');
                                            } else {
                                                $applicationOpenDateDisplay = $applicationOpenDate;
                                            }
                                        }
                                        $applicationCloseDateDisplay = '—';
                                        if ($applicationCloseDate !== '') {
                                            $applicationCloseDateObj = DateTime::createFromFormat('Y-m-d', $applicationCloseDate);
                                            if ($applicationCloseDateObj && $applicationCloseDateObj->format('Y-m-d') === $applicationCloseDate) {
                                                $applicationCloseDateDisplay = $applicationCloseDateObj->format('d/m/Y');
                                            } else {
                                                $applicationCloseDateDisplay = $applicationCloseDate;
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
                                        <td><span class="text-muted"><?php echo htmlspecialchars($applicationOpenDateDisplay); ?></span></td>
                                        <td><span class="text-muted"><?php echo htmlspecialchars($applicationCloseDateDisplay); ?></span></td>
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
                                                    data-application-close-date="<?php echo htmlspecialchars($applicationCloseDate, ENT_QUOTES, 'UTF-8'); ?>"
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
                        <a
                            href="applications.php?view_submissions=<?php echo (int)$selectedApplicationId; ?>&submissions_sort=<?php echo urlencode($selectedSubmissionSort); ?>&export_submissions_excel=1"
                            class="btn btn-outline-success <?php echo $selectedApplicationId > 0 ? '' : 'disabled'; ?>"
                            <?php echo $selectedApplicationId > 0 ? '' : 'aria-disabled="true"'; ?>
                        >
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </a>
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

                                        <div class="row g-3 mb-4">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label"><strong>Ημερομηνία Ανοίγματος *</strong></label>
                                                <input type="date" name="template_open_date_ui" id="create_template_open_date" class="form-control" value="<?php echo htmlspecialchars(getTodayUiDate(), ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label"><strong>Ημερομηνία Κλεισίματος</strong></label>
                                                <input type="date" name="template_close_date_ui" id="create_template_close_date" class="form-control">
                                            </div>
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
                        <div id="template_editor" class="admin-hidden">
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

                                <div class="row g-3 mb-4">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label"><strong>Ημερομηνία Ανοίγματος *</strong></label>
                                        <input type="date" name="template_open_date_ui" id="edit_template_open_date" class="form-control" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label"><strong>Ημερομηνία Κλεισίματος</strong></label>
                                        <input type="date" name="template_close_date_ui" id="edit_template_close_date" class="form-control">
                                    </div>
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
                                            <!-- Fields tha einai rendered here -->
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
                                    <button type="button" class="btn btn-secondary ms-auto" data-template-editor-close>
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
            <div class="modal-header admin-modal-header-blue">
                <h5 class="modal-title admin-modal-title-white">
                    <i class="fas fa-plus me-2"></i>Νέα Αίτηση
                </h5>
                <button type="button"
                        class="btn-close admin-modal-close-filter"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο">
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
                    <div class="col-12 col-md-6">
                        <label class="form-label"><strong>Ημερομηνία Ανοίγματος *</strong></label>
                        <input type="date" id="create_application_open_date" class="form-control form-control-custom" value="<?php echo htmlspecialchars(getTodayUiDate(), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label"><strong>Ημερομηνία Κλεισίματος</strong></label>
                        <input type="date" id="create_application_close_date" class="form-control form-control-custom">
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
                    <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
                    <div class="tab-pane fade show active" id="online-fill-pane" role="tabpanel" aria-labelledby="online-fill-tab">
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="mb-3"><i class="fas fa-list me-2"></i>Πεδία Φόρμας</h6>
                                <div id="online_form_fields_container" class="vstack gap-2 mb-3">
                                    <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
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
            <div class="modal-header admin-modal-header-blue">
                <h5 class="modal-title admin-modal-title-white">
                    <i class="fas fa-edit me-2"></i>Επεξεργασία Αίτησης
                </h5>
                <button type="button"
                        class="btn-close admin-modal-close-filter"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο">
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

                                        <div>
                                            <label class="form-label"><strong>Ημερομηνία Ανοίγματος *</strong></label>
                                            <input type="date" name="application_open_date_ui" id="edit_application_open_date" class="form-control form-control-sm form-control-custom" required>
                                        </div>

                                        <div>
                                            <label class="form-label"><strong>Ημερομηνία Κλεισίματος</strong></label>
                                            <input type="date" name="application_close_date_ui" id="edit_application_close_date" class="form-control form-control-sm form-control-custom">
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
            <div class="modal-header admin-modal-header-blue">
                <h5 class="modal-title admin-modal-title-white">
                    <i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση
                </h5>
                <button type="button"
                        class="btn-close admin-modal-close-filter"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο">
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
            <div class="modal-header admin-modal-header-blue">
                <h5 class="modal-title admin-modal-title-white">
                    <i class="fas fa-id-card me-2"></i>Λεπτομέρειες Υποβολής
                </h5>
                <button type="button"
                        class="btn-close admin-modal-close-filter"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο">
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

                <div class="modal-header admin-modal-header-blue">
                    <h5 class="modal-title admin-modal-title-white">
                        <i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση Διαγραφής
                    </h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Κλείσιμο"
                            class="admin-modal-close-filter">
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
            <div class="modal-header admin-modal-header-blue">
                <h5 class="modal-title admin-modal-title-white">
                    <i class="fas fa-question-circle me-2"></i>Επιβεβαίωση
                </h5>
                <button type="button"
                        class="btn-close admin-modal-close-filter"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο">
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
            <div class="modal-header admin-modal-header-blue">
                <h5 class="modal-title admin-modal-title-white">
                    <i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση Διαγραφής
                </h5>
                <button type="button"
                        class="btn-close admin-modal-close-filter"
                        data-bs-dismiss="modal"
                        aria-label="Κλείσιμο">
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

<?php
$adminApplicationsClientConfig = [
    'ADMIN_APPLICATIONS_TODAY_UI_DATE' => getTodayUiDate(),
    'ADMIN_APPLICATIONS_TEMPLATES_DATA' => array_map(function ($template) use ($templateUiMeta) {
        $templateId = (int)($template['template_id'] ?? 0);
        $templateMeta = $templateUiMeta[(string)$templateId] ?? [];

        return [
            'id' => $templateId,
            'name' => $template['name'] ?? '',
            'description' => $template['description'] ?? '',
            'open_date' => (string)($templateMeta['open_date'] ?? getTodayUiDate()),
            'close_date' => (string)($templateMeta['close_date'] ?? ''),
            'form_schema' => is_array($template['form_schema']) ? $template['form_schema'] : json_decode($template['form_schema'] ?? '[]', true),
            'instruction_files' => getTemplateInstructionFilesByTemplateId($templateId),
        ];
    }, $templates),
];
?>
<script src="../assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode($adminApplicationsClientConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="../assets/js/admin-applications.js"></script>
</body>
</html>
