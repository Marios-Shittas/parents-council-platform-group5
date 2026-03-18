<?php
require_once __DIR__ . '/site_context.php';

$site_title = 'Γυμνάσιο Αγίου Αθανασίου';
$current_page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$search_query = trim($_GET['q'] ?? '');
$portal_label = site_is_parent() ? 'Χώρος Γονέα' : 'Δημόσια Πύλη';

$useful_info_item = [
    'label' => 'Χρήσιμες Πληροφορίες',
    'href' => site_section_url('useful-information.php'),
    'icon' => 'fas fa-info-circle',
    'match' => ['useful-information.php'],
];

$nav_items = [
    [
        'label' => 'Αρχική',
        'href' => site_section_url('home.php'),
        'icon' => 'fas fa-home',
        'match' => ['home.php', 'index.php', ''],
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
];

if (site_is_parent()) {
    $nav_items[] = [
        'label' => 'Αιτήσεις',
        'href' => site_section_url('applications.php'),
        'icon' => 'fas fa-file-alt',
        'match' => ['applications.php'],
    ];
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
            padding: .5rem 0;
            font-family: 'Lato', sans-serif;
        }

        /* Διακριτική μπλε γραμμή πάνω για πιο premium εμφάνιση. */
        .navbar::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #1a3a5c 0%, #2f6ea0 50%, #1a3a5c 100%);
            opacity: .65;
        }

        /* Στυλ λογοτύπου. */
        .navbar-brand img {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 10px rgba(26, 58, 92, 0.22);
        }

        /* Κείμενο δίπλα στο λογότυπο. */
        .brand-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--brand-color);
            font-size: 1.12rem;
            letter-spacing: .1px;
            white-space: nowrap;
        }

        /* Λίγο κενό ανάμεσα στα menu items. */
        .navbar-nav .nav-item {
            margin: 0 .06rem;
        }

        /* Βασικό στυλ links menu. */
        .navbar-nav .nav-link {
            font-family: 'Lato', sans-serif;
            font-weight: 600;
            color: var(--text-main) !important;
            padding: .48rem .7rem;
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
        }

        /* Hover κατάσταση για πιο καθαρό feedback στον χρήστη. */
        .navbar-nav .nav-link:hover {
            background: rgba(230, 237, 245, 0.55);
            color: var(--text-strong) !important;
            /* Μικρή κίνηση για πιο "ζωντανό" αποτέλεσμα. */
            transform: translateY(-1px);
        }

        /* Όταν η σελίδα είναι ενεργή, φαίνεται καθαρά. */
        .navbar-nav .active > .nav-link {
            background: transparent;
            color: #0f2134 !important;
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
            background: rgba(26, 58, 92, 0.55);
        }

        /* Πεδίο αναζήτησης. */
        .search-input {
            font-family: 'Lato', sans-serif;
            /* Περιορίζει το search για να μη τρώει όλο το πλάτος. */
            min-width: 180px;
            max-width: 320px;
            border-radius: 999px 0 0 999px;
            border-color: #d6dce4;
        }

        /* Όταν ο χρήστης γράφει στο search, το πεδίο φωτίζεται. */
        .search-input:focus {
            border-color: var(--brand-color);
            box-shadow: 0 0 0 .2rem rgba(26, 58, 92, 0.12);
        }

        /* Κουμπί search. */
        .search-btn {
            font-family: 'Lato', sans-serif;
            /* Ίδιο pill style με το input για ενιαίο κουμπί. */
            border-radius: 0 999px 999px 0;
            border-color: #d6dce4;
        }

        /* Κουμπί login. */
        .login-btn {
            font-family: 'Lato', sans-serif;
            border-radius: 999px;
            font-weight: 600;
            padding: .3rem .9rem;
            border-width: 2px;
            font-size: .9rem;
        }

        .login-btn:visited,
        .login-btn:focus,
        .login-btn:active {
            color: var(--text-main) !important;
            font-weight: 600;
        }

        .login-btn:hover {
            color: var(--text-strong) !important;
        }

        .logout-btn {
            border-color: #dc3545;
            color: #dc3545 !important;
        }

        .logout-btn:hover,
        .logout-btn:focus,
        .logout-btn:active {
            background: #dc3545;
            border-color: #dc3545;
            color: #ffffff !important;
        }

        .login-btn-green {
            border-color: #28a745;
            color: #28a745 !important;
        }

        .login-btn-green:hover,
        .login-btn-green:focus,
        .login-btn-green:active {
            background: #28a745;
            border-color: #28a745;
            color: #ffffff !important;
        }

        .info-icon-link {
            font-family: 'Lato', sans-serif;
            width: 38px;
            height: 38px;
            padding: 0;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #b9e3c8;
            color: #5cab78;
            background: #effaf3;
            transition: all .2s ease;
        }

        .info-icon-link:hover {
            color: #3f9660;
            background: #e0f5e8;
            border-color: #9ed4b1;
            transform: translateY(-1px);
        }

        .info-icon-link.active {
            color: #2f7e4f;
            background: #d5f0df;
            border-color: #8bc7a0;
            box-shadow: inset 0 0 0 1px rgba(76, 153, 106, 0.08);
        }

        /* Μικρότερο κενό ανάμεσα στο λογότυπο και το menu. */
        .navbar-brand {
            margin-right: .7rem;
        }

        /* Το menu πιάνει όλο το διαθέσιμο πλάτος πιο ισορροπημένα. */
        .navbar-collapse {
            /* Χωρίζει menu και δεξιά εργαλεία σε 2 καθαρές ζώνες. */
            justify-content: space-between;
        }

        .navbar-nav {
            /* Τα κουμπιά απλώνονται ομοιόμορφα στον χώρο. */
            flex: 1 1 auto;
            justify-content: space-evenly;
            margin: 0 1rem;
        }

        .navbar-tools {
            /* Search + Login μένουν δεξιά. */
            margin-left: auto;
            min-width: 360px;
            justify-content: flex-end;
        }

        .navbar .container {
            /* Μεγαλύτερο max-width για να γεμίζει καλύτερα η μπάρα. */
            max-width: 1500px;
        }

        /* Βελτίωση προσβασιμότητας για πληκτρολόγιο (Tab). */
        .navbar-nav .nav-link:focus-visible,
        .search-btn:focus-visible,
        .login-btn:focus-visible,
        .info-icon-link:focus-visible,
        .navbar-toggler:focus-visible,
        .search-input:focus-visible {
            outline: 2px solid rgba(26, 58, 92, 0.45);
            outline-offset: 2px;
        }

        /* Ρυθμίσεις για κινητό/tablet. */
        @media (max-width: 991.98px) {
            .navbar-nav {
                margin-top: .75rem;
                /* Σε κινητό πάμε στο κλασικό στοίχισμα αριστερά. */
                justify-content: flex-start;
            }

            .navbar-nav .nav-item {
                margin: .15rem 0;
            }

            .navbar-tools {
                margin-top: .75rem;
                padding-top: .5rem;
                border-top: 1px solid #e7ebf0;
                gap: .5rem;
                min-width: 100%;
            }

            .search-input {
                /* Σε κινητό το search γίνεται full width. */
                min-width: 100%;
                max-width: none;
            }
        }
    </style>
