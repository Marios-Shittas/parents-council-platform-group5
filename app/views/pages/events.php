<?php
require_once __DIR__ . '/../../services/EventsService.php';
require_once __DIR__ . '/../../includes/site_context.php';

$eventsService = new EventsService();
$upcomingEvents = $eventsService->getUpcomingEvents();
$pastEvents = $eventsService->getPastEvents();
$defaultImage = SiteContext::assetUrl('img/placeholder.jpg');
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
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/events.css'); ?>">
    <title>Εκδηλώσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>

<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Εκδηλώσεις';
$pageHeaderSubtitle = 'Δείτε τις επερχόμενες και τις προηγούμενες δράσεις του σχολείου.';
$pageHeaderIcon = 'fas fa-calendar-alt';
$pageHeaderEyebrow = 'Σχολική Ζωή';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<div class="container">
    <?php if (empty($upcomingEvents) && empty($pastEvents)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Δεν υπάρχουν εκδηλώσεις</h3>
            <p>Δεν έχουν καταχωρηθεί εκδηλώσεις αυτή τη στιγμή.</p>
        </div>
    <?php else: ?>
        <div class="note-card mb-4">
            <i class="fas fa-user-shield"></i>
            <p class="mb-0">Το φωτογραφικό υλικό από σχολικές δράσεις και εκδηλώσεις αναρτάται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.</p>
        </div>

        <section class="mb-5">
            <div class="events-section-title">
                <span class="section-badge"><i class="fas fa-calendar-alt"></i></span>
                Επερχόμενες Εκδηλώσεις
            </div>

            <?php if (empty($upcomingEvents)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <h3>Δεν υπάρχουν επερχόμενες εκδηλώσεις</h3>
                    <p>Δεν έχουν προγραμματιστεί νέες εκδηλώσεις.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <?php include __DIR__ . '/../../includes/event_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <hr class="events-divider">

        <section class="mb-5 section-past">
            <div class="events-section-title">
                <span class="section-badge"><i class="fas fa-history"></i></span>
                Προηγούμενες Εκδηλώσεις
            </div>

            <?php if (empty($pastEvents)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>Δεν υπάρχουν προηγούμενες εκδηλώσεις</h3>
                    <p>Οι παλαιότερες εκδηλώσεις θα εμφανίζονται εδώ.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($pastEvents as $event): ?>
                        <?php include __DIR__ . '/../../includes/event_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SiteContext::assetUrl('js/events-open-modal.js'); ?>"></script>
</body>
</html>
