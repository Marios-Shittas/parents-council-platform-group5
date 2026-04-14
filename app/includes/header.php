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

$parents_utility_item = [
    'label' => 'Σύνδεσμος Γονέων',
    'href' => site_section_url('parents.php'),
    'icon' => 'fas fa-users',
    'match' => ['parents.php'],
];

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
        'label' => 'Χρήσιμοι Σύνδεσμοι & Πληροφορίες',
        'href' => site_section_url('useful-information.php'),
        'icon' => 'fas fa-info-circle',
        'match' => ['useful-information.php'],
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
            padding: .65rem 0;
            font-family: 'Lato', sans-serif;
        }

        /* Διακριτική μπλε γραμμή κάτω από το header. */
        .navbar::after {
            content: "";
            position: absolute;
            bottom: 0;
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
            display: inline-block;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--brand-color);
            font-size: .94rem;
            line-height: 1.15;
            letter-spacing: 0;
            white-space: normal;
            max-width: 230px;
        }

        .brand-line {
            display: block;
        }

        /* Λίγο κενό ανάμεσα στα menu items. */
        .navbar-nav .nav-item {
            margin: 0 .18rem;
        }

        /* Βασικό στυλ links menu. */
        .navbar-nav .nav-link {
            font-family: 'Lato', sans-serif;
            font-weight: 600;
            color: var(--text-main) !important;
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

        .site-header .login-btn:visited,
        .site-header .login-btn:focus,
        .site-header .login-btn:active {
            color: var(--text-main) !important;
            font-weight: 600 !important;
        }

        .site-header .login-btn:hover {
            color: var(--text-strong) !important;
        }

        .logout-btn {
            border-color: #dc3545;
            color: #dc3545 !important;
            font-weight: 600 !important;
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
            border: 1px solid #c4d8ec;
            color: #3e6d97;
            background: #edf4fb;
        }

        .profile-icon-link:hover {
            color: #234e76;
            background: #e2eef9;
            border-color: #a9c7e3;
            transform: translateY(-1px);
        }

        .profile-icon-link.active {
            color: #173f66;
            background: #dbe9f7;
            border-color: #98bbda;
            box-shadow: inset 0 0 0 1px rgba(49, 101, 146, 0.08);
        }

        .info-icon-link {
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
            flex: 0 0 auto;
        }

        /* Το menu πιάνει όλο το διαθέσιμο πλάτος πιο ισορροπημένα. */
        .navbar-collapse {
            /* Χωρίζει menu και δεξιά εργαλεία σε 2 καθαρές ζώνες. */
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
        }

        .navbar-nav {
            /* Το menu κάθεται πιο κεντραρισμένα αφού δεν υπάρχει search. */
            flex: 1 1 auto;
            justify-content: center;
            margin: 0 .9rem;
            gap: .25rem;
        }

        .navbar-public .navbar-nav {
            justify-content: space-evenly;
            margin: 0 1rem;
            gap: .35rem;
        }

        .navbar-public .navbar-nav .nav-item {
            margin: 0 .35rem;
        }

        .navbar-public .navbar-nav .nav-link {
            padding: .5rem 1.1rem;
        }

        .navbar-tools {
            /* Τα utility actions μένουν compact δεξιά. */
            flex: 0 0 auto;
            justify-content: flex-end;
            gap: .8rem;
            margin-left: 0;
            min-width: auto;
            padding: .2rem 0 .2rem .9rem;
            border-left: 1px solid rgba(26, 58, 92, 0.08);
        }

        .navbar .container {
            /* Μεγαλύτερο max-width για να γεμίζει καλύτερα η μπάρα. */
            max-width: 1560px;
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
                width: 68px;
                height: 68px;
            }

            .brand-text {
                max-width: 210px;
                font-size: .86rem;
            }

            .navbar-public .navbar-nav {
                margin: 0 .55rem;
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
            .navbar-brand {
                max-width: calc(100% - 78px);
            }

            .navbar-brand img {
                width: 58px;
                height: 58px;
            }

            .brand-text {
                display: inline-block;
                max-width: calc(100vw - 150px);
                font-size: .78rem;
                line-height: 1.18;
                overflow: visible;
                text-overflow: clip;
                white-space: normal;
            }

            .navbar-nav {
                margin-top: .75rem;
                /* Σε κινητό πάμε στο κλασικό στοίχισμα αριστερά. */
                justify-content: flex-start;
                gap: .1rem;
            }

            .navbar-nav .nav-item {
                margin: .15rem 0;
            }

            .navbar-tools {
                margin-top: .75rem;
                padding-top: .5rem;
                padding-left: 0;
                border-top: 1px solid #e7ebf0;
                border-left: 0;
                gap: .5rem;
                min-width: 100%;
                justify-content: flex-start;
            }
        }
    </style>

<?php if ($isProtectedPage): ?>
<script>
    // Aggressive back button prevention + continuous session validation
    (function() {
        const loginUrl = '/parents-council-platform-group5/public/login.php';
        const adminHomeUrl = '/parents-council-platform-group5/public/admin/home.php';
        const requiredRole = <?php echo json_encode(site_is_parent() ? 'parent' : null); ?>;
        
        // Clear any stored history data
        try {
            sessionStorage.setItem('lastProtectedPage', window.location.href);
        } catch(e) {}
        
        // Function to validate session
        function validateSession() {
            fetch('/parents-council-platform-group5/app/services/SessionCheck.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.isLoggedIn) {
                        window.location.href = loginUrl;
                        return;
                    }

                    if (requiredRole && data.role !== requiredRole) {
                        window.location.href = data.role === 'admin' ? adminHomeUrl : loginUrl;
                    }
                })
                .catch(() => {
                    // If check fails, assume logout for safety
                    window.location.href = loginUrl;
                });
        }
        
        // Validate session periodically
        setInterval(validateSession, 5000); // Every 5 seconds
        
        // Also validate on visibility change (tab focus)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                validateSession();
            }
        });
        
        // Immediately replace the current state
        window.history.replaceState(null, document.title, window.location.href);
        
        // Push many forward states to bury history
        for (let i = 0; i < 50; i++) {
            window.history.pushState({state: i}, document.title, window.location.href);
        }
        
        // Function to prevent back navigation
        function preventBack() {
            validateSession();
            // Always push forward when back is attempted
            window.history.pushState(null, document.title, window.location.href);
            // Redirect to login
            window.location.href = loginUrl;
        }
        
        // Listen for back button
        window.addEventListener('popstate', preventBack);
        
        // Prevent keyboard shortcuts
        function handleKeydown(e) {
            // Alt+Left Arrow
            if (e.altKey && e.key === 'ArrowLeft') {
                e.preventDefault();
                window.location.href = loginUrl;
                return false;
            }
            // Ctrl+Left Arrow (backward browser button)
            if (e.ctrlKey && e.key === 'ArrowLeft') {
                e.preventDefault();
                window.location.href = loginUrl;
                return false;
            }
            // Backspace key (if not in input field)
            if (e.key === 'Backspace' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                window.location.href = loginUrl;
                return false;
            }
        }
        document.addEventListener('keydown', handleKeydown);
        
        // Block back when page is unloaded/reloaded by trying to navigate back
        window.addEventListener('beforeunload', function(e) {
            sessionStorage.setItem('lastProtectedPage', window.location.href);
        });
        
        // Validate on page load
        validateSession();
    })();
