<?php
require_once __DIR__ . '/../../services/AnnouncementsService.php';
require_once __DIR__ . '/../../includes/site_context.php';

$announcementsService = new AnnouncementsService();
$announcements = $announcementsService->getAllAnnouncements();
$defaultImage = site_asset_url('img/placeholder.jpg');
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/announcements.css'); ?>">
    <title>Ανακοινώσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>

<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Ανακοινώσεις';
$pageHeaderSubtitle = 'Ενημερωθείτε για τα τελευταία νέα του σχολείου.';
$pageHeaderIcon = 'fas fa-bullhorn';
$pageHeaderEyebrow = 'Νέα Και Ενημερώσεις';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<div class="container">
    <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Δεν υπάρχουν ανακοινώσεις</h3>
            <p>Δεν έχουν δημοσιευτεί ανακοινώσεις ακόμα.</p>
        </div>
    <?php else: ?>
        <div class="note-card mb-4">
            <i class="fas fa-user-shield"></i>
            <p class="mb-0">Το περιεχόμενο των ανακοινώσεων και τα συνημμένα έγγραφα δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.</p>
        </div>

        <section class="mb-5">
            <div class="announcements-section-title">
                <span class="section-badge"><i class="fas fa-bullhorn"></i></span>
                Όλες οι Ανακοινώσεις
            </div>
            <div class="row">
                <?php foreach ($announcements as $announcement): ?>
                    <?php include __DIR__ . '/../../includes/announcement_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var params = new URLSearchParams(window.location.search);
        var openId = params.get('open');

        if (!openId) {
            return;
        }

        var modalElement = document.getElementById('announcementModal' + openId);
        if (!modalElement || typeof window.jQuery === 'undefined') {
            return;
        }

        window.jQuery(modalElement).modal('show');
    });
</script>
</body>
</html>
