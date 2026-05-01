<?php
// Arxeio: public\admin\photos.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: einai gia admin, opote kratame elegxous rolou kai feedback kathara gia ton diaxeiristi.
require_once __DIR__ . '/../../app/services/ParentsPageService.php';
require_once __DIR__ . '/../../app/includes/site_context.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . site_login_url());
    exit;
}

// Leitourgia photosAdminTrim: xeirizetai to antistoixo kommati tis selidas i tou service.
function photosAdminTrim($value)
{
    return trim((string)$value);
}

// Leitourgia getPhotosGalleryUploadDir: xeirizetai to antistoixo kommati tis selidas i tou service.
function getPhotosGalleryUploadDir()
{
    return dirname(__DIR__) . '/assets/Parents_img/';
}

// Leitourgia buildPhotosGalleryWebPath: xeirizetai to antistoixo kommati tis selidas i tou service.
function buildPhotosGalleryWebPath($fileName)
{
    return site_asset_url('Parents_img/' . $fileName);
}

// Leitourgia isLocalPhotosGalleryPath: xeirizetai to antistoixo kommati tis selidas i tou service.
function isLocalPhotosGalleryPath($imagePath)
{
    return str_starts_with((string)$imagePath, site_asset_url('Parents_img/'));
}

// Leitourgia resolvePhotosGalleryFilePath: xeirizetai to antistoixo kommati tis selidas i tou service.
function resolvePhotosGalleryFilePath($imagePath)
{
    return getPhotosGalleryUploadDir() . basename((string)$imagePath);
}

// Leitourgia diagrafiPhotosGalleryFileIfExists: xeirizetai to antistoixo kommati tis selidas i tou service.
function deletePhotosGalleryFileIfExists($imagePath)
{
    if (!isLocalPhotosGalleryPath($imagePath)) {
        return;
    }

    $filePath = resolvePhotosGalleryFilePath($imagePath);
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Leitourgia uploadPhotosGalleryImages: xeirizetai to antistoixo kommati tis selidas i tou service.
function uploadPhotosGalleryImages($service)
{
    $uploadedCount = 0;
    $uploadErrors = [];

    if (empty($_FILES['gallery_images']['name'][0])) {
        return [$uploadedCount, $uploadErrors];
    }

    $uploadDir = getPhotosGalleryUploadDir();
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            $uploadErrors[] = "Αδυναμία δημιουργίας του φακέλου ανεβάσματος: {$uploadDir}";
            return [$uploadedCount, $uploadErrors];
        }
    }

    @chmod($uploadDir, 0777);
    clearstatcache(true, $uploadDir);

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

        $imagePath = buildPhotosGalleryWebPath($newFileName);
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

// Leitourgia addPhotosGalleryImageFromUrl: xeirizetai to antistoixo kommati tis selidas i tou service.
function addPhotosGalleryImageFromUrl($service)
{
    $imageUrl = photosAdminTrim($_POST['image_url'] ?? '');

    if ($imageUrl === '') {
        return [false, 'Δώστε το URL της εικόνας.'];
    }

    if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
        return [false, 'Το URL της εικόνας δεν είναι έγκυρο.'];
    }

    $imagePath = parse_url($imageUrl, PHP_URL_PATH) ?? '';
    $imageExt = strtolower(pathinfo((string)$imagePath, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($imageExt, $allowedExtensions, true)) {
        return [false, 'Βάλτε direct URL εικόνας που να οδηγεί κατευθείαν σε αρχείο JPG, JPEG, PNG, GIF ή WEBP.'];
    }

    if ($service->addGalleryImage($imageUrl, $imageUrl, 'Φωτογραφικό υλικό σχολείου')) {
        return [true, 'Η φωτογραφία από εξωτερικό σύνδεσμο προστέθηκε επιτυχώς.'];
    }

    return [false, 'Αποτυχία αποθήκευσης της φωτογραφίας. ' . $service->getLastError()];
}

