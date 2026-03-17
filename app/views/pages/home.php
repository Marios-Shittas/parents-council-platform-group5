<?php
require_once __DIR__ . '/../../includes/site_context.php';
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
        <link rel="stylesheet" href="<?php echo site_asset_url('css/home.css'); ?>">
        <title>Αρχική - Γυμνάσιο Αγίου Αθανασίου</title>
    </head>

    <body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <div class="paragraph-container px-3">
        <h1>Καλώς ήρθατε στον Σύλλογο Γονέων του Γυμνασίου Αγίου Αθανασίου</h1>
        <p>Αυτή είναι η αρχική σελίδα του ιστότοπού μας. Εδώ μπορείτε να βρείτε τις τελευταίες ανακοινώσεις, εκδηλώσεις ημερολογίου και επερχόμενες εκδηλώσεις.</p>
    </div>

    <div class="row">
        <div class="col-12 col-md-4 px-1">
            <div class="block-content" id="announcements-block">
                <h5 class="block-title">Ανακοινώσεις</h5>
                <div id="announcements-root"></div>
                <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn btn-primary mt-3">Περισσότερα</a>
            </div>
        </div>
        <div class="col-12 col-md-4 px-1">
            <div class="block-content" id="calendar-block">
                <h5 class="block-title">Ημερολόγιο</h5>
                <div id="calendar-root"></div>
            </div>
        </div>
        <div class="col-12 col-md-4 px-1">
            <div class="block-content" id="upcoming-events-block">
                <h5 class="block-title">Επερχόμενες Εκδηλώσεις</h5>
                <div id="upcoming-events-root"></div>
                <?php if (site_is_parent()): ?>
                    <a href="<?php echo site_section_url('events.php'); ?>" class="btn btn-primary mt-3">Περισσότερα</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-announcements.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-upcoming-events.jsx'); ?>"></script>
    </body>
</html>
