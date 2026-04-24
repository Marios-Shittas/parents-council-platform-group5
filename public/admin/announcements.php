<?php
/**
 * Σελίδα διαχείρισης ανακοινώσεων (admin)
 * Εδώ ο διαχειριστής μπορεί να δημιουργήσει, να αλλάξει, να διαγράψει
 * ανακοινώσεις και να ανεβάσει εικόνες.
 */

require_once __DIR__ . '/../../app/services/AnnouncementsService.php';
require_once __DIR__ . '/../../app/includes/AdminAnnouncementsHelper.php';

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

const ANNOUNCEMENT_IMAGE_LIMIT = 6;

$announcementsService = new AnnouncementsService();
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// Επεξεργασία της φόρμας όταν πατηθεί submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Δημιουργία νέας ανακοίνωσης
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $gdprNotice = trim($_POST['gdpr_notice'] ?? AdminAnnouncementsHelper::getDefaultGdprNotice());
        $announcementDate = $_POST['announcement_date'] ?? date('Y-m-d');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        if (!empty($title)) {
            $announcementId = $announcementsService->createAnnouncement($title, $description, $announcementDate, $publishDate, $gdprNotice);
            
            if ($announcementId) {
                [$uploadedCount, $uploadErrors] = AdminAnnouncementsHelper::uploadImages($announcementsService, $announcementId, ANNOUNCEMENT_IMAGE_LIMIT);
                [$uploadedAttachmentsCount, $attachmentErrors] = AdminAnnouncementsHelper::uploadAttachments($announcementsService, $announcementId);
                
                // Φτιάχνουμε μήνυμα επιτυχίας ανάλογα με το πόσες εικόνες μπήκαν
                if ($uploadedCount > 0 || $uploadedAttachmentsCount > 0) {
                    $message = "Η ανακοίνωση δημιουργήθηκε επιτυχώς";
                    if ($uploadedCount > 0) {
                        $message .= " με {$uploadedCount} εικόνα/ες";
                    }
                    if ($uploadedAttachmentsCount > 0) {
                        $message .= ($uploadedCount > 0 ? ' και ' : ' με ') . "{$uploadedAttachmentsCount} συνημμένο/α";
                    }
                    $message .= '!';
                } else {
                    $message = 'Η ανακοίνωση δημιουργήθηκε επιτυχώς (χωρίς εικόνες ή συνημμένα).';
                }
                
                // Αν υπάρχουν λάθη σε αρχεία, τα δείχνουμε μαζεμένα
                $allUploadErrors = array_merge($uploadErrors, $attachmentErrors);
                if (!empty($allUploadErrors)) {
                    $message .= '<br><strong>Προβλήματα με τα αρχεία:</strong><ul><li>' . implode('</li><li>', $allUploadErrors) . '</li></ul>';
                    $messageType = 'warning';
                } else {
                    $messageType = 'success';
                }
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία της ανακοίνωσης.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Ο τίτλος είναι υποχρεωτικός.';
            $messageType = 'danger';
        }
    }
    
    // Ενημέρωση υπάρχουσας ανακοίνωσης
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $gdprNotice = trim($_POST['gdpr_notice'] ?? AdminAnnouncementsHelper::getDefaultGdprNotice());
        $announcementDate = $_POST['announcement_date'] ?? date('Y-m-d');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        if ($id > 0 && !empty($title)) {
            if ($announcementsService->updateAnnouncement($id, $title, $description, $announcementDate, $publishDate, $gdprNotice)) {
                [$uploadedCount, $uploadErrors] = AdminAnnouncementsHelper::uploadImages($announcementsService, $id, ANNOUNCEMENT_IMAGE_LIMIT);
                [$uploadedAttachmentsCount, $attachmentErrors] = AdminAnnouncementsHelper::uploadAttachments($announcementsService, $id);
                
                // Φτιάχνουμε μήνυμα επιτυχίας ανάλογα με το πόσες εικόνες μπήκαν
                if ($uploadedCount > 0 || $uploadedAttachmentsCount > 0) {
                    $message = "Η ανακοίνωση ενημερώθηκε επιτυχώς";
                    if ($uploadedCount > 0) {
                        $message .= " με {$uploadedCount} νέα/ες εικόνα/ες";
                    }
                    if ($uploadedAttachmentsCount > 0) {
                        $message .= ($uploadedCount > 0 ? ' και ' : ' με ') . "{$uploadedAttachmentsCount} νέο/α συνημμένο/α";
                    }
                    $message .= '!';
                } else {
                    $message = 'Η ανακοίνωση ενημερώθηκε επιτυχώς!';
                }
                
                // Αν υπάρχουν λάθη σε αρχεία, τα δείχνουμε μαζεμένα
                $allUploadErrors = array_merge($uploadErrors, $attachmentErrors);
                if (!empty($allUploadErrors)) {
                    $message .= '<br><strong>Προβλήματα με τα αρχεία:</strong><ul><li>' . implode('</li><li>', $allUploadErrors) . '</li></ul>';
                    $messageType = 'warning';
                } else {
                    $messageType = 'success';
                }
            } else {
                $message = 'Σφάλμα κατά την ενημέρωση της ανακοίνωσης.';
                $messageType = 'danger';
            }
        }
    }
    
    // Διαγραφή ανακοίνωσης
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $imagesToDelete = $id > 0 ? $announcementsService->getImages($id) : [];
        $attachmentsToDelete = $id > 0 ? $announcementsService->getAttachments($id) : [];

        if ($id > 0 && $announcementsService->deleteAnnouncement($id)) {
            foreach ($imagesToDelete as $image) {
                $filePath = AdminAnnouncementsHelper::resolveAssetFilePath($image['image_path'] ?? '', 'image');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            foreach ($attachmentsToDelete as $attachment) {
                $filePath = AdminAnnouncementsHelper::resolveAssetFilePath($attachment['file_path'] ?? '', 'attachment');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $message = 'Η ανακοίνωση διαγράφηκε επιτυχώς!';
            $messageType = 'success';
        } else {
            $message = 'Σφάλμα κατά τη διαγραφή της ανακοίνωσης.';
            $messageType = 'danger';
        }
    }
    
    // Διαγραφή μίας εικόνας από ανακοίνωση
    if ($action === 'delete_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $imageToDelete = null;

        if ($imageId > 0) {
            $announcementId = (int)($_POST['announcement_id'] ?? 0);
            $existingImages = $announcementId > 0 ? $announcementsService->getImages($announcementId) : [];
            foreach ($existingImages as $image) {
                if ((int)($image['an_image_id'] ?? 0) === $imageId) {
                    $imageToDelete = $image;
                    break;
                }
            }
        }

        if ($imageId > 0 && $announcementsService->deleteImage($imageId)) {
            if ($imageToDelete) {
                $filePath = AdminAnnouncementsHelper::resolveAssetFilePath($imageToDelete['image_path'] ?? '', 'image');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $message = 'Η εικόνα διαγράφηκε επιτυχώς!';
            $messageType = 'success';
        } else {
            $message = 'Σφάλμα κατά τη διαγραφή της εικόνας.';
            $messageType = 'danger';
        }
    }

    if ($action === 'delete_attachment') {
        $attachmentId = (int)($_POST['attachment_id'] ?? 0);
        $attachment = $attachmentId > 0 ? $announcementsService->getAttachmentById($attachmentId) : null;

        if ($attachment && $announcementsService->deleteAttachment($attachmentId)) {
            $filePath = AdminAnnouncementsHelper::resolveAssetFilePath($attachment['file_path'] ?? '', 'attachment');
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $message = 'Το συνημμένο διαγράφηκε επιτυχώς!';
            $messageType = 'success';
        } else {
            $message = 'Σφάλμα κατά τη διαγραφή του συνημμένου.';
            $messageType = 'danger';
        }
    }

    // PRG: Αποθηκεύουμε μήνυμα στο session και κάνουμε redirect,
    // ώστε το refresh να ΜΗΝ ξαναστείλει το ίδιο POST.
    if ($message === '') {
        $message = 'Η ενέργεια ολοκληρώθηκε.';
        $messageType = 'info';
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $messageType;

    $redirectUrl = 'announcements.php';

    if ($action === 'update') {
        $redirectEditId = (int)($_POST['id'] ?? 0);
        if ($redirectEditId > 0) {
            $redirectUrl = 'announcements.php?edit=' . $redirectEditId;
        }
    }

    if ($action === 'delete_image' || $action === 'delete_attachment') {
        $redirectEditId = (int)($_POST['announcement_id'] ?? ($_GET['edit'] ?? 0));
        if ($redirectEditId > 0) {
            $redirectUrl = 'announcements.php?edit=' . $redirectEditId;
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// Αν ζητήθηκε edit από το URL, φορτώνουμε τα δεδομένα για επεξεργασία
$editAnnouncement = null;
$editImages = [];
$editAttachments = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editAnnouncement = $announcementsService->getAnnouncementById($editId);
    if ($editAnnouncement) {
        $editImages = $announcementsService->getImages($editId);
        $editAttachments = $announcementsService->getAttachments($editId);
    }
}

// Φορτώνουμε όλες τις ανακοινώσεις για τον πίνακα
$announcements = $announcementsService->getAllAnnouncements();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Γραμματοσειρές -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Βασικά styles Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Εικονίδια -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_announcements.css">

    <title>Διαχείριση Ανακοινώσεων - Admin</title>
</head>

<body data-announcement-image-limit="<?php echo ANNOUNCEMENT_IMAGE_LIMIT; ?>">

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στην Αρχική
        </a>

        <div class="admin-header">
            <h1><i class="fas fa-bullhorn mr-2"></i>Διαχείριση Ανακοινώσεων</h1>
            <?php if (!$editAnnouncement): ?>
                <button class="btn btn-primary-custom" data-toggle="modal" data-target="#createModal">
                    <i class="fas fa-plus mr-1"></i>Νέα Ανακοίνωση
                </button>
            <?php endif; ?>
        </div>

        <!-- Μήνυμα επιτυχίας/λάθους -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($editAnnouncement): ?>
            <!-- Φόρμα επεξεργασίας ανακοίνωσης -->
            <div class="card card-custom p-4 mb-4">
                <h4 class="mb-4"><i class="fas fa-edit mr-2"></i>Επεξεργασία Ανακοίνωσης</h4>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $editAnnouncement['announcement_id']; ?>">
                    
                    <div class="form-group">
                        <label for="edit_title"><strong>Τίτλος *</strong></label>
                        <input type="text" class="form-control form-control-custom" id="edit_title" name="title" 
                               value="<?php echo htmlspecialchars($editAnnouncement['announcement_title']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_announcement_date"><strong>Ημερομηνία Ανακοίνωσης</strong></label>
                            <input type="date" class="form-control form-control-custom" id="edit_announcement_date" name="announcement_date"
                                   value="<?php echo htmlspecialchars($editAnnouncement['announcement_date'] ?? $editAnnouncement['publish_date']); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_publish_date"><strong>Ημερομηνία Δημοσίευσης</strong></label>
                            <input type="date" class="form-control form-control-custom" id="edit_publish_date" name="publish_date"
                                   value="<?php echo htmlspecialchars($editAnnouncement['publish_date']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="edit_description" name="description" rows="5"><?php echo htmlspecialchars($editAnnouncement['announcement_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_gdpr_notice"><strong>Ενημέρωση GDPR για φωτογραφικό υλικό</strong></label>
                        <textarea class="form-control form-control-custom" id="edit_gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars($editAnnouncement['gdpr_notice'] ?? AdminAnnouncementsHelper::getDefaultGdprNotice()); ?></textarea>
                    </div>
                    
                    <!-- Οι εικόνες που υπάρχουν ήδη -->
                    <?php if (!empty($editImages)): ?>
                        <div class="form-group">
                            <label><strong>Υπάρχουσες Εικόνες</strong></label>
                            <div class="image-preview">
                                <?php foreach ($editImages as $img): ?>
                                    <div class="image-preview-item">
                                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Image">
                                        <button
                                            type="button"
                                            class="delete-btn"
                                            onclick="deleteAnnouncementImage(<?php echo (int)$img['an_image_id']; ?>, <?php echo (int)$editAnnouncement['announcement_id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($editAttachments)): ?>
                        <div class="form-group">
                            <label><strong>Υπάρχοντα Συνημμένα</strong></label>
                            <div class="list-group">
                                <?php foreach ($editAttachments as $attachment): ?>
                                    <?php
                                    $attachmentName = trim((string)($attachment['original_name'] ?? '')) !== ''
                                        ? (string)$attachment['original_name']
                                        : basename((string)($attachment['file_path'] ?? ''));
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" rel="noopener noreferrer">
                                            <i class="fas fa-paperclip mr-2"></i><?php echo htmlspecialchars($attachmentName); ?>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteAnnouncementAttachment(<?php echo (int)$attachment['attachment_id']; ?>, <?php echo (int)$editAnnouncement['announcement_id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="edit_images"><strong>Προσθήκη Νέων Εικόνων</strong></label>
                        <input
                            type="file"
                            class="form-control-file"
                            id="edit_images"
                            name="images[]"
                            multiple
                            accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                            data-existing-count="<?php echo count($editImages); ?>"
                            <?php echo count($editImages) >= ANNOUNCEMENT_IMAGE_LIMIT ? 'disabled' : ''; ?>
                        >
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο <?php echo ANNOUNCEMENT_IMAGE_LIMIT; ?> εικόνες ανά ανακοίνωση
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-lightbulb"></i> Οι νέες εικόνες θα προστεθούν στις υπάρχουσες. Αυτή τη στιγμή μπορείτε να προσθέσετε έως <?php echo max(0, ANNOUNCEMENT_IMAGE_LIMIT - count($editImages)); ?> ακόμη.
                        </small>
                        <div id="editPreview" class="image-preview"></div>
                    </div>

                    <div class="form-group">
                        <label for="edit_attachments"><strong>Προσθήκη Συνημμένων Επιστολών</strong></label>
                        <input type="file" class="form-control-file" id="edit_attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG | Μέγιστο μέγεθος: 8MB ανά αρχείο
                        </small>
                        <div id="editAttachmentPreview" class="attachment-preview"></div>
                    </div>
                    
                    <div class="d-flex gap-2" style="gap: 10px;">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save mr-1"></i>Αποθήκευση
                        </button>
                        <a href="announcements.php" class="btn btn-secondary-custom">
                            <i class="fas fa-times mr-1"></i>Ακύρωση
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Πίνακας με όλες τις ανακοινώσεις -->
        <div class="card card-custom">
            <div class="card-body p-0">
                <?php if (empty($announcements)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Δεν υπάρχουν ανακοινώσεις</h3>
                        <p>Κάντε κλικ στο "Νέα Ανακοίνωση" για να δημιουργήσετε μία.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Εικόνα</th>
                                    <th>Τίτλος</th>
                                    <th>Ημ. Ανακοίνωσης</th>
                                    <th>Ημ. Δημοσίευσης</th>
                                    <th>Συνημμένα</th>
                                    <th>Περιγραφή</th>
                                    <th style="width: 150px;">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($announcements as $ann): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($ann['images'])): ?>
                                                <img src="<?php echo htmlspecialchars($ann['images'][0]); ?>" 
                                                     class="thumbnail" alt="Thumbnail">
                                            <?php else: ?>
                                                <div class="thumbnail d-flex align-items-center justify-content-center" 
                                                     style="background: #eee;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($ann['announcement_title']); ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($ann['announcement_date'] ?? $ann['publish_date'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($ann['publish_date'])); ?></td>
                                        <td><?php echo count($ann['attachments'] ?? []); ?></td>
                                        <td>
                                            <?php 
                                                $desc = $ann['announcement_description'] ?? '';
                                                echo htmlspecialchars(mb_substr($desc, 0, 80));
                                                if (mb_strlen($desc) > 80) echo '...';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $ann['announcement_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary mr-1" title="Επεξεργασία">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" class="js-confirm-submit" data-confirm-message="Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την ανακοίνωση;" data-confirm-title="Επιβεβαίωση διαγραφής">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $ann['announcement_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Διαγραφή">
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
    </main>
</div>

<!-- Παράθυρο (modal) για νέα ανακοίνωση -->
<div class="modal fade modal-custom" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header" style="background:#2f6fb3;">
                    <h5 class="modal-title" style="color:#ffffff !important;">
                        <i class="fas fa-plus mr-2" style="color:#ffffff !important;"></i>Νέα Ανακοίνωση
                    </h5>
                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close"
                            style="color:#ffffff !important; opacity:1; text-shadow:none; border:none; background:transparent;">
                        <span aria-hidden="true" style="color:#ffffff !important;">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title"><strong>Τίτλος *</strong></label>
                        <input type="text" class="form-control form-control-custom" id="title" name="title" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="announcement_date"><strong>Ημερομηνία Ανακοίνωσης</strong></label>
                            <input type="date" class="form-control form-control-custom" id="announcement_date" name="announcement_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="publish_date"><strong>Ημερομηνία Δημοσίευσης</strong></label>
                            <input type="date" class="form-control form-control-custom" id="publish_date" name="publish_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="5" 
                                  placeholder="Πληκτρολογήστε την περιγραφή της ανακοίνωσης..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="gdpr_notice"><strong>Ενημέρωση GDPR για φωτογραφικό υλικό</strong></label>
                        <textarea class="form-control form-control-custom" id="gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars(AdminAnnouncementsHelper::getDefaultGdprNotice()); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="images"><strong>Εικόνες</strong></label>
                        <input
                            type="file"
                            class="form-control-file"
                            id="images"
                            name="images[]"
                            multiple
                            accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                            data-existing-count="0"
                        >
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο <?php echo ANNOUNCEMENT_IMAGE_LIMIT; ?> εικόνες ανά ανακοίνωση
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-lightbulb"></i> Μπορείτε να επιλέξετε πολλές εικόνες ταυτόχρονα
                        </small>
                        <div id="imagePreview" class="image-preview"></div>
                    </div>

                    <div class="form-group">
                        <label for="attachments"><strong>Συνημμένες Επιστολές</strong></label>
                        <input type="file" class="form-control-file" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG | Μέγιστο μέγεθος: 8MB ανά αρχείο
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-paperclip"></i> Κάθε ανακοίνωση μπορεί να συνοδεύεται από την αντίστοιχη επιστολή ή σχετικό έγγραφο.
                        </small>
                        <div id="attachmentPreview" class="attachment-preview"></div>
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

<!-- Scripts Bootstrap -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin-announcements.js"></script>

</body>
</html>
