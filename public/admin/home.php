<?php
require_once __DIR__ . '/../../app/services/EventsService.php';
require_once __DIR__ . '/../../app/services/AnnouncementsService.php';
require_once __DIR__ . '/../../app/services/UsefulInformationService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

function adminCalendarIsValidIsoDate($value)
{
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
}

function adminCalendarNormalizeTime($value)
{
    $value = trim((string)$value);
    return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : '09:00';
}

function adminCalendarGetEventImageUploadDir()
{
    return dirname(__DIR__) . '/assets/Events_img/';
}

function adminCalendarBuildEventImageWebPath($fileName)
{
    return '/parents-council-platform-group5/public/assets/Events_img/' . $fileName;
}

function adminCalendarGetAnnouncementImageUploadDir()
{
    return dirname(__DIR__) . '/assets/Announcements_img/';
}

function adminCalendarBuildAnnouncementImageWebPath($fileName)
{
    return '/parents-council-platform-group5/public/assets/Announcements_img/' . $fileName;
}

function adminCalendarGetAnnouncementAttachmentUploadDir()
{
    return dirname(__DIR__) . '/assets/Announcements_docs/';
}

function adminCalendarBuildAnnouncementAttachmentWebPath($fileName)
{
    return '/parents-council-platform-group5/public/assets/Announcements_docs/' . $fileName;
}

function adminCalendarGetDefaultAnnouncementGdprNotice()
{
    return 'Το φωτογραφικό υλικό και τα συνημμένα έγγραφα των ανακοινώσεων δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.';
}

function adminCalendarGetDefaultEventGdprNotice()
{
    return 'Το φωτογραφικό υλικό της εκδήλωσης δημοσιεύεται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με τις ισχύουσες εγκρίσεις/πολιτικές του σχολείου.';
}

function adminCalendarUploadImages($fileField, $uploadDir, $pathBuilder, $persistImageCallback, $maxFiles = null, $persistErrorCallback = null)
{
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES[$fileField]['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 5 * 1024 * 1024;

    foreach ($_FILES[$fileField]['tmp_name'] as $key => $tmpName) {
        if ($maxFiles !== null && $uploadedCount >= $maxFiles) {
            $uploadErrors[] = "Μπορούν να αποθηκευτούν έως {$maxFiles} εικόνες.";
            break;
        }

        $fileName = basename($_FILES[$fileField]['name'][$key] ?? '');
        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $uploadError = $_FILES[$fileField]['error'][$key] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$uploadError})";
            continue;
        }

        $fileSize = $_FILES[$fileField]['size'][$key] ?? 0;
        $fileMime = mime_content_type($tmpName);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions, true)) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση.";
            continue;
        }

        if ($fileSize > $maxFileSize) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο.";
            continue;
        }

        if (!getimagesize($tmpName)) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα.";
            continue;
        }

        if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with((string)$fileMime, 'image/')) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας.";
            continue;
        }

        if (!file_exists($tmpName)) {
            $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε.";
            continue;
        }

        if (!is_writable($uploadDir)) {
            $uploadErrors[] = "Ο φάκελος αποθήκευσης δεν είναι εγγράψιμος.";
            continue;
        }

        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του αρχείου '{$safeFileName}'.";
            continue;
        }

        $imagePath = $pathBuilder($newFileName);
        if ($persistImageCallback($imagePath)) {
            $uploadedCount++;
            continue;
        }

        if (file_exists($targetPath)) {
            unlink($targetPath);
        }

        if (is_callable($persistErrorCallback)) {
            $persistError = trim((string)$persistErrorCallback());
            if ($persistError !== '') {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν αποθηκεύτηκε. {$persistError}";
                continue;
            }
        }

        $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων.";
    }

    return [$uploadedCount, $uploadErrors];
}

