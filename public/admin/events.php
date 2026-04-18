<?php
/**
 * Admin Events Management Page
 * Create, update, delete events and manage images
 */

require_once __DIR__ . '/../../app/services/EventsService.php';

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

const EVENT_IMAGE_LIMIT = 6;

function getDefaultEventGdprNotice() {
    return 'Το φωτογραφικό υλικό της εκδήλωσης δημοσιεύεται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με τις ισχύουσες εγκρίσεις/πολιτικές του σχολείου.';
}

function getEventImageUploadDir() {
    return dirname(__DIR__) . '/assets/Events_img/';
}

function buildEventImageWebPath($fileName) {
    return '/parents-council-platform-group5/public/assets/Events_img/' . $fileName;
}

function resolveEventImageFilePath($imagePath) {
    return getEventImageUploadDir() . basename((string)$imagePath);
}

function uploadEventImages($eventsService, $eventId) {
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES['images']['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    $uploadDir = getEventImageUploadDir();
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 5 * 1024 * 1024;

    $existingImagesCount = $eventsService->countImages($eventId);
    $availableSlots = max(0, EVENT_IMAGE_LIMIT - $existingImagesCount);
    $selectedFilesCount = is_array($_FILES['images']['name'] ?? null) ? count($_FILES['images']['name']) : 0;

    if ($availableSlots === 0) {
        $uploadErrors[] = 'Η εκδήλωση έχει ήδη τον μέγιστο επιτρεπόμενο αριθμό φωτογραφιών (' . EVENT_IMAGE_LIMIT . ').';
        return [$uploadedCount, $uploadErrors];
    }

    if ($selectedFilesCount > $availableSlots) {
        $uploadErrors[] = 'Επιλέχθηκαν ' . $selectedFilesCount . ' αρχεία, αλλά μπορούν να αποθηκευτούν μόνο ' . $availableSlots . ' ακόμη φωτογραφίες για αυτή την εκδήλωση.';
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if ($uploadedCount >= $availableSlots) {
            $uploadErrors[] = 'Μπορούν να αποθηκευτούν έως ' . EVENT_IMAGE_LIMIT . ' φωτογραφίες ανά εκδήλωση.';
            break;
        }

        $fileName = basename($_FILES['images']['name'][$key] ?? '');
        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $uploadError = $_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$uploadError})";
            continue;
        }

        $fileSize = $_FILES['images']['size'][$key] ?? 0;
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

        if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with((string)$fileMime, 'image/')) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας (MIME: {$fileMime})";
            continue;
        }

        if (!file_exists($tmpName)) {
            $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε";
            continue;
        }

        if (!is_writable($uploadDir)) {
            $uploadErrors[] = "Ο φάκελος Events_img δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα του {$uploadDir}";
            continue;
        }

        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $imagePath = buildEventImageWebPath($newFileName);
            if ($eventsService->addImage($eventId, $imagePath)) {
                $uploadedCount++;
            } else {
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }

                $serviceError = trim((string)$eventsService->getLastOperationError());
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

