<?php
session_start();
require_once __DIR__ . '/../app/config/db.php';

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
        $message = 'Invalid application.';
        $messageType = 'danger';
    } else {
        $checkStmt = $conn->prepare("SELECT application_id FROM Submissions WHERE application_id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $application_id, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = 'You have already submitted this application.';
            $messageType = 'warning';
        } else {
            if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
                $message = 'Please upload a valid file.';
                $messageType = 'danger';
            } else {
                $file = $_FILES['submission_file'];
                $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $message = 'Allowed file types: pdf, doc, docx, jpg, jpeg, png.';
                    $messageType = 'danger';
                } else {
                    $newFileName = 'submission_' . $user_id . '_' . $application_id . '_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $newFileName;
                    $dbPath = 'storage/uploads/submissions/' . $newFileName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $insertStmt = $conn->prepare("
                            INSERT INTO Submissions (application_id, user_id, file_path, sub_status)
                            VALUES (?, ?, ?, 'waiting')
                        ");
                        $insertStmt->bind_param("iis", $application_id, $user_id, $dbPath);

                        if ($insertStmt->execute()) {
                            $message = 'Application submitted successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Database error while saving submission.';
                            $messageType = 'danger';
                        }
                    } else {
                        $message = 'File upload failed.';
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
$applications = [];
$appSql = "
    SELECT 
        a.application_id,
        a.application_title,
        a.application_description,
        COUNT(ad.ap_document_id) AS document_count
    FROM Applications a
    LEFT JOIN ApplicationsDocuments ad ON a.application_id = ad.application_id
    GROUP BY a.application_id
    ORDER BY a.application_id DESC
";
$appResult = $conn->query($appSql);
while ($row = $appResult->fetch_assoc()) {
    $applications[] = $row;
}

/*
 |------------------------------------------------------------
 | Get documents grouped by application
 |------------------------------------------------------------
*/
$documentsByApplication = [];
$docsResult = $conn->query("SELECT ap_document_id, application_id, file_path FROM ApplicationsDocuments ORDER BY ap_document_id DESC");
while ($doc = $docsResult->fetch_assoc()) {
    $documentsByApplication[$doc['application_id']][] = $doc;
}

/*
 |------------------------------------------------------------
 | My submissions
 |------------------------------------------------------------
*/
$mySubmissions = [];
$subStmt = $conn->prepare("
    SELECT 
        s.application_id,
        s.file_path,
        s.sub_status,
        a.application_title
    FROM Submissions s
    INNER JOIN Applications a ON s.application_id = a.application_id
    WHERE s.user_id = ?
    ORDER BY a.application_title ASC
");
$subStmt->bind_param("i", $user_id);
$subStmt->execute();
$subRes = $subStmt->get_result();

while ($row = $subRes->fetch_assoc()) {
    $mySubmissions[] = $row;
}
?>

<?php include __DIR__ . '/../app/includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/applications.css">

<div class="applications-hero">
    <div class="container">
        <h1>Applications</h1>
        <p>
            This page allows parents and guardians to view available school applications
            and submit them electronically.
        </p>
    </div>
</div>

<div class="container py-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card applications-card mb-4">
                <div class="card-body p-4">
                    <h3 class="section-title">Available Applications</h3>

                    <?php if (empty($applications)): ?>
                        <div class="alert alert-info mb-0">No applications available.</div>
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
                                                <?php echo (int)$application['document_count']; ?> docs
                                            </span>
                                        </div>

                                        <p class="application-description">
                                            <?php echo nl2br(htmlspecialchars($application['application_description'] ?? 'No description available.')); ?>
                                        </p>

                                        <?php if (!empty($documentsByApplication[$application['application_id']])): ?>
                                            <div class="mb-3">
                                                <strong>Attached documents:</strong>
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
                                            Submit Application
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
                    <h3 class="section-title">My Submissions</h3>

                    <?php if (empty($mySubmissions)): ?>
                        <div class="alert alert-secondary mb-0">You have not submitted any applications yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table submissions-table">
                                <thead>
                                    <tr>
                                        <th>Application</th>
                                        <th>Submitted File</th>
                                        <th>Status</th>
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
                    <h4 class="section-title">Instructions</h4>
                    <ul class="instructions-list mb-0">
                        <li>Read the application description carefully before submitting.</li>
                        <li>Upload the correct file and required supporting document.</li>
                        <li>Each parent can submit only one file per application type.</li>
                        <li>The school administration will review all submissions.</li>
                        <li>Your submission status will be updated by the administrator.</li>
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
                <h5 class="modal-title">Submit Application</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="application_id" id="modal_application_id">

                <div class="form-group">
                    <label><strong>Application Title</strong></label>
                    <input type="text" id="modal_application_title" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label><strong>Description</strong></label>
                    <textarea id="modal_application_description" class="form-control" rows="4" readonly></textarea>
                </div>

                <div class="upload-box">
                    <label for="submission_file"><strong>Upload File</strong></label>
                    <input type="file" name="submission_file" id="submission_file" class="form-control-file" required>
                    <small class="text-muted d-block mt-2" id="selectedFileName">No file selected</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" name="submit_application" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/applications.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>