function adminCalendarUploadAnnouncementAttachments($announcementsService, $announcementId)
{
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES['attachments']['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    $uploadDir = adminCalendarGetAnnouncementAttachmentUploadDir();
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    clearstatcache(true, $uploadDir);

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
        clearstatcache(true, $uploadDir);
    }

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxFileSize = 8 * 1024 * 1024;

    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['attachments']['name'][$key] ?? '');
        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $uploadError = $_FILES['attachments']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Το συνημμένο '{$safeFileName}' απέτυχε να ανέβει (Error: {$uploadError})";
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
            $uploadErrors[] = "Το προσωρινό αρχείο για το συνημμένο '{$safeFileName}' δεν βρέθηκε.";
            continue;
        }

        if (!is_writable($uploadDir)) {
            $uploadErrors[] = "Ο φάκελος συνημμένων δεν είναι εγγράψιμος.";
            continue;
        }

        $newFileName = uniqid('announcement_attachment_', true) . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του συνημμένου '{$safeFileName}'.";
            continue;
        }

        $attachmentPath = adminCalendarBuildAnnouncementAttachmentWebPath($newFileName);
        if ($announcementsService->addAttachment($announcementId, $attachmentPath, $fileName)) {
            $uploadedCount++;
            continue;
        }

        if (file_exists($targetPath)) {
            unlink($targetPath);
        }

        $serviceError = trim((string)$announcementsService->getLastOperationError());
        if ($serviceError !== '') {
            $uploadErrors[] = "Το συνημμένο '{$safeFileName}' δεν αποθηκεύτηκε. {$serviceError}";
        } else {
            $uploadErrors[] = "Αποτυχία αποθήκευσης του συνημμένου '{$safeFileName}' στη βάση δεδομένων.";
        }
    }

    return [$uploadedCount, $uploadErrors];
}

function adminCalendarBuildItems($eventsService, $announcementsService, $usefulInformationService)
{
    $items = [];
    $typeOrder = [
        'holiday' => 0,
        'event' => 1,
        'announcement' => 2,
    ];

    foreach ($eventsService->getAllEvents() as $event) {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', (string)($event['event_date'] ?? ''));
        if (!$dateTime) {
            $dateTime = new DateTime((string)($event['event_date'] ?? 'now'));
        }

        $items[] = [
            'id' => (int)($event['event_id'] ?? 0),
            'type' => 'event',
            'title' => (string)($event['event_title'] ?? ''),
            'description' => (string)($event['event_description'] ?? ''),
            'date' => $dateTime->format('Y-m-d'),
            'time' => $dateTime->format('H:i'),
            'sort_key' => $dateTime->format('Y-m-d H:i:s'),
            'source_url' => 'events.php?edit=' . (int)($event['event_id'] ?? 0),
            'source_label' => 'Πάνελ Εκδηλώσεων',
        ];
    }

    foreach ($announcementsService->getAllAnnouncements() as $announcement) {
        $announcementDate = (string)($announcement['announcement_date'] ?? $announcement['publish_date'] ?? '');
        if (!adminCalendarIsValidIsoDate($announcementDate)) {
            continue;
        }

        $items[] = [
            'id' => (int)($announcement['announcement_id'] ?? 0),
            'type' => 'announcement',
            'title' => (string)($announcement['announcement_title'] ?? ''),
            'description' => (string)($announcement['announcement_description'] ?? ''),
            'date' => $announcementDate,
            'time' => null,
            'sort_key' => $announcementDate . ' 00:00:00',
            'source_url' => 'announcements.php?edit=' . (int)($announcement['announcement_id'] ?? 0),
            'source_label' => 'Πάνελ Ανακοινώσεων',
        ];
    }

    foreach ($usefulInformationService->getHolidayCalendarItems() as $holiday) {
        $items[] = [
            'id' => null,
            'type' => 'holiday',
            'title' => (string)($holiday['title'] ?? ''),
            'description' => '',
            'date' => (string)($holiday['date'] ?? ''),
            'time' => null,
            'sort_key' => (string)($holiday['date'] ?? '') . ' 00:00:00',
            'source_url' => 'useful-information.php',
            'source_label' => 'Χρήσιμες Πληροφορίες',
        ];
    }

    usort($items, static function ($left, $right) use ($typeOrder) {
        $leftDate = (string)($left['sort_key'] ?? '');
        $rightDate = (string)($right['sort_key'] ?? '');

        if ($leftDate !== $rightDate) {
            return strcmp($leftDate, $rightDate);
        }

        $leftOrder = $typeOrder[$left['type'] ?? 'announcement'] ?? 99;
        $rightOrder = $typeOrder[$right['type'] ?? 'announcement'] ?? 99;

        if ($leftOrder !== $rightOrder) {
            return $leftOrder <=> $rightOrder;
        }

        return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
    });

    return $items;
}

$eventsService = new EventsService();
$announcementsService = new AnnouncementsService();
$usefulInformationService = new UsefulInformationService();

