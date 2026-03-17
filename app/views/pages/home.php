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

    <main class="home-page">
        <section class="home-hero container-fluid px-0">
            <div class="home-hero-inner container">
                <div class="hero-copy">
                    <p class="hero-kicker">Σύλλογος Γονέων &amp; Κηδεμόνων</p>
                    <h1>Γυμνάσιο Αγίου Αθανασίου</h1>
                    <p class="hero-description">Ένας σύγχρονος, οργανωμένος χώρος ενημέρωσης για την καθημερινότητα του σχολείου. Παρακολουθήστε ανακοινώσεις, ημερολόγιο και επερχόμενες δράσεις σε μία κεντρική αρχική σελίδα.</p>
                    <div class="hero-actions">
                        <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn btn-primary">Ανακοινώσεις</a>
                        <a href="<?php echo site_section_url('events.php'); ?>" class="btn hero-outline-btn">Εκδηλώσεις</a>
                    </div>
                </div>
                <div class="hero-highlights">
                    <div class="highlight-card">
                        <span class="highlight-label">Ενημέρωση</span>
                        <p>Ανακοινώσεις με άμεση πληροφόρηση για γονείς και μαθητές.</p>
                    </div>
                    <div class="highlight-card">
                        <span class="highlight-label">Οργάνωση</span>
                        <p>Ημερολόγιο σχολικών δράσεων με καθαρή προβολή ανά μήνα.</p>
                    </div>
                    <div class="highlight-card">
                        <span class="highlight-label">Συμμετοχή</span>
                        <p>Προβολή επερχόμενων εκδηλώσεων και ενεργός συμμετοχή της κοινότητας.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-content container">
            <div class="row home-grid">
                <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                    <div class="block-content" id="announcements-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-bullhorn mr-2"></i>Ανακοινώσεις</h5>
                        </div>
                        <div id="announcements-root"></div>
                        <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn btn-primary home-cta-btn">Περισσότερα</a>
                    </div>
                </div>
                <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                    <div class="block-content" id="calendar-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-calendar-alt mr-2"></i>Ημερολόγιο</h5>
                        </div>
                        <div id="calendar-root"></div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="block-content" id="upcoming-events-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-star mr-2"></i>Επερχόμενες Εκδηλώσεις</h5>
                        </div>
                        <div id="upcoming-events-root"></div>
                        <a href="<?php echo site_section_url('events.php'); ?>" class="btn btn-primary home-cta-btn">Περισσότερα</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-announcements.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-upcoming-events.jsx'); ?>"></script>
    </body>
</html>
