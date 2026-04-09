<?php
/**
 * Σελίδα διαχείρισης ανακοινώσεων (admin)
 * Εδώ ο διαχειριστής μπορεί να δημιουργήσει, να αλλάξει, να διαγράψει
 * ανακοινώσεις και να ανεβάσει εικόνες.
 */

require_once __DIR__ . '/../../app/services/AnnouncementsService.php';

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

function getDefaultAnnouncementGdprNotice() {
    return 'Το φωτογραφικό υλικό και τα συνημμένα έγγραφα των ανακοινώσεων δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.';
}

function getAnnouncementImageUploadDir() {
    return dirname(__DIR__) . '/assets/Announcements_img/';
}

function buildAnnouncementImageWebPath($fileName) {
    return '/parents-council-platform-group5/public/assets/Announcements_img/' . $fileName;
}

function getAnnouncementAttachmentUploadDir() {
    return dirname(__DIR__) . '/assets/Announcements_docs/';
}

function buildAnnouncementAttachmentWebPath($fileName) {
    return '/parents-council-platform-group5/public/assets/Announcements_docs/' . $fileName;
}

function resolveAnnouncementAssetFilePath($filePath, $type = 'image') {
    $baseDir = $type === 'attachment' ? getAnnouncementAttachmentUploadDir() : getAnnouncementImageUploadDir();
    return $baseDir . basename((string)$filePath);
}

function uploadAnnouncementImages($announcementsService, $announcementId) {
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES['images']['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    $uploadDir = getAnnouncementImageUploadDir();
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 5 * 1024 * 1024;
    $existingImagesCount = $announcementsService->countImages($announcementId);
    $availableSlots = max(0, ANNOUNCEMENT_IMAGE_LIMIT - $existingImagesCount);
    $selectedFilesCount = is_array($_FILES['images']['name'] ?? null) ? count($_FILES['images']['name']) : 0;

    if ($availableSlots === 0) {
        $uploadErrors[] = 'Η ανακοίνωση έχει ήδη τον μέγιστο επιτρεπόμενο αριθμό εικόνων (' . ANNOUNCEMENT_IMAGE_LIMIT . ').';
        return [$uploadedCount, $uploadErrors];
    }

    if ($selectedFilesCount > $availableSlots) {
        $uploadErrors[] = 'Επιλέχθηκαν ' . $selectedFilesCount . ' αρχεία, αλλά μπορούν να αποθηκευτούν μόνο ' . $availableSlots . ' ακόμη εικόνες για αυτή την ανακοίνωση.';
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if ($uploadedCount >= $availableSlots) {
            $uploadErrors[] = 'Μπορούν να αποθηκευτούν έως ' . ANNOUNCEMENT_IMAGE_LIMIT . ' εικόνες ανά ανακοίνωση.';
            break;
        }

        $fileName = basename($_FILES['images']['name'][$key] ?? '');
        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');

        if (($_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$_FILES['images']['error'][$key]})";
            continue;
        }

        $fileSize = $_FILES['images']['size'][$key];
        $fileMime = mime_content_type($tmpName);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions, true)) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
            continue;
        }

        if ($fileSize > $maxFileSize) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο (" . round($fileSize / 1024 / 1024, 2) . "MB). Μέγιστο: 5MB";
            continue;
        }

        if (!getimagesize($tmpName)) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα";
            continue;
        }

        if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with($fileMime, 'image/')) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας (MIME: {$fileMime})";
            continue;
        }

        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (!file_exists($tmpName)) {
            $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε";
            continue;
        }

        if (!is_writable($uploadDir)) {
            $uploadErrors[] = "Ο φάκελος δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα (chmod 777 {$uploadDir})";
            continue;
        }

        if (move_uploaded_file($tmpName, $targetPath)) {
            $imagePath = buildAnnouncementImageWebPath($newFileName);
            if ($announcementsService->addImage($announcementId, $imagePath)) {
                $uploadedCount++;
            } else {
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }

                $serviceError = trim((string)$announcementsService->getLastOperationError());
                if ($serviceError !== '') {
                    $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν αποθηκεύτηκε. {$serviceError}";
                } else {
                    $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων";
                }
            }
        } else {
            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του αρχείου '{$safeFileName}' - Έλεγξε δικαιώματα φακέλου: " . substr(sprintf('%o', fileperms($uploadDir)), -4);
        }
    }

    return [$uploadedCount, $uploadErrors];
}