$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!adminCalendarIsValidIsoDate($selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectDate = $_POST['redirect_date'] ?? $selectedDate;
    if (!adminCalendarIsValidIsoDate($redirectDate)) {
        $redirectDate = date('Y-m-d');
    }

    $flashMessage = 'Η ενέργεια ολοκληρώθηκε.';
    $flashType = 'info';

    if ($action === 'create_event_from_calendar') {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $gdprNotice = trim((string)($_POST['gdpr_notice'] ?? adminCalendarGetDefaultEventGdprNotice()));
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        $eventTime = adminCalendarNormalizeTime($_POST['event_time'] ?? '09:00');

        if ($title === '' || !adminCalendarIsValidIsoDate($eventDate)) {
            $flashMessage = 'Ο τίτλος και η ημερομηνία της εκδήλωσης είναι υποχρεωτικά.';
            $flashType = 'danger';
        } else {
            $publishDate = date('Y-m-d');
            $eventDateTime = $eventDate . ' ' . $eventTime . ':00';
            $eventId = $eventsService->createEvent($title, $description, $eventDateTime, $publishDate, $gdprNotice);

            if ($eventId) {
                [$uploadedCount, $uploadErrors] = adminCalendarUploadImages(
                    'images',
                    adminCalendarGetEventImageUploadDir(),
                    'adminCalendarBuildEventImageWebPath',
                    static function ($imagePath) use ($eventsService, $eventId) {
                        return $eventsService->addImage($eventId, $imagePath);
                    },
                    6,
                    static function () use ($eventsService) {
                        return $eventsService->getLastOperationError();
                    }
                );

                if ($uploadedCount > 0) {
                    $flashMessage = "Η εκδήλωση δημιουργήθηκε με {$uploadedCount} εικόνα/ες και εμφανίζεται πλέον στο ημερολόγιο και στη σελίδα εκδηλώσεων.";
                } else {
                    $flashMessage = 'Η εκδήλωση δημιουργήθηκε και εμφανίζεται πλέον στο ημερολόγιο και στη σελίδα εκδηλώσεων.';
                }

                if (!empty($uploadErrors)) {
                    $flashMessage .= ' Προβλήματα αρχείων: ' . implode(' | ', $uploadErrors);
                    $flashType = 'warning';
                } else {
                    $flashType = 'success';
                }

                $redirectDate = $eventDate;
            } else {
                $flashMessage = 'Σφάλμα κατά τη δημιουργία της εκδήλωσης.';
                $flashType = 'danger';
            }
        }
    }

    if ($action === 'create_announcement_from_calendar') {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $gdprNotice = trim((string)($_POST['gdpr_notice'] ?? adminCalendarGetDefaultAnnouncementGdprNotice()));
        $announcementDate = trim((string)($_POST['announcement_date'] ?? ''));
        $publishDate = trim((string)($_POST['publish_date'] ?? date('Y-m-d')));

        if ($title === '' || !adminCalendarIsValidIsoDate($announcementDate)) {
            $flashMessage = 'Ο τίτλος και η ημερομηνία της ανακοίνωσης είναι υποχρεωτικά.';
            $flashType = 'danger';
        } else {
            if (!adminCalendarIsValidIsoDate($publishDate)) {
                $publishDate = date('Y-m-d');
            }

            $announcementId = $announcementsService->createAnnouncement(
                $title,
                $description,
                $announcementDate,
                $publishDate,
                $gdprNotice
            );

            if ($announcementId) {
                [$uploadedCount, $uploadErrors] = adminCalendarUploadImages(
                    'images',
                    adminCalendarGetAnnouncementImageUploadDir(),
                    'adminCalendarBuildAnnouncementImageWebPath',
                    static function ($imagePath) use ($announcementsService, $announcementId) {
                        return $announcementsService->addImage($announcementId, $imagePath);
                    },
                    6,
                    static function () use ($announcementsService) {
                        return $announcementsService->getLastOperationError();
                    }
                );
                [$uploadedAttachmentsCount, $attachmentErrors] = adminCalendarUploadAnnouncementAttachments(
                    $announcementsService,
                    $announcementId
                );

                if ($uploadedCount > 0 || $uploadedAttachmentsCount > 0) {
                    $flashMessage = 'Η ανακοίνωση δημιουργήθηκε';
                    if ($uploadedCount > 0) {
                        $flashMessage .= " με {$uploadedCount} εικόνα/ες";
                    }
                    if ($uploadedAttachmentsCount > 0) {
                        $flashMessage .= ($uploadedCount > 0 ? ' και ' : ' με ') . "{$uploadedAttachmentsCount} συνημμένο/α";
                    }
                    $flashMessage .= ' και εμφανίζεται πλέον στο πάνελ ανακοινώσεων.';
                } else {
                    $flashMessage = 'Η ανακοίνωση δημιουργήθηκε και εμφανίζεται πλέον στο πάνελ ανακοινώσεων.';
                }

                $allUploadErrors = array_merge($uploadErrors, $attachmentErrors);
                if (!empty($allUploadErrors)) {
                    $flashMessage .= ' Προβλήματα αρχείων: ' . implode(' | ', $allUploadErrors);
                    $flashType = 'warning';
                } else {
                    $flashType = 'success';
                }

                $redirectDate = $announcementDate;
            } else {
                $flashMessage = 'Σφάλμα κατά τη δημιουργία της ανακοίνωσης.';
                $flashType = 'danger';
            }
        }
    }

    if ($action === 'create_holiday_from_calendar') {
        $holidayName = trim((string)($_POST['title'] ?? ''));
        $holidayDate = trim((string)($_POST['holiday_date'] ?? ''));

        if ($holidayName === '' || !adminCalendarIsValidIsoDate($holidayDate)) {
            $flashMessage = 'Το όνομα και η ημερομηνία της αργίας είναι υποχρεωτικά.';
            $flashType = 'danger';
        } else {
            $saved = $usefulInformationService->addHolidayFromIsoDate($holidayDate, $holidayName);

            if ($saved) {
                $flashMessage = 'Η αργία αποθηκεύτηκε και εμφανίζεται πλέον στο ημερολόγιο και στις χρήσιμες πληροφορίες.';
                $flashType = 'success';
                $redirectDate = $holidayDate;
            } else {
                $flashMessage = 'Σφάλμα κατά την αποθήκευση της αργίας. ' . $usefulInformationService->getLastError();
                $flashType = 'danger';
            }
        }
    }

    $_SESSION['flash_message'] = $flashMessage;
    $_SESSION['flash_message_type'] = $flashType;

    header('Location: home.php?date=' . urlencode($redirectDate));
    exit;
}

