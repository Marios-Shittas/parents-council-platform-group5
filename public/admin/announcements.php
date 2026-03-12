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
        $announcementDate = $_POST['announcement_date'] ?? date('Y-m-d');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        if (!empty($title)) {
            $announcementId = $announcementsService->createAnnouncement($title, $description, $announcementDate, $publishDate);
            
            if ($announcementId) {
                $uploadedCount = 0;
                $uploadErrors = [];
                
                // Αν ο χρήστης έβαλε εικόνες, ξεκινά η διαδικασία ανεβάσματος
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = __DIR__ . '/../assets/Announcements_img/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $maxFileSize = 5 * 1024 * 1024; // Μέγιστο 5MB ανά αρχείο
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        $fileName = basename($_FILES['images']['name'][$key]);
                        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                        
                        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$_FILES['images']['error'][$key]})";
                            continue;
                        }
                        
                        $fileSize = $_FILES['images']['size'][$key];
                        $fileMime = mime_content_type($tmpName);
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        // Έλεγχος επέκτασης (π.χ. jpg, png)
                        if (!in_array($fileExt, $allowedExtensions)) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
                            continue;
                        }
                        
                        // Έλεγχος μεγέθους αρχείου
                        if ($fileSize > $maxFileSize) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο (" . round($fileSize / 1024 / 1024, 2) . "MB). Μέγιστο: 5MB";
                            continue;
                        }
                        
                        // Έλεγχος ότι το αρχείο είναι πράγματι εικόνα
                        if (!getimagesize($tmpName)) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα";
                            continue;
                        }
                        
                        // Έλεγχος τύπου MIME με πιο χαλαρό τρόπο για συμβατότητα
                        if (!in_array($fileMime, $allowedTypes) && !str_starts_with($fileMime, 'image/')) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας (MIME: {$fileMime})";
                            continue;
                        }
                        
                        // Όλοι οι έλεγχοι πέρασαν, άρα προσπαθούμε να σώσουμε το αρχείο
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                        $targetPath = $uploadDir . $newFileName;
                        
                        // Έλεγχος ότι υπάρχει ακόμα το προσωρινό αρχείο του upload
                        if (!file_exists($tmpName)) {
                            $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε";
                            continue;
                        }
                        
                        // Έλεγχος ότι ο φάκελος επιτρέπει εγγραφή
                        if (!is_writable($uploadDir)) {
                            $uploadErrors[] = "Ο φάκελος δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα (chmod 777 {$uploadDir})";
                            continue;
                        }
                        
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $imagePath = '/parents-council-platform-group5/public/assets/Announcements_img/' . $newFileName;
                            if ($announcementsService->addImage($announcementId, $imagePath)) {
                                $uploadedCount++;
                            } else {
                                $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων";
                            }
                        } else {
                            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του αρχείου '{$safeFileName}' - Έλεγξε δικαιώματα φακέλου: " . substr(sprintf('%o', fileperms($uploadDir)), -4);
                        }
                    }
                }
                
                // Φτιάχνουμε μήνυμα επιτυχίας ανάλογα με το πόσες εικόνες μπήκαν
                if ($uploadedCount > 0) {
                    $message = "Η ανακοίνωση δημιουργήθηκε επιτυχώς με {$uploadedCount} εικόνα/ες!";
                } else {
                    $message = 'Η ανακοίνωση δημιουργήθηκε επιτυχώς (χωρίς εικόνες).';
                }
                
                // Αν υπάρχουν λάθη σε αρχεία, τα δείχνουμε μαζεμένα
                if (!empty($uploadErrors)) {
                    $message .= '<br><strong>Προβλήματα με τα αρχεία:</strong><ul><li>' . implode('</li><li>', $uploadErrors) . '</li></ul>';
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
        $announcementDate = $_POST['announcement_date'] ?? date('Y-m-d');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        if ($id > 0 && !empty($title)) {
            if ($announcementsService->updateAnnouncement($id, $title, $description, $announcementDate, $publishDate)) {
                $uploadedCount = 0;
                $uploadErrors = [];
                
                // Αν ανέβηκαν νέες εικόνες, τις προσθέτουμε στην ανακοίνωση
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = __DIR__ . '/../assets/Announcements_img/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $maxFileSize = 5 * 1024 * 1024; // Μέγιστο 5MB ανά αρχείο
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        $fileName = basename($_FILES['images']['name'][$key]);
                        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                        
                        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$_FILES['images']['error'][$key]})";
                            continue;
                        }
                        
                        $fileSize = $_FILES['images']['size'][$key];
                        $fileMime = mime_content_type($tmpName);
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        // Έλεγχος επέκτασης (π.χ. jpg, png)
                        if (!in_array($fileExt, $allowedExtensions)) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
                            continue;
                        }
                        
                        // Έλεγχος μεγέθους αρχείου
                        if ($fileSize > $maxFileSize) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο (" . round($fileSize / 1024 / 1024, 2) . "MB). Μέγιστο: 5MB";
                            continue;
                        }
                        
                        // Έλεγχος ότι το αρχείο είναι πράγματι εικόνα
                        if (!getimagesize($tmpName)) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα";
                            continue;
                        }
                        
                        // Έλεγχος τύπου MIME με πιο χαλαρό τρόπο για συμβατότητα
                        if (!in_array($fileMime, $allowedTypes) && !str_starts_with($fileMime, 'image/')) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας (MIME: {$fileMime})";
                            continue;
                        }
                        
                        // Όλοι οι έλεγχοι πέρασαν, άρα προσπαθούμε να σώσουμε το αρχείο
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                        $targetPath = $uploadDir . $newFileName;
                        
                        // Έλεγχος ότι υπάρχει ακόμα το προσωρινό αρχείο του upload
                        if (!file_exists($tmpName)) {
                            $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε";
                            continue;
                        }
                        
                        // Έλεγχος ότι ο φάκελος επιτρέπει εγγραφή
                        if (!is_writable($uploadDir)) {
                            $uploadErrors[] = "Ο φάκελος δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα (chmod 777 {$uploadDir})";
                            continue;
                        }
                        
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $imagePath = '/parents-council-platform-group5/public/assets/Announcements_img/' . $newFileName;
                            if ($announcementsService->addImage($id, $imagePath)) {
                                $uploadedCount++;
                            } else {
                                $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων";
                            }
                        } else {
                            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του αρχείου '{$safeFileName}' - Έλεγξε δικαιώματα φακέλου: " . substr(sprintf('%o', fileperms($uploadDir)), -4);
                        }
                    }
                }
                
                // Φτιάχνουμε μήνυμα επιτυχίας ανάλογα με το πόσες εικόνες μπήκαν
                if ($uploadedCount > 0) {
                    $message = "Η ανακοίνωση ενημερώθηκε επιτυχώς με {$uploadedCount} νέα/ες εικόνα/ες!";
                } else {
                    $message = 'Η ανακοίνωση ενημερώθηκε επιτυχώς!';
                }
                
                // Αν υπάρχουν λάθη σε αρχεία, τα δείχνουμε μαζεμένα
                if (!empty($uploadErrors)) {
                    $message .= '<br><strong>Προβλήματα με τα αρχεία:</strong><ul><li>' . implode('</li><li>', $uploadErrors) . '</li></ul>';
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
        
        if ($id > 0 && $announcementsService->deleteAnnouncement($id)) {
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
        
        if ($imageId > 0 && $announcementsService->deleteImage($imageId)) {
            $message = 'Η εικόνα διαγράφηκε επιτυχώς!';
            $messageType = 'success';
        } else {
            $message = 'Σφάλμα κατά τη διαγραφή της εικόνας.';
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

    if ($action === 'delete_image') {
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
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editAnnouncement = $announcementsService->getAnnouncementById($editId);
    if ($editAnnouncement) {
        $editImages = $announcementsService->getImages($editId);
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
    <link rel="stylesheet" href="../assets/css/admin_announcements.css">

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
                    
                    <div class="form-group">
                        <label for="edit_announcement_date"><strong>Ημερομηνία Ανακοίνωσης</strong></label>
                        <input type="date" class="form-control form-control-custom" id="edit_announcement_date" name="announcement_date"
                               value="<?php echo htmlspecialchars($editAnnouncement['announcement_date'] ?? $editAnnouncement['publish_date']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_date"><strong>Ημερομηνία Δημοσίευσης</strong></label>
                        <input type="date" class="form-control form-control-custom" id="edit_date" name="publish_date"
                               value="<?php echo $editAnnouncement['publish_date']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="edit_description" name="description" rows="5"><?php echo htmlspecialchars($editAnnouncement['announcement_description'] ?? ''); ?></textarea>
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
                    
                    <div class="form-group">
                        <label for="edit_images"><strong>Προσθήκη Νέων Εικόνων</strong></label>
                        <input type="file" class="form-control-file" id="edit_images" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-lightbulb"></i> Οι νέες εικόνες θα προστεθούν στις υπάρχουσες
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
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $ann['announcement_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την ανακοίνωση;')"
                                                        title="Διαγραφή">
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
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Νέα Ανακοίνωση</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title"><strong>Τίτλος *</strong></label>
                        <input type="text" class="form-control form-control-custom" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="announcement_date"><strong>Ημερομηνία Ανακοίνωσης</strong></label>
                        <input type="date" class="form-control form-control-custom" id="announcement_date" name="announcement_date" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="publish_date"><strong>Ημερομηνία Δημοσίευσης</strong></label>
                        <input type="date" class="form-control form-control-custom" id="publish_date" name="publish_date" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="5" 
                                  placeholder="Πληκτρολογήστε την περιγραφή της ανακοίνωσης..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="images"><strong>Εικόνες</strong></label>
                        <input type="file" class="form-control-file" id="images" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-lightbulb"></i> Μπορείτε να επιλέξετε πολλές εικόνες ταυτόχρονα
                        </small>
                        <div id="imagePreview" class="image-preview"></div>
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
// Κάνει διαγραφή εικόνας με ξεχωριστό POST, χωρίς να χαλάει η φόρμα επεξεργασίας
function deleteAnnouncementImage(imageId, announcementId) {
    if (!confirm('Διαγραφή εικόνας;')) {
        return;
    }

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
}

// Έλεγχος αρχείων και μικρή προεπισκόπηση εικόνων πριν το submit
function validateAndPreviewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const maxFileSize = 5 * 1024 * 1024; // Μέγιστο 5MB ανά αρχείο
    let warnings = [];
    
    [...input.files].forEach((file, index) => {
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
                    <div style="font-size: 10px; text-align: center; margin-top: 2px;">${file.name.substring(0, 15)}...</div>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Δείχνουμε προειδοποιήσεις, αλλά αφήνουμε τον server να κάνει τον τελικό έλεγχο
    if (warnings.length > 0) {
        alert('Προειδοποιήσεις:\n\n' + warnings.join('\n\n') + '\n\nΜπορείτε να προσπαθήσετε να ανεβάσετε τα αρχεία, αλλά μπορεί να απορριφθούν από τον διακομιστή.');
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
        preview.style.marginTop = '10px';
        
        // Αν υπήρχε παλιά προεπισκόπηση, τη σβήνουμε
        const oldPreview = document.getElementById('editPreview');
        if (oldPreview && oldPreview !== preview) {
            oldPreview.remove();
        }
        
        this.parentElement.appendChild(preview);
        validateAndPreviewImages(this, 'editPreview');
    });
}
</script>

</body>
</html>