<header class="site-header">
<!-- Κύριο navigation όλου του site. -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">

        <!-- Λογότυπο + τίτλος σχολείου. -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo site_section_url('home.php'); ?>">
            <img src="<?php echo site_asset_url('img/logo-icon.png'); ?>" alt="Logo" class="mr-2">
            <span class="brand-text d-none d-md-inline"><?php echo $site_title; ?></span>
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
                        <a class="nav-link" href="<?php echo $item['href']; ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                            <i class="<?php echo $item['icon']; ?> mr-1"></i><?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Δεξιά εργαλεία: αναζήτηση + login. -->
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center navbar-tools">

                <!-- Form αναζήτησης (με GET για να φαίνεται το q στο URL). -->
                <form class="my-2 my-lg-0 mr-lg-2" action="<?php echo site_public_url('search.php'); ?>" method="get">
                    <div class="input-group input-group-sm">
                        <!-- Φιλτράρουμε τιμή με htmlspecialchars για ασφάλεια. -->
                        <input type="search" name="q" class="form-control search-input"
                               placeholder="Αναζήτηση..." aria-label="Search"
                               value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary search-btn" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <?php if (site_is_parent()): ?>
                    <a href="<?php echo site_public_url('home.php'); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-lg-0 login-btn logout-btn">
                        <i class="fas fa-sign-out-alt mr-1"></i>Log out
                    </a>
                <?php else: ?>
                    <a href="<?php echo site_login_url(); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-lg-0 login-btn login-btn-green">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login
                    </a>
                <?php endif; ?>

                <?php $is_useful_info_active = in_array($current_page, $useful_info_item['match'], true); ?>
                <a href="<?php echo $useful_info_item['href']; ?>"
                   class="ml-lg-2 mt-2 mt-lg-0 info-icon-link<?php echo $is_useful_info_active ? ' active' : ''; ?>"
                   aria-label="<?php echo htmlspecialchars($useful_info_item['label']); ?>"
                   title="<?php echo htmlspecialchars($useful_info_item['label']); ?>"
                   <?php echo $is_useful_info_active ? 'aria-current="page"' : ''; ?>>
                    <i class="<?php echo $useful_info_item['icon']; ?>"></i>
                </a>

            </div>
        </div>

    </div>
</nav>
</header>

<script>
    // Απλό fallback: αν δεν υπάρχει Bootstrap JS, ανοίγει/κλείνει το mobile menu.
    (function () {
        // Ελέγχουμε αν υπάρχει έτοιμο bootstrap collapse.
        var hasBootstrapCollapse = window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.collapse === 'function';
        if (hasBootstrapCollapse) return;

        // Βρίσκουμε τα στοιχεία που χρειάζονται για το fallback.
        var toggler = document.querySelector('[data-target="#mainNavbar"]');
        var menu = document.getElementById('mainNavbar');
        if (!toggler || !menu) return;

        // Εναλλαγή open/close όταν πατάμε το hamburger.
        toggler.addEventListener('click', function () {
            var isOpen = menu.classList.contains('show');
            menu.classList.toggle('show', !isOpen);
            // Ενημέρωση του aria-expanded για accessibility.
            toggler.setAttribute('aria-expanded', String(!isOpen));
        });
    })();
</script>
