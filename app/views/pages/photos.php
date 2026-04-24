<?php
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../services/ParentsPageService.php';

$parentsPageService = new ParentsPageService();
$sections = $parentsPageService->getAllSections();
$galleryImages = $parentsPageService->getGalleryImages();
$gallerySection = $sections['gallery_section'] ?? ['title' => 'Φωτογραφίες', 'subtitle' => '', 'content' => []];

$displayImages = [];

foreach ($galleryImages as $image) {
    $fullImage = trim((string)($image['full_image_path'] ?? ''));
    if ($fullImage === '') {
        continue;
    }

    $thumbImage = trim((string)($image['thumb_image_path'] ?? '')) !== '' ? $image['thumb_image_path'] : $fullImage;
    $altText = trim((string)($image['alt_text'] ?? '')) !== '' ? $image['alt_text'] : 'Φωτογραφικό υλικό σχολείου';
    $displayImages[] = [
        'full' => $fullImage,
        'thumb' => $thumbImage,
        'alt' => $altText,
    ];
}

$photosCount = count($displayImages);
$photosSectionTitle = trim((string)($gallerySection['title'] ?? '')) !== '' ? $gallerySection['title'] : 'Φωτογραφίες';
$photosSectionEyebrow = trim((string)($gallerySection['content']['eyebrow'] ?? '')) !== '' ? $gallerySection['content']['eyebrow'] : 'Φωτογραφικό Υλικό';

$pageHeaderTitle = 'Φωτογραφίες';
$pageHeaderSubtitle = 'Δείτε το φωτογραφικό υλικό από δράσεις, εκδηλώσεις και στιγμές της σχολικής κοινότητας.';
$pageHeaderIcon = 'fas fa-camera';
$pageHeaderEyebrow = SiteContext::isParent() ? 'Χώρος Γονέα' : 'Δημόσια Πύλη';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/photos.css'); ?>">
    <title>Φωτογραφίες - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php include __DIR__ . '/../../includes/public_page_header.php'; ?>

<main class="photos-page pb-5">
    <div class="container">
        <div class="photos-summary-card">
            <span class="photos-summary-card__icon"><i class="fas fa-images"></i></span>
            <div>
                <span class="photos-summary-card__label">Συνολικές Φωτογραφίες</span>
                <strong class="photos-summary-card__value"><?php echo $photosCount; ?></strong>
            </div>
        </div>

        <div class="note-card mb-4">
            <i class="fas fa-user-shield"></i>
            <p class="mb-0">Το φωτογραφικό υλικό από σχολικές δράσεις και εκδηλώσεις αναρτάται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.</p>
        </div>

        <?php if (!empty($displayImages)): ?>
            <section class="mb-5" aria-label="Gallery φωτογραφιών">
                <div class="photos-section-title">
                    <span class="section-badge"><i class="fas fa-camera"></i></span>
                    <?php echo htmlspecialchars($photosSectionTitle); ?>
                </div>

                <p class="photos-section-subtitle"><?php echo htmlspecialchars($photosSectionEyebrow); ?> με στιγμές από τη σχολική κοινότητα, δράσεις και εκδηλώσεις.</p>

                <div class="photos-gallery-grid">
                    <?php foreach ($displayImages as $index => $image): ?>
                        <article class="photos-gallery-card">
                            <a class="photos-gallery-card__media" href="<?php echo htmlspecialchars($image['full']); ?>" target="_blank" rel="noopener noreferrer">
                                <img src="<?php echo htmlspecialchars($image['thumb']); ?>" alt="<?php echo htmlspecialchars($image['alt']); ?>" loading="lazy">
                                <span class="photos-gallery-card__overlay">
                                    <span><i class="fas fa-search-plus"></i> Προβολή φωτογραφίας</span>
                                </span>
                            </a>

                            <div class="photos-gallery-card__body">
                                <h3>Φωτογραφία <?php echo $index + 1; ?></h3>
                                <p><?php echo htmlspecialchars($image['alt']); ?></p>
                                <a class="photos-gallery-card__action" href="<?php echo htmlspecialchars($image['full']); ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-external-link-alt"></i> Άνοιγμα σε νέα καρτέλα
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="photos-empty-state">
                <i class="fas fa-camera-retro"></i>
                <h3>Δεν υπάρχουν φωτογραφίες</h3>
                <p class="mb-0"><?php echo htmlspecialchars($gallerySection['content']['empty_message'] ?? 'Δεν έχουν προστεθεί ακόμη φωτογραφίες.'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
