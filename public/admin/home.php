<?php
require_once __DIR__ . '/../../app/services/EventsService.php';
require_once __DIR__ . '/../../app/services/AnnouncementsService.php';
require_once __DIR__ . '/../../app/services/HomePageService.php';
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

function adminHomeTrim($value)
{
    return trim((string)$value);
}

function adminHomeTextarea($value)
{
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    return trim($value);
}

function adminHomeGetBannerUploadDir()
{
    return dirname(__DIR__) . '/assets/Home_img/';
}

function adminHomeBuildBannerWebPath($fileName)
{
    return '/parents-council-platform-group5/public/assets/Home_img/' . $fileName;
}

function adminHomeDeleteManagedBannerImage($path)
{
    $trimmed = trim((string)$path);
    $managedPrefix = '/parents-council-platform-group5/public/assets/Home_img/';

    if ($trimmed === '' || strpos($trimmed, $managedPrefix) !== 0) {
        return;
    }

    $filePath = adminHomeGetBannerUploadDir() . basename($trimmed);
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

function adminHomeUploadBannerImage($fileField, $existingPath)
{
    $upload = $_FILES[$fileField] ?? null;
    if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [$existingPath, ''];
    }

    $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        return [$existingPath, "Το αρχείο '{$fileField}' απέτυχε να ανέβει (Error: {$uploadError})."];
    }

    $tmpName = (string)($upload['tmp_name'] ?? '');
    $fileName = basename((string)($upload['name'] ?? ''));
    $fileSize = (int)($upload['size'] ?? 0);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileMime = mime_content_type($tmpName);
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxFileSize = 5 * 1024 * 1024;

    if (!in_array($fileExt, $allowedExtensions, true)) {
        return [$existingPath, "Το αρχείο '{$fileName}' δεν έχει έγκυρη επέκταση."];
    }

    if ($fileSize > $maxFileSize) {
        return [$existingPath, "Το αρχείο '{$fileName}' είναι πολύ μεγάλο. Μέγιστο μέγεθος: 5MB."];
    }

    if (!file_exists($tmpName) || !getimagesize($tmpName)) {
        return [$existingPath, "Το αρχείο '{$fileName}' δεν είναι έγκυρη εικόνα."];
    }

    if (!str_starts_with((string)$fileMime, 'image/')) {
        return [$existingPath, "Το αρχείο '{$fileName}' δεν έχει έγκυρο τύπο εικόνας."];
    }

    $uploadDir = adminHomeGetBannerUploadDir();
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    clearstatcache(true, $uploadDir);

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
        clearstatcache(true, $uploadDir);
    }

    if (!is_writable($uploadDir)) {
        return [$existingPath, 'Ο φάκελος αποθήκευσης του banner δεν είναι εγγράψιμος.'];
    }

    $newFileName = uniqid('home_banner_', true) . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return [$existingPath, "Αποτυχία μεταφόρτωσης του αρχείου '{$fileName}'."];
    }

    adminHomeDeleteManagedBannerImage($existingPath);

    return [adminHomeBuildBannerWebPath($newFileName), ''];
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
$homePageService = new HomePageService();
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
    $redirectHomeTab = trim((string)($_POST['home_tab'] ?? ($_GET['home_tab'] ?? 'hero_section')));
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

    if ($action === 'update_home_content_section') {
        $sectionKey = trim((string)($_POST['section_key'] ?? ''));
        $saved = false;

        switch ($sectionKey) {
            case 'banner_section':
                $existingBannerSection = $homePageService->getSection('banner_section');
                $existingSlides = is_array($existingBannerSection['content']['slides'] ?? null)
                    ? $existingBannerSection['content']['slides']
                    : [];
                $updatedSlides = [];
                $bannerErrors = [];

                for ($i = 1; $i <= 3; $i++) {
                    $existingSlide = is_array($existingSlides[$i - 1] ?? null) ? $existingSlides[$i - 1] : [];
                    $existingPath = adminHomeTrim($_POST["current_banner_{$i}_src"] ?? ($existingSlide['src'] ?? ''));
                    $hideSlide = isset($_POST["banner_{$i}_hide"]) && $_POST["banner_{$i}_hide"] === '1';
                    $deleteSlide = isset($_POST["banner_{$i}_delete"]) && $_POST["banner_{$i}_delete"] === '1';
                    [$uploadedPath, $uploadError] = adminHomeUploadBannerImage("banner_{$i}_image", $existingPath);

                    if ($uploadError !== '') {
                        $bannerErrors[] = $uploadError;
                    }

                    if ($deleteSlide) {
                        adminHomeDeleteManagedBannerImage($uploadedPath);
                        $updatedSlides[] = [
                            'src' => '',
                            'alt' => trim((string)($existingSlide['alt'] ?? '')) !== ''
                                ? (string)$existingSlide['alt']
                                : 'Banner αρχικής σελίδας ' . $i,
                            'hidden' => false,
                        ];
                        continue;
                    }

                    $updatedSlides[] = [
                        'src' => $uploadedPath,
                        'alt' => trim((string)($existingSlide['alt'] ?? '')) !== ''
                            ? (string)$existingSlide['alt']
                            : 'Banner αρχικής σελίδας ' . $i,
                        'hidden' => $hideSlide,
                    ];
                }

                $saved = $homePageService->updateSection(
                    'banner_section',
                    adminHomeTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'slides' => $updatedSlides,
                    ]
                );

                if ($saved && !empty($bannerErrors)) {
                    $flashMessage = 'Το banner της αρχικής ενημερώθηκε, αλλά προέκυψαν προβλήματα σε ορισμένες εικόνες: ' . implode(' | ', $bannerErrors);
                    $flashType = 'warning';
                }
                break;

            case 'hero_section':
                $saved = $homePageService->updateSection(
                    'hero_section',
                    adminHomeTrim($_POST['title'] ?? ''),
                    adminHomeTextarea($_POST['subtitle'] ?? ''),
                    [
                        'kicker' => adminHomeTrim($_POST['kicker'] ?? ''),
                        'announcements_button_label' => adminHomeTrim($_POST['announcements_button_label'] ?? ''),
                        'events_button_label' => adminHomeTrim($_POST['events_button_label'] ?? ''),
                    ]
                );
                break;

            case 'calendar_section':
                $saved = $homePageService->updateSection(
                    'calendar_section',
                    adminHomeTrim($_POST['title'] ?? ''),
                    '',
                    []
                );
                break;

            case 'announcements_section':
                $saved = $homePageService->updateSection(
                    'announcements_section',
                    adminHomeTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'button_label' => adminHomeTrim($_POST['button_label'] ?? ''),
                    ]
                );
                break;

            case 'events_section':
                $saved = $homePageService->updateSection(
                    'events_section',
                    adminHomeTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'button_label' => adminHomeTrim($_POST['button_label'] ?? ''),
                    ]
                );
                break;
        }

        if ($saved) {
            if ($flashType !== 'warning') {
                $flashMessage = 'Το περιεχόμενο της αρχικής σελίδας ενημερώθηκε επιτυχώς.';
                $flashType = 'success';
            }
        } else {
            $serviceError = trim((string)$homePageService->getLastError());
            $flashMessage = $serviceError !== ''
                ? 'Σφάλμα αποθήκευσης περιεχομένου: ' . $serviceError
                : 'Δεν ήταν δυνατή η αποθήκευση του περιεχομένου της αρχικής σελίδας.';
            $flashType = 'danger';
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

    $redirectUrl = 'home.php?date=' . urlencode($redirectDate);
    if ($action === 'update_home_content_section' && $redirectHomeTab !== '') {
        $redirectUrl .= '&home_tab=' . urlencode($redirectHomeTab);
        $redirectUrl .= '#home-content-management';
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$homeContentTabs = [
    'banner_section' => ['label' => 'Banner', 'icon' => 'fas fa-images'],
    'hero_section' => ['label' => 'Κεντρικό Μήνυμα', 'icon' => 'fas fa-home'],
    'calendar_section' => ['label' => 'Ημερολόγιο', 'icon' => 'fas fa-calendar-alt'],
    'announcements_section' => ['label' => 'Ανακοινώσεις', 'icon' => 'fas fa-bullhorn'],
    'events_section' => ['label' => 'Εκδηλώσεις', 'icon' => 'fas fa-star'],
];
$activeHomeTab = $_GET['home_tab'] ?? 'hero_section';
if (!isset($homeContentTabs[$activeHomeTab])) {
    $activeHomeTab = 'banner_section';
}

$homeSections = $homePageService->getAllSections();
$bannerContentSection = $homeSections['banner_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$heroContentSection = $homeSections['hero_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$calendarContentSection = $homeSections['calendar_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$announcementsContentSection = $homeSections['announcements_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$eventsContentSection = $homeSections['events_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];

$defaultBannerSlides = [
    ['src' => '/parents-council-platform-group5/public/assets/img/home-school-banner.png', 'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 1'],
    ['src' => '/parents-council-platform-group5/public/assets/img/home-school-banner-2.png', 'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 2'],
    ['src' => '/parents-council-platform-group5/public/assets/img/home-school-banner-3.png', 'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 3'],
];
$bannerSlidesForEditor = [];
for ($i = 0; $i < 3; $i++) {
    $storedSlide = is_array($bannerContentSection['content']['slides'][$i] ?? null) ? $bannerContentSection['content']['slides'][$i] : [];
    $defaultSlide = $defaultBannerSlides[$i];
    $bannerSlidesForEditor[] = [
        'src' => trim((string)($storedSlide['src'] ?? $defaultSlide['src'])),
        'alt' => trim((string)($storedSlide['alt'] ?? $defaultSlide['alt'])),
        'hidden' => !empty($storedSlide['hidden']),
    ];
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
    <link rel="stylesheet" href="../assets/css/admin_css/admin_useful_information.css">
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

        <section class="home-content-management" id="home-content-management">
            <div class="card card-custom page-intro">
                <p class="mb-2"><strong>Διαχείριση δημόσιου περιεχομένου αρχικής σελίδας</strong></p>
                <p>Από εδώ ενημερώνεις τα βασικά κείμενα και τους τίτλους που προβάλλονται στην αρχική σελίδα, τόσο για τους επισκέπτες όσο και για τους συνδεδεμένους γονείς. Κάθε ενότητα αποθηκεύεται ξεχωριστά, ώστε να επεξεργάζεσαι με έλεγχο το Hero, το block του ημερολογίου και τις ενότητες ανακοινώσεων και εκδηλώσεων.</p>
            </div>

            <ul class="nav nav-tabs admin-section-tabs mb-4" role="tablist">
                <?php foreach ($homeContentTabs as $tabKey => $tab): ?>
                    <?php $isActiveTab = $activeHomeTab === $tabKey; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $isActiveTab ? 'active' : ''; ?>"
                           id="tab-<?php echo htmlspecialchars($tabKey); ?>-link"
                           data-toggle="tab"
                           href="#tab-<?php echo htmlspecialchars($tabKey); ?>"
                           role="tab"
                           aria-controls="tab-<?php echo htmlspecialchars($tabKey); ?>"
                           aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>">
                            <i class="<?php echo htmlspecialchars($tab['icon']); ?> mr-2"></i><?php echo htmlspecialchars($tab['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content admin-section-tabs-content">
                <section class="card card-custom section-editor tab-pane fade <?php echo $activeHomeTab === 'banner_section' ? 'show active' : ''; ?>" id="tab-banner_section" role="tabpanel" aria-labelledby="tab-banner_section-link">
                    <div class="section-editor__header">
                        <div>
                            <h2>Banner Αρχικής Σελίδας</h2>
                            <p>Από εδώ μπορείς να αλλάζεις τις 3 εικόνες που εμφανίζονται στο επάνω slider της αρχικής σελίδας.</p>
                        </div>
                        <span class="section-editor__icon"><i class="fas fa-images"></i></span>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_home_content_section">
                        <input type="hidden" name="section_key" value="banner_section">
                        <input type="hidden" name="home_tab" value="banner_section">
                        <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($bannerContentSection['title'] ?? 'Banner Αρχικής'); ?>">

                        <div class="section-form-grid">
                            <?php foreach ($bannerSlidesForEditor as $index => $slide): ?>
                                <div class="editor-subcard">
                                    <h3>Slide <?php echo $index + 1; ?></h3>
                                    <input type="hidden" name="current_banner_<?php echo $index + 1; ?>_src" value="<?php echo htmlspecialchars($slide['src']); ?>">

                                    <div class="home-banner-admin-preview">
                                        <img src="<?php echo htmlspecialchars($slide['src']); ?>" alt="<?php echo htmlspecialchars($slide['alt']); ?>">
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check home-banner-remove-check">
                                            <label class="form-check-label" for="banner_<?php echo $index + 1; ?>_hide">
                                                Απόκρυψη από την αρχική σελίδα (Hide)
                                            </label>
                                            <input class="form-check-input" type="checkbox" name="banner_<?php echo $index + 1; ?>_hide" value="1" id="banner_<?php echo $index + 1; ?>_hide" <?php echo !empty($slide['hidden']) ? 'checked' : ''; ?>>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check home-banner-delete-check">
                                            <label class="form-check-label" for="banner_<?php echo $index + 1; ?>_delete">
                                                Διαγραφή φωτογραφίας
                                            </label>
                                            <input class="form-check-input" type="checkbox" name="banner_<?php echo $index + 1; ?>_delete" value="1" id="banner_<?php echo $index + 1; ?>_delete">
                                        </div>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label><strong>Νέα Εικόνα</strong></label>
                                        <input type="file" name="banner_<?php echo $index + 1; ?>_image" class="form-control-file" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                                        <small class="editor-help">Προτεινόμενη διάσταση: <code>1600 x 240 px</code> για πιο σωστή εμφάνιση.</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="section-actions">
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Banner</button>
                        </div>
                    </form>
                </section>

                <section class="card card-custom section-editor tab-pane fade <?php echo $activeHomeTab === 'hero_section' ? 'show active' : ''; ?>" id="tab-hero_section" role="tabpanel" aria-labelledby="tab-hero_section-link">
                    <div class="section-editor__header">
                        <div>
                            <h2>Hero Ενότητα</h2>
                            <p>Το βασικό μήνυμα καλωσορίσματος, ο μεγάλος τίτλος και τα δύο κουμπιά πλοήγησης.</p>
                        </div>
                        <span class="section-editor__icon"><i class="fas fa-home"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_home_content_section">
                        <input type="hidden" name="section_key" value="hero_section">
                        <input type="hidden" name="home_tab" value="hero_section">
                        <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

                        <div class="section-form-grid">
                            <div>
                                <label><strong>Υπέρτιτλος</strong></label>
                                <input type="text" name="kicker" class="form-control form-control-custom" value="<?php echo htmlspecialchars($heroContentSection['content']['kicker'] ?? ''); ?>">
                            </div>
                            <div>
                                <label><strong>Κύριος Τίτλος</strong></label>
                                <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($heroContentSection['title'] ?? ''); ?>">
                            </div>
                            <div class="full-width">
                                <label><strong>Περιγραφή</strong></label>
                                <textarea name="subtitle" class="form-control form-control-custom textarea-tall"><?php echo htmlspecialchars($heroContentSection['subtitle'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label><strong>Κείμενο Κουμπιού Ανακοινώσεων</strong></label>
                                <input type="text" name="announcements_button_label" class="form-control form-control-custom" value="<?php echo htmlspecialchars($heroContentSection['content']['announcements_button_label'] ?? ''); ?>">
                            </div>
                            <div>
                                <label><strong>Κείμενο Κουμπιού Εκδηλώσεων</strong></label>
                                <input type="text" name="events_button_label" class="form-control form-control-custom" value="<?php echo htmlspecialchars($heroContentSection['content']['events_button_label'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="section-actions">
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Hero</button>
                        </div>
                    </form>
                </section>

                <section class="card card-custom section-editor tab-pane fade <?php echo $activeHomeTab === 'calendar_section' ? 'show active' : ''; ?>" id="tab-calendar_section" role="tabpanel" aria-labelledby="tab-calendar_section-link">
                    <div class="section-editor__header">
                        <div>
                            <h2>Block Ημερολογίου</h2>
                            <p>Ο τίτλος που εμφανίζεται στο πλαίσιο του ημερολογίου στην αρχική σελίδα.</p>
                        </div>
                        <span class="section-editor__icon"><i class="fas fa-calendar-alt"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_home_content_section">
                        <input type="hidden" name="section_key" value="calendar_section">
                        <input type="hidden" name="home_tab" value="calendar_section">
                        <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

                        <div class="section-form-grid">
                            <div class="full-width">
                                <label><strong>Τίτλος Block</strong></label>
                                <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($calendarContentSection['title'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="section-actions">
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Ημερολογίου</button>
                        </div>
                    </form>
                </section>

                <section class="card card-custom section-editor tab-pane fade <?php echo $activeHomeTab === 'announcements_section' ? 'show active' : ''; ?>" id="tab-announcements_section" role="tabpanel" aria-labelledby="tab-announcements_section-link">
                    <div class="section-editor__header">
                        <div>
                            <h2>Ενότητα Ανακοινώσεων</h2>
                            <p>Ο τίτλος του block και το κείμενο του κουμπιού που οδηγεί σε όλες τις ανακοινώσεις.</p>
                        </div>
                        <span class="section-editor__icon"><i class="fas fa-bullhorn"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_home_content_section">
                        <input type="hidden" name="section_key" value="announcements_section">
                        <input type="hidden" name="home_tab" value="announcements_section">
                        <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

                        <div class="section-form-grid">
                            <div>
                                <label><strong>Τίτλος Block</strong></label>
                                <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($announcementsContentSection['title'] ?? ''); ?>">
                            </div>
                            <div>
                                <label><strong>Κείμενο Κουμπιού</strong></label>
                                <input type="text" name="button_label" class="form-control form-control-custom" value="<?php echo htmlspecialchars($announcementsContentSection['content']['button_label'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="section-actions">
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Ανακοινώσεων</button>
                        </div>
                    </form>
                </section>

                <section class="card card-custom section-editor tab-pane fade <?php echo $activeHomeTab === 'events_section' ? 'show active' : ''; ?>" id="tab-events_section" role="tabpanel" aria-labelledby="tab-events_section-link">
                    <div class="section-editor__header">
                        <div>
                            <h2>Ενότητα Εκδηλώσεων</h2>
                            <p>Ο τίτλος του block και το κείμενο του κουμπιού που οδηγεί σε όλες τις εκδηλώσεις.</p>
                        </div>
                        <span class="section-editor__icon"><i class="fas fa-star"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_home_content_section">
                        <input type="hidden" name="section_key" value="events_section">
                        <input type="hidden" name="home_tab" value="events_section">
                        <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

                        <div class="section-form-grid">
                            <div>
                                <label><strong>Τίτλος Block</strong></label>
                                <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($eventsContentSection['title'] ?? ''); ?>">
                            </div>
                            <div>
                                <label><strong>Κείμενο Κουμπιού</strong></label>
                                <input type="text" name="button_label" class="form-control form-control-custom" value="<?php echo htmlspecialchars($eventsContentSection['content']['button_label'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="section-actions">
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Εκδηλώσεων</button>
                        </div>
                    </form>
                </section>
            </div>
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
                        <input type="file" class="form-control-file" id="calendar_event_images" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif" data-existing-count="0">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο 6 εικόνες ανά εκδήλωση
                        </small>
                        <div id="calendarEventImagePreview" class="image-preview"></div>
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
                        <input type="file" class="form-control-file" id="calendar_announcement_images" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif" data-existing-count="0">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF | Μέγιστο μέγεθος: 5MB ανά αρχείο | Μέγιστο 6 εικόνες ανά ανακοίνωση
                        </small>
                        <div id="calendarAnnouncementImagePreview" class="image-preview"></div>
                    </div>

                    <div class="form-group">
                        <label for="calendar_announcement_attachments"><strong>Συνημμένες Επιστολές</strong></label>
                        <input type="file" class="form-control-file" id="calendar_announcement_attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG | Μέγιστο μέγεθος: 8MB ανά αρχείο
                        </small>
                        <div id="calendarAnnouncementAttachmentPreview" class="attachment-preview"></div>
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
<script>
function ensureDashboardNoticeElements() {
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

function showDashboardNotice(message, options) {
    ensureDashboardNoticeElements();

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

function truncateDashboardPreviewFileName(fileName, maxLength) {
    if (fileName.length <= maxLength) {
        return fileName;
    }

    return fileName.slice(0, Math.max(0, maxLength - 3)) + '...';
}

function getDashboardFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

function syncDashboardInputFiles(input, stagedFiles) {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const dataTransfer = new DataTransfer();
    stagedFiles.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

function renderDashboardImagePreview(preview, stagedFiles, onRemove) {
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
        caption.textContent = truncateDashboardPreviewFileName(file.name, 18);

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

function renderDashboardAttachmentPreview(preview, stagedFiles, onRemove) {
    if (!preview) {
        return;
    }

    preview.innerHTML = '';

    stagedFiles.forEach((file, index) => {
        const fileExt = (file.name.split('.').pop() || '').toLowerCase();
        const item = document.createElement('div');
        item.className = 'attachment-preview-item';

        const info = document.createElement('div');
        info.className = 'attachment-preview-info';

        const icon = document.createElement('i');
        icon.className = fileExt === 'pdf' ? 'fas fa-file-pdf' : 'fas fa-file-image';

        const text = document.createElement('span');
        text.className = 'attachment-preview-name';
        text.textContent = truncateDashboardPreviewFileName(file.name, 40);

        const size = document.createElement('span');
        size.className = 'attachment-preview-size';
        size.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'attachment-remove-btn';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.setAttribute('aria-label', `Αφαίρεση ${file.name}`);
        deleteBtn.addEventListener('click', function () {
            onRemove(index);
        });

        info.appendChild(icon);
        info.appendChild(text);
        info.appendChild(size);
        item.appendChild(info);
        item.appendChild(deleteBtn);
        preview.appendChild(item);
    });
}

function setupDashboardImageInput(input, previewId, imageLimit, noticeTitle) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const stagedFiles = [];
    const stagedKeys = new Set();

    function updateInputState() {
        if (existingCount + stagedFiles.length >= imageLimit) {
            input.disabled = true;
        } else if (existingCount < imageLimit) {
            input.disabled = false;
        }
    }

    function removeStagedFile(index) {
        const removedFile = stagedFiles[index];
        if (!removedFile) {
            return;
        }

        stagedFiles.splice(index, 1);
        stagedKeys.delete(getDashboardFileKey(removedFile));
        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();
    }

    input.addEventListener('change', function () {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        const maxFileSize = 5 * 1024 * 1024;
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];
        let reachedLimit = false;

        if (incomingFiles.length === 0) {
            return;
        }

        if (existingCount >= imageLimit) {
            warnings.push(`Έχει ήδη συμπληρωθεί το όριο των ${imageLimit} εικόνων.`);
        } else {
            incomingFiles.forEach((file) => {
                const fileKey = getDashboardFileKey(file);
                const fileExt = (file.name.split('.').pop() || '').toLowerCase();

                if (!allowedExtensions.includes(fileExt)) {
                    warnings.push(`Το αρχείο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο JPG, JPEG, PNG, GIF.`);
                    return;
                }

                if (file.size > maxFileSize) {
                    warnings.push(`Το αρχείο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 5MB.`);
                    return;
                }

                if (!String(file.type || '').startsWith('image/')) {
                    warnings.push(`Το αρχείο "${file.name}" δεν φαίνεται να είναι εικόνα.`);
                    return;
                }

                if (stagedKeys.has(fileKey)) {
                    warnings.push(`Το αρχείο "${file.name}" έχει ήδη επιλεγεί.`);
                    return;
                }

                if (existingCount + stagedFiles.length >= imageLimit) {
                    if (!reachedLimit) {
                        const remainingSlots = Math.max(0, imageLimit - existingCount - stagedFiles.length);
                        warnings.push(`Μπορείτε να προσθέσετε μόνο ${remainingSlots} ακόμη εικόνα/ες.`);
                        reachedLimit = true;
                    }
                    return;
                }

                stagedFiles.push(file);
                stagedKeys.add(fileKey);
            });
        }

        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();

        if (warnings.length > 0) {
            showDashboardNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: noticeTitle,
                variant: 'warning'
            });
        }
    });

    updateInputState();
}

function setupDashboardAttachmentInput(input, previewId, noticeTitle) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const stagedFiles = [];
    const stagedKeys = new Set();

    function removeStagedFile(index) {
        const removedFile = stagedFiles[index];
        if (!removedFile) {
            return;
        }

        stagedFiles.splice(index, 1);
        stagedKeys.delete(getDashboardFileKey(removedFile));
        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardAttachmentPreview(preview, stagedFiles, removeStagedFile);
    }

    input.addEventListener('change', function () {
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        const maxFileSize = 8 * 1024 * 1024;
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];

        if (incomingFiles.length === 0) {
            return;
        }

        incomingFiles.forEach((file) => {
            const fileKey = getDashboardFileKey(file);
            const fileExt = (file.name.split('.').pop() || '').toLowerCase();
            const fileType = String(file.type || '');

            if (!allowedExtensions.includes(fileExt)) {
                warnings.push(`Το συνημμένο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο PDF, JPG, JPEG, PNG.`);
                return;
            }

            if (file.size > maxFileSize) {
                warnings.push(`Το συνημμένο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 8MB.`);
                return;
            }

            if (fileType !== '' && fileType !== 'application/pdf' && !fileType.startsWith('image/')) {
                warnings.push(`Το συνημμένο "${file.name}" δεν έχει έγκυρο τύπο αρχείου.`);
                return;
            }

            if (stagedKeys.has(fileKey)) {
                warnings.push(`Το συνημμένο "${file.name}" έχει ήδη επιλεγεί.`);
                return;
            }

            stagedFiles.push(file);
            stagedKeys.add(fileKey);
        });

        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardAttachmentPreview(preview, stagedFiles, removeStagedFile);

        if (warnings.length > 0) {
            showDashboardNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: noticeTitle,
                variant: 'warning'
            });
        }
    });
}

setupDashboardImageInput(document.getElementById('calendar_event_images'), 'calendarEventImagePreview', 6, 'Έλεγχος εικόνων εκδήλωσης');
setupDashboardImageInput(document.getElementById('calendar_announcement_images'), 'calendarAnnouncementImagePreview', 6, 'Έλεγχος εικόνων ανακοίνωσης');
setupDashboardAttachmentInput(document.getElementById('calendar_announcement_attachments'), 'calendarAnnouncementAttachmentPreview', 'Έλεγχος συνημμένων ανακοίνωσης');
</script>
</body>
</html>
