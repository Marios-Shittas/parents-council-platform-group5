<?php
/**
 * Admin Events Management Page
 * Create, update, delete events and manage images
 */

require_once __DIR__ . '/../../app/services/EventsService.php';
require_once __DIR__ . '/../../app/includes/AdminEventsHelper.php';

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

$eventsService = new EventsService();
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $gdprNotice = trim($_POST['gdpr_notice'] ?? AdminEventsHelper::getDefaultGdprNotice());
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = date('Y-m-d');
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';

        if (!empty($title) && !empty($eventDate)) {
            $eventId = $eventsService->createEvent($title, $description, $eventDateTime, $publishDate, $gdprNotice);

            if ($eventId) {
                [$uploadedCount, $uploadErrors] = AdminEventsHelper::uploadEventImages($eventsService, $eventId, EVENT_IMAGE_LIMIT);

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
        $gdprNotice = trim($_POST['gdpr_notice'] ?? AdminEventsHelper::getDefaultGdprNotice());
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';

        if ($id > 0 && !empty($title) && !empty($eventDate)) {
            if ($eventsService->updateEvent($id, $title, $description, $eventDateTime, $publishDate, $gdprNotice)) {
                [$uploadedCount, $uploadErrors] = AdminEventsHelper::uploadEventImages($eventsService, $id, EVENT_IMAGE_LIMIT);

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
                    $filePath = AdminEventsHelper::resolveImageFilePath($img['image_path'] ?? '');
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
                $filePath = AdminEventsHelper::resolveImageFilePath($imageToDelete['image_path'] ?? '');
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
<body data-event-image-limit="<?php echo EVENT_IMAGE_LIMIT; ?>">

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
                        <textarea id="edit_gdpr_notice" name="gdpr_notice" class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars($editEvent['gdpr_notice'] ?? AdminEventsHelper::getDefaultGdprNotice()); ?></textarea>
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
                        <textarea class="form-control form-control-custom" id="gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars(AdminEventsHelper::getDefaultGdprNotice()); ?></textarea>
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

<script src="../assets/js/admin-events.js"></script>

</body>
</html>
