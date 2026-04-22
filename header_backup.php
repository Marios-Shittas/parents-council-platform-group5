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

$site_title = '╬ú╧ì╬╜╬┤╬╡╧â╬╝╬┐╧é ╬ô╬┐╬╜╬¡╧ë╬╜ & ╬Ü╬╖╬┤╬╡╬╝╧î╬╜╧ë╬╜';
$current_page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$portal_label = site_is_parent() ? '╬º╧Ä╧ü╬┐╧é ╬ô╬┐╬╜╬¡╬▒' : '╬ö╬╖╬╝╧î╧â╬╣╬▒ ╬á╧ì╬╗╬╖';

$profile_item = [
    'label' => '╬ñ╬┐ ╬á╧ü╬┐╧å╬»╬╗ ╬£╬┐╧à',
    'href' => site_section_url('profile.php'),
    'icon' => 'fas fa-user-circle',
    'match' => ['profile.php'],
];

$nav_items = [
    [
        'label' => '╬æ╧ü╧ç╬╣╬║╬«',
        'href' => site_section_url('home.php'),
        'icon' => 'fas fa-home',
        'match' => ['home.php', 'index.php', ''],
    ],
    [
        'label' => '╬ú╧ì╬╜╬┤╬╡╧â╬╝╬┐╧é ╬ô╬┐╬╜╬¡╧ë╬╜',
        'href' => site_section_url('parents.php'),
        'icon' => 'fas fa-users',
        'match' => ['parents.php'],
    ],
    [
        'label' => '╬æ╬╜╬▒╬║╬┐╬╣╬╜╧Ä╧â╬╡╬╣╧é',
        'href' => site_section_url('announcements.php'),
        'icon' => 'fas fa-bullhorn',
        'match' => ['announcements.php'],
    ],
    [
        'label' => '╬ò╬║╬┤╬╖╬╗╧Ä╧â╬╡╬╣╧é',
        'href' => site_section_url('events.php'),
        'icon' => 'fas fa-calendar-alt',
        'match' => ['events.php', 'event.php'],
    ],
    [
        'label' => '╬º╧ü╬«╧â╬╣╬╝╬╡╧é ╬á╬╗╬╖╧ü╬┐╧å╬┐╧ü╬»╬╡╧é',
        'href' => site_section_url('useful-information.php'),
        'icon' => 'fas fa-info-circle',
        'match' => ['useful-information.php'],
    ],
    [
        'label' => '╬æ╬╣╧ä╬«╧â╬╡╬╣╧é',
        'href' => site_section_url('applications.php'),
        'icon' => 'fas fa-file-alt',
        'match' => ['applications.php'],
    ],
];

if (site_is_parent()) {
    $nav_items[] = [
        'label' => '╬Ü╬▒╧ä╬¼╧â╧ä╬╖╬╝╬▒',
        'href' => site_section_url('eshop.php'),
        'icon' => 'fas fa-store',
        'match' => ['eshop.php'],
    ];
}

$nav_items[] = [
    'label' => '╬ò╧Ç╬╣╬║╬┐╬╣╬╜╧ë╬╜╬»╬▒',
    'href' => site_section_url('epikoinonia.php'),
    'icon' => 'fas fa-envelope',
    'match' => ['epikoinonia.php'],
];

if (site_is_parent()) {
    $nav_items[] = [
        'label' => '╬ª╧ë╧ä╬┐╬│╧ü╬▒╧å╬»╬╡╧é',
        'href' => site_section_url('photos.php'),
        'icon' => 'fas fa-camera',
        'match' => ['photos.php'],
        'icon_only' => true,
    ];
}
?>
<script>
    (function () {
        var faviconHref = '<?php echo site_asset_url('img/logo-icon.png'); ?>';
        var head = document.head || document.getElementsByTagName('head')[0];
        if (!head) return;

        var existingIcons = head.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
        existingIcons.forEach(function (icon) {
            icon.parentNode.removeChild(icon);
        });

        var icon = document.createElement('link');
        icon.rel = 'icon';
        icon.type = 'image/png';
        icon.href = faviconHref;
        head.appendChild(icon);
    })();
