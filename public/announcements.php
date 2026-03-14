<?php
/**
 * Public Announcements Page
 * Displays all announcements with images fetched from the database
 */

require_once __DIR__ . '/../app/services/AnnouncementsService.php';

// Initialize the service and fetch announcements
$announcementsService = new AnnouncementsService();
$announcements = $announcementsService->getAllAnnouncements();

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
    <link rel="stylesheet" href="assets/css/user_css/announcements.css">

    <title>Ανακοινώσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>

<body>

<!-- Header -->
<?php include __DIR__ . '/../app/includes/header.php'; ?>

<!-- Page Header -->
<div class="announcements-hero">
    <div class="container">
        <h1><i class="fas fa-bullhorn mr-2"></i>Ανακοινώσεις</h1>
        <p class="lead">Ενημερωθείτε για τα τελευταία νέα του σχολείου</p>
    </div>
</div>

<!-- Announcements Grid -->
<div class="container">
    <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Δεν υπάρχουν ανακοινώσεις</h3>
            <p>Δεν έχουν δημοσιευτεί ανακοινώσεις ακόμα.</p>
        </div>
    <?php else: ?>
        <section class="mb-5">
            <div class="announcements-section-title">
                <span class="section-badge"><i class="fas fa-bullhorn"></i></span>
                Όλες οι Ανακοινώσεις
            </div>
            <div class="row">
                <?php foreach ($announcements as $announcement): ?>
                    <?php include __DIR__ . '/../app/includes/announcement_card.php'; ?>
                <?php endforeach; ?>
            </div>
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