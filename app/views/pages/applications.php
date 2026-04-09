<?php
/**
 * Parent Applications Page
 * Displays all applications and allows parent users to submit them.
 */

require_once __DIR__ . '/../../services/ApplicationsService.php';
require_once __DIR__ . '/../../includes/site_context.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the service
$applicationsService = new ApplicationsService();

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
 * Normalize $_FILES input (single or multiple) into a flat files array.
 *
 * @param array<string, mixed> $files
 * @return array<int, array<string, mixed>>
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

function getUploadedSubmissionDisplayName(string $fileName): string {
    $fileName = trim($fileName);
    if ($fileName === '') {
        return '';
    }

    return basename(str_replace('\\', '/', $fileName));
}

function getApplicationDocumentDisplayNamesPathPublic(): string {
    return __DIR__ . '/../../../storage/application_document_display_names.json';
}

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

function getSubmissionFileDisplayNamesPathPublic(): string {
    return __DIR__ . '/../../../storage/submission_file_display_names.json';
}

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

function saveSubmissionFileDisplayNamesPublic(array $displayNames): bool {
    $path = getSubmissionFileDisplayNamesPathPublic();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return file_put_contents($path, json_encode($displayNames, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

function normalizeSubmissionMode($mode): string {
    $mode = trim((string)$mode);
    return in_array($mode, ['manual', 'upload'], true) ? $mode : 'upload';
}

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

// Προσωρινό parent id μέχρι να συνδεθεί το πραγματικό auth flow.
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
$currentUserEmail = trim((string)($_SESSION['email'] ?? ''));

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

        if ($applicationsService->hasUserSubmitted($application_id, $user_id)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Έχετε ήδη υποβάλει αυτή την αίτηση.']);
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
            echo json_encode(['success' => false, 'message' => 'Παρακαλώ ανεβάστε τουλάχιστον ένα αρχείο για την υποβολή της αίτησης.']);
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
            echo json_encode(['success' => false, 'message' => 'Μπορείτε να ανεβάσετε έως 4 αρχεία.']);
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
                echo json_encode(['success' => false, 'message' => 'Σφάλμα ανεβάσματος αρχείου.']);
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
                echo json_encode(['success' => false, 'message' => 'Επιτρεπόμενοι τύποι αρχείων: pdf, doc, docx, jpg, jpeg, png.']);
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
                echo json_encode(['success' => false, 'message' => 'Η αποθήκευση του αρχείου απέτυχε.']);
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
                'message' => 'Η αίτηση υποβλήθηκε επιτυχώς.',
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
            echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων. Δοκιμάστε ξανά.']);
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
        $application_id = (int)($_POST['application_id'] ?? 0);
        $raw            = $_POST['submission_data'] ?? '';

        if ($application_id <= 0) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρη αίτηση.']);
            exit;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) && !is_object(json_decode($raw))) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρα δεδομένα φόρμας.']);
            exit;
        }

        if ($applicationsService->hasUserSubmitted($application_id, $user_id)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Έχετε ήδη υποβάλει αυτή την αίτηση.']);
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
                echo json_encode(['success' => false, 'message' => 'Σφάλμα ανεβάσματος αρχείου.']);
                exit;
            }

            $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Επιτρεπόμενοι τύποι αρχείων: pdf, doc, docx, jpg, jpeg, png.']);
                exit;
            }

            $newFileName = 'submission_' . $user_id . '_' . $application_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $uploadedAbsPath = $uploadDir . $newFileName;
            $uploadedDbPath = 'storage/uploads/submissions/' . $newFileName;
            $uploadedDisplayName = getUploadedSubmissionDisplayName((string)$file['name']);

            if (!move_uploaded_file((string)$file['tmp_name'], $uploadedAbsPath)) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Η αποθήκευση του αρχείου απέτυχε.']);
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
            echo json_encode(['success' => true, 'message' => 'Η αίτηση υποβλήθηκε επιτυχώς.']);
        } else {
            if ($uploadedAbsPath && file_exists($uploadedAbsPath)) {
                unlink($uploadedAbsPath);
            }
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων. Δοκιμάστε ξανά.']);
        }
    } catch (Throwable $e) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Σφάλμα διακομιστή: ' . $e->getMessage()]);
    }
    exit;
}