$parentsPageService = new ParentsPageService();
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectUrl = 'photos.php';

    if ($action === 'update_gallery_settings') {
        $gallerySection = $parentsPageService->getSection('gallery_section') ?? ['content' => []];
        $saved = $parentsPageService->updateSection(
            'gallery_section',
            photosAdminTrim($_POST['title'] ?? 'Φωτογραφίες'),
            photosAdminTrim($_POST['subtitle'] ?? ''),
            [
                'eyebrow' => photosAdminTrim($_POST['eyebrow'] ?? ''),
                'empty_message' => photosAdminTrim($_POST['empty_message'] ?? ''),
            ]
        );

        $_SESSION['flash_message'] = $saved
            ? 'Οι ρυθμίσεις της σελίδας φωτογραφιών αποθηκεύτηκαν επιτυχώς.'
            : 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση. ' . $parentsPageService->getLastError();
        $_SESSION['flash_type'] = $saved ? 'success' : 'danger';
        $redirectUrl .= '#content-management';
    } elseif ($action === 'upload_gallery_images') {
        [$uploadedCount, $uploadErrors] = uploadPhotosGalleryImages($parentsPageService);

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
        $redirectUrl .= '#content-management';
    } elseif ($action === 'add_gallery_image_url') {
        [$saved, $message] = addPhotosGalleryImageFromUrl($parentsPageService);
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $saved ? 'success' : 'danger';
        $redirectUrl .= '#content-management';
    } elseif ($action === 'delete_gallery_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $image = $parentsPageService->getGalleryImageById($imageId);

        if ($image && $parentsPageService->deleteGalleryImage($imageId)) {
            deletePhotosGalleryFileIfExists($image['full_image_path'] ?? '');

            $thumbImagePath = $image['thumb_image_path'] ?? '';
            if ($thumbImagePath !== ($image['full_image_path'] ?? '')) {
                deletePhotosGalleryFileIfExists($thumbImagePath);
            }

            $_SESSION['flash_message'] = 'Η φωτογραφία διαγράφηκε επιτυχώς.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Αποτυχία διαγραφής της φωτογραφίας. ' . $parentsPageService->getLastError();
            $_SESSION['flash_type'] = 'danger';
        }

        $redirectUrl .= '#content-management';
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$sections = $parentsPageService->getAllSections();
$gallerySection = $sections['gallery_section'] ?? ['title' => 'Φωτογραφίες', 'subtitle' => '', 'content' => []];
$galleryImages = $parentsPageService->getGalleryImages();

$validGalleryImages = [];
$localImagesCount = 0;
$externalImagesCount = 0;

foreach ($galleryImages as $image) {
    $fullPath = trim((string)($image['full_image_path'] ?? ''));
    if ($fullPath === '') {
        continue;
    }

    $validGalleryImages[] = $image;
    if (isLocalPhotosGalleryPath($fullPath)) {
        $localImagesCount++;
    } else {
        $externalImagesCount++;
    }
}

$galleryImages = $validGalleryImages;
$photosCount = count($galleryImages);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Διαχείριση Φωτογραφιών - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_parents.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_photos.css">
</head>
<body>
<div class="admin-wrapper">
    <?php include_once __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Πίσω στην Αρχική
        </a>

        <div class="admin-header">
            <h1>
                <i class="fas fa-camera"></i>
                Διαχείριση Σελίδας Φωτογραφιών
            </h1>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $flashMessage; ?></span>
            </div>
        <?php endif; ?>

        <section class="photos-admin-overview">
            <div class="photos-admin-overview__content">
                <p class="photos-admin-overview__kicker">Διαχείριση Γκαλερί</p>
                <h2>Οργάνωσε τη σελίδα φωτογραφιών με απλό τρόπο</h2>
                <p>Από εδώ ρυθμίζεις τα βασικά κείμενα, ανεβάζεις νέες εικόνες και διαχειρίζεσαι τη βιβλιοθήκη που εμφανίζεται στους γονείς.</p>
            </div>
            <div class="photos-admin-overview__meta">
                <span class="photos-admin-overview__count"><?php echo $photosCount; ?></span>
                <span class="photos-admin-overview__label">φωτογραφίες στη βιβλιοθήκη</span>
            </div>
        </section>

        <div id="content-management" class="content-management photos-admin-shell">
            <div class="content-management__intro">
                <div>
                    <h2><i class="fas fa-edit"></i> Διαχείριση Περιεχομένου</h2>
                    <p>Επίλεξε μία από τις 3 ενέργειες πιο κάτω για να ανοίξει το αντίστοιχο pop-up και να κάνεις γρήγορα τις αλλαγές σου.</p>
                </div>
            </div>

            <div class="photos-admin-actions-grid">
                <button type="button" class="photos-admin-action-card" data-toggle="modal" data-target="#photosSettingsModal">
                    <span class="photos-admin-action-card__icon"><i class="fas fa-sliders-h"></i></span>
                    <span class="photos-admin-action-card__title">Ρυθμίσεις Σελίδας</span>
                    <span class="photos-admin-action-card__text">Τίτλος, μικρός τίτλος ενότητας και μήνυμα όταν η gallery είναι άδεια.</span>
                </button>

                <button type="button" class="photos-admin-action-card" data-toggle="modal" data-target="#externalUrlModal">
                    <span class="photos-admin-action-card__icon"><i class="fas fa-link"></i></span>
                    <span class="photos-admin-action-card__title">Προσθήκη Από Εξωτερικό Σύνδεσμο</span>
                    <span class="photos-admin-action-card__text">Πρόσθεσε εικόνα μέσα από direct URL εξωτερικής πηγής.</span>
                </button>

                <button type="button" class="photos-admin-action-card" data-toggle="modal" data-target="#uploadPhotosModal">
                    <span class="photos-admin-action-card__icon"><i class="fas fa-upload"></i></span>
                    <span class="photos-admin-action-card__title">Ανέβασμα Φωτογραφιών</span>
                    <span class="photos-admin-action-card__text">Ανέβασε νέες εικόνες απευθείας από τον υπολογιστή σου.</span>
                </button>
            </div>

            <section class="content-editor-card photos-admin-card photos-admin-collapsible mt-4">
                <div class="content-editor-card__header photos-admin-collapsible__header">
                    <div>
                        <h3>Τρέχουσες Φωτογραφίες</h3>
                        <p>Εδώ βλέπεις όλες τις φωτογραφίες που εμφανίζονται στη σελίδα φωτογραφιών και μπορείς να τις αφαιρέσεις άμεσα.</p>
                    </div>
                    <div class="photos-admin-collapsible__actions">
                        <span class="content-editor-card__icon"><i class="fas fa-photo-video"></i></span>
                        <button
                            type="button"
                            class="photos-admin-collapse-toggle"
                            data-toggle="collapse"
                            data-target="#photosLibraryCollapse"
                            aria-expanded="true"
                            aria-controls="photosLibraryCollapse"
                        >
                            <span>Άνοιγμα / Κλείσιμο</span>
                            <i class="fas fa-chevron-down photos-admin-collapse-toggle__chevron" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="collapse show photos-admin-collapsible__body" id="photosLibraryCollapse">
                    <?php if (empty($galleryImages)): ?>
                        <div class="photos-admin-empty-state">
                            <i class="fas fa-camera"></i>
                            <h3>Δεν υπάρχουν ακόμη φωτογραφίες</h3>
                            <p class="mb-0">Ανέβασε αρχεία ή πρόσθεσε ένα εξωτερικό URL για να αρχίσει να γεμίζει η gallery.</p>
                        </div>
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
                                                <?php echo isLocalPhotosGalleryPath($fullPath) ? 'Τοπικό αρχείο' : 'Εξωτερικό URL'; ?>
                                            </span>
                                        </div>

                                        <p class="parents-gallery-admin-card__path"><?php echo htmlspecialchars($fullPath); ?></p>

                                        <div class="parents-gallery-admin-card__actions">
                                            <a href="<?php echo htmlspecialchars($fullPath); ?>" target="_blank" rel="noopener noreferrer" class="parents-gallery-action parents-gallery-action--view">
                                                <i class="fas fa-eye"></i> Προβολή
                                            </a>

                                            <form
                                                method="POST"
                                                class="parents-gallery-delete-form"
                                                data-image-label="<?php echo htmlspecialchars('Φωτογραφία #' . (int)$image['image_id']); ?>"
                                                data-image-path="<?php echo htmlspecialchars($fullPath); ?>"
                                                data-image-thumb="<?php echo htmlspecialchars($thumbPath); ?>"
                                                data-image-source="<?php echo htmlspecialchars(isLocalPhotosGalleryPath($fullPath) ? 'Τοπικό αρχείο' : 'Εξωτερικό URL'); ?>"
                                            >
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
        </div>
    </main>
</div>

<div class="modal fade" id="photosSettingsModal" tabindex="-1" role="dialog" aria-labelledby="photosSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content photos-admin-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="photosSettingsModalLabel">Ρυθμίσεις Σελίδας</h5>
                    <p class="photos-admin-modal__subtitle mb-0">Κείμενα που χρησιμοποιούνται όταν ανοίγει η σελίδα ή όταν δεν υπάρχουν ακόμη φωτογραφίες.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_gallery_settings">

                    <div class="content-form-grid">
                        <div class="form-group">
                            <label for="photos-page-title">Τίτλος ενότητας</label>
                            <input type="text" class="form-control" id="photos-page-title" name="title" value="<?php echo htmlspecialchars($gallerySection['title'] ?? 'Φωτογραφίες'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="photos-page-eyebrow">Μικρός τίτλος ενότητας</label>
                            <input type="text" class="form-control" id="photos-page-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($gallerySection['content']['eyebrow'] ?? 'Φωτογραφικό Υλικό'); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="photos-page-empty-message">Μήνυμα όταν δεν υπάρχουν φωτογραφίες</label>
                            <textarea class="form-control content-textarea" id="photos-page-empty-message" name="empty_message"><?php echo htmlspecialchars($gallerySection['content']['empty_message'] ?? 'Δεν έχουν προστεθεί ακόμη φωτογραφίες.'); ?></textarea>
                        </div>
                    </div>

                    <div class="content-editor-card__actions">
                        <button type="submit" class="btn-save-section">
                            <i class="fas fa-save"></i> Αποθήκευση Ρυθμίσεων
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="externalUrlModal" tabindex="-1" role="dialog" aria-labelledby="externalUrlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content photos-admin-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="externalUrlModalLabel">Προσθήκη Από Εξωτερικό Σύνδεσμο</h5>
                    <p class="photos-admin-modal__subtitle mb-0">Βάλε direct URL εικόνας που να ανοίγει κατευθείαν το αρχείο.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_gallery_image_url">

                    <div class="form-group">
                        <label for="gallery-image-url">URL εικόνας</label>
                        <input type="url" class="form-control" id="gallery-image-url" name="image_url" placeholder="https://example.com/image.jpg" required>
                        <small class="form-text text-muted">Βάλε link που να ανοίγει κατευθείαν την εικόνα, όχι link σε σελίδα.</small>
                    </div>

                    <div class="photos-admin-tip">
                        <i class="fas fa-info-circle"></i>
                        <span>Το εξωτερικό URL εμφανίζεται κανονικά στη gallery, αλλά η εικόνα παραμένει φιλοξενούμενη στον εξωτερικό server.</span>
                    </div>

                    <div class="content-editor-card__actions">
                        <button type="submit" class="btn-save-section">
                            <i class="fas fa-plus-circle"></i> Προσθήκη Από URL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadPhotosModal" tabindex="-1" role="dialog" aria-labelledby="uploadPhotosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content photos-admin-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="uploadPhotosModalLabel">Ανέβασμα Φωτογραφιών</h5>
                    <p class="photos-admin-modal__subtitle mb-0">Υποστηρίζονται αρχεία JPG, PNG, GIF και WEBP έως 5MB το καθένα.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
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
        </div>
    </div>
</div>

<div class="modal fade parents-confirm-modal" id="deleteGalleryImageConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteGalleryImageConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="deleteGalleryImageConfirmModalLabel">Οριστική Διαγραφή</h5>
                    <p class="parents-confirm-modal__subtitle mb-0">Η φωτογραφία θα αφαιρεθεί αμέσως από τη σελίδα φωτογραφιών.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="parents-confirm-modal__preview">
                    <div class="parents-confirm-modal__thumb-wrap">
                        <img src="" alt="" class="parents-confirm-modal__thumb" id="deleteGalleryImageThumb">
                    </div>
                    <div class="parents-confirm-modal__details">
                        <span class="parents-confirm-modal__eyebrow">Επιλεγμένη Φωτογραφία</span>
                        <strong class="parents-confirm-modal__name" id="deleteGalleryImageLabel">φωτογραφία</strong>
                        <span class="parents-confirm-modal__meta-badge" id="deleteGalleryImageSource">Τοπικό αρχείο</span>
                        <p class="parents-confirm-modal__path mb-0" id="deleteGalleryImagePath"></p>
                    </div>
                </div>
                <div class="parents-confirm-modal__warning">
                    <span class="parents-confirm-modal__icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div>
                        <p class="mb-2">Θέλεις σίγουρα να προχωρήσεις στη διαγραφή;</p>
                        <p class="mb-0">Η ενέργεια δεν αναιρείται. Αν είναι τοπικό αρχείο, θα αφαιρεθεί και από τον server.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="parents-modal-btn parents-modal-btn--secondary" data-dismiss="modal">Ακύρωση</button>
                <button type="button" class="parents-modal-btn parents-modal-btn--danger" id="confirmDeleteGalleryImageButton">
                    <i class="fas fa-trash"></i> Ναι, διαγραφή
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-photos.js"></script>
</body>
</html>
