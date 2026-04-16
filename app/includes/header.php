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
$portal_label = site_is_parent() ? 'Χώρος Γονέα' : 'Δημόσια Πύλη';

$profile_item = [
    'label' => 'Το Προφίλ Μου',
    'href' => site_section_url('profile.php'),
    'icon' => 'fas fa-user-circle',
    'match' => ['profile.php'],
];

$nav_items = [
    [
        'label' => 'Αρχική',
        'href' => site_section_url('home.php'),
        'icon' => 'fas fa-home',
        'match' => ['home.php', 'index.php', ''],
    ],
    [
        'label' => 'Σύνδεσμος Γονέων',
        'href' => site_section_url('parents.php'),
        'icon' => 'fas fa-users',
        'match' => ['parents.php'],
    ],
    [
        'label' => 'Ανακοινώσεις',
        'href' => site_section_url('announcements.php'),
        'icon' => 'fas fa-bullhorn',
        'match' => ['announcements.php'],
    ],
    [
        'label' => 'Εκδηλώσεις',
        'href' => site_section_url('events.php'),
        'icon' => 'fas fa-calendar-alt',
        'match' => ['events.php', 'event.php'],
    ],
    [
        'label' => 'Χρήσιμες Πληροφορίες',
        'href' => site_section_url('useful-information.php'),
        'icon' => 'fas fa-info-circle',
        'match' => ['useful-information.php'],
    ],
    [
        'label' => 'Αιτήσεις',
        'href' => site_section_url('applications.php'),
        'icon' => 'fas fa-file-alt',
        'match' => ['applications.php'],
    ],
];

if (site_is_parent()) {
    $nav_items[] = [
        'label' => 'Κατάστημα',
        'href' => site_section_url('eshop.php'),
        'icon' => 'fas fa-store',
        'match' => ['eshop.php'],
    ];
}

$nav_items[] = [
    'label' => 'Επικοινωνία',
    'href' => site_section_url('epikoinonia.php'),
    'icon' => 'fas fa-envelope',
    'match' => ['epikoinonia.php'],
];