function uploadAnnouncementAttachments($announcementsService, $announcementId) {
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES['attachments']['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    $uploadDir = getAnnouncementAttachmentUploadDir();
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxFileSize = 8 * 1024 * 1024;

    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['attachments']['name'][$key] ?? '');
        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $errorCode = $_FILES['attachments']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

        if ($errorCode !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Το συνημμένο '{$safeFileName}' απέτυχε να ανέβει (Error: {$errorCode})";
            continue;
        }

        $fileSize = (int)($_FILES['attachments']['size'][$key] ?? 0);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions, true)) {
            $uploadErrors[] = "Το συνημμένο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
            continue;
        }

        if ($fileSize > $maxFileSize) {
            $uploadErrors[] = "Το συνημμένο '{$safeFileName}' είναι πολύ μεγάλο (" . round($fileSize / 1024 / 1024, 2) . "MB). Μέγιστο: 8MB";
            continue;
        }

        if (!file_exists($tmpName)) {
            $uploadErrors[] = "Το προσωρινό αρχείο για το συνημμένο '{$safeFileName}' δεν βρέθηκε";
            continue;
        }

        if (!is_writable($uploadDir)) {
            $uploadErrors[] = "Ο φάκελος συνημμένων δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα του {$uploadDir}";
            continue;
        }

        $newFileName = uniqid('announcement_attachment_', true) . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $attachmentPath = buildAnnouncementAttachmentWebPath($newFileName);
            if ($announcementsService->addAttachment($announcementId, $attachmentPath, $fileName)) {
                $uploadedCount++;
            } else {
                @unlink($targetPath);
                $uploadErrors[] = "Αποτυχία αποθήκευσης του συνημμένου '{$safeFileName}' στη βάση δεδομένων";
            }
        } else {
            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του συνημμένου '{$safeFileName}'.";
        }
    }

    return [$uploadedCount, $uploadErrors];
}

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
        $gdprNotice = trim($_POST['gdpr_notice'] ?? getDefaultAnnouncementGdprNotice());
        $announcementDate = $_POST['announcement_date'] ?? date('Y-m-d');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        if (!empty($title)) {
            $announcementId = $announcementsService->createAnnouncement($title, $description, $announcementDate, $publishDate, $gdprNotice);
            
            if ($announcementId) {
                [$uploadedCount, $uploadErrors] = uploadAnnouncementImages($announcementsService, $announcementId);
                [$uploadedAttachmentsCount, $attachmentErrors] = uploadAnnouncementAttachments($announcementsService, $announcementId);
                
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
        $gdprNotice = trim($_POST['gdpr_notice'] ?? getDefaultAnnouncementGdprNotice());
        $announcementDate = $_POST['announcement_date'] ?? date('Y-m-d');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        if ($id > 0 && !empty($title)) {
            if ($announcementsService->updateAnnouncement($id, $title, $description, $announcementDate, $publishDate, $gdprNotice)) {
                [$uploadedCount, $uploadErrors] = uploadAnnouncementImages($announcementsService, $id);
                [$uploadedAttachmentsCount, $attachmentErrors] = uploadAnnouncementAttachments($announcementsService, $id);
                
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
                $filePath = resolveAnnouncementAssetFilePath($image['image_path'] ?? '', 'image');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            foreach ($attachmentsToDelete as $attachment) {
                $filePath = resolveAnnouncementAssetFilePath($attachment['file_path'] ?? '', 'attachment');
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
                $filePath = resolveAnnouncementAssetFilePath($imageToDelete['image_path'] ?? '', 'image');
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
            $filePath = resolveAnnouncementAssetFilePath($attachment['file_path'] ?? '', 'attachment');
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

<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
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
                        <textarea class="form-control form-control-custom" id="edit_gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars($editAnnouncement['gdpr_notice'] ?? getDefaultAnnouncementGdprNotice()); ?></textarea>
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
                    </div>

                    <div class="form-group">
                        <label for="edit_attachments"><strong>Προσθήκη Συνημμένων Επιστολών</strong></label>
                        <input type="file" class="form-control-file" id="edit_attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG | Μέγιστο μέγεθος: 8MB ανά αρχείο
                        </small>
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
                        <textarea class="form-control form-control-custom" id="gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars(getDefaultAnnouncementGdprNotice()); ?></textarea>
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

<script>
function ensureNoticeElements() {
    if (document.getElementById('page-notice-overlay')) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'page-notice-overlay';
    overlay.className = 'page-notice-overlay';
    overlay.innerHTML = '' +
        '<div class="page-notice-card" id="page-notice-card" role="dialog" aria-modal="true" aria-labelledby="page-notice-title">' +
            '<h3 class="page-notice-title" id="page-notice-title">Ειδοποίηση</h3>' +
            '<div class="page-notice-message" id="page-notice-message">—</div>' +
            '<div class="page-notice-actions"><button type="button" class="page-notice-btn" id="page-notice-close">Εντάξει</button></div>' +
        '</div>';

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            overlay.classList.remove('is-open');
            document.body.style.overflow = overlay.getAttribute('data-prev-overflow') || '';
        }
    });

    document.body.appendChild(overlay);

    const closeBtn = document.getElementById('page-notice-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            overlay.classList.remove('is-open');
            document.body.style.overflow = overlay.getAttribute('data-prev-overflow') || '';
        });
    }
}