/*
 |------------------------------------------------------------
 | Submit application
 |------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $application_id = (int) ($_POST['application_id'] ?? 0);

    if ($application_id <= 0) {
        $message = 'Μη έγκυρη αίτηση.';
        $messageType = 'danger';
    } else {
        // Check if user already submitted this application
        if ($applicationsService->hasUserSubmitted($application_id, $user_id)) {
            $message = 'Έχετε ήδη υποβάλει αυτή την αίτηση.';
            $messageType = 'warning';
        } else {
            if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
                $message = 'Παρακαλούμε ανεβάστε ένα έγκυρο αρχείο.';
                $messageType = 'danger';
            } else {
                $file = $_FILES['submission_file'];
                $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $message = 'Επιτρεπόμενοι τύποι αρχείων: pdf, doc, docx, jpg, jpeg, png.';
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

                            $message = 'Η αίτηση υποβλήθηκε με επιτυχία.';
                            $messageType = 'success';
                        } else {
                            $message = 'Σφάλμα βάσης δεδομένων κατά την αποθήκευση της υποβολής.';
                            $messageType = 'danger';
                        }
                    } else {
                        $message = 'Η μεταφόρτωση του αρχείου απέτυχε.';
                        $messageType = 'danger';
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
$mySubmissions = $applicationsService->getUserSubmissions($user_id);
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

    <style>
        .post-card {
            cursor: pointer;
            overflow: hidden;
            padding: 0;
            background: #f8f9fb;
            border: 1px solid #dbe4f3;
            border-radius: 0;
            box-shadow: none;
            border-bottom: 4px solid #2f6fb3;
        }

        .post-image-wrap {
            display: none !important;
        }

        .post-content {
            padding: 22px 26px 16px;
        }

        .application-item.post-card:hover {
            box-shadow: none;
            transform: none;
        }

        .application-title {
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.25px;
        }

        .application-instruction-links {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 0;
        }

        .application-instruction-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            width: fit-content;
        }

        .application-instruction-link:hover {
            text-decoration: underline;
        }

        .application-instruction-empty {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .application-date-bottom {
            margin-top: 10px;
            width: 100%;
            text-align: right;
        }

        .application-date-bottom span {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: transparent;
            color: #355b85;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .app-meta-top,
        .js-dates-placeholder,
        .submit-btn {
            display: none !important;
        }

        .application-view-attachments a {
            text-decoration: none;
        }

        #applicationViewModal .modal-content {
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 18px 44px rgba(18, 41, 70, 0.18);
        }

        #applicationViewModal .modal-header {
            background: linear-gradient(135deg, #1f63b6 0%, #2f7fd8 100%);
            border-bottom: 0;
        }

        #applicationViewModal .modal-title,
        #applicationViewModal .modal-header .close {
            color: #ffffff;
            text-shadow: none;
            opacity: 1;
        }

        #applicationViewModal .modal-body {
            background: #f8fbff;
        }

        .application-view-files-panel {
            background: #ffffff;
            border: 1px solid #dce9f8;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(31, 79, 143, 0.06);
        }

        .application-view-files-panel h6 {
            color: #1f4f8f;
            font-weight: 700;
            margin-bottom: 12px;
        }

        #application-view-attachments-list {
            list-style: none;
            margin-bottom: 0;
            padding-left: 0;
        }

        #application-view-attachments-list li {
            margin-bottom: 10px;
            line-height: 1.35;
            background: #f7fbff;
            border: 1px solid #d8e7f8;
            border-radius: 10px;
            padding: 10px 12px;
        }

        #application-view-attachments-list li:last-child {
            margin-bottom: 0;
        }

        #application-view-attachments-list a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #18395f;
            font-weight: 600;
        }

        .application-submit-methods {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .application-submit-option {
            position: relative;
            width: 100%;
            border: 1px solid #d7e6f7;
            border-radius: 14px;
            background: linear-gradient(180deg, #fbfdff 0%, #f2f8ff 100%);
            padding: 18px 18px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-align: left;
            color: #1f456d;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            cursor: pointer;
        }

        .application-submit-option:hover {
            border-color: #a8c7ec;
            box-shadow: 0 10px 24px rgba(31, 79, 143, 0.08);
            transform: translateY(-1px);
        }

        .application-submit-option.is-active {
            border-color: #1f6fc4;
            box-shadow: 0 14px 28px rgba(31, 111, 196, 0.16);
            background: linear-gradient(180deg, #ffffff 0%, #f4f9ff 100%);
        }

        .application-submit-option__icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eaf3ff;
            color: #2368b9;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .application-submit-option__title {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: #173a63;
        }

        .application-submit-option__text {
            display: block;
            margin-top: 5px;
            color: #60758e;
            font-size: 0.92rem;
            line-height: 1.45;
        }

        .application-submit-option__check {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            border-radius: 9px;
            border: 1px solid #bdd3ef;
            background: #ffffff;
            color: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .application-submit-option.is-active .application-submit-option__check {
            background: #1f6fc4;
            border-color: #1f6fc4;
            color: #ffffff;
        }

        .application-submit-panel {
            display: none;
        }

        .application-submit-panel.is-active {
            display: block;
        }

        .application-submit-helper {
            margin-bottom: 14px;
            color: #60758e;
            font-size: 0.93rem;
            line-height: 1.5;
        }

        .application-view-manual-fields .form-group:last-child {
            margin-bottom: 0 !important;
        }

        .application-view-manual-fields .form-control {
            border: 1px solid #b9d1ef;
            border-radius: 10px;
            color: #223d60;
            background: #fbfdff;
        }

        .application-view-manual-fields .form-control:focus {
            border-color: #2b76cc;
            box-shadow: 0 0 0 0.2rem rgba(43, 118, 204, 0.12);
        }

        .application-view-manual-fields textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        #application-view-upload {
            background: #ffffff;
            border: 1px solid #dce9f8;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 8px 24px rgba(31, 79, 143, 0.06);
        }

        #application-view-upload .upload-title {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 700;
            color: #1f4f8f;
            margin-bottom: 12px;
            font-size: 1.05rem;
        }

        #application-view-upload .upload-title i {
            width: 18px;
            text-align: center;
        }

        .application-view-upload-note {
            display: block;
            margin-top: 10px;
            color: #60758e !important;
            font-size: 0.92rem;
        }

        .application-view-upload-note strong {
            color: #304c6f;
        }

        #application-view-file-input {
            border: 1px solid #b9d1ef;
            background: linear-gradient(180deg, #fafdff 0%, #f3f8ff 100%);
            border-radius: 10px;
            min-height: 46px;
            padding: 6px 8px;
            color: #223d60;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
        }

        #application-view-file-input::file-selector-button,
        #application-view-file-input::-webkit-file-upload-button {
            border: 0;
            background: linear-gradient(135deg, #2c73c8 0%, #1d5faa 100%);
            color: #ffffff;
            padding: 0.42rem 0.75rem;
            margin-right: 0.65rem;
            border-radius: 7px;
            cursor: pointer;
            font-size: 0.92rem;
            line-height: 1.1;
            font-weight: 700;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        #application-view-selected-files {
            list-style: none;
            margin-top: 14px;
            margin-bottom: 0;
            padding-left: 0;
            color: #2b4462;
            font-size: 0.9rem;
        }

        #application-view-selected-files:empty {
            display: none;
        }

        #application-view-selected-files li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            padding: 9px 12px;
            background: #f7fbff;
            border: 1px solid #d8e7f8;
            border-radius: 10px;
            color: #24476d;
            font-weight: 600;
        }

        #application-view-selected-files li:last-child {
            margin-bottom: 0;
        }

        #application-view-selected-files li i {
            color: #2b76cc;
        }

        #submitModal .modal-dialog {
            margin: 0.75rem auto;
        }

        #submitModal .modal-content {
            max-height: calc(100vh - 1.5rem);
        }

        #submitModal .modal-header {
            background: #1f7aec;
            border-bottom: 0;
        }

        #submitModal .modal-title {
            color: #ffffff;
        }

        #application-notice-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1085;
            width: min(360px, calc(100vw - 32px));
            display: none;
            padding: 0;
            background: transparent !important;
            border: 0;
            box-shadow: none !important;
            outline: none;
        }

        #application-notice-box.is-visible {
            display: block;
        }

        .application-notice-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 31, 52, 0.28);
            z-index: 1084;
            display: none;
        }

        .application-notice-backdrop.is-visible {
            display: block;
        }

        .application-notice-card {
            background: linear-gradient(180deg, #f7fbff 0%, #e6f1ff 100%);
            border: none;
            border-radius: 14px;
            box-shadow: 0 18px 44px rgba(18, 41, 70, 0.22);
            overflow: hidden;
            background-clip: padding-box;
        }

        .application-notice-header {
            padding: 12px 16px;
            background: linear-gradient(135deg, #1f63b6 0%, #2f7fd8 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.98rem;
        }

        .application-notice-body {
            padding: 16px;
            color: #284566;
            font-size: 0.95rem;
            line-height: 1.45;
            background: transparent;
        }

        .application-notice-footer {
            padding: 0 16px 16px;
            display: flex;
            justify-content: center;
            background: transparent;
        }

        .application-notice-btn {
            min-width: 96px;
            border-radius: 9px;
            font-weight: 700;
        }

        #submitModal .modal-header .close {
            color: #ffffff;
            opacity: 1;
            text-shadow: none;
        }

        #submitModal .modal-header .close:hover {
            color: #ffffff;
            opacity: 0.85;
        }

        #submitModal .modal-body {
            overflow-y: auto;
        }

        #application-unavailable-modal .modal-content {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 14px 40px rgba(15, 31, 50, 0.2);
        }

        #application-unavailable-modal .modal-body {
            padding: 1.4rem 1.2rem 1rem;
            text-align: center;
            color: #1f3550;
            font-weight: 700;
        }

        #application-unavailable-modal .modal-footer {
            border-top: 0;
            justify-content: center;
            padding-top: 0;
            padding-bottom: 1rem;
        }

        #modal-submission-file {
            font-size: 0.9rem;
            line-height: 1.2;
            padding: 0.28rem 0.5rem;
        }

        #modal-submission-file::file-selector-button,
        #modal-submission-file::-webkit-file-upload-button {
            font-size: 0.82rem;
            line-height: 1.2;
            padding: 0.28rem 0.62rem;
            margin-right: 0.5rem;
        }

        @media (max-width: 767.98px) {
            .application-submit-methods {
                grid-template-columns: 1fr;
            }

            .application-submit-option {
                padding: 16px 16px 14px;
            }
        }
    </style>

    <title>Αιτήσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Αιτήσεις';
$pageHeaderSubtitle = 'Υποβάλλετε αιτήσεις και παρακολουθήστε εύκολα την πορεία τους.';
$pageHeaderIcon = 'fas fa-file-alt';
$pageHeaderEyebrow = 'Υποβολές Και Έγγραφα';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<div class="container py-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Κλείσιμο">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <div class="card applications-card mb-4">
                <div class="card-body p-4">
                    <h3 class="section-title">Διαθέσιμες Αιτήσεις</h3>

                    <?php if (empty($applications)): ?>
                        <div class="alert alert-info mb-0">Δεν υπάρχουν διαθέσιμες αιτήσεις.</div>
                    <?php else: ?>
                        <div class="row" id="applications-grid">
                            <?php $appIndex = 0; foreach ($applications as $application):
                                $appId = (int)$application['application_id'];
                                $isDbApplied = in_array($appId, $appliedIds);
                                $appMeta = $applicationUiMeta[(string)$appId] ?? [];
                                $openDateRaw = (string)($appMeta['open_date'] ?? '');
                                $closeDateRaw = (string)($appMeta['close_date'] ?? ($appMeta['deadline'] ?? ''));
                                $openDateFormatted = formatUiDatePublic($openDateRaw);
                                $closeDateFormatted = formatUiDatePublic($closeDateRaw);
                                $fullDescription = (string)($application['application_description'] ?? 'Δεν υπάρχει διαθέσιμη περιγραφή.');
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
                                $applicationDateDisplay = $openDateFormatted !== '' ? $openDateFormatted : '—';
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
                                            <img src="<?php echo htmlspecialchars($postImageSrc); ?>" alt="Εικόνα αίτησης">
                                        </div>

                                        <div class="post-content d-flex flex-column h-100">

                                        <!-- Status badge & category tag – filled by JS -->
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
                                                <span>Δείτε εδώ</span><i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>

                                        <div class="application-date-bottom">
                                            <span><?php echo htmlspecialchars($applicationDateDisplay); ?></span>
                                        </div>

                                        <!-- Open / close date row – filled by JS -->
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
                                            <i class="fas fa-paper-plane mr-1"></i> Υποβολή Αίτησης
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
                    <h3 class="section-title">Οι Υποβολές Μου</h3>

                    <div class="alert alert-secondary mb-3" id="no-submissions-msg"<?php echo (!empty($mySubmissions)) ? ' style="display:none"' : ''; ?>>
                        <i class="fas fa-inbox mr-2"></i>Δεν έχετε υποβάλει ακόμη καμία αίτηση.
                    </div>

                    <div class="table-responsive">
                        <table class="table submissions-table" id="submissions-table"<?php echo empty($mySubmissions) ? ' style="display:none"' : ''; ?>>
                            <thead>
                                <tr>
                                    <th>Αίτηση</th>
                                    <th>Ημ. Υποβολής</th>
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
                                    $submissionMode = ($formData['_submission_mode'] ?? 'upload') === 'manual' ? 'Online Συμπλήρωση' : 'Ανέβασμα Αρχείου';
                                    $submittedDate = !empty($submission['submitted_at'])
                                        ? date('d/m/Y', strtotime($submission['submitted_at']))
                                        : '—';
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
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="applicationViewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="application-view-title">Λεπτομέρειες Αίτησης</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="application-view-description"></p>
                <div class="application-view-files-panel application-view-attachments">
                    <h6 class="mb-2">Συνημμένα Αρχεία</h6>
                    <ul id="application-view-attachments-list"></ul>
                </div>
                <div class="application-submit-methods" id="application-submit-methods">
                    <button type="button" class="application-submit-option" data-submit-mode="manual" aria-pressed="false">
                        <span class="application-submit-option__check"><i class="fas fa-check"></i></span>
                        <span class="application-submit-option__icon"><i class="fas fa-keyboard"></i></span>
                        <span>
                            <span class="application-submit-option__title">Online Συμπλήρωση</span>
                            <span class="application-submit-option__text">Συμπληρώστε την αίτηση απευθείας εδώ, χωρίς download, εκτύπωση ή νέο upload.</span>
                        </span>
                    </button>

                    <button type="button" class="application-submit-option" data-submit-mode="upload" aria-pressed="false">
                        <span class="application-submit-option__check"><i class="fas fa-check"></i></span>
                        <span class="application-submit-option__icon"><i class="fas fa-upload"></i></span>
                        <span>
                            <span class="application-submit-option__title">Ανέβασμα Αρχείου</span>
                            <span class="application-submit-option__text">Κατεβάστε την αίτηση, συμπληρώστε την και ανεβάστε εδώ έως 4 αρχεία για υποβολή.</span>
                        </span>
                    </button>
                </div>

                <div class="application-submit-panel" id="application-view-manual-panel">
                    <div class="application-view-files-panel mb-0">
                        <h6 class="mb-2">Συμπλήρωση Αίτησης Online</h6>
                        <p class="application-submit-helper mb-3">Συμπληρώστε τα παρακάτω στοιχεία και γράψτε το αίτημά σας στο πεδίο κειμένου.</p>
                        <form id="application-view-manual-form" novalidate>
                            <div id="application-view-manual-fields" class="application-view-manual-fields"></div>
                        </form>
                    </div>
                </div>

                <div class="application-submit-panel" id="application-view-upload-panel">
                    <div id="application-view-upload">
                    <label class="upload-title" for="application-view-file-input">
                        <i class="fas fa-paperclip"></i>
                        <span>Upload Αίτησης (έως 4 αρχεία)</span>
                    </label>
                    <input
                        type="file"
                        id="application-view-file-input"
                        class="form-control"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        multiple
                    >
                    <small class="application-view-upload-note">Απαιτείται τουλάχιστον 1 αρχείο. Επιτρεπόμενοι τύποι: <strong>pdf, doc, docx, jpg, jpeg, png</strong>.</small>
                    <ul id="application-view-selected-files"></ul>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
                <button type="button" class="btn btn-primary" id="application-view-submit-btn">
                    <i class="fas fa-paper-plane mr-1"></i>Υποβολή Αίτησης
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Submit Application Modal ────────────────────────────────────── -->
<div class="modal fade" id="submitModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-group">
                    <h5 class="modal-title" id="modal-title">Υποβολή Αίτησης</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="modal-description" class="text-muted small mb-3"></p>
                <hr class="my-2">
                <div id="modal-dynamic-fields">
                    <!-- Rendered by JavaScript -->
                </div>
                <div class="form-group mt-3 mb-0">
                    <label class="form-label-custom mb-2">
                        <i class="fas fa-paperclip text-primary mr-1"></i>Προαιρετικό αρχείο υποβολής
                    </label>
                    <input type="file" id="modal-submission-file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="text-muted d-block mt-1">Επιτρεπόμενοι τύποι: pdf, doc, docx, jpg, jpeg, png.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Ακύρωση
                </button>
                <button type="button" id="modal-submit-btn" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-1"></i> Υποβολή
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── View Submission Details Modal ─────────────────────────────────── -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt mr-2 text-primary"></i>Λεπτομέρειες Αίτησης</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="view-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="application-unavailable-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p class="mb-0" id="application-unavailable-message">Η αίτηση δεν έχει ανοίξει ακόμα.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary btn-sm px-4" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Success Toast ──────────────────────────────────────────────────── -->
<div id="submission-toast" class="position-fixed" style="bottom:1.5rem;right:1.5rem;z-index:9999;display:none;">
    <div class="alert alert-success shadow py-3 px-4 mb-0">
        <i class="fas fa-check-circle mr-2"></i> Η αίτησή σας υποβλήθηκε επιτυχώς!
    </div>
</div>

<div id="application-notice-backdrop" class="application-notice-backdrop"></div>
<div id="application-notice-box" role="alertdialog" aria-modal="true" aria-labelledby="application-notice-title">
    <div class="application-notice-card">
        <div class="application-notice-header" id="application-notice-title">Ειδοποίηση</div>
        <div class="application-notice-body" id="application-notice-message">—</div>
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
