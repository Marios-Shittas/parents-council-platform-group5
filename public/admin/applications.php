<?php
/**
 * Admin Applications Management Page
 * Create, update, delete applications and manage documents
 */

session_start();
require_once __DIR__ . '/../../app/services/ApplicationsService.php';

// Initialize the service
$applicationsService = new ApplicationsService();

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

    $uploadedCount = 0;
    $errors = [];
    $uploadedFiles = [];

    foreach ($definitions as $fieldName => $definition) {
        if (!isset($_FILES[$fieldName])) {
            continue;
        }

        foreach (normalizeUploadedFiles($_FILES[$fieldName]) as $file) {
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

            $uploadedCount++;
        }
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

function getSubmissionStatusMeta(string $status): array {
    $map = [
        'waiting' => ['label' => 'Υπό Εξέταση', 'badge' => 'bg-info'],
        'approved' => ['label' => 'Εγκρίθηκε', 'badge' => 'bg-success'],
        'rejected' => ['label' => 'Απορρίφθηκε', 'badge' => 'bg-danger'],
    ];

    return $map[$status] ?? ['label' => $status, 'badge' => 'bg-secondary'];
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

/*
|--------------------------------------------------------------------------
| POST actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $applicationUiMeta = loadApplicationUiMeta();
    $returnViewSubmissions = (int)($_POST['return_view_submissions'] ?? 0);
    $returnScrollY = max(0, (int)($_POST['return_scroll_y'] ?? 0));

    if ($action === 'create') {
        $title = trim($_POST['application_title'] ?? '');
        $description = trim($_POST['application_description'] ?? '');
        $openDateUi = normalizeUiDate((string)($_POST['application_open_date_ui'] ?? ''));
        $closeDateUi = normalizeUiDate((string)($_POST['application_close_date_ui'] ?? ''));
        $statusUi = normalizeApplicationStatus((string)($_POST['application_status_ui'] ?? 'active'));

        if ($title === '') {
            $message = 'Ο τίτλος της αίτησης είναι υποχρεωτικός.';
            $messageType = 'danger';
        } elseif ($openDateUi !== '' && $closeDateUi !== '' && $openDateUi > $closeDateUi) {
            $message = 'Η ημερομηνία ανοίγματος δεν μπορεί να είναι μετά την ημερομηνία κλεισίματος.';
            $messageType = 'danger';
        } else {
            $newApplicationId = $applicationsService->createApplication($title, $description);
            if ($newApplicationId) {
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

                $message = 'Η αίτηση δημιουργήθηκε επιτυχώς!';
                if ($uploadResult['uploaded_count'] > 0) {
                    $message .= ' Ανέβηκαν ' . $uploadResult['uploaded_count'] . ' αρχεία.';
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
        $openDateUi = normalizeUiDate((string)($_POST['application_open_date_ui'] ?? ''));
        $closeDateUi = normalizeUiDate((string)($_POST['application_close_date_ui'] ?? ''));
        $statusUi = normalizeApplicationStatus((string)($_POST['application_status_ui'] ?? 'active'));

        if ($application_id > 0 && $title !== '') {
            $existingMeta = $applicationUiMeta[(string)$application_id] ?? [];
            if ($openDateUi !== '' && $closeDateUi !== '' && $openDateUi > $closeDateUi) {
                $message = 'Η ημερομηνία ανοίγματος δεν μπορεί να είναι μετά την ημερομηνία κλεισίματος.';
                $messageType = 'danger';
            } elseif ($applicationsService->updateApplication($application_id, $title, $description)) {
                $uploadResult = uploadApplicationFiles($applicationsService, $application_id, $documentsUploadDir);

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
            foreach ($applicationsService->getDocuments($application_id) as $document) {
                $filePath = getDocumentAbsolutePath((string)($document['file_path'] ?? ''));
                if ($filePath !== '' && file_exists($filePath)) {
                    unlink($filePath);
                }
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

    if ($action === 'delete_document') {
        $doc_id = (int)($_POST['ap_document_id'] ?? 0);

        if ($doc_id > 0) {
            $doc = $applicationsService->getDocumentById($doc_id);

            if ($doc) {
                $filePath = getDocumentAbsolutePath((string)$doc['file_path']);
                if ($filePath !== '' && file_exists($filePath)) {
                    unlink($filePath);
                }

                if ($applicationsService->deleteDocument($doc_id)) {
                    $message = 'Το έγγραφο διαγράφηκε επιτυχώς!';
                    $messageType = 'success';
                } else {
                    $message = 'Σφάλμα κατά τη διαγραφή του εγγράφου.';
                    $messageType = 'danger';
                }
            }
        }
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
            } elseif (in_array($sub_status, ['waiting', 'approved', 'rejected'], true)) {
                if ($applicationsService->updateSubmissionStatus($application_id, $user_id, $sub_status)) {
                    $message = 'Η κατάσταση της υποβολής ενημερώθηκε επιτυχώς!';
                    $messageType = 'success';
                } else {
                    $message = 'Σφάλμα κατά την ενημέρωση της υποβολής.';
                    $messageType = 'danger';
                }
            }
        }
    }

    $redirectUrl = 'applications.php';
    if ($returnViewSubmissions > 0) {
        $redirectUrl .= '?view_submissions=' . $returnViewSubmissions;
        if ($returnScrollY > 0) {
            $redirectUrl .= '&scroll_y=' . $returnScrollY;
        }
    } elseif ($returnScrollY > 0) {
        $redirectUrl .= '?scroll_y=' . $returnScrollY;
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
$applicationUiMeta = loadApplicationUiMeta();

$submissionCountByApplication = [];
$pendingReviews = 0;
$approvedSubmissions = 0;
$rejectedSubmissions = 0;

foreach ($submissions as $submission) {
    $applicationId = (int)($submission['application_id'] ?? 0);
    if (!isset($submissionCountByApplication[$applicationId])) {
        $submissionCountByApplication[$applicationId] = 0;
    }
    $submissionCountByApplication[$applicationId]++;

    if (($submission['sub_status'] ?? '') === 'waiting') {
        $pendingReviews++;
    } elseif (($submission['sub_status'] ?? '') === 'approved') {
        $approvedSubmissions++;
    } elseif (($submission['sub_status'] ?? '') === 'rejected') {
        $rejectedSubmissions++;
    }
}

$selectedApplicationId = (int)($_GET['view_submissions'] ?? 0);
$selectedApplication = $selectedApplicationId > 0 ? $applicationsService->getApplicationById($selectedApplicationId) : null;
$selectedSubmissions = [];

if ($selectedApplicationId > 0) {
    foreach ($submissions as $submission) {
        if ((int)$submission['application_id'] === $selectedApplicationId) {
            $selectedSubmissions[] = $submission;
        }
    }
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
                                    <th>Περίοδος</th>
                                    <th>Κατάσταση</th>
                                    <th>Υποβολές</th>
                                    <th>Ημ. Δημιουργίας</th>
                                    <th class="text-end">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $application): ?>
                                    <?php
                                        $applicationId = (int)$application['application_id'];
                                        $submissionTotal = $submissionCountByApplication[$applicationId] ?? 0;
                                        $appMeta = $applicationUiMeta[(string)$applicationId] ?? [];
                                        $applicationOpenDate = (string)($appMeta['open_date'] ?? '');
                                        $applicationCloseDate = (string)($appMeta['close_date'] ?? ($appMeta['deadline'] ?? ''));
                                        $applicationStatus = normalizeApplicationStatus((string)($appMeta['status'] ?? 'active'));
                                        $applicationStatusLabel = $applicationStatus === 'inactive' ? 'Ανενεργή' : 'Ενεργή';
                                        $applicationStatusBadge = $applicationStatus === 'inactive' ? 'bg-secondary' : 'bg-success';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($application['application_title']); ?></div>
                                            <div class="small text-muted text-truncate-two-lines"><?php echo htmlspecialchars($application['application_description'] ?? 'Χωρίς περιγραφή.'); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($applicationOpenDate !== '' || $applicationCloseDate !== ''): ?>
                                                <div class="small">
                                                    <div><strong>Ανοίγει:</strong> <?php echo $applicationOpenDate !== '' ? htmlspecialchars($applicationOpenDate) : '—'; ?></div>
                                                    <div><strong>Κλείνει:</strong> <?php echo $applicationCloseDate !== '' ? htmlspecialchars($applicationCloseDate) : '—'; ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?php echo $applicationStatusBadge; ?>"><?php echo $applicationStatusLabel; ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $submissionTotal; ?></span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary py-0 px-2 js-edit-application"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editApplicationModal"
                                                    data-application-id="<?php echo $applicationId; ?>"
                                                    data-application-title="<?php echo htmlspecialchars($application['application_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-description="<?php echo htmlspecialchars($application['application_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-open-date="<?php echo htmlspecialchars($applicationOpenDate, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-close-date="<?php echo htmlspecialchars($applicationCloseDate, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-application-status="<?php echo htmlspecialchars($applicationStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                                >
                                                    <i class="fas fa-edit me-1"></i>Επεξεργασία
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-danger py-0 px-2 js-open-delete-modal"
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
                                    <th>Μαθητής</th>
                                    <th>Τάξη</th>
                                    <th>Γονέας</th>
                                    <th>Ημ. Υποβολής</th>
                                    <th>Συνημμένα</th>
                                    <th>Κατάσταση</th>
                                    <th>Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selectedSubmissions as $submission): ?>
                                    <?php
                                        $formData = json_decode($submission['submission_data'] ?? '{}', true) ?? [];
                                        $studentName = $formData['student_name'] ?? '—';
                                        $studentClass = $formData['class'] ?? '—';
                                        $parentName = $formData['parent_name'] ?? trim(($submission['name'] ?? '') . ' ' . ($submission['surname'] ?? ''));
                                        $submittedAt = !empty($submission['submitted_at']) ? date('d/m/Y H:i', strtotime($submission['submitted_at'])) : '—';
                                        $statusMeta = getSubmissionStatusMeta((string)$submission['sub_status']);
                                        $submissionFileUrl = !empty($submission['file_path']) ? getDocumentPublicUrl((string)$submission['file_path']) : '';
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars((string)$studentName); ?></td>
                                        <td><?php echo htmlspecialchars((string)$studentClass); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars((string)$parentName); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars((string)($submission['email'] ?? '')); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$submittedAt); ?></td>
                                        <td>
                                            <?php if ($submissionFileUrl !== ''): ?>
                                                <a href="<?php echo htmlspecialchars($submissionFileUrl); ?>" target="_blank" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(basename((string)$submission['file_path'])); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?php echo htmlspecialchars($statusMeta['badge']); ?>"><?php echo htmlspecialchars($statusMeta['label']); ?></span></td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center gap-2 js-submission-action-form" data-student-name="<?php echo htmlspecialchars((string)$studentName, ENT_QUOTES, 'UTF-8'); ?>" data-parent-name="<?php echo htmlspecialchars((string)$parentName, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="update_submission_status">
                                                <input type="hidden" name="application_id" value="<?php echo (int)$submission['application_id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$submission['user_id']; ?>">
                                                <input type="hidden" name="return_view_submissions" value="<?php echo $selectedApplicationId; ?>">
                                                <input type="hidden" name="return_scroll_y" value="0">

                                                <select name="sub_status" class="form-select form-select-sm" required>
                                                    <option value="" <?php echo ($submission['sub_status'] !== 'approved' && $submission['sub_status'] !== 'rejected') ? 'selected' : ''; ?> disabled>Επιλογή...</option>
                                                    <option value="approved" <?php echo ($submission['sub_status'] === 'approved') ? 'selected' : ''; ?>>Αποδοχή</option>
                                                    <option value="rejected" <?php echo ($submission['sub_status'] === 'rejected') ? 'selected' : ''; ?>>Απόρριψη</option>
                                                    <option value="delete">Διαγραφή Υποβολής</option>
                                                </select>

                                                <button type="submit" class="btn btn-sm btn-primary-custom">Αποθήκευση</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="createApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" enctype="multipart/form-data" id="create_application_form">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Νέα Αίτηση</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-12 col-lg-7">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label"><strong>Τίτλος *</strong></label>
                                    <input type="text" name="application_title" class="form-control form-control-custom" required>
                                </div>

                                <div>
                                    <label class="form-label"><strong>Περιγραφή</strong></label>
                                    <textarea name="application_description" class="form-control form-control-custom" rows="5"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body vstack gap-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label"><strong>Ημερομηνία Ανοίγματος</strong></label>
                                            <input type="date" name="application_open_date_ui" id="create_application_open_date" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label"><strong>Ημερομηνία Κλεισίματος</strong></label>
                                            <input type="date" name="application_close_date_ui" id="create_application_close_date" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label"><strong>Κατάσταση</strong></label>
                                            <select name="application_status_ui" id="create_application_status" class="form-select">
                                                <option value="active" selected>Ενεργή</option>
                                                <option value="inactive">Ανενεργή</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Προαιρετική Εικόνα</strong></label>
                                        <input type="file" name="application_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Αρχείο Οδηγιών (PDF/DOC)</strong></label>
                                        <input type="file" name="instruction_file" class="form-control" accept=".pdf,.doc,.docx">
                                    </div>

                                    <div>
                                        <button type="submit" class="btn btn-success w-100 js-create-publish-btn">
                                            <i class="fas fa-bullhorn me-1"></i>Δημοσίευση Αίτησης
                                        </button>
                                        <div class="form-text">Με τη δημοσίευση η κατάσταση ορίζεται αυτόματα σε «Ενεργή».</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Ακύρωση</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" enctype="multipart/form-data" id="edit_application_form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="application_id" id="edit_application_id">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Επεξεργασία Αίτησης</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-12 col-lg-7">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label"><strong>Τίτλος *</strong></label>
                                    <input type="text" name="application_title" id="edit_application_title" class="form-control form-control-custom" required>
                                </div>

                                <div>
                                    <label class="form-label"><strong>Περιγραφή</strong></label>
                                    <textarea name="application_description" id="edit_application_description" class="form-control form-control-custom" rows="5"></textarea>
                                </div>

                                <div>
                                    <label class="form-label"><strong>Οδηγίες για γονείς</strong></label>
                                    <textarea name="application_instructions_ui" class="form-control" rows="4" placeholder="UI-only πεδίο για μελλοντική σύνδεση."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body vstack gap-3">
                                    <div>
                                        <label class="form-label"><strong>Ημερομηνία Ανοίγματος</strong></label>
                                        <input type="date" name="application_open_date_ui" id="edit_application_open_date" class="form-control">
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Ημερομηνία Κλεισίματος</strong></label>
                                        <input type="date" name="application_close_date_ui" id="edit_application_close_date" class="form-control">
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Κατάσταση</strong></label>
                                        <select name="application_status_ui" id="edit_application_status" class="form-select">
                                            <option value="active" selected>Ενεργή</option>
                                            <option value="inactive">Ανενεργή</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Νέα Εικόνα</strong></label>
                                        <input type="file" name="application_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Νέο Αρχείο Οδηγιών</strong></label>
                                        <input type="file" name="instruction_file" class="form-control" accept=".pdf,.doc,.docx">
                                    </div>

                                    <div>
                                        <label class="form-label"><strong>Πρόσθετα Δικαιολογητικά</strong></label>
                                        <input type="file" name="required_documents[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Αποθήκευση Αλλαγών
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="submissionDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Λεπτομέρειες Υποβολής</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><div class="detail-card"><span>Αίτηση</span><strong id="detail_application_title">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Κατάσταση</span><strong id="detail_status_badge_wrapper">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Μαθητής</span><strong id="detail_student_name">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Τάξη</span><strong id="detail_student_class">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Γονέας</span><strong id="detail_parent_name">—</strong></div></div>
                    <div class="col-md-6"><div class="detail-card"><span>Ημ. Υποβολής</span><strong id="detail_submitted_at">—</strong></div></div>
                </div>

                <div class="card bg-light border-0 mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1">Συνημμένο Αρχείο</h6>
                                <p class="mb-0 text-muted small" id="detail_file_name">Δεν υπάρχει ανεβασμένο αρχείο.</p>
                            </div>
                            <a href="#" id="detail_file_link" target="_blank" class="btn btn-outline-secondary btn-sm d-none">
                                <i class="fas fa-download me-1"></i>Λήψη Αρχείου
                            </a>
                        </div>
                    </div>
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

            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <form method="POST" class="d-flex align-items-center flex-wrap gap-2 mb-0" id="submission_status_form">
                    <input type="hidden" name="action" value="update_submission_status">
                    <input type="hidden" name="application_id" id="detail_application_id_input">
                    <input type="hidden" name="user_id" id="detail_user_id_input">

                    <label for="detail_status_select" class="mb-0 fw-semibold">Κατάσταση</label>
                    <select name="sub_status" id="detail_status_select" class="form-select form-select-sm">
                        <option value="waiting">Υπό Εξέταση</option>
                        <option value="approved">Εγκρίθηκε</option>
                        <option value="rejected">Απορρίφθηκε</option>
                    </select>

                    <button type="submit" class="btn btn-primary-custom btn-sm">
                        <i class="fas fa-save me-1"></i>Ενημέρωση Κατάστασης
                    </button>
                </form>

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

                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση Διαγραφής</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" id="delete_submission_confirm_form">
                <input type="hidden" name="action" value="update_submission_status">
                <input type="hidden" name="application_id" id="delete_submission_application_id">
                <input type="hidden" name="user_id" id="delete_submission_user_id">
                <input type="hidden" name="sub_status" value="delete">
                <input type="hidden" name="return_view_submissions" id="delete_submission_return_view_submissions" value="0">
                <input type="hidden" name="return_scroll_y" id="delete_submission_return_scroll_y" value="0">

                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Επιβεβαίωση Διαγραφής Υποβολής</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
                </div>

                <div class="modal-body text-center">
                    <p class="mb-2 fw-semibold">Θέλετε σίγουρα να διαγράψετε αυτή την υποβολή;</p>
                    <p class="text-muted mb-0" id="delete_submission_details">—</p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="submit" class="btn btn-danger px-4">Ναι</button>
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Όχι</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindDateRangeValidation(openInput, closeInput, form) {
        if (!openInput || !closeInput) return;

        function syncCloseMinDate() {
            var openVal = openInput.value || '';
            closeInput.min = openVal;

            if (openVal && closeInput.value && closeInput.value < openVal) {
                closeInput.value = '';
            }
        }

        openInput.addEventListener('change', syncCloseMinDate);
        syncCloseMinDate();

        if (form) {
            form.addEventListener('submit', function (event) {
                var openVal = openInput.value || '';
                var closeVal = closeInput.value || '';

                if (openVal && closeVal && closeVal < openVal) {
                    event.preventDefault();
                    alert('Η ημερομηνία κλεισίματος δεν μπορεί να είναι πριν από την ημερομηνία ανοίγματος.');
                    closeInput.focus();
                }
            });
        }
    }

    var createOpenDate = document.getElementById('create_application_open_date');
    var createCloseDate = document.getElementById('create_application_close_date');
    var createForm = document.getElementById('create_application_form');
    bindDateRangeValidation(createOpenDate, createCloseDate, createForm);

    var urlParams = new URLSearchParams(window.location.search);
    var scrollYParam = parseInt(urlParams.get('scroll_y') || '0', 10);
    if (!Number.isNaN(scrollYParam) && scrollYParam > 0) {
        window.scrollTo({ top: scrollYParam, behavior: 'auto' });
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
    var editOpenDate = document.getElementById('edit_application_open_date');
    var editCloseDate = document.getElementById('edit_application_close_date');
    var editForm = document.getElementById('edit_application_form');
    bindDateRangeValidation(editOpenDate, editCloseDate, editForm);

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            document.getElementById('edit_application_id').value = button.getAttribute('data-application-id') || '';
            document.getElementById('edit_application_title').value = button.getAttribute('data-application-title') || '';
            document.getElementById('edit_application_description').value = button.getAttribute('data-application-description') || '';
            document.getElementById('edit_application_open_date').value = button.getAttribute('data-application-open-date') || '';
            document.getElementById('edit_application_close_date').value = button.getAttribute('data-application-close-date') || '';
            document.getElementById('edit_application_status').value = button.getAttribute('data-application-status') || 'active';

            if (editOpenDate) {
                editOpenDate.dispatchEvent(new Event('change'));
            }
        });
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
    var deleteSubmissionReturnScroll = document.getElementById('delete_submission_return_scroll_y');
    var deleteSubmissionDetails = document.getElementById('delete_submission_details');

    document.querySelectorAll('.js-submission-action-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var scrollInput = form.querySelector('input[name="return_scroll_y"]');
            if (scrollInput) {
                scrollInput.value = String(window.scrollY || window.pageYOffset || 0);
            }

            var statusSelect = form.querySelector('select[name="sub_status"]');
            if (!statusSelect || statusSelect.value !== 'delete' || !deleteSubmissionModal) {
                return;
            }

            event.preventDefault();

            var applicationIdInput = form.querySelector('input[name="application_id"]');
            var userIdInput = form.querySelector('input[name="user_id"]');
            var returnViewInput = form.querySelector('input[name="return_view_submissions"]');
            var studentName = form.getAttribute('data-student-name') || '—';
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
            if (deleteSubmissionReturnScroll) {
                deleteSubmissionReturnScroll.value = String(window.scrollY || window.pageYOffset || 0);
            }
            if (deleteSubmissionDetails) {
                deleteSubmissionDetails.textContent = 'Μαθητής: ' + studentName + ' | Γονέας: ' + parentName;
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

    function getSubmissionNoteKey(applicationId, userId) {
        return 'admin_submission_note_' + String(applicationId) + '_' + String(userId);
    }

    var currentSubmissionNoteKey = '';
    var detailFields = document.getElementById('detail_submission_fields');
    var detailFileLink = document.getElementById('detail_file_link');
    var detailFileName = document.getElementById('detail_file_name');
    var noteField = document.getElementById('submission_internal_note');
    var noteSaveButton = document.getElementById('save_submission_note_btn');

    document.querySelectorAll('.js-view-submission').forEach(function (button) {
        button.addEventListener('click', function () {
            var raw = button.getAttribute('data-submission');
            if (!raw) return;

            try {
                var data = JSON.parse(raw);
                document.getElementById('detail_application_title').textContent = data.application_title || '—';
                document.getElementById('detail_student_name').textContent = data.student_name || '—';
                document.getElementById('detail_student_class').textContent = data.student_class || '—';
                document.getElementById('detail_parent_name').textContent = data.parent_name || data.parent_account || '—';
                document.getElementById('detail_submitted_at').textContent = data.submitted_at || '—';
                document.getElementById('detail_application_id_input').value = data.application_id || '';
                document.getElementById('detail_user_id_input').value = data.user_id || '';
                document.getElementById('detail_status_select').value = data.status || 'waiting';
                document.getElementById('detail_status_badge_wrapper').innerHTML = '<span class="badge ' + (data.status_badge || 'bg-secondary') + '">' + (data.status_label || data.status || '—') + '</span>';

                if (data.file_url) {
                    detailFileLink.href = data.file_url;
                    detailFileLink.classList.remove('d-none');
                    detailFileName.textContent = data.file_name || 'Λήψη αρχείου';
                } else {
                    detailFileLink.href = '#';
                    detailFileLink.classList.add('d-none');
                    detailFileName.textContent = 'Δεν υπάρχει ανεβασμένο αρχείο.';
                }

                var fields = data.submission_data || {};
                var html = '';
                Object.keys(fields).forEach(function (key) {
                    if (key === '_formType' || key === '_category') return;
                    var label = key.replace(/_/g, ' ');
                    html += '<div class="detail-field-item"><span>' + label + '</span><strong>' + String(fields[key] || '—') + '</strong></div>';
                });
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
</script>
</body>
</html>