function showNotice(message, options) {
    ensureNoticeElements();

    const overlay = document.getElementById('page-notice-overlay');
    const card = document.getElementById('page-notice-card');
    const title = document.getElementById('page-notice-title');
    const body = document.getElementById('page-notice-message');
    const opts = options || {};

    if (!overlay || !card || !title || !body) {
        console.error(message);
        return;
    }

    card.classList.remove('is-error', 'is-warning');
    if (opts.variant === 'error') card.classList.add('is-error');
    if (opts.variant === 'warning') card.classList.add('is-warning');

    title.textContent = opts.title || 'Ειδοποίηση';
    body.textContent = message || 'Συνέβη ένα απρόσμενο σφάλμα.';

    overlay.setAttribute('data-prev-overflow', document.body.style.overflow || '');
    document.body.style.overflow = 'hidden';
    overlay.classList.add('is-open');
}

function showConfirm(message, onConfirm, options) {
    const opts = options || {};
    const overlay = document.createElement('div');
    const previousOverflow = document.body.style.overflow || '';

    overlay.className = 'page-confirm-overlay is-open';

    overlay.innerHTML = '' +
        '<div class="page-confirm-card" role="dialog" aria-modal="true">' +
            '<h3 class="page-confirm-title">' + (opts.title || 'Επιβεβαίωση') + '</h3>' +
            '<div class="page-confirm-message">' + (message || 'Είστε σίγουροι;') + '</div>' +
            '<div class="page-confirm-actions">' +
                '<button type="button" data-action="cancel" class="page-confirm-btn page-confirm-btn--cancel">Όχι</button>' +
                '<button type="button" data-action="confirm" class="page-confirm-btn page-confirm-btn--confirm">Ναι</button>' +
            '</div>' +
        '</div>';

    function closeOverlay() {
        document.body.style.overflow = previousOverflow;
        overlay.remove();
    }

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeOverlay();
        }
    });

    const cancelBtn = overlay.querySelector('[data-action="cancel"]');
    const confirmBtn = overlay.querySelector('[data-action="confirm"]');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeOverlay);
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            closeOverlay();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    document.body.style.overflow = 'hidden';
    document.body.appendChild(overlay);
}

// Κάνει διαγραφή εικόνας με ξεχωριστό POST, χωρίς να χαλάει η φόρμα επεξεργασίας
function deleteAnnouncementImage(imageId, announcementId) {
    showConfirm('Διαγραφή εικόνας;', function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_image';

        const imageIdInput = document.createElement('input');
        imageIdInput.type = 'hidden';
        imageIdInput.name = 'image_id';
        imageIdInput.value = String(imageId);

        const announcementIdInput = document.createElement('input');
        announcementIdInput.type = 'hidden';
        announcementIdInput.name = 'announcement_id';
        announcementIdInput.value = String(announcementId);

        form.appendChild(actionInput);
        form.appendChild(imageIdInput);
        form.appendChild(announcementIdInput);
        document.body.appendChild(form);
        form.submit();
    }, {
        title: 'Επιβεβαίωση διαγραφής'
    });
}

