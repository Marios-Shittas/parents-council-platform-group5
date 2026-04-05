<?php
require_once __DIR__ . '/../../app/services/ParentsPageService.php';

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

function parentsAdminTrim($value)
{
    return trim((string)$value);
}

function parentsAdminTextarea($value)
{
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    return trim($value);
}

function parentsAdminTextareaToList($value)
{
    $lines = explode("\n", parentsAdminTextarea($value));
    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $items[] = $line;
        }
    }

    return $items;
}

function parentsAdminTextareaToRows($value, array $keys)
{
    $lines = explode("\n", parentsAdminTextarea($value));
    $rows = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', array_pad(explode('|', $line), count($keys), ''));
        $row = [];
        $hasValue = false;

        foreach ($keys as $index => $key) {
            $row[$key] = $parts[$index] ?? '';
            if ($row[$key] !== '') {
                $hasValue = true;
            }
        }

        if ($hasValue) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function parentsAdminListToTextarea($items)
{
    return implode("\n", is_array($items) ? $items : []);
}

function parentsAdminRowsToTextarea($rows, array $keys)
{
    if (!is_array($rows)) {
        return '';
    }

    $lines = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $parts = [];
        $hasValue = false;
        foreach ($keys as $key) {
            $value = trim((string)($row[$key] ?? ''));
            $parts[] = $value;
            if ($value !== '') {
                $hasValue = true;
            }
        }

        if ($hasValue) {
            $lines[] = implode(' | ', $parts);
        }
    }

    return implode("\n", $lines);
}

function getParentsGalleryUploadDir()
{
    return dirname(__DIR__) . '/assets/Parents_img/';
}

function buildParentsGalleryWebPath($fileName)
{
    return '/parents-council-platform-group5/public/assets/Parents_img/' . $fileName;
}

function isLocalParentsGalleryPath($imagePath)
{
    return str_starts_with((string)$imagePath, '/parents-council-platform-group5/public/assets/Parents_img/');
}

function resolveParentsGalleryFilePath($imagePath)
{
    return getParentsGalleryUploadDir() . basename((string)$imagePath);
}

function deleteParentsGalleryFileIfExists($imagePath)
{
    if (!isLocalParentsGalleryPath($imagePath)) {
        return;
    }

    $filePath = resolveParentsGalleryFilePath($imagePath);
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

function uploadParentsGalleryImages($service)
{
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES['gallery_images']['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    $uploadDir = getParentsGalleryUploadDir();
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            $uploadErrors[] = "Αδυναμία δημιουργίας του φακέλου ανεβάσματος: {$uploadDir}";
            return [$uploadedCount, $uploadErrors];
        }

        @chmod($uploadDir, 0777);
    }

    if (!is_writable($uploadDir)) {
        $uploadErrors[] = "Ο φάκελος ανεβάσματος δεν είναι εγγράψιμος: {$uploadDir}";
        return [$uploadedCount, $uploadErrors];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxFileSize = 5 * 1024 * 1024;

    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['gallery_images']['name'][$key] ?? '');
        $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $uploadError = $_FILES['gallery_images']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$uploadError})";
            continue;
        }

        $fileSize = $_FILES['gallery_images']['size'][$key] ?? 0;
        $fileMime = mime_content_type($tmpName);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions, true)) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
            continue;
        }

        if ($fileSize > $maxFileSize) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο. Μέγιστο: 5MB";
            continue;
        }

        if (!getimagesize($tmpName)) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα.";
            continue;
        }

        if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with((string)$fileMime, 'image/')) {
            $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο MIME type ({$fileMime}).";
            continue;
        }

        $newFileName = uniqid('parents_', true) . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            $uploadErrors[] = "Αποτυχία μεταφόρτωσης του '{$safeFileName}'.";
            continue;
        }

        $imagePath = buildParentsGalleryWebPath($newFileName);
        if ($service->addGalleryImage($imagePath, $imagePath, 'Φωτογραφικό υλικό σχολείου')) {
            $uploadedCount++;
        } else {
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων.";
        }
    }

    return [$uploadedCount, $uploadErrors];
}

