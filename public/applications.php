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

$message = '';
$messageType = 'success';

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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/user_css/public-page-header.css">
    <link rel="stylesheet" href="assets/css/user_css/applications.css">

    <title>Αιτήσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body>

<?php include __DIR__ . '/../app/includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Αιτήσεις';
$pageHeaderSubtitle = 'Υποβάλλετε αιτήσεις και παρακολουθήστε εύκολα την πορεία τους.';
$pageHeaderIcon = 'fas fa-file-alt';
$pageHeaderEyebrow = 'Υποβολές Και Έγγραφα';
include __DIR__ . '/../app/includes/public_page_header.php';
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

    <div class="row">
        <div class="col-lg-8">
            <div class="card applications-card mb-4">
                <div class="card-body p-4">
                    <h3 class="section-title">Διαθέσιμες Αιτήσεις</h3>

                    <?php if (empty($applications)): ?>
                        <div class="alert alert-info mb-0">Δεν υπάρχουν διαθέσιμες αιτήσεις.</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($applications as $application): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="application-item h-100">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="application-title">
                                                <?php echo htmlspecialchars($application['application_title']); ?>
                                            </h5>
                                            <span class="doc-badge">
                                                <?php $dc = (int)$application['document_count']; echo $dc . ' ' . ($dc === 1 ? 'έγγραφο' : 'έγγραφα'); ?>
                                            </span>
                                        </div>

                                        <p class="application-description">
                                            <?php echo nl2br(htmlspecialchars($application['application_description'] ?? 'Δεν υπάρχει διαθέσιμη περιγραφή.')); ?>
                                        </p>

                                        <?php if (!empty($documentsByApplication[$application['application_id']])): ?>
                                            <div class="mb-3">
                                                <strong>Συνημμένα έγγραφα:</strong>
                                                <ul class="mt-2 mb-0">
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
                                                                <?php echo htmlspecialchars(basename($cleanPath)); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <button
                                            class="btn btn-primary submit-btn"
                                            data-toggle="modal"
                                            data-target="#submitModal"
                                            data-application-id="<?php echo (int)$application['application_id']; ?>"
                                            data-application-title="<?php echo htmlspecialchars($application['application_title']); ?>"
                                            data-application-description="<?php echo htmlspecialchars($application['application_description'] ?? ''); ?>"
                                        >
                                            Υποβολή Αίτησης
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card applications-card">
                <div class="card-body p-4">
                    <h3 class="section-title">Οι Υποβολές Μου</h3>

                    <?php if (empty($mySubmissions)): ?>
                        <div class="alert alert-secondary mb-0">Δεν έχετε υποβάλει ακόμη καμία αίτηση.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table submissions-table">
                                <thead>
                                    <tr>
                                        <th>Αίτηση</th>
                                        <th>Υποβληθέν Αρχείο</th>
                                        <th>Κατάσταση</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mySubmissions as $submission): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($submission['application_title']); ?></td>
                                            <td>
                                                <a href="../<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars(basename($submission['file_path'])); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars($submission['sub_status']); ?>">
                                                    <?php echo htmlspecialchars($submission['sub_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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

<div class="modal fade" id="submitModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Υποβολή Αίτησης</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="application_id" id="modal_application_id">

                <div class="form-group">
                    <label><strong>Τίτλος Αίτησης</strong></label>
                    <input type="text" id="modal_application_title" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label><strong>Περιγραφή</strong></label>
                    <textarea id="modal_application_description" class="form-control" rows="4" readonly></textarea>
                </div>

                <div class="upload-box">
                    <label for="submission_file"><strong>Μεταφόρτωση Αρχείου</strong></label>
                    <input type="file" name="submission_file" id="submission_file" class="form-control-file" required>
                    <small class="text-muted d-block mt-2" id="selectedFileName">Δεν έχει επιλεγεί αρχείο</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" name="submit_application" class="btn btn-primary">Υποβολή</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/applications.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
