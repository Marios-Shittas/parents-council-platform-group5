<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadContactMessages = 0;
try {
    require_once __DIR__ . '/../services/EpikoinoniaService.php';
    $epikoinoniaService = new EpikoinoniaService();
    $unreadContactMessages = max(0, (int)$epikoinoniaService->getUnreadMessageCount());
} catch (Throwable $exception) {
    $unreadContactMessages = 0;
}

$epikoinoniaBadgeText = '';
if ($unreadContactMessages > 0) {
    $epikoinoniaBadgeText = $unreadContactMessages > 10 ? '10+' : (string)$unreadContactMessages;
}
?>

<link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">

<nav class="admin-sidebar">
    
    <div class="brand">
        <i class="fas fa-school mr-2"></i>
        Admin Panel
    </div>

    <ul class="nav flex-column">

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'home.php' ? 'active' : ''; ?>" href="home.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                <i class="fas fa-bullhorn"></i> Ανακοινώσεις
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>" href="events.php">
                <i class="fas fa-calendar-alt"></i> Εκδηλώσεις
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'useful-information.php' ? 'active' : ''; ?>" href="useful-information.php">
                <i class="fas fa-info-circle"></i> Χρήσιμες Πληροφορίες
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users"></i> Χρήστες
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'applications.php' ? 'active' : ''; ?>" href="applications.php">
                <i class="fas fa-file-alt"></i> Αιτήσεις
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'eshop.php' ? 'active' : ''; ?>" href="eshop.php">
                <i class="fas fa-shopping-cart"></i> E-shop
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
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
      
        
    </ul>

</nav>