</script>
<style>
        /* ╬Æ╬▒╧â╬╣╬║╬¼ ╧ç╧ü╧Ä╬╝╬▒╧ä╬▒ ╬│╬╣╬▒ ╬╡╬╜╬╣╬▒╬»╬┐ design. */
        :root {
            /* ╬º╧ü╧Ä╬╝╬▒ ╧å╧î╬╜╧ä╬┐╧à ╧ä╬┐╧à header. */
            --header-bg: rgba(248, 249, 250, 0.95);
            /* ╬¢╬╡╧Ç╧ä╬« ╬│╧ü╬▒╬╝╬╝╬«/╧Ç╬╡╧ü╬»╬│╧ü╬▒╬╝╬╝╬▒ ╧ä╬┐╧à header. */
            --header-border: rgba(0, 0, 0, 0.06);
            /* ╬Ü╧ì╧ü╬╣╬┐ ╬╝╧Ç╬╗╬╡ ╧ç╧ü╧Ä╬╝╬▒ brand. */
            --brand-color: #1a3a5c;
            /* ╬Æ╬▒╧â╬╣╬║╧î ╧ç╧ü╧Ä╬╝╬▒ ╬║╬╡╬╣╬╝╬¡╬╜╬┐╧à. */
            --text-main: #3f4a56;
            /* ╬á╬╣╬┐ ╬¡╬╜╧ä╬┐╬╜╬┐ ╧ç╧ü╧Ä╬╝╬▒ ╬│╬╣╬▒ hover/active. */
            --text-strong: #152536;
            /* ╬æ╧Ç╬▒╬╗╧î hover ╧å╧î╬╜╧ä╬┐ ╧â╧ä╬▒ links. */
            --link-hover-bg: #eef3f8;
            /* ╬æ╧Ç╬▒╬╗╧î active ╧å╧î╬╜╧ä╬┐ (╬║╧ü╬▒╧ä╬«╬╕╬╖╬║╬╡ ╬│╬╣╬▒ ╧Ç╬╣╬╕╬▒╬╜╬« ╧ç╧ü╬«╧â╬╖). */
            --link-active-bg: #e6edf5;
            /* ╬£╧Ç╬╗╬╡ gradient ╬»╬┤╬╣╬┐ ╬╝╬╡ ╧ä╬┐ hero panel. */
            --nav-row-bg: linear-gradient(135deg, #102f52 0%, #1a3a5c 55%, #0057a8 100%);
        }

        /* Scoped ╬▓╬¼╧â╬╖ ╬│╬╣╬▒ ╬╜╬▒ ╬╝╬╖ ╬▓╬▒╧â╬»╬╢╬╡╧ä╬▒╬╣ ╧ä╬┐ header ╧â╬╡ global ╬║╬▒╬╜╧î╬╜╬╡╧é ╧ä╬╖╧é main.css. */
        .site-header,
        .site-header * {
            box-sizing: border-box;
        }

        .site-header {
            font-family: 'Lato', sans-serif;
            color: var(--text-main);
            max-width: 100%;
            overflow-x: clip;
        }

        .site-header a,
        .site-header button,
        .site-header input {
            font-family: 'Lato', sans-serif;
        }

        /* ╬Ü╬┐╬╗╬╗╬¼╬╡╬╣ ╧Ç╬¼╬╜╧ë ╧î╧ä╬▒╬╜ ╬║╬¼╬╜╬┐╧à╬╝╬╡ scroll ╬║╬▒╬╣ ╬╝╬¡╬╜╬╡╬╣ ╧Ç╬¼╬╜╧ä╬▒ ╬┐╧ü╬▒╧ä╧î. */
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
            overflow-x: clip;
            overflow-y: visible;
        }

        /* ╬è╬┤╬╣╬▒ ╬┤╬╣╬▒╬║╧ü╬╣╧ä╬╣╬║╬« ╬╝╧Ç╬╗╬╡ ╬│╧ü╬▒╬╝╬╝╬« ╬║╬▒╬╣ ╬║╬¼╧ä╧ë ╬▒╧Ç╧î ╧ä╬┐ navbar. */
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

        /* ╬ú╧ä╧à╬╗ ╬╗╬┐╬│╬┐╧ä╧ì╧Ç╬┐╧à. */
        .logo-stack {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            flex: 0 0 auto;
            width: fit-content;
            min-width: 0;
        }

        .navbar-brand .logo-stack {
            margin-right: 0 !important;
        }

        .navbar-brand img {
            width: 145px;
            height: 145px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(26, 58, 92, 0.18);
        }

        /* ╬Ü╬╡╬»╬╝╬╡╬╜╬┐ ╬┤╬»╧Ç╬╗╬▒ ╧â╧ä╬┐ ╬╗╬┐╬│╧î╧ä╧à╧Ç╬┐. */
        .brand-text {
            display: block;
            flex: 0 1 auto;
            min-width: 0;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--brand-color);
            font-size: 2.2rem;
            line-height: 1.02;
            letter-spacing: 0.01em;
            white-space: normal;
            overflow-wrap: normal;
            word-break: normal;
            max-width: calc(100% - 170px);
            width: auto;
        }

        .brand-line {
            display: inline;
            width: auto;
        }

        .brand-line + .brand-line {
            margin-left: .35rem;
        }

        /* ╬¢╬»╬│╬┐ ╬║╬╡╬╜╧î ╬▒╬╜╬¼╬╝╬╡╧â╬▒ ╧â╧ä╬▒ menu items. */
        .navbar-nav .nav-item {
            margin: 0 .18rem;
            flex: 0 0 auto;
        }

        /* ╬Æ╬▒╧â╬╣╬║╧î ╧â╧ä╧à╬╗ links menu. */
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

        /* ╬è╬┤╬╣╬┐ ╧Ç╬╗╬¼╧ä╬┐╧é ╧â╧ä╬┐ icon ╧Ä╧â╧ä╬╡ ╬╜╬▒ ╧å╬▒╬»╬╜╬┐╬╜╧ä╬▒╬╣ ╧î╬╗╬▒ ╬╡╧à╬╕╧à╬│╧ü╬▒╬╝╬╝╬╣╧â╬╝╬¡╬╜╬▒. */
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

        /* Hover ╬║╬▒╧ä╬¼╧â╧ä╬▒╧â╬╖ ╬│╬╣╬▒ ╧Ç╬╣╬┐ ╬║╬▒╬╕╬▒╧ü╧î feedback ╧â╧ä╬┐╬╜ ╧ç╧ü╬«╧â╧ä╬╖. */
        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff !important;
            /* ╬£╬╣╬║╧ü╬« ╬║╬»╬╜╬╖╧â╬╖ ╬│╬╣╬▒ ╧Ç╬╣╬┐ "╬╢╧ë╬╜╧ä╬▒╬╜╧î" ╬▒╧Ç╬┐╧ä╬¡╬╗╬╡╧â╬╝╬▒. */
            transform: translateY(-1px);
        }

        /* ╬î╧ä╬▒╬╜ ╬╖ ╧â╬╡╬╗╬»╬┤╬▒ ╬╡╬»╬╜╬▒╬╣ ╬╡╬╜╬╡╧ü╬│╬«, ╧å╬▒╬»╬╜╬╡╧ä╬▒╬╣ ╬║╬▒╬╕╬▒╧ü╬¼. */
        .navbar-nav .active > .nav-link {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff !important;
            font-weight: 700;
        }

        /* ╬£╬╣╬║╧ü╬« ╬╝╧Ç╬╗╬╡ ╬╝╧Ç╬¼╧ü╬▒ ╬║╬¼╧ä╧ë ╬▒╧Ç╧î ╧ä╬┐ ╬╡╬╜╬╡╧ü╬│╧î link. */
        .navbar-nav .nav-link-content {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .navbar-nav .active > .nav-link .nav-link-content::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -.35rem;
            height: 2px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.82);
        }

        /* ╬Ü╬┐╧à╬╝╧Ç╬» login. */
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

        /* ╬£╬╣╬║╧ü╧î╧ä╬╡╧ü╬┐ ╬║╬╡╬╜╧î ╬▒╬╜╬¼╬╝╬╡╧â╬▒ ╧â╧ä╬┐ ╬╗╬┐╬│╧î╧ä╧à╧Ç╬┐ ╬║╬▒╬╣ ╧ä╬┐ menu. */
        .navbar-brand {
            position: relative;
            margin: 0 0 1rem;
            flex: 0 0 100%;
            justify-content: center;
            flex-wrap: nowrap;
            gap: 1rem;
            text-align: center;
            width: 100%;
            min-width: 0;
            max-width: 100%;
        }

        .navbar-brand::after {
            content: "";
            position: absolute;
            left: 50%;
            right: 50%;
            bottom: -.45rem;
            width: 100vw;
            margin-left: -50vw;
            margin-right: -50vw;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #1a3a5c 0%, #2f6ea0 50%, #1a3a5c 100%);
            opacity: .65;
            pointer-events: none;
            margin-top: 18px; /* ╧Ç╧ü╬┐╧â╬╕╬¡╧ä╬╡╬╣ ╬╡╬╝╧å╬▒╬╜╬¡╧é ╬║╬╡╬╜╧î ╧Ç╬¼╬╜╧ë ╬▒╧Ç╧î ╧ä╬╖ ╬│╧ü╬▒╬╝╬╝╬« */
        }

        /* ╬ñ╬┐ menu ╧Ç╬╣╬¼╬╜╬╡╬╣ ╧î╬╗╬┐ ╧ä╬┐ ╬┤╬╣╬▒╬╕╬¡╧â╬╣╬╝╬┐ ╧Ç╬╗╬¼╧ä╬┐╧é ╧Ç╬╣╬┐ ╬╣╧â╬┐╧ü╧ü╬┐╧Ç╬╖╬╝╬¡╬╜╬▒. */
        .navbar-collapse {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            justify-content: safe center;
            align-items: center;
            gap: .7rem;
            margin-top: .05rem;
            padding: .85rem .9rem;
            border-top: 0;
            border-radius: 18px !important;
            background: var(--nav-row-bg);
            box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.28), 0 18px 34px rgba(15, 42, 76, 0.18);
            flex-wrap: nowrap !important;
            overflow-x: auto;
            overflow-y: visible;
            box-sizing: border-box;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }

        .navbar-nav {
            flex: 0 0 auto;
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            margin: 0;
            gap: clamp(.55rem, 1vw, 1.2rem);
            min-width: max-content;
            max-width: none;
            overflow: visible;
            box-sizing: border-box;
        }

        .navbar-public .navbar-nav {
            justify-content: center;
            margin: 0;
            gap: clamp(.7rem, 1.4vw, 1.6rem);
        }

        .navbar-parent .navbar-collapse {
            gap: .45rem;
        }

        .navbar-parent .navbar-nav {
            justify-content: center;
            gap: clamp(.5rem, .9vw, 1.05rem);
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
            flex-wrap: nowrap;
            justify-content: flex-end;
            gap: .8rem;
            margin-left: 1rem;
            min-width: auto;
            padding: .2rem 0 .2rem .9rem;
            border-left: 1px solid rgba(255, 255, 255, 0.18);
            white-space: nowrap;
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
            max-width: 100%;
            width: 100%;
            overflow: visible;
            position: relative;
            z-index: 1;
        }

        .navbar-toggler {
            margin-left: auto;
        }

        .navbar-nav .nav-link {
            white-space: nowrap;
        }

        .site-header .login-btn {
            white-space: nowrap;
        }

        /* ╬Æ╬╡╬╗╧ä╬»╧ë╧â╬╖ ╧Ç╧ü╬┐╧â╬▓╬▒╧â╬╣╬╝╧î╧ä╬╖╧ä╬▒╧é ╬│╬╣╬▒ ╧Ç╬╗╬╖╬║╧ä╧ü╬┐╬╗╧î╬│╬╣╬┐ (Tab). */
        .navbar-nav .nav-link:focus-visible,
        .login-btn:focus-visible,
        .utility-icon-link:focus-visible,
        .navbar-toggler:focus-visible {
            outline: 2px solid rgba(26, 58, 92, 0.45);
            outline-offset: 2px;
        }

        @media (min-width: 1200px) and (max-width: 1799.98px) {
            .navbar .container {
                max-width: 100%;
                padding-left: .65rem;
                padding-right: .65rem;
            }

            .navbar-brand {
                gap: .8rem;
            }

            .navbar-brand img {
                width: 116px;
                height: 116px;
            }

            .brand-text {
                font-size: 1.85rem;
            }

            .navbar-collapse {
                gap: .38rem;
                padding: .7rem .65rem;
                border-radius: 18px !important;
            }

            .navbar-nav {
                flex: 0 0 auto;
                flex-wrap: nowrap;
                justify-content: center;
                gap: 1.2rem;
                min-width: max-content;
                max-width: none;
            }

            .navbar-nav .nav-item,
            .navbar-public .navbar-nav .nav-item,
            .navbar-parent .navbar-nav .nav-item {
                flex: 0 0 auto;
                min-width: auto;
                margin: 0;
            }

            .navbar-nav .nav-link,
            .navbar-public .navbar-nav .nav-link,
            .navbar-parent .navbar-nav .nav-link {
                padding: .38rem .48rem;
                font-size: .82rem;
            }

            .navbar-parent .navbar-nav .nav-link--icon-only {
                width: 2.25rem;
                height: 2.25rem;
                min-width: 2.25rem;
            }

            .navbar-tools,
            .navbar-parent .navbar-tools {
                gap: .35rem;
                margin-left: .35rem;
                padding-left: .45rem;
            }

            .site-header .login-btn,
            .site-header .login-btn.btn,
            .site-header .login-btn.btn-sm {
                padding: .26rem .65rem !important;
                font-size: .82rem !important;
            }

            .utility-icon-link {
                width: 34px;
                height: 34px;
            }
        }

        @media (max-width: 1199.98px) {
            .navbar-brand img {
                width: 85px;
                height: 85px;
            }

            .logo-line {
                margin-top: .35rem;
            }

            .brand-text {
                font-size: 1.35rem;
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

        /* ╬í╧à╬╕╬╝╬»╧â╬╡╬╣╧é ╬│╬╣╬▒ ╬║╬╣╬╜╬╖╧ä╧î/tablet. */
        @media (max-width: 1199.98px) {
            .navbar {
                padding: .65rem 0;
            }

            .navbar-brand {
                margin: 0;
                flex: 0 1 auto;
                justify-content: flex-start;
                flex-wrap: nowrap;
                max-width: calc(100% - 78px);
                gap: .7rem;
                min-width: 0;
            }

            .navbar-brand img {
                width: 85px;
                height: 85px;
            }

            .logo-line {
                margin-top: .3rem;
            }

            .brand-text {
                display: block;
                flex: 1 1 auto;
                min-width: 0;
                max-width: calc(100vw - 180px);
                font-size: 1.35rem;
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
                min-width: 0;
                border-radius: 18px !important;
                flex-direction: column;
                flex-wrap: nowrap !important;
                align-items: center;
                justify-content: flex-start;
                overflow-x: hidden;
                overflow-y: visible;
            }

            .navbar-collapse.show {
                display: flex;
            }

            .navbar-nav {
                margin-top: 0;
                flex: 0 0 auto;
                flex-direction: column;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: center;
                gap: .75rem;
                min-width: max-content;
                max-width: none;
                overflow: visible;
                box-sizing: border-box;
            }

            .navbar-nav .nav-item {
                flex: 0 0 auto;
                margin: 0;
            }

            .navbar-nav .nav-link {
                white-space: nowrap;
                overflow-wrap: normal;
            }

            .navbar-tools {
                flex-direction: row !important;
                align-items: center !important;
                flex: 0 0 auto;
                margin-top: .75rem;
                margin-left: 0;
                padding-top: .75rem;
                padding-left: 0;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
                border-left: 0;
                gap: .5rem;
                min-width: 100%;
                justify-content: center;
            }

            .navbar-tools .login-btn,
            .navbar-tools .utility-icon-link {
                margin-top: 0 !important;
                margin-bottom: 0 !important;
            }
        }

        @media (max-width: 767.98px) {
            .navbar-brand {
                max-width: calc(100% - 64px);
                gap: .55rem;
            }

            .navbar-brand img {
                width: 70px;
                height: 70px;
            }

            .brand-text {
                font-size: 1.12rem;
                max-width: calc(100vw - 132px);
            }
        }
    </style>

<header class="site-header">
<!-- ╬Ü╧ì╧ü╬╣╬┐ navigation ╧î╬╗╬┐╧à ╧ä╬┐╧à site. -->
<nav class="navbar navbar-expand-xl navbar-light<?php echo site_is_parent() ? ' navbar-parent' : ' navbar-public'; ?>">
    <div class="container">

        <!-- ╬¢╬┐╬│╧î╧ä╧à╧Ç╬┐ + ╧ä╬»╧ä╬╗╬┐╧é ╧â╧ç╬┐╬╗╬╡╬»╬┐╧à. -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo site_section_url('home.php'); ?>">
            <span class="logo-stack mr-2">
                <img src="<?php echo site_asset_url('img/logo-icon.png'); ?>" alt="Logo">
            </span>
            <span class="brand-text" aria-label="<?php echo htmlspecialchars($site_title); ?>">
                <span class="brand-line">╬ú╧ì╬╜╬┤╬╡╧â╬╝╬┐╧é ╬ô╬┐╬╜╬¡╧ë╬╜ &amp;</span>
                <span class="brand-line">╬Ü╬╖╬┤╬╡╬╝╧î╬╜╧ë╬╜ ╬ô╧à╬╝╬╜╬▒╧â╬»╬┐╧à</span>
                <span class="brand-line">╬æ╬│╬»╬┐╧à ╬æ╬╕╬▒╬╜╬▒╧â╬»╬┐╧à</span>
            </span>
        </a>

        <!-- ╬Ü╬┐╧à╬╝╧Ç╬» ╧Ç╬┐╧à ╬▒╬╜╬┐╬»╬│╬╡╬╣ ╧ä╬┐ menu ╧â╬╡ ╬║╬╣╬╜╬╖╧ä╬¡╧é ╧â╧à╧â╬║╬╡╧à╬¡╧é. -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- ╬ñ╬▒ ╬▓╬▒╧â╬╣╬║╬¼ links ╧ä╬┐╧à site ╧â╧ä╬┐ ╬║╬¡╬╜╧ä╧ü╬┐. -->
            <ul class="navbar-nav text-center">
                <!-- ╬Ü╬¼╬╜╬┐╧à╬╝╬╡ loop ╧â╧ä╬┐ $nav_items ╬│╬╣╬▒ ╬╜╬▒ ╬▒╧Ç╬┐╧å╧ì╬│╬┐╧à╬╝╬╡ ╬╡╧Ç╬▒╬╜╬▒╬╗╬▒╬╝╬▓╬▒╬╜╧î╬╝╬╡╬╜╬┐ HTML. -->
                <?php foreach ($nav_items as $item): ?>
                    <!-- ╬ò╬╗╬¡╬│╧ç╬┐╧à╬╝╬╡ ╬▒╬╜ ╧ä╬┐ link ╬▒╬╜╧ä╬╣╧â╧ä╬┐╬╣╧ç╬╡╬» ╧â╧ä╬╖╬╜ ╧ä╧ë╧ü╬╣╬╜╬« ╧â╬╡╬╗╬»╬┤╬▒. -->
                    <?php $is_active = in_array($current_page, $item['match'], true); ?>
                    <li class="nav-item<?php echo $is_active ? ' active' : ''; ?>">
                        <!-- aria-current ╬▓╬┐╬╖╬╕╬¼╬╡╬╣ accessibility (screen readers). -->
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

            <!-- ╬ö╬╡╬╛╬╣╬¼ ╬╡╧ü╬│╬▒╬╗╬╡╬»╬▒: login + shortcut. -->
            <div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center navbar-tools">

                <?php if (site_is_parent()): ?>
                    <a href="<?php echo site_public_url('logout.php'); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-xl-0 login-btn logout-btn">
                        <i class="fas fa-sign-out-alt mr-1"></i> Log out
                    </a>
                <?php else: ?>
                    <a href="<?php echo site_login_url(); ?>"
                       class="btn btn-outline-dark btn-sm my-2 my-xl-0 login-btn login-btn-green">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                <?php endif; ?>

                <?php if (site_is_parent()): ?>
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

<script>
    // Fallback ╬╝╧î╬╜╬┐ ╧î╧ä╬▒╬╜ ╧ä╬╡╬╗╬╡╬╣╧Ä╧â╬╡╬╣ ╧ä╬┐ ╧å╧î╧ü╧ä╧ë╬╝╬▒ ╬║╬▒╬╣ ╬┤╬╡╬╜ ╧à╧Ç╬¼╧ü╧ç╬╡╬╣ ╬║╬▒╬╕╧î╬╗╬┐╧à Bootstrap collapse.
    (function () {
        function hasBootstrapCollapse() {
            return window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.collapse === 'function';
        }

        function bindFallbackNavbarToggle() {
            if (hasBootstrapCollapse()) return;

            // ╬Æ╧ü╬»╧â╬║╬┐╧à╬╝╬╡ ╧ä╬▒ ╧â╧ä╬┐╬╣╧ç╬╡╬»╬▒ ╧Ç╬┐╧à ╧ç╧ü╬╡╬╣╬¼╬╢╬┐╬╜╧ä╬▒╬╣ ╬│╬╣╬▒ ╧ä╬┐ fallback.
            var toggler = document.querySelector('[data-target="#mainNavbar"]');
            var menu = document.getElementById('mainNavbar');
            if (!toggler || !menu || toggler.dataset.fallbackBound === 'true') return;

            toggler.dataset.fallbackBound = 'true';

            // ╬ò╬╜╬▒╬╗╬╗╬▒╬│╬« open/close ╧î╧ä╬▒╬╜ ╧Ç╬▒╧ä╬¼╬╝╬╡ ╧ä╬┐ hamburger.
            toggler.addEventListener('click', function (event) {
                // ╬æ╬╜ ╧å╬┐╧ü╧ä╧Ä╬╕╬╖╬║╬╡ ╧â╧ä╬┐ ╬╝╬╡╧ä╬▒╬╛╧ì Bootstrap, ╬▒╧å╬«╬╜╬┐╧à╬╝╬╡ ╬╡╬║╬╡╬»╬╜╬┐ ╬╜╬▒ ╧ç╬╡╬╣╧ü╬╣╧â╧ä╬╡╬» ╧ä╬┐ toggle.
                if (hasBootstrapCollapse()) return;

                event.preventDefault();

                var isOpen = menu.classList.contains('show');
                menu.classList.toggle('show', !isOpen);
                toggler.classList.toggle('collapsed', isOpen);
                // ╬ò╬╜╬╖╬╝╬¡╧ü╧ë╧â╬╖ ╧ä╬┐╧à aria-expanded ╬│╬╣╬▒ accessibility.
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
