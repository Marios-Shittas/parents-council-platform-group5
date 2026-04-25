<?php
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../services/HomePageService.php';

$homePageService = new HomePageService();
$homeSections = $homePageService->getAllSections();

$bannerSection = $homeSections['banner_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$heroSection = $homeSections['hero_section'] ?? [
    'title' => 'Σύνδεσμος Γονέων & Κηδεμόνων Γυμνασίου Αγίου Αθανασίου',
    'subtitle' => '',
    'content' => [],
];
$calendarSection = $homeSections['calendar_section'] ?? ['title' => 'Ημερολόγιο', 'subtitle' => '', 'content' => []];
$announcementsSection = $homeSections['announcements_section'] ?? ['title' => 'Τελευταίες Ανακοινώσεις', 'subtitle' => '', 'content' => []];
$eventsSection = $homeSections['events_section'] ?? ['title' => 'Τελευταίες Εκδηλώσεις', 'subtitle' => '', 'content' => []];

$heroKicker = (string)($heroSection['content']['kicker'] ?? 'Καλωσορίσατε στην επίσημη ιστοσελίδα');
$heroTitle = (string)($heroSection['title'] ?? 'Σύνδεσμος Γονέων & Κηδεμόνων Γυμνασίου Αγίου Αθανασίου');
$heroDescription = (string)($heroSection['subtitle'] ?? '');
$heroAnnouncementsButtonLabel = (string)($heroSection['content']['announcements_button_label'] ?? 'Ανακοινώσεις');
$heroEventsButtonLabel = (string)($heroSection['content']['events_button_label'] ?? 'Εκδηλώσεις');
$calendarBlockTitle = (string)($calendarSection['title'] ?? 'Ημερολόγιο');
$announcementsBlockTitle = (string)($announcementsSection['title'] ?? 'Τελευταίες Ανακοινώσεις');
$announcementsBlockButton = (string)($announcementsSection['content']['button_label'] ?? 'Όλες οι Ανακοινώσεις');
$eventsBlockTitle = (string)($eventsSection['title'] ?? 'Τελευταίες Εκδηλώσεις');
$eventsBlockButton = (string)($eventsSection['content']['button_label'] ?? 'Όλες οι Εκδηλώσεις');

if (!function_exists('home_public_content_url_exists')) {
    // Leitourgia home_public_content_url_exists: xeirizetai to antistoixo kommati tis selidas i tou service.
    function home_public_content_url_exists(string $url): bool
    {
        $path = (string)parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return true;
        }

        $publicPrefix = '/parents-council-platform-group5/public/';
        if (strpos($path, $publicPrefix) !== 0) {
            return true;
        }

        $relativePath = urldecode(substr($path, strlen($publicPrefix)));
        if ($relativePath === '' || strpos(str_replace('\\', '/', $relativePath), '..') !== false) {
            return false;
        }

        $publicRoot = realpath(__DIR__ . '/../../../public');
        if ($publicRoot === false) {
            return false;
        }

        $filePath = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        return is_file($filePath);
    }
}
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
        <title>Αρχική - <?php echo htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    </head>

    <body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="home-page">
        <?php
        $defaultHomeBannerSlides = [
            ['src' => site_asset_url('img/home-school-banner.png'), 'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 1'],
            ['src' => site_asset_url('img/home-school-banner-2.png'), 'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 2'],
            ['src' => site_asset_url('img/home-school-banner-3.png'), 'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Banner 3'],
        ];
        $storedHomeBannerSlides = is_array($bannerSection['content']['slides'] ?? null) ? $bannerSection['content']['slides'] : [];
        $homeBannerSlides = [];
        $isRenderableBannerAsset = static function (string $url): bool {
            $trimmedUrl = trim($url);
            if ($trimmedUrl === '') {
                return false;
            }

            if (strpos($trimmedUrl, 'data:') === 0 || preg_match('#^https?://#i', $trimmedUrl)) {
                return true;
            }

            if ($trimmedUrl[0] !== '/') {
                return true;
            }

            $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
            if ($documentRoot === '') {
                return true;
            }

            $relativePath = str_replace('/', DIRECTORY_SEPARATOR, ltrim($trimmedUrl, '/'));
            $absolutePath = $documentRoot . DIRECTORY_SEPARATOR . $relativePath;

            return is_file($absolutePath);
        };

        foreach ($defaultHomeBannerSlides as $index => $defaultSlide) {
            $storedSlide = is_array($storedHomeBannerSlides[$index] ?? null) ? $storedHomeBannerSlides[$index] : [];
            if (!empty($storedSlide['hidden'])) {
                continue;
            }

            $storedSrc = trim((string)($storedSlide['src'] ?? ''));
            $defaultSrc = (string)$defaultSlide['src'];
            $resolvedSrc = site_resolve_content_url($storedSrc !== '' ? $storedSrc : $defaultSrc);

            if (!$isRenderableBannerAsset($resolvedSrc)) {
                $resolvedSrc = site_resolve_content_url($defaultSrc);
            }

            if (trim($resolvedSrc) === '') {
                continue;
            }

            if (!$isRenderableBannerAsset($resolvedSrc)) {
                continue;
            }

            $homeBannerSlides[] = [
                'src' => $resolvedSrc,
                'alt' => (string)($storedSlide['alt'] ?? $defaultSlide['alt']),
            ];
        }
        ?>

        <?php if (!empty($homeBannerSlides)): ?>
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
        <?php endif; ?>

        <section class="home-hero container-fluid px-0">
            <div class="home-hero-inner container">
                <div class="hero-copy">
                    <p class="hero-kicker"><?php echo htmlspecialchars($heroKicker, ENT_QUOTES, 'UTF-8'); ?></p>
                    <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-description"><?php echo htmlspecialchars($heroDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="hero-actions">
                        <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn hero-outline-btn">
                            <i class="fas fa-bullhorn"></i>
                            <?php echo htmlspecialchars($heroAnnouncementsButtonLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <a href="<?php echo site_section_url('events.php'); ?>" class="btn hero-outline-btn">
                            <i class="fas fa-calendar-check"></i>
                            <?php echo htmlspecialchars($heroEventsButtonLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </div>
                <div class="hero-calendar">
                    <div class="block-content" id="calendar-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-calendar-alt mr-2"></i><?php echo htmlspecialchars($calendarBlockTitle, ENT_QUOTES, 'UTF-8'); ?></h5>
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
                            <h5 class="block-title"><i class="fas fa-bullhorn mr-2"></i><?php echo htmlspecialchars($announcementsBlockTitle, ENT_QUOTES, 'UTF-8'); ?></h5>
                        </div>
                        <div id="announcements-root"></div>
                        <a href="<?php echo site_section_url('announcements.php'); ?>" class="btn btn-primary home-cta-btn"><?php echo htmlspecialchars($announcementsBlockButton, ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="block-content" id="upcoming-events-block">
                        <div class="block-heading-wrap">
                            <h5 class="block-title"><i class="fas fa-star mr-2"></i><?php echo htmlspecialchars($eventsBlockTitle, ENT_QUOTES, 'UTF-8'); ?></h5>
                        </div>
                        <div id="upcoming-events-root"></div>
                        <a href="<?php echo site_section_url('events.php'); ?>" class="btn btn-primary home-cta-btn"><?php echo htmlspecialchars($eventsBlockButton, ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="<?php echo site_asset_url('js/home-banner-carousel.js'); ?>" defer></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-calendar.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-announcements.jsx'); ?>"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/home-page-upcoming-events.jsx'); ?>"></script>
    </body>
</html>
