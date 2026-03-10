<?php
/**
 * Admin Events Management Page
 * Create, update, delete events and manage images
 */

require_once __DIR__ . '/../../app/services/EventsService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$eventsService = new EventsService();
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create new event
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        // Combine date and time
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';
        
        if (!empty($title) && !empty($eventDate)) {
            $eventId = $eventsService->createEvent($title, $description, $eventDateTime, $publishDate);
            
            if ($eventId) {
                $uploadedCount = 0;
                $uploadErrors = [];
                
                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = __DIR__ . '/../assets/Events_img/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $maxFileSize = 5 * 1024 * 1024; // 5MB
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        $fileName = basename($_FILES['images']['name'][$key]);
                        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                        
                        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει";
                            continue;
                        }
                        
                        $fileSize = $_FILES['images']['size'][$key];
                        $fileMime = mime_content_type($tmpName);
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        if (!in_array($fileExt, $allowedExtensions)) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση";
                            continue;
                        }
                        
                        if ($fileSize > $maxFileSize) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο";
                            continue;
                        }
                        
                        if (!getimagesize($tmpName)) {
                            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα";
                            continue;
                        }
                        
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                        $targetPath = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $imagePath = '/parents-council-platform-group5/public/assets/Events_img/' . $newFileName;
                            if ($eventsService->addImage($eventId, $imagePath)) {
                                $uploadedCount++;
                            }
                        }
                    }
                }
                
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
    
    // Update existing event
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventTime = trim($_POST['event_time'] ?? '00:00');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        
        // Combine date and time
        $eventDateTime = $eventDate . ' ' . $eventTime . ':00';
        
        if ($id > 0 && !empty($title) && !empty($eventDate)) {
            if ($eventsService->updateEvent($id, $title, $description, $eventDateTime, $publishDate)) {
                $uploadedCount = 0;
                $uploadErrors = [];
                
                // Handle new image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = __DIR__ . '/../assets/Events_img/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $maxFileSize = 5 * 1024 * 1024;
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        $fileName = basename($_FILES['images']['name'][$key]);
                        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                        
                        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        
                        $fileSize = $_FILES['images']['size'][$key];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        if (!in_array($fileExt, $allowedExtensions) || $fileSize > $maxFileSize || !getimagesize($tmpName)) {
                            continue;
                        }
                        
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                        $targetPath = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $imagePath = '/parents-council-platform-group5/public/assets/Events_img/' . $newFileName;
                            if ($eventsService->addImage($id, $imagePath)) {
                                $uploadedCount++;
                            }
                        }
                    }
                }
                
                if ($uploadedCount > 0) {
                    $message = "Η εκδήλωση ενημερώθηκε επιτυχώς με {$uploadedCount} νέα/ες εικόνα/ες!";
                } else {
                    $message = 'Η εκδήλωση ενημερώθηκε επιτυχώς!';
                }
                $messageType = 'success';
            } else {
                $message = 'Σφάλμα κατά την ενημέρωση της εκδήλωσης.';
                $messageType = 'danger';
            }
        }
    }
    
    // Delete event
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Get images to delete files
            $images = $eventsService->getImages($id);
            
            if ($eventsService->deleteEvent($id)) {
                // Delete image files
                foreach ($images as $img) {
                    $filePath = __DIR__ . '/..' . parse_url($img['image_path'], PHP_URL_PATH);
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
    
    // Delete single image
    if ($action === 'delete_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId > 0) {
            $images = $eventsService->getImages($_POST['event_id'] ?? 0);
            $imageToDelete = null;
            foreach ($images as $img) {
                if ($img['ev_image_id'] == $imageId) {
                    $imageToDelete = $img;
                    break;
                }
            }
            
            if ($imageToDelete && $eventsService->deleteImage($imageId)) {
                $filePath = __DIR__ . '/..' . parse_url($imageToDelete['image_path'], PHP_URL_PATH);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $message = 'Η εικόνα διαγράφηκε επιτυχώς!';
                $messageType = 'success';
            }
        }
    }
}

// Fetch all events
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
    <link rel="stylesheet" href="../assets/css/events.css">
    <link rel="stylesheet" href="../assets/css/admin_events.css">

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
            <button class="btn btn-primary-custom" data-toggle="modal" data-target="#createModal">
                <i class="fas fa-plus mr-1"></i>Νέα Εκδήλωση
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

        <div class="row">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Δεν υπάρχουν εκδηλώσεις. Δημιουργήστε μία νέα εκδήλωση.</div>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php
                        $eventDateTime = new DateTime($event['event_date']);
                        $dateFormatted = $eventDateTime->format('d/m/Y');
                        $timeFormatted = $eventDateTime->format('H:i');
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($event['images'])): ?>
                                <img src="<?php echo htmlspecialchars($event['images'][0]); ?>" class="card-img-top" alt="Event" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['event_title']); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="far fa-calendar mr-1"></i><?php echo $dateFormatted; ?>
                                    <i class="far fa-clock ml-2 mr-1"></i><?php echo $timeFormatted; ?>
                                </p>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(mb_substr($event['event_description'] ?? '', 0, 100)); ?>...
                                </p>
                            </div>
                            <div class="card-footer bg-white">
                                <button class="btn btn-sm btn-warning" onclick="editEvent(<?php echo $event['event_id']; ?>)">
                                    <i class="fas fa-edit"></i> Επεξεργασία
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?php echo $event['event_id']; ?>)">
                                    <i class="fas fa-trash"></i> Διαγραφή
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Νέα Εκδήλωση</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Τίτλος *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Ημερομηνία *</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Ώρα</label>
                        <input type="time" name="event_time" class="form-control" value="09:00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Ημερομηνία Δημοσίευσης</label>
                    <input type="date" name="publish_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Περιγραφή</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Εικόνες</label>
                    <input type="file" name="images[]" class="form-control-file" multiple accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Ακύρωση</button>
                <button type="submit" class="btn btn-primary">Δημιουργία</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function deleteEvent(id) {
    if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την εκδήλωση;')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function editEvent(id) {
    // Implement edit functionality - redirect to edit page or open modal
    alert('Edit functionality - ID: ' + id);
}
</script>

</body>
</html>
