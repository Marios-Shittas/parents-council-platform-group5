<?php
require_once __DIR__ . '/../viewmodels/AdminSidebarViewModel.php';

$adminSidebarViewModel = new AdminSidebarViewModel(isset($conn) && $conn instanceof mysqli ? $conn : null);
$adminSidebarData = $adminSidebarViewModel->build();
$currentPage = $adminSidebarData['current_page'];
$epikoinoniaBadgeText = $adminSidebarData['epikoinonia_badge_text'];
$usersBadgeText = $adminSidebarData['users_badge_text'];
$applicationsBadgeText = $adminSidebarData['applications_badge_text'];
$ordersBadgeText = $adminSidebarData['orders_badge_text'];
?>
<script src="../assets/js/site-favicon.js" data-favicon-href="/parents-council-platform-group5/public/assets/img/logo-icon.png" data-favicon-shape="direct" defer></script>

<button class="admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-controls="adminSidebar" aria-expanded="false" aria-label="Άνοιγμα ή κλείσιμο admin menu">
    <i class="fas fa-bars"></i>
    <span>Menu</span>
</button>

<div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop></div>

<nav class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    
    <div class="admin-sidebar-top">
        <div class="brand">
            <i class="fas fa-school"></i>
            Πίνακας Διαχείρισης
        </div>

        <button class="admin-sidebar-close" type="button" data-admin-sidebar-close aria-label="Κλείσιμο admin menu">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <ul class="nav flex-column">

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'home.php' ? 'active' : ''; ?>" href="home.php">
                <i class="fas fa-home"></i>
                <span class="admin-nav-label">Αρχική</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                <i class="fas fa-bullhorn"></i>
                <span class="admin-nav-label">Ανακοινώσεις</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>" href="events.php">
                <i class="fas fa-calendar-alt"></i>
                <span class="admin-nav-label">Εκδηλώσεις</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'useful-information.php' ? 'active' : ''; ?>" href="useful-information.php">
                <i class="fas fa-info-circle"></i>
                <span class="admin-nav-label">Χρήσιμοι Σύνδεσμοι</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'parents.php' ? 'active' : ''; ?>" href="parents.php">
                <i class="fas fa-users"></i>
                <span class="admin-nav-label">Σύνδεσμος Γονέων</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users"></i>
                <span class="admin-nav-label">Χρήστες</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'applications.php' ? 'active' : ''; ?>" href="applications.php">
                <i class="fas fa-file-alt"></i>
                <span class="admin-nav-label">Αιτήσεις</span>
                <?php if ($applicationsBadgeText !== ''): ?>
                    <span class="admin-notification-badge" aria-label="Νέες αιτήσεις προς έλεγχο: <?php echo htmlspecialchars($applicationsBadgeText); ?>"><?php echo htmlspecialchars($applicationsBadgeText); ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'eshop.php' ? 'active' : ''; ?>" href="eshop.php">
                <i class="fas fa-shopping-cart"></i>
                <span class="admin-nav-label">Κατάστημα</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'Orders.php' ? 'active' : ''; ?>" href="Orders.php">
                <i class="fas fa-receipt"></i>
                <span class="admin-nav-label">Παραγγελίες</span>
                <?php if ($ordersBadgeText !== ''): ?>
                    <span class="admin-notification-badge" aria-label="Νέες πληρωμένες παραγγελίες: <?php echo htmlspecialchars($ordersBadgeText); ?>"><?php echo htmlspecialchars($ordersBadgeText); ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'epikoinonia.php' ? 'active' : ''; ?>" href="epikoinonia.php">
                <i class="fas fa-envelope"></i>
                <span class="admin-nav-label">Επικοινωνία</span>
                <?php if ($epikoinoniaBadgeText !== ''): ?>
                    <span class="admin-notification-badge" aria-label="Νέα μηνύματα επικοινωνίας: <?php echo htmlspecialchars($epikoinoniaBadgeText); ?>"><?php echo htmlspecialchars($epikoinoniaBadgeText); ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'photos.php' ? 'active' : ''; ?>" href="photos.php">
                <i class="fas fa-camera"></i>
                <span class="admin-nav-label">Φωτογραφίες</span>
            </a>
        </li>

    </ul>

    <div class="admin-sidebar-footer">
        <a class="nav-link admin-logout-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span class="admin-nav-label">Logout</span>
        </a>
    </div>

</nav>

<script src="../assets/js/admin-sidebar.js" data-users-badge-text="<?php echo htmlspecialchars($usersBadgeText, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
