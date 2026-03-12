<?php
/**
 * Public Events Page
 * Displays all events with images fetched from the database
 */

require_once __DIR__ . '/../app/services/EventsService.php';

// Initialize the service and fetch events
$eventsService = new EventsService();
$upcomingEvents = $eventsService->getUpcomingEvents();
$pastEvents = $eventsService->getPastEvents();

// Default placeholder image if no image exists
$defaultImage = '/parents-council-platform-group5/public/assets/img/placeholder.jpg';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/events.css">

    <title>Εκδηλώσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>

<body>

<!-- Header -->
<?php include __DIR__ . '/../app/includes/header.php'; ?>

<!-- Page Header -->
<div class="events-hero">
    <div class="container">
        <h1><i class="fas fa-calendar-alt mr-2"></i>Εκδηλώσεις</h1>
        <p class="lead">Ενημερωθείτε για τις επερχόμενες εκδηλώσεις του σχολείου</p>
    </div>
</div>

<!-- Events Grid -->
<div class="container">
    <?php if (empty($upcomingEvents) && empty($pastEvents)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Δεν υπάρχουν εκδηλώσεις</h3>
            <p>Δεν έχουν καταχωρηθεί εκδηλώσεις αυτή τη στιγμή.</p>
        </div>
    <?php else: ?>
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
                        <?php include __DIR__ . '/../app/includes/event_card.php'; ?>
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
                        <?php include __DIR__ . '/../app/includes/event_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<!-- Footer -->
<?php include __DIR__ . '/../app/includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