function deleteAnnouncementAttachment(attachmentId, announcementId) {
    showConfirm('Διαγραφή συνημμένου;', function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_attachment';

        const attachmentIdInput = document.createElement('input');
        attachmentIdInput.type = 'hidden';
        attachmentIdInput.name = 'attachment_id';
        attachmentIdInput.value = String(attachmentId);

        const announcementIdInput = document.createElement('input');
        announcementIdInput.type = 'hidden';
        announcementIdInput.name = 'announcement_id';
        announcementIdInput.value = String(announcementId);

        form.appendChild(actionInput);
        form.appendChild(attachmentIdInput);
        form.appendChild(announcementIdInput);
        document.body.appendChild(form);
        form.submit();
    }, {
        title: 'Επιβεβαίωση διαγραφής'
    });
}

// Έλεγχος αρχείων και μικρή προεπισκόπηση εικόνων πριν το submit
function validateAndPreviewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const maxFileSize = 5 * 1024 * 1024; // Μέγιστο 5MB ανά αρχείο
    const announcementImageLimit = <?php echo ANNOUNCEMENT_IMAGE_LIMIT; ?>;
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const availableSlots = Math.max(0, announcementImageLimit - existingCount);
    let warnings = [];
    let files = [...input.files];

    if (availableSlots === 0) {
        input.value = '';
        warnings.push(`Η ανακοίνωση έχει ήδη ${announcementImageLimit} εικόνες. Διαγράψτε πρώτα κάποια εικόνα για να προσθέσετε νέα.`);
    } else if (files.length > availableSlots) {
        warnings.push(`Μπορείτε να προσθέσετε μόνο ${availableSlots} ακόμη εικόνα/ες σε αυτή την ανακοίνωση. Θα κρατηθούν μόνο οι πρώτες ${availableSlots}.`);

        files = files.slice(0, availableSlots);

        if (typeof DataTransfer !== 'undefined') {
            const dataTransfer = new DataTransfer();
            files.forEach((file) => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
        }
    }
    
    files.forEach((file) => {
        const fileExt = file.name.split('.').pop().toLowerCase();
        let hasWarning = false;
        
        // Έλεγχος επέκτασης αρχείου
        if (!allowedExtensions.includes(fileExt)) {
            warnings.push(`Το αρχείο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο JPG, JPEG, PNG, GIF.`);
            hasWarning = true;
        }
        
        // Έλεγχος μεγέθους αρχείου
        if (file.size > maxFileSize) {
            warnings.push(`Το αρχείο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 5MB.`);
            hasWarning = true;
        }
        
        // Έλεγχος ότι είναι τύπος εικόνας
        if (!file.type.startsWith('image/')) {
            warnings.push(`Το αρχείο "${file.name}" δεν φαίνεται να είναι εικόνα.`);
            hasWarning = true;
        }
        
        // Αν δεν έχει προειδοποίηση, δείχνουμε προεπισκόπηση
        if (file.type.startsWith('image/') && !hasWarning) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'image-preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div class="preview-file-caption">${file.name.substring(0, 15)}...</div>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
    
    if (warnings.length > 0) {
        showNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
            title: 'Έλεγχος αρχείων',
            variant: 'warning'
        });
    }
}

// Σύνδεση ελέγχου με το input αρχείων στη φόρμα δημιουργίας
document.getElementById('images').addEventListener('change', function(e) {
    validateAndPreviewImages(this, 'imagePreview');
});

// Σύνδεση ελέγχου με το input αρχείων στη φόρμα επεξεργασίας (αν υπάρχει)
const editImagesInput = document.getElementById('edit_images');
if (editImagesInput) {
    editImagesInput.addEventListener('change', function(e) {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.id = 'editPreview';
        
        // Αν υπήρχε παλιά προεπισκόπηση, τη σβήνουμε
        const oldPreview = document.getElementById('editPreview');
        if (oldPreview && oldPreview !== preview) {
            oldPreview.remove();
        }
        
        this.parentElement.appendChild(preview);
        validateAndPreviewImages(this, 'editPreview');
    });
}

document.querySelectorAll('form.js-confirm-submit').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = form.getAttribute('data-confirm-message') || 'Είστε σίγουροι;';
        const title = form.getAttribute('data-confirm-title') || 'Επιβεβαίωση';

        showConfirm(message, function () {
            form.submit();
        }, {
            title: title
        });
    });
});
</script>

</body>
</html>