if (site_is_parent()) {
    $nav_items[] = [
        'label' => 'Φωτογραφίες',
        'href' => site_section_url('photos.php'),
        'icon' => 'fas fa-camera',
        'match' => ['photos.php'],
        'icon_only' => true,
    ];
}
?>
<style>
        /* Βασικά χρώματα για ενιαίο design. */
        :root {
            /* Χρώμα φόντου του header. */
            --header-bg: rgba(248, 249, 250, 0.95);
            /* Λεπτή γραμμή/περίγραμμα του header. */
            --header-border: rgba(0, 0, 0, 0.06);
            /* Κύριο μπλε χρώμα brand. */
            --brand-color: #1a3a5c;
            /* Βασικό χρώμα κειμένου. */
            --text-main: #3f4a56;
            /* Πιο έντονο χρώμα για hover/active. */
            --text-strong: #152536;
            /* Απαλό hover φόντο στα links. */
            --link-hover-bg: #eef3f8;
            /* Απαλό active φόντο (κρατήθηκε για πιθανή χρήση). */
            --link-active-bg: #e6edf5;
            /* Μπλε gradient ίδιο με το hero panel. */
            --nav-row-bg: linear-gradient(135deg, #102f52 0%, #1a3a5c 55%, #0057a8 100%);
        }

        /* Scoped βάση για να μη βασίζεται το header σε global κανόνες της main.css. */
        .site-header,
        .site-header * {
            box-sizing: border-box;
        }

        .site-header {
            font-family: 'Lato', sans-serif;
            color: var(--text-main);
        }

        .site-header a,
        .site-header button,
        .site-header input {
            font-family: 'Lato', sans-serif;
        }

        /* Κολλάει πάνω όταν κάνουμε scroll και μένει πάντα ορατό. */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: var(--header-bg);
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
            border-bottom: 1px solid var(--header-border);
            padding: .95rem 0 .7rem;
            font-family: 'Lato', sans-serif;
        }

        /* Ίδια διακριτική μπλε γραμμή και κάτω από το navbar. */
        .navbar::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #1a3a5c 0%, #2f6ea0 50%, #1a3a5c 100%);
            opacity: .65;
            z-index: 2;
            pointer-events: none;
        }

        /* Στυλ λογοτύπου. */
        .logo-stack {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            flex: 0 0 auto;
            width: fit-content;
        }

        .navbar-brand img {
            width: 104px;
            height: 104px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(26, 58, 92, 0.18);
        }

        /* Κείμενο δίπλα στο λογότυπο. */
        .brand-text {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            flex: 1 1 auto;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--brand-color);
            font-size: 2.2rem;
            line-height: 1.02;
            letter-spacing: 0.01em;
            white-space: nowrap;
            max-width: none;
            width: 100%;
            min-width: 0;
        }

        .brand-line {
            display: inline;
            width: auto;
        }

        .brand-line + .brand-line {
            margin-left: .35rem;
        }

        /* Λίγο κενό ανάμεσα στα menu items. */
        .navbar-nav .nav-item {
            margin: 0 .18rem;
            flex: 0 0 auto;
        }

        /* Βασικό στυλ links menu. */
        .navbar-nav .nav-link {
            font-family: 'Lato', sans-serif;
            font-weight: 600;
            color: #ffffff !important;
            padding: .5rem .95rem;
            border-radius: .65rem;
            transition: all .2s ease;
            position: relative;
            border: 0 !important;
            box-shadow: none !important;
            background: transparent;
            font-size: 0.95rem;
        }

        /* Ίδιο πλάτος στο icon ώστε να φαίνονται όλα ευθυγραμμισμένα. */
        .navbar-nav .nav-link i {
            width: 1rem;
            text-align: center;
            margin-right: .5rem;
        }

        .navbar-nav .nav-link--icon-only {
            width: 2.75rem;
            height: 2.75rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
        }

        .navbar-nav .nav-link--icon-only i {
            width: auto;
            margin-right: 0;
            font-size: 1rem;
        }

        /* Hover κατάσταση για πιο καθαρό feedback στον χρήστη. */
        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff !important;
            /* Μικρή κίνηση για πιο "ζωντανό" αποτέλεσμα. */
            transform: translateY(-1px);
        }

        /* Όταν η σελίδα είναι ενεργή, φαίνεται καθαρά. */
        .navbar-nav .active > .nav-link {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff !important;
            font-weight: 700;
        }

        /* Μικρή μπλε μπάρα κάτω από το ενεργό link. */
        .navbar-nav .active > .nav-link::after {
            content: "";
            position: absolute;
            left: .8rem;
            right: .8rem;
            bottom: .28rem;
            height: 2px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.82);
        }

        /* Κουμπί login. */
        .site-header .login-btn,
        .site-header .login-btn.btn,
        .site-header .login-btn.btn-sm {
            font-family: 'Lato', sans-serif !important;
            border-radius: 999px;
            font-weight: 600 !important;
            padding: .3rem .9rem !important;
            border-width: 2px !important;
            font-size: .95rem !important;
            line-height: 1.25 !important;
            letter-spacing: 0 !important;
            text-rendering: geometricPrecision;
        }

        .site-header .login-btn i,
        .site-header .login-btn span {
            font-size: inherit !important;
            line-height: inherit !important;
        }

        .site-header .login-btn i {
            margin-right: .5rem;
        }

        .site-header .login-btn:visited,
        .site-header .login-btn:focus,
        .site-header .login-btn:active {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        .site-header .login-btn:hover {
            color: #ffffff !important;
        }

        .logout-btn {
            border-color: rgba(255, 255, 255, 0.42);
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600 !important;
        }

        .logout-btn:hover,
        .logout-btn:focus,
        .logout-btn:active {
            background: linear-gradient(135deg, #d94b4b 0%, #b72727 100%);
            border-color: #b72727;
            color: #ffffff !important;
            box-shadow: 0 10px 22px rgba(185, 39, 39, 0.28);
        }

        .login-btn-green {
            border-color: rgba(255, 255, 255, 0.42);
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1);
        }

        .login-btn-green:hover,
        .login-btn-green:focus,
        .login-btn-green:active {
            background: linear-gradient(135deg, #2e9f4f 0%, #1f7a35 100%);
            border-color: #1f7a35;
            color: #ffffff !important;
            box-shadow: 0 10px 22px rgba(31, 122, 53, 0.28);
        }

        .utility-icon-link {
            font-family: 'Lato', sans-serif;
            width: 38px;
            height: 38px;
            padding: 0;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
        }

        .profile-icon-link {
            border: 1px solid rgba(255, 255, 255, 0.32);
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }

        .profile-icon-link:hover {
            color: #ffffff;
            background: linear-gradient(135deg, #4a90e2 0%, #1f6fb8 100%);
            border-color: #1f6fb8;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(31, 111, 184, 0.28);
        }

        .profile-icon-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.64);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }

        .info-icon-link {
            border: 1px solid rgba(255, 255, 255, 0.32);
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            transition: all .2s ease;
        }

        .info-icon-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.58);
            transform: translateY(-1px);
        }

        .info-icon-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.64);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }

        /* Μικρότερο κενό ανάμεσα στο λογότυπο και το menu. */
        .navbar-brand {
            position: relative;
            margin: 0 0 1rem;
            flex: 0 0 100%;
            justify-content: flex-start;
            gap: 1rem;
            text-align: left;
            width: 100%;
        }

        .navbar-brand::after {
            content: "";
            position: absolute;
            left: calc(50% - 50vw);
            bottom: -.45rem;
            width: 100vw;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #1a3a5c 0%, #2f6ea0 50%, #1a3a5c 100%);
            opacity: .65;
            pointer-events: none;
        }

        /* Το menu πιάνει όλο το διαθέσιμο πλάτος πιο ισορροπημένα. */
        .navbar-collapse {
            width: 100%;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: .05rem;
            padding: .95rem 1.15rem;
            border-top: 0;
            border-radius: 18px;
            background: var(--nav-row-bg);
            box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.28), 0 18px 34px rgba(15, 42, 76, 0.18);
        }

        .navbar-nav {
            flex: 1 1 auto;
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            margin: 0;
            gap: .25rem;
            min-width: 0;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        .navbar-public .navbar-nav {
            justify-content: center;
            margin: 0;
            gap: .35rem;
        }

        .navbar-parent .navbar-collapse {
            gap: .7rem;
        }

        .navbar-parent .navbar-nav {
            justify-content: flex-start;
            gap: .12rem;
            padding-right: .35rem;
        }

        .navbar-parent .navbar-nav .nav-item {
            margin: 0 .08rem;
        }

        .navbar-parent .navbar-nav .nav-link {
            padding: .46rem .72rem;
            font-size: .91rem;
        }

        .navbar-parent .navbar-nav .nav-link--icon-only {
            width: 2.55rem;
            height: 2.55rem;
            min-width: 2.55rem;
        }

        .navbar-public .navbar-nav .nav-item {
            margin: 0 .35rem;
        }

        .navbar-public .navbar-nav .nav-link {
            padding: .5rem 1.1rem;
        }

        .navbar-tools {
            flex: 0 0 auto;
            justify-content: flex-end;
            gap: .8rem;
            margin-left: 1rem;
            min-width: auto;
            padding: .2rem 0 .2rem .9rem;
            border-left: 1px solid rgba(255, 255, 255, 0.18);
        }

        .navbar-parent .navbar-tools {
            gap: .55rem;
            margin-left: .55rem;
            padding-left: .7rem;
        }

        .navbar .container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            max-width: 1560px;
            position: relative;
            z-index: 1;
        }

        .navbar-toggler {
            margin-left: auto;
        }

        .navbar-nav .nav-link {
            white-space: nowrap;
        }

        /* Βελτίωση προσβασιμότητας για πληκτρολόγιο (Tab). */
        .navbar-nav .nav-link:focus-visible,
        .login-btn:focus-visible,
        .utility-icon-link:focus-visible,
        .navbar-toggler:focus-visible {
            outline: 2px solid rgba(26, 58, 92, 0.45);
            outline-offset: 2px;
        }

        @media (max-width: 1199.98px) {
            .navbar-brand img {
                width: 88px;
                height: 88px;
            }

            .logo-line {
                margin-top: .35rem;
            }

            .brand-text {
                font-size: 1.7rem;
            }

            .navbar-public .navbar-nav {
                gap: .25rem;
            }

            .navbar-public .navbar-nav .nav-link,
            .navbar-nav .nav-link {
                padding: .45rem .65rem;
                font-size: .9rem;
            }
        }

        /* Ρυθμίσεις για κινητό/tablet. */
        @media (max-width: 991.98px) {
            .navbar {
                padding: .65rem 0;
            }

            .navbar-brand {
                margin: 0;
                flex: 0 1 auto;
                justify-content: flex-start;
                max-width: calc(100% - 78px);
                gap: .7rem;
            }

            .navbar-brand img {
                width: 58px;
                height: 58px;
            }

            .logo-line {
                margin-top: .3rem;
            }

            .brand-text {
                display: inline-block;
                max-width: calc(100vw - 150px);
                font-size: 1.22rem;
                line-height: 1.1;
                overflow: visible;
                text-overflow: clip;
                white-space: normal;
            }

            .brand-line {
                display: inline;
                width: auto;
            }

            .brand-line + .brand-line {
                margin-left: 0;
            }

            .navbar-collapse {
                margin-top: .7rem;
                padding: .85rem 1rem;
            }

            .navbar-nav {
                margin-top: .75rem;
                flex-wrap: wrap;
                justify-content: flex-start;
                gap: .1rem;
                overflow: visible;
            }

            .navbar-nav .nav-item {
                margin: .15rem 0;
            }

            .navbar-tools {
                margin-top: .75rem;
                margin-left: 0;
                padding-top: .5rem;
                padding-left: 0;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
                border-left: 0;
                gap: .5rem;
                min-width: 100%;
                justify-content: flex-start;
            }
        }
    </style>