$parentsPageService = new ParentsPageService();
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectUrl = 'parents.php';

    if ($action === 'update_content_section') {
        $sectionKey = $_POST['section_key'] ?? '';
        $saved = false;

        switch ($sectionKey) {
            case 'page_header':
                $saved = $parentsPageService->updateSection(
                    'page_header',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'public_eyebrow' => parentsAdminTrim($_POST['public_eyebrow'] ?? ''),
                        'parent_eyebrow' => parentsAdminTrim($_POST['parent_eyebrow'] ?? ''),
                        'icon' => parentsAdminTrim($_POST['icon'] ?? ''),
                    ]
                );
                break;

            case 'history_section':
                $saved = $parentsPageService->updateSection(
                    'history_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'items' => parentsAdminTextareaToList($_POST['items'] ?? ''),
                    ]
                );
                break;

            case 'schedule_section':
                $blocks = [];
                for ($index = 1; $index <= 2; $index++) {
                    $blocks[] = [
                        'title' => parentsAdminTrim($_POST["block_{$index}_title"] ?? ''),
                        'rows' => parentsAdminTextareaToRows($_POST["block_{$index}_rows"] ?? '', ['period', 'time']),
                    ];
                }

                $saved = $parentsPageService->updateSection(
                    'schedule_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'period_label' => parentsAdminTrim($_POST['period_label'] ?? ''),
                        'time_label' => parentsAdminTrim($_POST['time_label'] ?? ''),
                        'blocks' => $blocks,
                    ]
                );
                break;

            case 'board_section':
                $saved = $parentsPageService->updateSection(
                    'board_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'position_label' => parentsAdminTrim($_POST['position_label'] ?? ''),
                        'name_label' => parentsAdminTrim($_POST['name_label'] ?? ''),
                        'committee_label' => parentsAdminTrim($_POST['committee_label'] ?? ''),
                        'board_members' => parentsAdminTextareaToRows($_POST['board_members'] ?? '', ['role', 'name']),
                        'committee_members' => parentsAdminTextareaToList($_POST['committee_members'] ?? ''),
                    ]
                );
                break;

            case 'class_responsibles_section':
                $saved = $parentsPageService->updateSection(
                    'class_responsibles_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTrim($_POST['subtitle'] ?? ''),
                    [
                        'modal_title' => parentsAdminTrim($_POST['modal_title'] ?? ''),
                        'class_label' => parentsAdminTrim($_POST['class_label'] ?? ''),
                        'responsible_label' => parentsAdminTrim($_POST['responsible_label'] ?? ''),
                        'assistant_label' => parentsAdminTrim($_POST['assistant_label'] ?? ''),
                        'room_label' => parentsAdminTrim($_POST['room_label'] ?? ''),
                        'rows' => parentsAdminTextareaToRows($_POST['rows'] ?? '', ['class', 'responsible', 'assistant', 'room']),
                    ]
                );
                break;

            case 'electronic_admin_section':
                $saved = $parentsPageService->updateSection(
                    'electronic_admin_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTrim($_POST['subtitle'] ?? ''),
                    [
                        'modal_title' => parentsAdminTrim($_POST['modal_title'] ?? ''),
                        'registration_heading' => parentsAdminTrim($_POST['registration_heading'] ?? ''),
                        'registration_intro' => parentsAdminTextarea($_POST['registration_intro'] ?? ''),
                        'registration_steps' => parentsAdminTextareaToList($_POST['registration_steps'] ?? ''),
                        'login_heading' => parentsAdminTrim($_POST['login_heading'] ?? ''),
                        'login_steps' => parentsAdminTextareaToList($_POST['login_steps'] ?? ''),
                        'edge_heading' => parentsAdminTrim($_POST['edge_heading'] ?? ''),
                        'edge_steps' => parentsAdminTextareaToList($_POST['edge_steps'] ?? ''),
                        'chrome_heading' => parentsAdminTrim($_POST['chrome_heading'] ?? ''),
                        'chrome_steps' => parentsAdminTextareaToList($_POST['chrome_steps'] ?? ''),
                        'link_label' => parentsAdminTrim($_POST['link_label'] ?? ''),
                        'link_url' => parentsAdminTrim($_POST['link_url'] ?? ''),
                    ]
                );
                break;

            case 'gallery_section':
                $saved = $parentsPageService->updateSection(
                    'gallery_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'empty_message' => parentsAdminTrim($_POST['empty_message'] ?? ''),
                    ]
                );
                break;
        }

        $_SESSION['flash_message'] = $saved
            ? 'Το περιεχόμενο της σελίδας Γονείς ενημερώθηκε επιτυχώς.'
            : 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση. ' . $parentsPageService->getLastError();
        $_SESSION['flash_type'] = $saved ? 'success' : 'danger';
        $redirectUrl .= '#content-management';
    } elseif ($action === 'upload_gallery_images') {
        [$uploadedCount, $uploadErrors] = uploadParentsGalleryImages($parentsPageService);

        if ($uploadedCount > 0) {
            $message = "Ανέβηκαν επιτυχώς {$uploadedCount} φωτογραφία/ες.";
            $type = empty($uploadErrors) ? 'success' : 'warning';
        } else {
            $message = 'Δεν ανέβηκε καμία φωτογραφία.';
            $type = 'danger';
        }

        if (!empty($uploadErrors)) {
            $message .= '<br><strong>Προβλήματα:</strong><ul><li>' . implode('</li><li>', $uploadErrors) . '</li></ul>';
        }

        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        $redirectUrl .= '#gallery-management';
    } elseif ($action === 'delete_gallery_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $image = $parentsPageService->getGalleryImageById($imageId);

        if ($image && $parentsPageService->deleteGalleryImage($imageId)) {
            deleteParentsGalleryFileIfExists($image['full_image_path'] ?? '');

            $thumbImagePath = $image['thumb_image_path'] ?? '';
            if ($thumbImagePath !== ($image['full_image_path'] ?? '')) {
                deleteParentsGalleryFileIfExists($thumbImagePath);
            }

            $_SESSION['flash_message'] = 'Η φωτογραφία διαγράφηκε επιτυχώς.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Αποτυχία διαγραφής της φωτογραφίας. ' . $parentsPageService->getLastError();
            $_SESSION['flash_type'] = 'danger';
        }

        $redirectUrl .= '#gallery-management';
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$sections = $parentsPageService->getAllSections();
$galleryImages = $parentsPageService->getGalleryImages();

$pageHeaderSection = $sections['page_header'];
$historySection = $sections['history_section'];
$scheduleSection = $sections['schedule_section'];
$boardSection = $sections['board_section'];
$classResponsiblesSection = $sections['class_responsibles_section'];
$electronicAdminSection = $sections['electronic_admin_section'];
$gallerySection = $sections['gallery_section'];
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Διαχείριση Σελίδας Γονείς - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_epikoinonia.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_parents.css">
</head>
<body>
<div class="admin-wrapper">
    <?php include_once __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Πίσω στο Dashboard
        </a>

        <div class="admin-header">
            <h1>
                <i class="fas fa-users"></i>
                Διαχείριση Σελίδας Γονείς
            </h1>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $flashMessage; ?></span>
            </div>
        <?php endif; ?>

        <div id="content-management" class="content-management">
            <div class="content-management__intro">
                <div>
                    <h2><i class="fas fa-edit"></i> Διαχείριση Περιεχομένου</h2>
                    <p>Από εδώ αλλάζεις όλα τα κείμενα που εμφανίζονται και στο <code>public/parents.php</code> και στο <code>public/parent/parents.php</code>. Τα sections αποθηκεύονται ξεχωριστά όπως στις άλλες editable σελίδες του admin panel.</p>
                </div>
            </div>

            <div class="content-sections-grid">
                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Page Header</h3>
                            <p>Τίτλος, υπότιτλος και διαφορετικό eyebrow για public και parent view.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-heading"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="page_header">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="page-header-title">Τίτλος</label>
                                <input type="text" class="form-control" id="page-header-title" name="title" value="<?php echo htmlspecialchars($pageHeaderSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="page-header-icon">Icon class</label>
                                <input type="text" class="form-control" id="page-header-icon" name="icon" value="<?php echo htmlspecialchars($pageHeaderSection['content']['icon'] ?? 'fas fa-users'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="page-header-public-eyebrow">Eyebrow για public</label>
                                <input type="text" class="form-control" id="page-header-public-eyebrow" name="public_eyebrow" value="<?php echo htmlspecialchars($pageHeaderSection['content']['public_eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="page-header-parent-eyebrow">Eyebrow για parent</label>
                                <input type="text" class="form-control" id="page-header-parent-eyebrow" name="parent_eyebrow" value="<?php echo htmlspecialchars($pageHeaderSection['content']['parent_eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="page-header-subtitle">Υπότιτλος</label>
                                <textarea class="form-control content-textarea" id="page-header-subtitle" name="subtitle"><?php echo htmlspecialchars($pageHeaderSection['subtitle']); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Header
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Ιστορικό Σχολείου</h3>
                            <p>Τίτλος ενότητας, eyebrow και bullets της πρώτης κάρτας.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-landmark"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="history_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="history-title">Τίτλος</label>
                                <input type="text" class="form-control" id="history-title" name="title" value="<?php echo htmlspecialchars($historySection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="history-eyebrow">Eyebrow</label>
                                <input type="text" class="form-control" id="history-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($historySection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="history-items">Bullets, μία γραμμή ανά στοιχείο</label>
                                <textarea class="form-control content-textarea content-textarea--large" id="history-items" name="items"><?php echo htmlspecialchars(parentsAdminListToTextarea($historySection['content']['items'] ?? [])); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Ιστορικού
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Ωράριο</h3>
                            <p>Οι 2 πίνακες του ωραρίου. Μορφή γραμμής: <code>Περίοδος | Ώρα</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-clock"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="schedule_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="schedule-title">Τίτλος</label>
                                <input type="text" class="form-control" id="schedule-title" name="title" value="<?php echo htmlspecialchars($scheduleSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="schedule-eyebrow">Eyebrow</label>
                                <input type="text" class="form-control" id="schedule-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($scheduleSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="schedule-period-label">Κεφαλίδα 1ης στήλης</label>
                                <input type="text" class="form-control" id="schedule-period-label" name="period_label" value="<?php echo htmlspecialchars($scheduleSection['content']['period_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="schedule-time-label">Κεφαλίδα 2ης στήλης</label>
                                <input type="text" class="form-control" id="schedule-time-label" name="time_label" value="<?php echo htmlspecialchars($scheduleSection['content']['time_label'] ?? ''); ?>">
                            </div>

                            <?php for ($index = 0; $index < 2; $index++): ?>
                                <?php
                                $block = $scheduleSection['content']['blocks'][$index] ?? [];
                                $blockNumber = $index + 1;
                                ?>
                                <div class="content-subcard">
                                    <h4>Πίνακας <?php echo $blockNumber; ?></h4>

                                    <div class="form-group">
                                        <label for="schedule-block-<?php echo $blockNumber; ?>-title">Τίτλος πίνακα</label>
                                        <input type="text" class="form-control" id="schedule-block-<?php echo $blockNumber; ?>-title" name="block_<?php echo $blockNumber; ?>_title" value="<?php echo htmlspecialchars($block['title'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="schedule-block-<?php echo $blockNumber; ?>-rows">Γραμμές</label>
                                        <textarea class="form-control content-textarea content-textarea--large" id="schedule-block-<?php echo $blockNumber; ?>-rows" name="block_<?php echo $blockNumber; ?>_rows"><?php echo htmlspecialchars(parentsAdminRowsToTextarea($block['rows'] ?? [], ['period', 'time'])); ?></textarea>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Ωραρίου
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Σύνδεσμος Γονέων</h3>
                            <p>Lead paragraph, labels πίνακα και μέλη Δ.Σ. Μορφή γραμμής: <code>Θέση | Ονοματεπώνυμο</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-user-friends"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="board_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="board-title">Τίτλος</label>
                                <input type="text" class="form-control" id="board-title" name="title" value="<?php echo htmlspecialchars($boardSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-eyebrow">Eyebrow</label>
                                <input type="text" class="form-control" id="board-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($boardSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-position-label">Κεφαλίδα στήλης θέσης</label>
                                <input type="text" class="form-control" id="board-position-label" name="position_label" value="<?php echo htmlspecialchars($boardSection['content']['position_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-name-label">Κεφαλίδα στήλης ονόματος</label>
                                <input type="text" class="form-control" id="board-name-label" name="name_label" value="<?php echo htmlspecialchars($boardSection['content']['name_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-committee-label">Label για τα μέλη</label>
                                <input type="text" class="form-control" id="board-committee-label" name="committee_label" value="<?php echo htmlspecialchars($boardSection['content']['committee_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="board-subtitle">Lead κείμενο</label>
                                <textarea class="form-control content-textarea" id="board-subtitle" name="subtitle"><?php echo htmlspecialchars($boardSection['subtitle']); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="board-members">Μέλη Δ.Σ.</label>
                                <textarea class="form-control content-textarea content-textarea--large" id="board-members" name="board_members"><?php echo htmlspecialchars(parentsAdminRowsToTextarea($boardSection['content']['board_members'] ?? [], ['role', 'name'])); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="committee-members">Απλά μέλη, μία γραμμή ανά όνομα</label>
                                <textarea class="form-control content-textarea" id="committee-members" name="committee_members"><?php echo htmlspecialchars(parentsAdminListToTextarea($boardSection['content']['committee_members'] ?? [])); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Συνδέσμου
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Υπεύθυνοι Τμημάτων</h3>
                            <p>Widget, modal τίτλος και πίνακας. Μορφή γραμμής: <code>Τμήμα | Υπεύθυνος | Βοηθός | Αίθουσα</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-table"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="class_responsibles_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="class-widget-title">Τίτλος widget</label>
                                <input type="text" class="form-control" id="class-widget-title" name="title" value="<?php echo htmlspecialchars($classResponsiblesSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="class-widget-button">Κείμενο κουμπιού widget</label>
                                <input type="text" class="form-control" id="class-widget-button" name="subtitle" value="<?php echo htmlspecialchars($classResponsiblesSection['subtitle']); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="class-modal-title">Τίτλος modal</label>
                                <input type="text" class="form-control" id="class-modal-title" name="modal_title" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['modal_title'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="class-label">Κεφαλίδα στήλης 1</label>
                                <input type="text" class="form-control" id="class-label" name="class_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['class_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="responsible-label">Κεφαλίδα στήλης 2</label>
                                <input type="text" class="form-control" id="responsible-label" name="responsible_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['responsible_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="assistant-label">Κεφαλίδα στήλης 3</label>
                                <input type="text" class="form-control" id="assistant-label" name="assistant_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['assistant_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="room-label">Κεφαλίδα στήλης 4</label>
                                <input type="text" class="form-control" id="room-label" name="room_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['room_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="class-rows">Γραμμές πίνακα</label>
                                <textarea class="form-control content-textarea content-textarea--large" id="class-rows" name="rows"><?php echo htmlspecialchars(parentsAdminRowsToTextarea($classResponsiblesSection['content']['rows'] ?? [], ['class', 'responsible', 'assistant', 'room'])); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Υπευθύνων
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Ηλεκτρονική Διοίκηση</h3>
                            <p>Όλα τα κείμενα του widget και του modal. Κάθε textarea list παίρνει μία γραμμή ανά βήμα.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-laptop-house"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="electronic_admin_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="electronic-title">Τίτλος widget</label>
                                <input type="text" class="form-control" id="electronic-title" name="title" value="<?php echo htmlspecialchars($electronicAdminSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="electronic-subtitle">Κείμενο κουμπιού widget</label>
                                <input type="text" class="form-control" id="electronic-subtitle" name="subtitle" value="<?php echo htmlspecialchars($electronicAdminSection['subtitle']); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="electronic-modal-title">Τίτλος modal</label>
                                <input type="text" class="form-control" id="electronic-modal-title" name="modal_title" value="<?php echo htmlspecialchars($electronicAdminSection['content']['modal_title'] ?? ''); ?>">
                            </div>

                            <div class="content-subcard">
                                <h4>Ενότητα Εγγραφής</h4>

                                <div class="form-group">
                                    <label for="registration-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="registration-heading" name="registration_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['registration_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="registration-intro">Εισαγωγικό κείμενο</label>
                                    <textarea class="form-control content-textarea" id="registration-intro" name="registration_intro"><?php echo htmlspecialchars($electronicAdminSection['content']['registration_intro'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="registration-steps">Βήματα</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="registration-steps" name="registration_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['registration_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Ενότητα Εισόδου</h4>

                                <div class="form-group">
                                    <label for="login-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="login-heading" name="login_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['login_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="login-steps">Γενικά βήματα</label>
                                    <textarea class="form-control content-textarea" id="login-steps" name="login_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['login_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Internet Explorer / Edge</h4>

                                <div class="form-group">
                                    <label for="edge-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="edge-heading" name="edge_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['edge_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="edge-steps">Βήματα</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="edge-steps" name="edge_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['edge_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Google Chrome</h4>

                                <div class="form-group">
                                    <label for="chrome-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="chrome-heading" name="chrome_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['chrome_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="chrome-steps">Βήματα</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="chrome-steps" name="chrome_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['chrome_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="system-link-label">Label συνδέσμου</label>
                                <input type="text" class="form-control" id="system-link-label" name="link_label" value="<?php echo htmlspecialchars($electronicAdminSection['content']['link_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="system-link-url">URL συνδέσμου</label>
                                <input type="text" class="form-control" id="system-link-url" name="link_url" value="<?php echo htmlspecialchars($electronicAdminSection['content']['link_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Ηλεκτρονικής Διοίκησης
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Gallery Texts</h3>
                            <p>Ο τίτλος του widget και το μήνυμα που φαίνεται όταν δεν υπάρχουν φωτογραφίες.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-camera"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="gallery_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="gallery-title">Τίτλος widget</label>
                                <input type="text" class="form-control" id="gallery-title" name="title" value="<?php echo htmlspecialchars($gallerySection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="gallery-empty-message">Μήνυμα χωρίς φωτογραφίες</label>
                                <input type="text" class="form-control" id="gallery-empty-message" name="empty_message" value="<?php echo htmlspecialchars($gallerySection['content']['empty_message'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Gallery Texts
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>

        <section id="gallery-management" class="content-management">
            <div class="content-management__intro">
                <div>
                    <h2><i class="fas fa-images"></i> Φωτογραφικό Υλικό</h2>
                    <p>Ανέβασε νέες φωτογραφίες όπως στα events. Οι εικόνες αποθηκεύονται στο <code>public/assets/Parents_img</code> και εμφανίζονται αυτόματα στη σελίδα Γονείς.</p>
                </div>
            </div>

            <div class="content-editor-card">
                <div class="content-editor-card__header">
                    <div>
                        <h3>Ανέβασμα Φωτογραφιών</h3>
                        <p>Υποστηρίζονται αρχεία JPG, PNG, GIF και WEBP έως 5MB το καθένα.</p>
                    </div>
                    <span class="content-editor-card__icon"><i class="fas fa-upload"></i></span>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_gallery_images">

                    <div class="parents-upload-panel">
                        <label for="gallery-images" class="parents-upload-dropzone" id="parents-upload-dropzone">
                            <span class="parents-upload-dropzone__icon"><i class="fas fa-cloud-upload-alt"></i></span>
                            <span class="parents-upload-dropzone__title">Σύρε φωτογραφίες εδώ ή πάτησε για επιλογή</span>
                            <span class="parents-upload-dropzone__subtitle">JPG, PNG, GIF, WEBP έως 5MB η καθεμία</span>
                            <span class="parents-upload-dropzone__helper">Πολλαπλή επιλογή: <code>Command</code> σε Mac ή <code>Ctrl</code> σε Windows</span>
                        </label>

                        <input type="file" class="parents-upload-input" id="gallery-images" name="gallery_images[]" accept="image/*" multiple>

                        <div class="parents-upload-status" id="parents-upload-status">
                            Δεν έχουν επιλεγεί ακόμη αρχεία.
                        </div>

                        <div class="parents-upload-preview" id="parents-upload-preview" aria-live="polite"></div>
                    </div>

                    <div class="content-editor-card__actions">
                        <button type="submit" class="btn-save-section parents-upload-submit">
                            <i class="fas fa-cloud-upload-alt"></i> Ανέβασμα Φωτογραφιών
                        </button>
                    </div>
                </form>
            </div>

            <div class="content-editor-card">
                <div class="content-editor-card__header">
                    <div>
                        <h3>Τρέχουσες Φωτογραφίες</h3>
                        <p>Εδώ βλέπεις όλες τις φωτογραφίες που εμφανίζονται στο public gallery και μπορείς να τις αφαιρέσεις.</p>
                    </div>
                    <span class="content-editor-card__icon"><i class="fas fa-photo-video"></i></span>
                </div>

                <?php if (empty($galleryImages)): ?>
                    <p class="mb-0">Δεν υπάρχουν φωτογραφίες στη gallery.</p>
                <?php else: ?>
                    <div class="parents-gallery-admin-grid">
                        <?php foreach ($galleryImages as $image): ?>
                            <?php
                            $thumbPath = $image['thumb_image_path'] ?: $image['full_image_path'];
                            $fullPath = $image['full_image_path'] ?? '';
                            ?>
                            <article class="parents-gallery-admin-card">
                                <a href="<?php echo htmlspecialchars($fullPath); ?>" target="_blank" rel="noopener noreferrer" class="parents-gallery-admin-card__image-link">
                                    <img src="<?php echo htmlspecialchars($thumbPath); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? ''); ?>" class="parents-gallery-admin-card__image">
                                </a>

                                <div class="parents-gallery-admin-card__body">
                                    <div class="parents-gallery-admin-card__meta">
                                        <span class="parents-gallery-admin-card__badge">#<?php echo (int)$image['image_id']; ?></span>
                                        <span class="parents-gallery-admin-card__badge">
                                            <?php echo isLocalParentsGalleryPath($fullPath) ? 'Τοπικό αρχείο' : 'Εξωτερικό URL'; ?>
                                        </span>
                                    </div>

                                    <p class="parents-gallery-admin-card__path"><?php echo htmlspecialchars($fullPath); ?></p>

                                    <div class="parents-gallery-admin-card__actions">
                                        <a href="<?php echo htmlspecialchars($fullPath); ?>" target="_blank" rel="noopener noreferrer" class="parents-gallery-action parents-gallery-action--view">
                                            <i class="fas fa-eye"></i> Προβολή
                                        </a>

                                        <form method="POST" onsubmit="return confirm('Να διαγραφεί αυτή η φωτογραφία;');" class="parents-gallery-delete-form">
                                            <input type="hidden" name="action" value="delete_gallery_image">
                                            <input type="hidden" name="image_id" value="<?php echo (int)$image['image_id']; ?>">
                                            <button type="submit" class="parents-gallery-action parents-gallery-action--delete">
                                                <i class="fas fa-trash"></i> Διαγραφή
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('gallery-images');
    var preview = document.getElementById('parents-upload-preview');
    var status = document.getElementById('parents-upload-status');
    var dropzone = document.getElementById('parents-upload-dropzone');

    if (!input || !preview || !status || !dropzone) {
        return;
    }

    function renderPreview(files) {
        preview.innerHTML = '';

        if (!files || files.length === 0) {
            status.textContent = 'Δεν έχουν επιλεγεί ακόμη αρχεία.';
            dropzone.classList.remove('is-active');
            return;
        }

        status.textContent = files.length + (files.length === 1 ? ' φωτογραφία έτοιμη για ανέβασμα.' : ' φωτογραφίες έτοιμες για ανέβασμα.');
        dropzone.classList.add('is-active');

        Array.prototype.forEach.call(files, function (file) {
            var item = document.createElement('div');
            item.className = 'parents-upload-preview__item';

            var thumb = document.createElement('img');
            thumb.className = 'parents-upload-preview__thumb';
            thumb.alt = file.name;
            thumb.src = URL.createObjectURL(file);
            thumb.onload = function () {
                URL.revokeObjectURL(thumb.src);
            };

            var meta = document.createElement('div');
            meta.className = 'parents-upload-preview__meta';

            var name = document.createElement('strong');
            name.textContent = file.name;

            var size = document.createElement('span');
            size.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

            meta.appendChild(name);
            meta.appendChild(size);
            item.appendChild(thumb);
            item.appendChild(meta);
            preview.appendChild(item);
        });
    }

    input.addEventListener('change', function () {
        renderPreview(input.files);
    });
});
</script>
</body>
</html>