$eventsService = new EventsService();
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $gdprNotice = trim($_POST['gdpr_notice'] ?? getDefaultEventGdprNotice());
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = date('Y-m-d');
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';

        if (!empty($title) && !empty($eventDate)) {
            $eventId = $eventsService->createEvent($title, $description, $eventDateTime, $publishDate, $gdprNotice);

            if ($eventId) {
                [$uploadedCount, $uploadErrors] = uploadEventImages($eventsService, $eventId);

                if ($uploadedCount > 0) {
                    $message = "Η εκδήλωση δημιουργήθηκε επιτυχώς με {$uploadedCount} εικόνα/ες!";
                } else {
                    $message = 'Η εκδήλωση δημιουργήθηκε επιτυχώς (χωρίς εικόνες).';
                }

                if (!empty($uploadErrors)) {
                    $message .= '<br><strong>Προβλήματα με τα αρχεία:</strong><ul><li>' . implode('</li><li>', $uploadErrors) . '</li></ul>';
                    $messageType = 'warning';
                } else {
                    $messageType = 'success';
                }
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία της εκδήλωσης.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Ο τίτλος και η ημερομηνία είναι υποχρεωτικά.';
            $messageType = 'danger';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $gdprNotice = trim($_POST['gdpr_notice'] ?? getDefaultEventGdprNotice());
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';

        if ($id > 0 && !empty($title) && !empty($eventDate)) {
            if ($eventsService->updateEvent($id, $title, $description, $eventDateTime, $publishDate, $gdprNotice)) {
                [$uploadedCount, $uploadErrors] = uploadEventImages($eventsService, $id);

                if ($uploadedCount > 0) {
                    $message = "Η εκδήλωση ενημερώθηκε επιτυχώς με {$uploadedCount} νέα/ες εικόνα/ες!";
                } else {
                    $message = 'Η εκδήλωση ενημερώθηκε επιτυχώς!';
                }

                if (!empty($uploadErrors)) {
                    $message .= '<br><strong>Προβλήματα με τα αρχεία:</strong><ul><li>' . implode('</li><li>', $uploadErrors) . '</li></ul>';
                    $messageType = 'warning';
                } else {
                    $messageType = 'success';
                }
            } else {
                $message = 'Σφάλμα κατά την ενημέρωση της εκδήλωσης.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Ο τίτλος και η ημερομηνία είναι υποχρεωτικά.';
            $messageType = 'danger';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $images = $eventsService->getImages($id);

            if ($eventsService->deleteEvent($id)) {
                foreach ($images as $img) {
                    $filePath = resolveEventImageFilePath($img['image_path'] ?? '');
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $message = 'Η εκδήλωση διαγράφηκε επιτυχώς!';
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά τη διαγραφή της εκδήλωσης.';
                $messageType = 'danger';
            }
        }
    }

    if ($action === 'delete_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($imageId > 0 && $eventId > 0) {
            $images = $eventsService->getImages($eventId);
            $imageToDelete = null;

            foreach ($images as $img) {
                if ((int)$img['ev_image_id'] === $imageId) {
                    $imageToDelete = $img;
                    break;
                }
            }

            if ($imageToDelete && $eventsService->deleteImage($imageId)) {
                $filePath = resolveEventImageFilePath($imageToDelete['image_path'] ?? '');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $message = 'Η εικόνα διαγράφηκε επιτυχώς!';
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά τη διαγραφή της εικόνας.';
                $messageType = 'danger';
            }
        }
    }

    if ($message === '') {
        $message = 'Η ενέργεια ολοκληρώθηκε.';
        $messageType = 'info';
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $messageType;

    $redirectUrl = 'events.php';

    if ($action === 'delete_image') {
        $redirectEditId = (int)($_POST['event_id'] ?? 0);
        if ($redirectEditId > 0) {
            $redirectUrl = 'events.php?edit=' . $redirectEditId;
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$editEvent = null;
$editImages = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editEvent = $eventsService->getEventById($editId);
    if ($editEvent) {
        $editImages = $eventsService->getImages($editId);
    }
}

$events = $eventsService->getAllEvents();
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
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_events.css">

    <title>Διαχείριση Εκδηλώσεων - Admin</title>
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στην Αρχική
        </a>

        <div class="admin-header">
            <h1><i class="fas fa-calendar-alt mr-2"></i>Διαχείριση Εκδηλώσεων</h1>
            <?php if (!$editEvent): ?>
                <button class="btn btn-primary-custom" data-toggle="modal" data-target="#createModal">
                    <i class="fas fa-plus mr-1"></i>Νέα Εκδήλωση
                </button>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($editEvent): ?>
            <?php
                $editDateTime = new DateTime($editEvent['event_date']);
            ?>
            <div class="card card-custom p-4 mb-4">
                <h4 class="mb-4"><i class="fas fa-edit mr-2"></i>Επεξεργασία Εκδήλωσης</h4>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo (int)$editEvent['event_id']; ?>">

                    <div class="form-group">
                        <label for="edit_title"><strong>Τίτλος *</strong></label>
                        <input type="text" id="edit_title" name="title" class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($editEvent['event_title']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_event_date"><strong>Ημερομηνία *</strong></label>
                            <input type="date" id="edit_event_date" name="event_date" class="form-control form-control-custom"
                                   value="<?php echo $editDateTime->format('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_event_time"><strong>Ώρα</strong></label>
                            <input type="time" id="edit_event_time" name="event_time" class="form-control form-control-custom"
                                   value="<?php echo $editDateTime->format('H:i'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_publish_date"><strong>Ημερομηνία Δημοσίευσης</strong></label>
                        <input type="date" id="edit_publish_date" name="publish_date" class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($editEvent['publish_date']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_description"><strong>Περιγραφή</strong></label>
                        <textarea id="edit_description" name="description" class="form-control form-control-custom" rows="5"><?php echo htmlspecialchars($editEvent['event_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_gdpr_notice"><strong>Ενημέρωση GDPR για φωτογραφικό υλικό</strong></label>
                        <textarea id="edit_gdpr_notice" name="gdpr_notice" class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars($editEvent['gdpr_notice'] ?? getDefaultEventGdprNotice()); ?></textarea>
                    </div>

                    <?php if (!empty($editImages)): ?>
                        <div class="form-group">
                            <label><strong>Υπάρχουσες Εικόνες</strong></label>
                            <div class="image-preview">
                                <?php foreach ($editImages as $img): ?>
                                    <div class="image-preview-item">
                                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Event Image">
                                        <button
                                            type="button"
                                            class="delete-btn"
                                            onclick="deleteEventImage(<?php echo (int)$img['ev_image_id']; ?>, <?php echo (int)$editEvent['event_id']; ?>)"
                                        >
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
                            id="edit_images"
                            name="images[]"
                            class="form-control-file"
                            multiple
                            accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                            data-existing-count="<?php echo count($editImages); ?>"
                            <?php echo count($editImages) >= EVENT_IMAGE_LIMIT ? 'disabled' : ''; ?>
                        >
                        <small class="text-muted d-block mt-1">
                            Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο <?php echo EVENT_IMAGE_LIMIT; ?> φωτογραφίες ανά εκδήλωση
                        </small>
                        <small class="text-muted d-block">
                            Οι νέες εικόνες θα προστεθούν στις υπάρχουσες. Αυτή τη στιγμή μπορείτε να προσθέσετε έως <?php echo max(0, EVENT_IMAGE_LIMIT - count($editImages)); ?> ακόμη.
                        </small>
                        <div id="editPreview" class="image-preview"></div>
                    </div>

                    <div class="d-flex gap-2" style="gap: 10px;">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save mr-1"></i>Αποθήκευση
                        </button>
                        <a href="events.php" class="btn btn-secondary-custom">
                            <i class="fas fa-times mr-1"></i>Ακύρωση
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="card card-custom">
            <div class="card-body p-0">
                <?php if (empty($events)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Δεν υπάρχουν εκδηλώσεις</h3>
                        <p>Κάντε κλικ στο "Νέα Εκδήλωση" για να δημιουργήσετε μία.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Εικόνα</th>
                                    <th>Τίτλος</th>
                                    <th>Ημ. Εκδήλωσης</th>
                                    <th>Ημ. Δημοσίευσης</th>
                                    <th>Περιγραφή</th>
                                    <th style="width: 150px;">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <?php $eventDateTime = new DateTime($event['event_date']); ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($event['images'])): ?>
                                                <img src="<?php echo htmlspecialchars($event['images'][0]); ?>" class="thumbnail" alt="Thumbnail">
                                            <?php else: ?>
                                                <div class="thumbnail d-flex align-items-center justify-content-center" style="background: #eee;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></td>
                                        <td><?php echo $eventDateTime->format('d/m/Y H:i'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($event['publish_date'])); ?></td>
                                        <td>
                                            <?php
                                                $description = $event['event_description'] ?? '';
                                                echo htmlspecialchars(mb_substr($description, 0, 80));
                                                if (mb_strlen($description) > 80) {
                                                    echo '...';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo (int)$event['event_id']; ?>" class="btn btn-sm btn-outline-primary mr-1" title="Επεξεργασία">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" class="js-confirm-submit" data-confirm-message="Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την εκδήλωση;" data-confirm-title="Επιβεβαίωση διαγραφής">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$event['event_id']; ?>">
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

<div class="modal fade modal-custom" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="modal-header" style="background:#2f6fb3;">
                    <h5 class="modal-title" style="color:#ffffff !important;">
                        <i class="fas fa-plus mr-2" style="color:#ffffff !important;"></i>Νέα Εκδήλωση
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
                            <label for="event_date"><strong>Ημερομηνία Εκδήλωσης *</strong></label>
                            <input type="date" class="form-control form-control-custom" id="event_date" name="event_date" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="event_time"><strong>Ώρα</strong></label>
                            <input type="time" class="form-control form-control-custom" id="event_time" name="event_time" value="09:00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="5" placeholder="Πληκτρολογήστε την περιγραφή της εκδήλωσης..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="gdpr_notice"><strong>Ενημέρωση GDPR για φωτογραφικό υλικό</strong></label>
                        <textarea class="form-control form-control-custom" id="gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars(getDefaultEventGdprNotice()); ?></textarea>
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
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο <?php echo EVENT_IMAGE_LIMIT; ?> φωτογραφίες ανά εκδήλωση
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

function deleteEventImage(imageId, eventId) {
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

        const eventIdInput = document.createElement('input');
        eventIdInput.type = 'hidden';
        eventIdInput.name = 'event_id';
        eventIdInput.value = String(eventId);

        form.appendChild(actionInput);
        form.appendChild(imageIdInput);
        form.appendChild(eventIdInput);
        document.body.appendChild(form);
        form.submit();
    }, {
        title: 'Επιβεβαίωση διαγραφής'
    });
}

function getEventImageLimit() {
    return <?php echo EVENT_IMAGE_LIMIT; ?>;
}

function getEventImageFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

function truncatePreviewFileName(fileName, maxLength) {
    if (fileName.length <= maxLength) {
        return fileName;
    }

    return fileName.slice(0, Math.max(0, maxLength - 3)) + '...';
}

function validateEventImageFile(file) {
    const warnings = [];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const maxFileSize = 5 * 1024 * 1024;
    const fileExt = (file.name.split('.').pop() || '').toLowerCase();

    if (!allowedExtensions.includes(fileExt)) {
        warnings.push(`Το αρχείο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο JPG, JPEG, PNG, GIF.`);
    }

    if (file.size > maxFileSize) {
        warnings.push(`Το αρχείο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 5MB.`);
    }

    if (!String(file.type || '').startsWith('image/')) {
        warnings.push(`Το αρχείο "${file.name}" δεν φαίνεται να είναι εικόνα.`);
    }

    return warnings;
}

function syncEventImageInputFiles(input, stagedFiles) {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const dataTransfer = new DataTransfer();
    stagedFiles.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

function renderEventImagePreview(preview, stagedFiles, onRemove) {
    if (!preview) {
        return;
    }

    preview.innerHTML = '';

    stagedFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'image-preview-item';

        const image = document.createElement('img');
        image.alt = file.name;

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'delete-btn';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.setAttribute('aria-label', `Αφαίρεση ${file.name}`);
        deleteBtn.addEventListener('click', function () {
            onRemove(index);
        });

        const caption = document.createElement('div');
        caption.className = 'preview-file-caption';
        caption.textContent = truncatePreviewFileName(file.name, 18);

        const reader = new FileReader();
        reader.onload = function (event) {
            image.src = String(event.target && event.target.result ? event.target.result : '');
        };
        reader.readAsDataURL(file);

        item.appendChild(image);
        item.appendChild(deleteBtn);
        item.appendChild(caption);
        preview.appendChild(item);
    });
}

function setupEventImageInput(input, previewId) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const eventImageLimit = getEventImageLimit();
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const stagedFiles = [];
    const stagedKeys = new Set();

    function updateInputState() {
        if (existingCount + stagedFiles.length >= eventImageLimit) {
            input.disabled = true;
        } else if (existingCount < eventImageLimit) {
            input.disabled = false;
        }
    }

    function removeStagedFile(index) {
        const removedFile = stagedFiles[index];
        if (!removedFile) {
            return;
        }

        stagedFiles.splice(index, 1);
        stagedKeys.delete(getEventImageFileKey(removedFile));
        syncEventImageInputFiles(input, stagedFiles);
        renderEventImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();
    }

    input.addEventListener('change', function () {
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];
        let reachedLimit = false;

        if (incomingFiles.length === 0) {
            return;
        }

        if (existingCount >= eventImageLimit) {
            warnings.push(`Η εκδήλωση έχει ήδη ${eventImageLimit} φωτογραφίες. Διαγράψτε πρώτα κάποια εικόνα για να προσθέσετε νέα.`);
        } else {
            incomingFiles.forEach((file) => {
                const fileKey = getEventImageFileKey(file);
                const validationWarnings = validateEventImageFile(file);

                if (validationWarnings.length > 0) {
                    warnings.push(...validationWarnings);
                    return;
                }

                if (stagedKeys.has(fileKey)) {
                    warnings.push(`Το αρχείο "${file.name}" έχει ήδη επιλεγεί.`);
                    return;
                }

                if (existingCount + stagedFiles.length >= eventImageLimit) {
                    if (!reachedLimit) {
                        const remainingSlots = Math.max(0, eventImageLimit - existingCount - stagedFiles.length);
                        warnings.push(`Μπορείτε να προσθέσετε μόνο ${remainingSlots} ακόμη φωτογραφία/ες σε αυτή την εκδήλωση.`);
                        reachedLimit = true;
                    }
                    return;
                }

                stagedFiles.push(file);
                stagedKeys.add(fileKey);
            });
        }

        syncEventImageInputFiles(input, stagedFiles);
        renderEventImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();

        if (warnings.length > 0) {
            showNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: 'Έλεγχος αρχείων',
                variant: 'warning'
            });
        }
    });

    updateInputState();
}

setupEventImageInput(document.getElementById('images'), 'imagePreview');
setupEventImageInput(document.getElementById('edit_images'), 'editPreview');

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