</script>
<?php endif; ?>

<header class="site-header">
<!-- Κύριο navigation όλου του site. -->
<nav class="navbar navbar-expand-lg navbar-light<?php echo site_is_parent() ? ' navbar-parent' : ' navbar-public'; ?>">
    <div class="container">

        <!-- Λογότυπο + τίτλος σχολείου. -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo site_section_url('home.php'); ?>">
            <img src="<?php echo site_asset_url('img/logo-icon.png'); ?>" alt="Logo" class="mr-2">
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
                        <a class="nav-link" href="<?php echo $item['href']; ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                            <i class="<?php echo $item['icon']; ?> mr-1"></i><?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Δεξιά εργαλεία: login + shortcut. -->
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center navbar-tools">

                <?php if (site_is_parent()): ?>
                    <a href="<?php echo site_public_url('logout.php'); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-lg-0 login-btn logout-btn">
                        <i class="fas fa-sign-out-alt mr-1"></i>Log out
                    </a>
                <?php else: ?>
                    <a href="<?php echo site_login_url(); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-lg-0 login-btn login-btn-green">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login
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

                <?php $is_parents_utility_active = in_array($current_page, $parents_utility_item['match'], true); ?>
                <a href="<?php echo $parents_utility_item['href']; ?>"
                   class="mt-2 mt-lg-0 utility-icon-link info-icon-link<?php echo $is_parents_utility_active ? ' active' : ''; ?>"
                   aria-label="<?php echo htmlspecialchars($parents_utility_item['label']); ?>"
                   title="<?php echo htmlspecialchars($parents_utility_item['label']); ?>"
                   <?php echo $is_parents_utility_active ? 'aria-current="page"' : ''; ?>>
                    <i class="<?php echo $parents_utility_item['icon']; ?>"></i>
                </a>

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
