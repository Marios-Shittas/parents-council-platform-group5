<?php
/**
 * Admin Events Management Page
 * Create, update, delete events and manage images
 */

require_once __DIR__ . '/../../app/services/EventsService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
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
                $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων";
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
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = date('Y-m-d');
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';

        if (!empty($title) && !empty($eventDate)) {
            $eventId = $eventsService->createEvent($title, $description, $eventDateTime, $publishDate);

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
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';

        if ($id > 0 && !empty($title) && !empty($eventDate)) {
            if ($eventsService->updateEvent($id, $title, $description, $eventDateTime, $publishDate)) {
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
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
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
                        <input type="file" id="edit_images" name="images[]" class="form-control-file" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                        <small class="text-muted d-block mt-1">
                            Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο
                        </small>
                        <small class="text-muted d-block">
                            Οι νέες εικόνες θα προστεθούν στις υπάρχουσες
                        </small>
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
                                    <th>Ημερομηνία Εκδήλωσης</th>
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
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$event['event_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την εκδήλωση;')" title="Διαγραφή">
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

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function deleteEventImage(imageId, eventId) {
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

    const eventIdInput = document.createElement('input');
    eventIdInput.type = 'hidden';
    eventIdInput.name = 'event_id';
    eventIdInput.value = String(eventId);

    form.appendChild(actionInput);
    form.appendChild(imageIdInput);
    form.appendChild(eventIdInput);
    document.body.appendChild(form);
    form.submit();
}

function validateAndPreviewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';

    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const maxFileSize = 5 * 1024 * 1024;
    let warnings = [];

    [...input.files].forEach((file) => {
        const fileExt = file.name.split('.').pop().toLowerCase();
        let hasWarning = false;

        if (!allowedExtensions.includes(fileExt)) {
            warnings.push(`Το αρχείο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο JPG, JPEG, PNG, GIF.`);
            hasWarning = true;
        }

        if (file.size > maxFileSize) {
            warnings.push(`Το αρχείο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 5MB.`);
            hasWarning = true;
        }

        if (!file.type.startsWith('image/')) {
            warnings.push(`Το αρχείο "${file.name}" δεν φαίνεται να είναι εικόνα.`);
            hasWarning = true;
        }

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

    if (warnings.length > 0) {
        alert('Προειδοποιήσεις:\n\n' + warnings.join('\n\n') + '\n\nΜπορείτε να προσπαθήσετε να ανεβάσετε τα αρχεία, αλλά μπορεί να απορριφθούν από τον διακομιστή.');
    }
}

const createImagesInput = document.getElementById('images');
if (createImagesInput) {
    createImagesInput.addEventListener('change', function() {
        validateAndPreviewImages(this, 'imagePreview');
    });
}

const editImagesInput = document.getElementById('edit_images');
if (editImagesInput) {
    editImagesInput.addEventListener('change', function() {
        let preview = document.getElementById('editPreview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.id = 'editPreview';
            preview.style.marginTop = '10px';
            this.parentElement.appendChild(preview);
        }

        validateAndPreviewImages(this, 'editPreview');
    });
}
</script>

</body>
</html>