$calendarItems = adminCalendarBuildItems($eventsService, $announcementsService, $usefulInformationService);
$summary = [
    'total' => count($calendarItems),
    'events' => count(array_filter($calendarItems, static fn ($item) => ($item['type'] ?? '') === 'event')),
    'announcements' => count(array_filter($calendarItems, static fn ($item) => ($item['type'] ?? '') === 'announcement')),
    'holidays' => count(array_filter($calendarItems, static fn ($item) => ($item['type'] ?? '') === 'holiday')),
];

$calendarPayload = [
    'selectedDate' => $selectedDate,
    'today' => date('Y-m-d'),
    'items' => $calendarItems,
];
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_home.css">

    <title>Dashboard Ημερολογίου - Admin</title>
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <section class="dashboard-hero card-custom">
            <div>
                <p class="dashboard-kicker">Admin Dashboard</p>
                <h1><i class="fas fa-calendar-check mr-2"></i>Κεντρικό Ημερολόγιο Διαχείρισης</h1>
                <p class="dashboard-subtitle">
                    Εδώ ο admin βλέπει συγκεντρωμένα τι υπάρχει σε κάθε ημερομηνία και μπορεί να καταχωρεί
                    νέα εκδήλωση, ανακοίνωση ή αργία χωρίς να φεύγει από το dashboard.
                </p>
            </div>
            <div class="dashboard-hero-actions">
                <a href="events.php" class="btn btn-outline-primary dashboard-link-btn">Πάνελ Εκδηλώσεων</a>
                <a href="announcements.php" class="btn btn-outline-primary dashboard-link-btn">Πάνελ Ανακοινώσεων</a>
                <a href="useful-information.php" class="btn btn-outline-primary dashboard-link-btn">Χρήσιμες Πληροφορίες</a>
            </div>
        </section>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType ?: 'info'); ?> alert-dismissible fade show mt-4" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <section class="dashboard-stats">
            <article class="stat-card card-custom">
                <span class="stat-label">Σύνολο εγγραφών</span>
                <strong class="stat-value"><?php echo (int)$summary['total']; ?></strong>
            </article>
            <article class="stat-card card-custom">
                <span class="stat-label">Εκδηλώσεις</span>
                <strong class="stat-value"><?php echo (int)$summary['events']; ?></strong>
            </article>
            <article class="stat-card card-custom">
                <span class="stat-label">Ανακοινώσεις</span>
                <strong class="stat-value"><?php echo (int)$summary['announcements']; ?></strong>
            </article>
            <article class="stat-card card-custom">
                <span class="stat-label">Αργίες</span>
                <strong class="stat-value"><?php echo (int)$summary['holidays']; ?></strong>
            </article>
        </section>

        <section class="calendar-dashboard-shell">
            <div id="admin-calendar-app"></div>
        </section>
    </main>
</div>

