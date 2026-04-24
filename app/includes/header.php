<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Check if user is logged in (protected page)
$isProtectedPage = isset($_SESSION['user_id']);

require_once __DIR__ . '/site_context.php';

$site_title = 'Σύνδεσμος Γονέων & Κηδεμόνων';
$current_page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$portal_label = SiteContext::isParent() ? 'Χώρος Γονέα' : 'Δημόσια Πύλη';

$profile_item = [
    'label' => 'Το Προφίλ Μου',
    'href' => SiteContext::sectionUrl('profile.php'),
    'icon' => 'fas fa-user-circle',
    'match' => ['profile.php'],
];

$nav_items = [
    [
        'label' => 'Αρχική',
        'href' => SiteContext::sectionUrl('home.php'),
        'icon' => 'fas fa-home',
        'match' => ['home.php', 'index.php', ''],
    ],
    [
        'label' => 'Σύνδεσμος Γονέων',
        'href' => SiteContext::sectionUrl('parents.php'),
        'icon' => 'fas fa-users',
        'match' => ['parents.php'],
    ],
    [
        'label' => 'Ανακοινώσεις',
        'href' => SiteContext::sectionUrl('announcements.php'),
        'icon' => 'fas fa-bullhorn',
        'match' => ['announcements.php'],
    ],
    [
        'label' => 'Εκδηλώσεις',
        'href' => SiteContext::sectionUrl('events.php'),
        'icon' => 'fas fa-calendar-alt',
        'match' => ['events.php', 'event.php'],
    ],
    [
        'label' => 'Χρήσιμες Πληροφορίες',
        'href' => SiteContext::sectionUrl('useful-information.php'),
        'icon' => 'fas fa-info-circle',
        'match' => ['useful-information.php'],
    ],
    [
        'label' => 'Αιτήσεις',
        'href' => SiteContext::sectionUrl('applications.php'),
        'icon' => 'fas fa-file-alt',
        'match' => ['applications.php'],
    ],
];

if (SiteContext::isParent()) {
    $nav_items[] = [
        'label' => 'Κατάστημα',
        'href' => SiteContext::sectionUrl('eshop.php'),
        'icon' => 'fas fa-store',
        'match' => ['eshop.php'],
    ];
}

$nav_items[] = [
    'label' => 'Επικοινωνία',
    'href' => SiteContext::sectionUrl('epikoinonia.php'),
    'icon' => 'fas fa-envelope',
    'match' => ['epikoinonia.php'],
];

if (SiteContext::isParent()) {
    $nav_items[] = [
        'label' => 'Φωτογραφίες',
        'href' => SiteContext::sectionUrl('photos.php'),
        'icon' => 'fas fa-camera',
        'match' => ['photos.php'],
    ];
}
?>
<link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/header.css'); ?>">
<script src="<?php echo SiteContext::assetUrl('js/favicon-round.js'); ?>" defer data-favicon-src="<?php echo SiteContext::assetUrl('img/logo-icon.png'); ?>"></script>

<header class="site-header">
<!-- Κύριο navigation όλου του site. -->
<nav class="navbar navbar-expand-xl navbar-light<?php echo SiteContext::isParent() ? ' navbar-parent' : ' navbar-public'; ?>">
    <div class="container">

        <!-- Λογότυπο + τίτλος σχολείου. -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo SiteContext::sectionUrl('home.php'); ?>">
            <span class="logo-stack">
                <img src="<?php echo SiteContext::assetUrl('img/logo-icon.png'); ?>" alt="Logo">
            </span>
            <span class="brand-text" aria-label="<?php echo htmlspecialchars($site_title); ?>">Σύνδεσμος Γονέων &amp; Κηδεμόνων Γυμνασίου Αγίου Αθανασίου</span>
        </a>

        <!-- Κουμπί που ανοίγει το menu σε κινητές συσκευές. -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- Τα βασικά links του site στο κέντρο. -->
            <ul class="navbar-nav text-center">
                <!-- Κάνουμε loop στο $nav_items για να αποφύγουμε επαναλαμβανόμενο HTML. -->
                <?php foreach ($nav_items as $item): ?>
                    <!-- Ελέγχουμε αν το link αντιστοιχεί στην τωρινή σελίδα. -->
                    <?php $is_active = in_array($current_page, $item['match'], true); ?>
                    <li class="nav-item<?php echo $is_active ? ' active' : ''; ?>">
                        <!-- aria-current βοηθάει accessibility (screen readers). -->
                        <a class="nav-link<?php echo !empty($item['icon_only']) ? ' nav-link--icon-only' : ''; ?>"
                           href="<?php echo $item['href']; ?>"
                           aria-label="<?php echo htmlspecialchars($item['label']); ?>"
                           title="<?php echo htmlspecialchars($item['label']); ?>"
                           <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                            <span class="nav-link-content">
                                <i class="<?php echo $item['icon']; ?>" aria-hidden="true"></i>
                                <?php if (empty($item['icon_only'])): ?>
                                    <span class="nav-link-text"><?php echo $item['label']; ?></span>
                                <?php else: ?>
                                    <span class="sr-only"><?php echo htmlspecialchars($item['label']); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Δεξιά εργαλεία: login + shortcut. -->
            <div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center navbar-tools">

                <?php if (SiteContext::isParent()): ?>
                    <a href="<?php echo SiteContext::publicUrl('logout.php'); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-xl-0 login-btn logout-btn">
                        <i class="fas fa-sign-out-alt mr-1"></i> Log out
                    </a>
                <?php else: ?>
                    <a href="<?php echo SiteContext::loginUrl(); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-xl-0 login-btn login-btn-green">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                <?php endif; ?>

                <?php if (SiteContext::isParent()): ?>
                    <?php $is_profile_active = in_array($current_page, $profile_item['match'], true); ?>
                    <a href="<?php echo $profile_item['href']; ?>"
                       class="mt-2 mt-xl-0 utility-icon-link profile-icon-link<?php echo $is_profile_active ? ' active' : ''; ?>"
                       aria-label="<?php echo htmlspecialchars($profile_item['label']); ?>"
                       title="<?php echo htmlspecialchars($profile_item['label']); ?>"
                       <?php echo $is_profile_active ? 'aria-current="page"' : ''; ?>>
                        <i class="<?php echo $profile_item['icon']; ?>"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</nav>
</header>

<script src="<?php echo SiteContext::assetUrl('js/navbar-fallback.js'); ?>" defer></script>
