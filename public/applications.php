<?php
/**
 * Public Applications Page
 * Displays all applications and allows users to submit them
 */

session_start();
require_once __DIR__ . '/../app/services/ApplicationsService.php';

// Initialize the service
$applicationsService = new ApplicationsService();

/*
 |------------------------------------------------------------
 | Προσωρινό demo user id μόνο για testing submissions
 | ΒΓΑΛ' ΤΟ όταν κάνετε login system
 |------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$user_id = (int) $_SESSION['user_id'];

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

        if ($applicationsService->createSubmissionWithData($application_id, $user_id, $raw)) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Η αίτηση υποβλήθηκε επιτυχώς.']);
        } else {
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

$uploadDir = __DIR__ . '/../storage/uploads/submissions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
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

<?php include __DIR__ . '/../app/includes/header.php'; ?>

<!-- Google fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/applications.css">

<div class="applications-hero">
    <div class="container">
        <h1><i class="fas fa-file-alt mr-2"></i>Αιτήσεις</h1>
        <p class="lead">Υποβάλλετε τις δικές σας αιτήσεις και παρακολουθήστε την κατάστασή τους</p>
    </div>
</div>

<div class="container py-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Κλείσιμο">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
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
                            ?>
                                <div class="col-md-6 mb-4">
                                    <div class="application-item h-100 app-card-wrapper"
                                         id="app-card-<?php echo $appId; ?>"
                                         data-app-id="<?php echo $appId; ?>"
                                         data-app-index="<?php echo $appIndex; ?>"
                                         data-db-applied="<?php echo $isDbApplied ? 'true' : 'false'; ?>">

                                        <!-- Status badge & category tag – filled by JS -->
                                        <div class="app-meta-top d-flex justify-content-between align-items-center mb-2">
                                            <span class="js-status-placeholder"></span>
                                            <span class="js-category-placeholder"></span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h5 class="application-title">
                                                <?php echo htmlspecialchars($application['application_title']); ?>
                                            </h5>
                                            <?php $dc = (int)$application['document_count']; ?>
                                            <?php if ($dc > 0): ?>
                                                <span class="doc-badge">
                                                    <i class="fas fa-paperclip mr-1"></i><?php echo $dc; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="application-description">
                                            <?php echo nl2br(htmlspecialchars($application['application_description'] ?? 'Δεν υπάρχει διαθέσιμη περιγραφή.')); ?>
                                        </p>

                                        <!-- Open / close date row – filled by JS -->
                                        <div class="js-dates-placeholder mb-3"></div>

                                        <?php if (!empty($documentsByApplication[$application['application_id']])): ?>
                                            <div class="attachments-box mb-3">
                                                <div class="attachments-label"><i class="fas fa-paperclip mr-1"></i> Συνημμένα έγγραφα</div>
                                                <ul class="attachments-list mt-2 mb-0">
                                                    <?php foreach ($documentsByApplication[$application['application_id']] as $doc): ?>
                                                        <?php
                                                            $rawPath = $doc['file_path'];
                                                            $prefix = '/parents-council-platform-group5/public/assets/Applications_docs/';
                                                            $pos = strpos($rawPath, $prefix);
                                                            if ($pos !== false) {
                                                                $rest = substr($rawPath, $pos + strlen($prefix));
                                                                $rest = explode($prefix, $rest)[0];
                                                                $cleanPath = $prefix . $rest;
                                                            } else {
                                                                $cleanPath = $rawPath;
                                                            }
                                                        ?>
                                                        <li>
                                                            <a href="<?php echo htmlspecialchars($cleanPath); ?>" target="_blank">
                                                                <i class="fas fa-file-pdf mr-1 text-danger"></i><?php echo htmlspecialchars(basename($cleanPath)); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <button
                                            class="btn btn-primary submit-btn mt-auto"
                                            data-toggle="modal"
                                            data-target="#submitModal"
                                            data-application-id="<?php echo $appId; ?>"
                                            data-application-title="<?php echo htmlspecialchars($application['application_title']); ?>"
                                            data-application-description="<?php echo htmlspecialchars($application['application_description'] ?? ''); ?>"
                                            data-app-index="<?php echo $appIndex; ?>"
                                        >
                                            <i class="fas fa-paper-plane mr-1"></i> Υποβολή Αίτησης
                                        </button>
                                    </div>
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
                                                <a href="../<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
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

        <div class="col-lg-4">
            <div class="card applications-card">
                <div class="card-body p-4">
                    <h4 class="section-title">Οδηγίες</h4>
                    <ul class="instructions-list mb-0">
                        <li>Διαβάστε προσεκτικά την περιγραφή της αίτησης πριν την υποβάλετε.</li>
                        <li>Ανεβάστε το σωστό αρχείο και τα απαιτούμενα δικαιολογητικά.</li>
                        <li>Κάθε γονέας μπορεί να υποβάλει μόνο ένα αρχείο ανά τύπο αίτησης.</li>
                        <li>Η σχολική διοίκηση θα εξετάσει όλες τις υποβολές.</li>
                        <li>Η κατάσταση της υποβολής σας θα ενημερώνεται από τον διαχειριστή.</li>
                    </ul>
                </div>
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

<!-- ── Success Toast ──────────────────────────────────────────────────── -->
<div id="submission-toast" class="position-fixed" style="bottom:1.5rem;right:1.5rem;z-index:9999;display:none;">
    <div class="alert alert-success shadow py-3 px-4 mb-0">
        <i class="fas fa-check-circle mr-2"></i> Η αίτησή σας υποβλήθηκε επιτυχώς!
    </div>
</div>

<script src="assets/js/applications.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>