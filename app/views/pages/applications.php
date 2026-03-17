<?php
/**
 * Parent Applications Page
 * Displays all applications and allows parent users to submit them.
 */

require_once __DIR__ . '/../../services/ApplicationsService.php';
require_once __DIR__ . '/../../includes/site_context.php';

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

// Προσωρινό parent id μέχρι να συνδεθεί το πραγματικό auth flow.
$user_id = 1;

$uploadDir = __DIR__ . '/../../../storage/uploads/submissions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
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
        if (!is_array($decoded)) {
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

            if (!move_uploaded_file((string)$file['tmp_name'], $uploadedAbsPath)) {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Η αποθήκευση του αρχείου απέτυχε.']);
                exit;
            }
        }

        if ($applicationsService->createSubmissionWithDataAndFile($application_id, $user_id, $raw, $uploadedDbPath)) {
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

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        if ($applicationsService->createSubmission($application_id, $user_id, $dbPath)) {
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

/*
 |------------------------------------------------------------
 | My submissions
 |------------------------------------------------------------
*/
$mySubmissions = $applicationsService->getUserSubmissions($user_id);
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
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/applications.css'); ?>">

    <style>
        .post-card {
            cursor: pointer;
            overflow: hidden;
            padding: 0;
        }

        .post-image-wrap {
            width: 100%;
            height: 190px;
            overflow: hidden;
            background: #eef3fb;
        }

        .post-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .post-content {
            padding: 16px 16px 18px;
        }

        .post-excerpt {
            color: #6c757d;
            margin-bottom: 12px;
            min-height: 66px;
        }

        .post-read-more {
            color: #0d6efd;
            font-weight: 700;
            font-size: 0.92rem;
        }

        .application-view-attachments a {
            text-decoration: none;
        }

        #submitModal .modal-dialog {
            margin: 0.75rem auto;
        }

        #submitModal .modal-content {
            max-height: calc(100vh - 1.5rem);
        }

        #submitModal .modal-body {
            overflow-y: auto;
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
                                $excerpt = mb_strlen($fullDescription) > 130
                                    ? mb_substr($fullDescription, 0, 130) . '...'
                                    : $fullDescription;
                                $postSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1b4de4"/><stop offset="100%" stop-color="#1aa7ec"/></linearGradient></defs><rect width="1200" height="600" fill="url(#g)"/><circle cx="980" cy="120" r="220" fill="rgba(255,255,255,0.16)"/><circle cx="260" cy="500" r="180" fill="rgba(255,255,255,0.12)"/><rect x="70" y="420" width="420" height="24" rx="12" fill="rgba(255,255,255,0.5)"/><rect x="70" y="460" width="320" height="20" rx="10" fill="rgba(255,255,255,0.45)"/></svg>';
                                $defaultImageSrc = 'data:image/svg+xml;utf8,' . rawurlencode($postSvg);
                                $profileImagePath = (string)($appMeta['image_path'] ?? '');
                                $postImageSrc = $profileImagePath !== ''
                                    ? site_resolve_content_url($profileImagePath)
                                    : $defaultImageSrc;

                                $allDocs = $documentsByApplication[$application['application_id']] ?? [];
                                $filteredDocs = array_values(array_filter($allDocs, function ($doc) use ($profileImagePath) {
                                    $path = (string)($doc['file_path'] ?? '');
                                    if ($profileImagePath !== '' && $path === $profileImagePath) {
                                        return false;
                                    }
                                    return true;
                                }));
                                $attachmentsJson = json_encode($filteredDocs);
                            ?>
                                <div class="col-12 col-md-6 col-lg-4 mb-4">
                                    <article class="application-item h-100 app-card-wrapper post-card"
                                         id="app-card-<?php echo $appId; ?>"
                                         data-app-id="<?php echo $appId; ?>"
                                         data-app-index="<?php echo $appIndex; ?>"
                                         data-db-applied="<?php echo $isDbApplied ? 'true' : 'false'; ?>"
                                         data-application-title="<?php echo htmlspecialchars($application['application_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                         data-application-description="<?php echo htmlspecialchars($fullDescription, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-app-open-date="<?php echo htmlspecialchars($openDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-app-close-date="<?php echo htmlspecialchars($closeDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
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

                                        <p class="application-description">
                                            <?php echo nl2br(htmlspecialchars($excerpt)); ?>
                                        </p>

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
                                    <th>Όνομα Μαθητή</th>
                                    <th>Τάξη</th>
                                    <th>Ημ. Υποβολής</th>
                                    <th>Κατάσταση</th>
                                    <th>Ενέργεια</th>
                                </tr>
                            </thead>
                            <tbody id="submissions-tbody">
                                <?php foreach ($mySubmissions as $submission):
                                    $subStatusMap  = ['waiting' => 'Υπό Εξέταση', 'approved' => 'Εγκρίθηκε', 'rejected' => 'Απορρίφθηκε'];
                                    $subStatusLabel = $subStatusMap[$submission['sub_status']] ?? ucfirst($submission['sub_status']);
                                    $formData      = json_decode($submission['submission_data'] ?? '{}', true) ?? [];
                                    $studentName   = htmlspecialchars($formData['student_name'] ?? '—');
                                    $studentClass  = htmlspecialchars($formData['class']         ?? '—');
                                    $submittedDate = !empty($submission['submitted_at'])
                                        ? date('d/m/Y', strtotime($submission['submitted_at']))
                                        : '—';
                                ?>
                                    <tr data-db-row="1">
                                        <td><strong><?php echo htmlspecialchars($submission['application_title']); ?></strong></td>
                                        <td><?php echo $studentName; ?></td>
                                        <td><?php echo $studentClass; ?></td>
                                        <td><?php echo $submittedDate; ?></td>
                                        <td><span class="sub-status-badge status-<?php echo htmlspecialchars($submission['sub_status']); ?>"><?php echo $subStatusLabel; ?></span></td>
                                        <td>
                                            <?php if (!empty($submission['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars(site_resolve_content_url((string) $submission['file_path'])); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye mr-1"></i>Αρχείο
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary view-db-submission"
                                                        data-sub-data="<?php echo htmlspecialchars($submission['submission_data'] ?? '{}'); ?>"
                                                        data-sub-title="<?php echo htmlspecialchars($submission['application_title']); ?>"
                                                        data-sub-status="<?php echo htmlspecialchars($submission['sub_status']); ?>">
                                                    <i class="fas fa-eye mr-1"></i>Προβολή
                                                </button>
                                            <?php endif; ?>
                                        </td>
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
                <div class="application-view-attachments">
                    <h6 class="mb-2">Συνημμένα</h6>
                    <ul class="mb-0 pl-3" id="application-view-attachments-list"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
                <button type="button" class="btn btn-primary" id="application-view-continue-btn">
                    <i class="fas fa-paper-plane mr-1"></i>Συνέχεια για Υποβολή
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
                    <span id="modal-type-badge" class="category-tag mr-2"></span>
                    <h5 class="modal-title d-inline" id="modal-title">Υποβολή Αίτησης</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="modal-description" class="text-muted small mb-3"></p>
                <div class="row modal-dates-info mb-3">
                    <div class="col-6">
                        <small><i class="fas fa-calendar-plus mr-1 text-success"></i><strong>Άνοιξε:</strong> <span id="modal-open-date"></span></small>
                    </div>
                    <div class="col-6">
                        <small><i class="fas fa-calendar-times mr-1 text-danger"></i><strong>Λήγει:</strong> <span id="modal-close-date"></span></small>
                    </div>
                </div>
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
                <button type="button" id="modal-save-draft-btn" class="btn btn-outline-secondary">
                    <i class="fas fa-save mr-1"></i> Αποθήκευση Πρόχειρου
                </button>
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

<!-- ── Success Toast ──────────────────────────────────────────────────── -->
<div id="submission-toast" class="position-fixed" style="bottom:1.5rem;right:1.5rem;z-index:9999;display:none;">
    <div class="alert alert-success shadow py-3 px-4 mb-0">
        <i class="fas fa-check-circle mr-2"></i> Η αίτησή σας υποβλήθηκε επιτυχώς!
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo site_asset_url('js/applications.js'); ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
