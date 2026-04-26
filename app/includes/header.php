<?php
// Arxeio: app\includes\header.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Elegxei an o xristis einai sindedemenos se prostÏ„ÎµÏ…omeni selida.
$isProtectedPage = isset($_SESSION['user_id']);

require_once __DIR__ . '/site_context.php';
require_once __DIR__ . '/../viewmodels/HeaderViewModel.php';

$headerViewModel = new HeaderViewModel();
$headerData = $headerViewModel->build();
$site_title = $headerData['site_title'];
$current_page = $headerData['current_page'];
$portal_label = $headerData['portal_label'];
$profile_item = $headerData['profile_item'];
$nav_items = $headerData['nav_items'];
?>
<script src="<?php echo site_asset_url('js/site-favicon.js'); ?>" data-favicon-href="<?php echo site_asset_url('img/logo-icon.png'); ?>" data-favicon-shape="circle" defer></script>
<link rel="stylesheet" href="<?php echo site_asset_url('css/site-header.css'); ?>">

<header class="site-header">
<!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
<nav class="navbar navbar-expand-xl navbar-light<?php echo site_is_parent() ? ' navbar-parent' : ' navbar-public'; ?>">
    <div class="container">

        <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo site_section_url('home.php'); ?>">
            <span class="logo-stack">
                <img src="<?php echo site_asset_url('img/logo-icon.png'); ?>" alt="Logo">
            </span>
            <span class="brand-text" aria-label="<?php echo htmlspecialchars($site_title); ?>">Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½ &amp; ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Ï‰Î½ Î“Ï…Î¼Î½Î±ÏƒÎ¯Î¿Ï… Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï…</span>
        </a>

        <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
            <ul class="navbar-nav text-center">
                <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
                <?php foreach ($nav_items as $item): ?>
                    <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
                    <?php $is_active = in_array($current_page, $item['match'], true); ?>
                    <li class="nav-item<?php echo $is_active ? ' active' : ''; ?>">
                        <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
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

            <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
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

<script src="<?php echo site_asset_url('js/site-navbar.js'); ?>" defer></script>