<header class="site-header">
<!-- Κύριο navigation όλου του site. -->
<nav class="navbar navbar-expand-lg navbar-light<?php echo site_is_parent() ? ' navbar-parent' : ' navbar-public'; ?>">
    <div class="container">

        <!-- Λογότυπο + τίτλος σχολείου. -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo site_section_url('home.php'); ?>">
            <span class="logo-stack mr-2">
                <img src="<?php echo site_asset_url('img/logo-icon.png'); ?>" alt="Logo">
            </span>
            <span class="brand-text" aria-label="<?php echo htmlspecialchars($site_title); ?>">
                <span class="brand-line">Σύνδεσμος Γονέων &amp;</span>
                <span class="brand-line">Κηδεμόνων Γυμνασίου</span>
                <span class="brand-line">Αγίου Αθανασίου</span>
            </span>
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
                            <i class="<?php echo $item['icon']; ?>" aria-hidden="true"></i>
                            <?php if (empty($item['icon_only'])): ?>
                                <?php echo $item['label']; ?>
                            <?php else: ?>
                                <span class="sr-only"><?php echo htmlspecialchars($item['label']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Δεξιά εργαλεία: login + shortcut. -->
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center navbar-tools">

                <?php if (site_is_parent()): ?>
                    <a href="<?php echo site_public_url('logout.php'); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-lg-0 login-btn logout-btn">
                        <i class="fas fa-sign-out-alt mr-1"></i> Log out
                    </a>
                <?php else: ?>
                    <a href="<?php echo site_login_url(); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-lg-0 login-btn login-btn-green">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                <?php endif; ?>

                <?php if (site_is_parent()): ?>
                    <?php $is_profile_active = in_array($current_page, $profile_item['match'], true); ?>
                    <a href="<?php echo $profile_item['href']; ?>"
                       class="mt-2 mt-lg-0 utility-icon-link profile-icon-link<?php echo $is_profile_active ? ' active' : ''; ?>"
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

<script>
    // Fallback μόνο όταν τελειώσει το φόρτωμα και δεν υπάρχει καθόλου Bootstrap collapse.
    (function () {
        function hasBootstrapCollapse() {
            return window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.collapse === 'function';
        }

        function bindFallbackNavbarToggle() {
            if (hasBootstrapCollapse()) return;

            // Βρίσκουμε τα στοιχεία που χρειάζονται για το fallback.
            var toggler = document.querySelector('[data-target="#mainNavbar"]');
            var menu = document.getElementById('mainNavbar');
            if (!toggler || !menu || toggler.dataset.fallbackBound === 'true') return;

            toggler.dataset.fallbackBound = 'true';

            // Εναλλαγή open/close όταν πατάμε το hamburger.
            toggler.addEventListener('click', function (event) {
                // Αν φορτώθηκε στο μεταξύ Bootstrap, αφήνουμε εκείνο να χειριστεί το toggle.
                if (hasBootstrapCollapse()) return;

                event.preventDefault();

                var isOpen = menu.classList.contains('show');
                menu.classList.toggle('show', !isOpen);
                toggler.classList.toggle('collapsed', isOpen);
                // Ενημέρωση του aria-expanded για accessibility.
                toggler.setAttribute('aria-expanded', String(!isOpen));
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindFallbackNavbarToggle);
            return;
        }

        bindFallbackNavbarToggle();
    })();
</script>