<div class="modal fade modal-custom" id="createEventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_event_from_calendar">
                <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>" data-redirect-date-field>

                <div class="modal-header admin-modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus mr-2"></i>Νέα Εκδήλωση</h5>
                    <button type="button" class="close admin-modal-close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="selected-date-hint">Επιλεγμένη ημέρα: <strong data-calendar-selected-label></strong></p>

                    <div class="form-group">
                        <label for="calendar_event_title"><strong>Τίτλος *</strong></label>
                        <input type="text" class="form-control form-control-custom" id="calendar_event_title" name="title" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="calendar_event_date"><strong>Ημερομηνία *</strong></label>
                            <input type="date" class="form-control form-control-custom" id="calendar_event_date" name="event_date" required data-calendar-date-field>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="calendar_event_time"><strong>Ώρα</strong></label>
                            <input type="time" class="form-control form-control-custom" id="calendar_event_time" name="event_time" value="09:00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="calendar_event_description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="calendar_event_description" name="description" rows="5" placeholder="Πληκτρολογήστε περιγραφή για την εκδήλωση..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="calendar_event_gdpr_notice"><strong>Ενημέρωση GDPR για φωτογραφικό υλικό</strong></label>
                        <textarea class="form-control form-control-custom" id="calendar_event_gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars(adminCalendarGetDefaultEventGdprNotice()); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="calendar_event_images"><strong>Εικόνες</strong></label>
                        <input type="file" class="form-control-file" id="calendar_event_images" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο 6 εικόνες ανά εκδήλωση
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">Αποθήκευση Εκδήλωσης</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-custom" id="createAnnouncementModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_announcement_from_calendar">
                <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>" data-redirect-date-field>

                <div class="modal-header admin-modal-header">
                    <h5 class="modal-title"><i class="fas fa-bullhorn mr-2"></i>Νέα Ανακοίνωση</h5>
                    <button type="button" class="close admin-modal-close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="selected-date-hint">Επιλεγμένη ημέρα: <strong data-calendar-selected-label></strong></p>

                    <div class="form-group">
                        <label for="calendar_announcement_title"><strong>Τίτλος *</strong></label>
                        <input type="text" class="form-control form-control-custom" id="calendar_announcement_title" name="title" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="calendar_announcement_date"><strong>Ημερομηνία Ανακοίνωσης *</strong></label>
                            <input type="date" class="form-control form-control-custom" id="calendar_announcement_date" name="announcement_date" required data-calendar-date-field>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="calendar_publish_date"><strong>Ημερομηνία Δημοσίευσης</strong></label>
                            <input type="date" class="form-control form-control-custom" id="calendar_publish_date" name="publish_date" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="calendar_announcement_description"><strong>Περιγραφή</strong></label>
                        <textarea class="form-control form-control-custom" id="calendar_announcement_description" name="description" rows="5" placeholder="Πληκτρολογήστε το κείμενο της ανακοίνωσης..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="calendar_announcement_gdpr_notice"><strong>Ενημέρωση GDPR για φωτογραφικό υλικό</strong></label>
                        <textarea class="form-control form-control-custom" id="calendar_announcement_gdpr_notice" name="gdpr_notice" rows="3"><?php echo htmlspecialchars(adminCalendarGetDefaultAnnouncementGdprNotice()); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="calendar_announcement_images"><strong>Εικόνες</strong></label>
                        <input type="file" class="form-control-file" id="calendar_announcement_images" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο 6 εικόνες ανά ανακοίνωση
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="calendar_announcement_attachments"><strong>Συνημμένες Επιστολές</strong></label>
                        <input type="file" class="form-control-file" id="calendar_announcement_attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG | Μέγιστο μέγεθος: 8MB ανά αρχείο
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">Αποθήκευση Ανακοίνωσης</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-custom" id="createHolidayModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create_holiday_from_calendar">
                <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>" data-redirect-date-field>

                <div class="modal-header admin-modal-header">
                    <h5 class="modal-title"><i class="fas fa-umbrella-beach mr-2"></i>Νέα Αργία</h5>
                    <button type="button" class="close admin-modal-close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="selected-date-hint">Επιλεγμένη ημέρα: <strong data-calendar-selected-label></strong></p>

                    <div class="form-group">
                        <label for="calendar_holiday_title"><strong>Όνομα Αργίας *</strong></label>
                        <input type="text" class="form-control form-control-custom" id="calendar_holiday_title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="calendar_holiday_date"><strong>Ημερομηνία *</strong></label>
                        <input type="date" class="form-control form-control-custom" id="calendar_holiday_date" name="holiday_date" required data-calendar-date-field>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary-custom">Αποθήκευση Αργίας</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.adminCalendarData = <?php echo json_encode(
    $calendarPayload,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-home-calendar.js"></script>
</body>
</html>
