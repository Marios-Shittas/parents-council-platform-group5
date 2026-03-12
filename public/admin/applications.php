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

$documentsUploadDir = __DIR__ . '/../../storage/uploads/application-documents/';
if (!is_dir($documentsUploadDir)) {
    mkdir($documentsUploadDir, 0777, true);
}

/*
|--------------------------------------------------------------------------
| POST actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['application_title'] ?? '');
        $description = trim($_POST['application_description'] ?? '');

        if ($title === '') {
            $message = 'Ο τίτλος της αίτησης είναι υποχρεωτικός.';
            $messageType = 'danger';
        } else {
            if ($applicationsService->createApplication($title, $description)) {
                $message = 'Η αίτηση δημιουργήθηκε επιτυχώς!';
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

        if ($application_id > 0 && $title !== '') {
            if ($applicationsService->updateApplication($application_id, $title, $description)) {
                $message = 'Η αίτηση ενημερώθηκε επιτυχώς!';
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
                $dbPath = 'storage/uploads/application-documents/' . $newFileName;

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
                $filePath = __DIR__ . '/../../' . $doc['file_path'];
                if (file_exists($filePath)) {
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

        if ($application_id > 0 && $user_id > 0 && in_array($sub_status, ['waiting', 'approved', 'rejected'], true)) {
            if ($applicationsService->updateSubmissionStatus($application_id, $user_id, $sub_status)) {
                $message = 'Η κατάσταση της υποβολής ενημερώθηκε επιτυχώς!';
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά την ενημέρωση της υποβολής.';
                $messageType = 'danger';
            }
        }
    }

    $_SESSION['flash_message'] = $message ?: 'Η ενέργεια ολοκληρώθηκε.';
    $_SESSION['flash_message_type'] = $messageType ?: 'info';

    header('Location: applications.php');
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
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_applications.css">

    <title>Διαχείριση Αιτήσεων - Admin</title>
</head>
<body>


<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
        </a>

        <div class="admin-header">
            <h1><i class="fas fa-file-alt mr-2"></i>Διαχείριση Αιτήσεων</h1>
            <button class="btn btn-primary-custom" data-toggle="modal" data-target="#createModal">
                <i class="fas fa-plus mr-1"></i>Νέα Αίτηση
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <h4 class="mb-4"><i class="fas fa-list mr-2"></i>Λίστα Αιτήσεων</h4>

                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Δεν υπάρχουν αιτήσεις</h3>
                        <p>Δημιουργήστε μία νέα αίτηση.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <div class="border rounded p-3 mb-3 application-box">
                            <form method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">

                                <div class="form-group">
                                    <label><strong>Τίτλος</strong></label>
                                    <input
                                        type="text"
                                        name="application_title"
                                        class="form-control form-control-custom"
                                        value="<?php echo htmlspecialchars($application['application_title']); ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label><strong>Περιγραφή</strong></label>
                                    <textarea
                                        name="application_description"
                                        class="form-control form-control-custom"
                                        rows="4"
                                    ><?php echo htmlspecialchars($application['application_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="d-flex" style="gap:10px;">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="fas fa-save mr-1"></i>Αποθήκευση
                                    </button>
                                </div>
                            </form>

                            <form method="POST" class="mt-2" onsubmit="return confirm('Είστε σίγουροι για διαγραφή αυτής της αίτησης;')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash mr-1"></i>Διαγραφή
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <h4 class="mb-4"><i class="fas fa-file-upload mr-2"></i>Ανέβασμα Εγγράφου Αίτησης</h4>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_document">

                    <div class="form-group">
                        <label><strong>Επιλογή Αίτησης</strong></label>
                        <select name="application_id" class="form-control form-control-custom" required>
                            <option value="">Επιλέξτε...</option>
                            <?php foreach ($applications as $application): ?>
                                <option value="<?php echo $application['application_id']; ?>">
                                    <?php echo htmlspecialchars($application['application_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><strong>Αρχείο</strong></label>
                        <input type="file" name="document_file" class="form-control-file" required>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-upload mr-1"></i>Ανέβασμα Εγγράφου
                    </button>
                </form>
            </div>
        </div>

        <div class="card card-custom mb-4">
            <div class="card-body">
                <h4 class="mb-4"><i class="fas fa-paperclip mr-2"></i>Έγγραφα Αιτήσεων</h4>

                <?php if (empty($documents)): ?>
                    <div class="empty-state small-empty">
                        <i class="fas fa-folder-open"></i>
                        <p>Δεν υπάρχουν ανεβασμένα έγγραφα.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Αίτηση</th>
                                    <th>Έγγραφο</th>
                                    <th>Ενέργεια</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['application_title']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($document['file_path']); ?>" target="_blank">
                                                <?php echo htmlspecialchars(basename($document['file_path'])); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Διαγραφή αυτού του εγγράφου;')">
                                                <input type="hidden" name="action" value="delete_document">
                                                <input type="hidden" name="ap_document_id" value="<?php echo $document['ap_document_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

        <div class="card card-custom">
            <div class="card-body">
                <h4 class="mb-4"><i class="fas fa-users-cog mr-2"></i>Υποβολές Γονέων</h4>

                <?php if (empty($submissions)): ?>
                    <div class="empty-state small-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Δεν υπάρχουν υποβολές.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Γονέας</th>
                                    <th>Email</th>
                                    <th>Αίτηση</th>
                                    <th>Μαθητής</th>
                                    <th>Τάξη</th>
                                    <th>Ημ/νία</th>
                                    <th>Αρχείο</th>
                                    <th>Κατάσταση</th>
                                    <th>Ενημέρωση</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $submission):
                                    $formData     = json_decode($submission['submission_data'] ?? '{}', true) ?? [];
                                    $studentName  = htmlspecialchars($formData['student_name'] ?? '—');
                                    $studentClass = htmlspecialchars($formData['class']         ?? '—');
                                    $submittedAt  = !empty($submission['submitted_at'])
                                        ? date('d/m/Y H:i', strtotime($submission['submitted_at']))
                                        : '—';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($submission['name'] . ' ' . $submission['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($submission['email']); ?></td>
                                        <td><?php echo htmlspecialchars($submission['application_title']); ?></td>
                                        <td><?php echo $studentName; ?></td>
                                        <td><?php echo $studentClass; ?></td>
                                        <td><small><?php echo $submittedAt; ?></small></td>
                                        <td>
                                            <?php if (!empty($submission['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars(basename($submission['file_path'])); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($submission['sub_status']); ?>">
                                                <?php echo htmlspecialchars($submission['sub_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center" style="gap:8px;">
                                                <input type="hidden" name="action" value="update_submission_status">
                                                <input type="hidden" name="application_id" value="<?php echo $submission['application_id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $submission['user_id']; ?>">

                                                <select name="sub_status" class="form-control form-control-sm">
                                                    <option value="waiting" <?php echo $submission['sub_status'] === 'waiting' ? 'selected' : ''; ?>>waiting</option>
                                                    <option value="approved" <?php echo $submission['sub_status'] === 'approved' ? 'selected' : ''; ?>>approved</option>
                                                    <option value="rejected" <?php echo $submission['sub_status'] === 'rejected' ? 'selected' : ''; ?>>rejected</option>
                                                </select>

                                                <button type="submit" class="btn btn-sm btn-primary-custom">
                                                    <i class="fas fa-save"></i>
                                                </button>
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

<div class="modal fade modal-custom" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Νέα Αίτηση</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Τίτλος *</strong></label>
                        <input type="text" name="application_title" class="form-control form-control-custom" required>
                    </div>

                    <div class="form-group">
                        <label><strong>Περιγραφή</strong></label>
                        <textarea name="application_description" class="form-control form-control-custom" rows="5"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save mr-1"></i>Δημιουργία
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
