<?php
/**
 * Parent Applications Page
 * Displays all applications and allows parent users to submit them.
 */

require_once __DIR__ . '/../../services/ApplicationsService.php';
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../includes/PublicApplicationsViewHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the service
$applicationsService = new ApplicationsService();

$isAuthenticatedParent = isset($_SESSION['user_id'], $_SESSION['role'])
    && (int)$_SESSION['user_id'] > 0
    && (string)$_SESSION['role'] === 'parent';

$guestIdentity = ['user_id' => 0, 'email' => ''];
if (!$isAuthenticatedParent) {
    $guestIdentity = PublicApplicationsViewHelper::ensurePublicGuestSubmissionIdentity();
}

$user_id = $isAuthenticatedParent ? (int)$_SESSION['user_id'] : (int)($guestIdentity['user_id'] ?? 0);
$canSubmitApplications = $user_id > 0;
$canShowSubmissions = $isAuthenticatedParent && $user_id > 0;
$currentUserEmail = $isAuthenticatedParent
    ? trim((string)($_SESSION['email'] ?? ''))
    : trim((string)($guestIdentity['email'] ?? ''));
$showGuestRegistrationReminder = !$isAuthenticatedParent;
$loginUrl = SiteContext::loginUrl();
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
                'message' => 'Δεν είναι δυνατή η προετοιμασία προφίλ υποβολής αυτή τη στιγμή. Παρακαλώ ανανεώστε τη σελίδα και δοκιμάστε ξανά.',
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
            echo json_encode(['success' => false, 'message' => 'Έχετε ήδη υποβάλει αυτή την αίτηση.']);
            exit;
        }

        $submissionMode = PublicApplicationsViewHelper::normalizeSubmissionMode($decoded['_submission_mode'] ?? ($_POST['submission_mode'] ?? 'upload'));
        $decoded['_submission_mode'] = $submissionMode;

        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $maxSubmissionFiles = 4;
        $incomingFiles = [];

        if (isset($_FILES['submission_files'])) {
            $incomingFiles = array_merge($incomingFiles, PublicApplicationsViewHelper::normalizeUploadedSubmissionFiles((array)$_FILES['submission_files']));
        }
        if (isset($_FILES['submission_file'])) {
            $incomingFiles = array_merge($incomingFiles, PublicApplicationsViewHelper::normalizeUploadedSubmissionFiles((array)$_FILES['submission_file']));
        }

        if ($submissionMode === 'upload' && count($incomingFiles) === 0) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Παρακαλώ ανεβάστε τουλάχιστον ένα αρχείο για την υποβολή της αίτησης.']);
            exit;
        }

        if ($submissionMode === 'manual') {
            $applicationManualFields = $applicationsService->getApplicationFormFields($application_id);
            $manualValidationMessage = PublicApplicationsViewHelper::validateManualSubmissionPayload($decoded, is_array($applicationManualFields) ? $applicationManualFields : []);
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
            $originalDisplayName = PublicApplicationsViewHelper::getUploadedSubmissionDisplayName((string)($file['name'] ?? ''));

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

            $submissionDisplayNames = PublicApplicationsViewHelper::loadSubmissionFileDisplayNames();
            $submissionDisplayNamesChanged = false;
            foreach ($uploadedOriginalNames as $uploadedDbPath => $uploadedDisplayName) {
                $uploadedDbPath = trim((string)$uploadedDbPath);
                $uploadedDisplayName = PublicApplicationsViewHelper::getUploadedSubmissionDisplayName((string)$uploadedDisplayName);
                if ($uploadedDbPath === '' || $uploadedDisplayName === '') {
                    continue;
                }

                if (!isset($submissionDisplayNames[$uploadedDbPath]) || (string)$submissionDisplayNames[$uploadedDbPath] !== $uploadedDisplayName) {
                    $submissionDisplayNames[$uploadedDbPath] = $uploadedDisplayName;
                    $submissionDisplayNamesChanged = true;
                }
            }

            if ($submissionDisplayNamesChanged) {
                PublicApplicationsViewHelper::saveSubmissionFileDisplayNames($submissionDisplayNames);
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
                    'url' => SiteContext::resolveContentUrl($uploadedDbPath),
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
        if (!$canSubmitApplications) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Δεν είναι δυνατή η προετοιμασία προφίλ υποβολής αυτή τη στιγμή. Παρακαλώ ανανεώστε τη σελίδα και δοκιμάστε ξανά.',
            ]);
            exit;
        }

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

        if ($isAuthenticatedParent && $applicationsService->hasUserSubmitted($application_id, $user_id)) {
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
            $uploadedDisplayName = PublicApplicationsViewHelper::getUploadedSubmissionDisplayName((string)$file['name']);

            if (!move_uploaded_file((string)$file['tmp_name'], $uploadedAbsPath)) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Η αποθήκευση του αρχείου απέτυχε.']);
                exit;
            }
        }

        if ($applicationsService->createSubmissionWithDataAndFile($application_id, $user_id, $raw, $uploadedDbPath)) {
            if ($uploadedDbPath !== null && $uploadedDbPath !== '' && $uploadedDisplayName !== '') {
                $submissionDisplayNames = PublicApplicationsViewHelper::loadSubmissionFileDisplayNames();
                $submissionDisplayNames[(string)$uploadedDbPath] = $uploadedDisplayName;
                PublicApplicationsViewHelper::saveSubmissionFileDisplayNames($submissionDisplayNames);
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
    if (!$canSubmitApplications) {
        $message = 'Δεν είναι δυνατή η προετοιμασία προφίλ υποβολής αυτή τη στιγμή. Παρακαλώ ανανεώστε τη σελίδα και δοκιμάστε ξανά.';
        $messageType = 'warning';
    } else {
        $application_id = (int) ($_POST['application_id'] ?? 0);

        if ($application_id <= 0) {
            $message = 'Μη έγκυρη αίτηση.';
            $messageType = 'warning';
        } else {
            // Check if user already submitted this application
            if ($isAuthenticatedParent && $applicationsService->hasUserSubmitted($application_id, $user_id)) {
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
                        $uploadedDisplayName = PublicApplicationsViewHelper::getUploadedSubmissionDisplayName((string)$file['name']);

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            if ($applicationsService->createSubmission($application_id, $user_id, $dbPath)) {
                                if ($uploadedDisplayName !== '') {
                                    $submissionDisplayNames = PublicApplicationsViewHelper::loadSubmissionFileDisplayNames();
                                    $submissionDisplayNames[$dbPath] = $uploadedDisplayName;
                                    PublicApplicationsViewHelper::saveSubmissionFileDisplayNames($submissionDisplayNames);
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
}

/*
 |------------------------------------------------------------
 | Get applications
 |------------------------------------------------------------
*/
$applications = $applicationsService->getAllApplications();
$applicationUiMeta = PublicApplicationsViewHelper::loadApplicationUiMeta();

/*
 |------------------------------------------------------------
 | Get documents grouped by application
 |------------------------------------------------------------
*/
$documentsByApplication = $applicationsService->getDocumentsByApplication();
$applicationDocumentDisplayNamesByPath = PublicApplicationsViewHelper::loadApplicationDocumentDisplayNames();

/*
 |------------------------------------------------------------
 | My submissions
 |------------------------------------------------------------
*/
$mySubmissions = $canShowSubmissions ? $applicationsService->getUserSubmissions($user_id) : [];
$submissionFileDisplayNamesByPath = PublicApplicationsViewHelper::loadSubmissionFileDisplayNames();
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
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/applications.css'); ?>?v=<?php echo (int)(@filemtime(__DIR__ . '/../../../public/assets/css/user_css/applications.css') ?: time()); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/applications-inline-overrides.css'); ?>?v=<?php echo (int)(@filemtime(__DIR__ . '/../../../public/assets/css/user_css/applications-inline-overrides.css') ?: time()); ?>">

    <title>Αιτήσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body data-applications-can-submit="<?php echo $canSubmitApplications ? '1' : '0'; ?>" data-applications-login-url="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>">

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
                                $openDateRaw = (string)($appMeta['open_date'] ?? ($application['open_date'] ?? ''));
                                $closeDateRaw = (string)($appMeta['close_date'] ?? ($appMeta['deadline'] ?? ($application['due_date'] ?? '')));
                                $openDateFormatted = PublicApplicationsViewHelper::formatUiDate($openDateRaw);
                                $closeDateFormatted = PublicApplicationsViewHelper::formatUiDate($closeDateRaw);
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
                                $primaryInstructionUrl = $primaryInstructionPath !== '' ? SiteContext::resolveContentUrl($primaryInstructionPath) : '';
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
                                    $applicationDateDisplay = '—';
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

                    <?php if (!$canShowSubmissions): ?>
                        <div class="alert alert-light border mb-0" role="alert">
                            <?php if ($isAuthenticatedParent): ?>
                                Δεν ήταν δυνατή η φόρτωση στοιχείων υποβολών. Παρακαλούμε δοκιμάστε ξανά από
                                <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="alert-link">τη σελίδα εισόδου</a>.
                            <?php else: ?>
                                Οι υποβολές μου είναι διαθέσιμες μόνο μετά από είσοδο με λογαριασμό γονέα.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
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
                                                $displayName = PublicApplicationsViewHelper::getUploadedSubmissionDisplayName((string)$displayName);
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
                                                            <a href="<?php echo htmlspecialchars(SiteContext::resolveContentUrl($submissionFilePath)); ?>" target="_blank" rel="noopener noreferrer" class="submission-file-link d-block small mb-1">
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

                <?php if ($showGuestRegistrationReminder): ?>
                    <div class="application-registration-reminder" role="note">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        Αφού συμπληρώσετε την αίτηση, θα χρειαστεί να κάνετε εγγραφή.
                    </div>
                <?php endif; ?>
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
<script src="<?php echo SiteContext::assetUrl('js/applications.js'); ?>?v=<?php echo (int)(@filemtime(__DIR__ . '/../../../public/assets/js/applications.js') ?: time()); ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
