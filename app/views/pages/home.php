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
        <title>Αρχική - Σύνδεσμος Γονέων &amp; Κηδεμόνων Γυμνασίου Αγίου Αθανασίου</title>
    </head>

    <body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="home-page">
        <?php
        $homeBannerSlides = [
            [
                'src' => site_asset_url('img/home-school-banner.png'),
                'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 1',
            ],
            [
                'src' => site_asset_url('img/home-school-banner-2.png'),
                'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 2',
            ],
            [
                'src' => site_asset_url('img/home-school-banner-3.png'),
                'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 3',
            ],
        ];
        ?>

        <section class="home-school-banner container-fluid px-0" aria-label="Banner σχολείου">
            <div class="container">
                <div class="home-school-banner__frame">
                    <div class="home-school-banner__carousel" aria-live="polite" data-interval="15000">
                        <?php foreach ($homeBannerSlides as $index => $slide): ?>
                            <img
                                src="<?php echo htmlspecialchars($slide['src'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($slide['alt'], ENT_QUOTES, 'UTF-8'); ?>"
                                class="home-school-banner__image<?php echo $index === 0 ? ' is-active' : ''; ?>"
                            >
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-hero container-fluid px-0">
            <div class="home-hero-inner container">
                <div class="hero-copy">
                    <p class="hero-kicker">Καλωσορίσατε στην επίσημη ιστοσελίδα</p>
                    <h1>Σύνδεσμος Γονέων &amp; Κηδεμόνων Γυμνασίου Αγίου Αθανασίου</h1>
                    <p class="hero-description">Στην ιστοσελίδα μας μπορείτε να ενημερώνεστε για όλες τις ανακοινώσεις, δράσεις και εκδηλώσεις του Συνδέσμου Γονέων. Μπορείτε να βρείτε χρήσιμες πληροφορίες, αιτήσεις, φωτογραφικό υλικό και πρωτοβουλίες που συμβάλλουν στη δημιουργία ενός καλύτερου σχολικού περιβάλλοντος για τα παιδιά μας.</p>
                    <div class="hero-actions">
                        <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn hero-outline-btn">
                            <i class="fas fa-bullhorn"></i>
                            Ανακοινώσεις
                        </a>
                        <a href="<?php echo site_section_url('events.php'); ?>" class="btn hero-outline-btn">
                            <i class="fas fa-calendar-check"></i>
                            Εκδηλώσεις
                        </a>
                    </div>
                </div>
                <div class="hero-calendar">
                    <div class="block-content" id="calendar-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-calendar-alt mr-2"></i>Ημερολόγιο</h5>
                        </div>
                        <div id="calendar-root"></div>
                        <div id="event-detail-root"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-content container">
            <div class="row home-grid">
                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                    <div class="block-content" id="announcements-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-bullhorn mr-2"></i>Τελευταίες Ανακοινώσεις</h5>
                        </div>
                        <div id="announcements-root"></div>
                        <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn btn-primary home-cta-btn">Όλες οι Ανακοινώσεις</a>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="block-content" id="upcoming-events-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-star mr-2"></i>Τελευταίες Εκδηλώσεις</h5>
                        </div>
                        <div id="upcoming-events-root"></div>
                        <a href="<?php echo site_section_url('events.php'); ?>" class="btn btn-primary home-cta-btn">Όλες οι Εκδηλώσεις</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.home-school-banner__carousel').forEach(function (carousel) {
                var slides = carousel.querySelectorAll('.home-school-banner__image');

                if (slides.length <= 1) {
                    return;
                }

                var currentIndex = 0;
                var intervalMs = parseInt(carousel.getAttribute('data-interval'), 10) || 15000;

                window.setInterval(function () {
                    slides[currentIndex].classList.remove('is-active');
                    currentIndex = (currentIndex + 1) % slides.length;
                    slides[currentIndex].classList.add('is-active');
                }, intervalMs);
            });
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-calendar.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-announcements.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-upcoming-events.jsx'); ?>"></script>
    </body>
</html>
