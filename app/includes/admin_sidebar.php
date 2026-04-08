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

<button class="admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-controls="adminSidebar" aria-expanded="false" aria-label="Άνοιγμα ή κλείσιμο admin menu">
    <i class="fas fa-bars"></i>
    <span>Menu</span>
</button>

<div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop></div>

<nav class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    
    <div class="admin-sidebar-top">
        <div class="brand">
            <i class="fas fa-school"></i>
            Admin Panel
        </div>

        <button class="admin-sidebar-close" type="button" data-admin-sidebar-close aria-label="Κλείσιμο admin menu">
            <i class="fas fa-times"></i>
        </button>
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
            <a class="nav-link <?php echo $currentPage === 'parents.php' ? 'active' : ''; ?>" href="parents.php">
                <i class="fas fa-users"></i> Γονείς
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
            <a class="nav-link <?php echo $currentPage === 'Orders.php' ? 'active' : ''; ?>" href="Orders.php">
                <i class="fas fa-receipt"></i> Παραγγελίες
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

<script>
    (function () {
        if (window.__adminSidebarToggleBound) return;
        window.__adminSidebarToggleBound = true;

        var wrapper = document.querySelector('.admin-wrapper');
        var sidebar = document.getElementById('adminSidebar');
        var toggleButton = document.querySelector('[data-admin-sidebar-toggle]');
        var closeButton = document.querySelector('[data-admin-sidebar-close]');
        var backdrop = document.querySelector('[data-admin-sidebar-backdrop]');
        var mobileQuery = window.matchMedia('(max-width: 991.98px)');

        if (!wrapper || !sidebar || !toggleButton || !backdrop) return;

        function isMobile() {
            return mobileQuery.matches;
        }

        function getDesktopCollapsedPreference() {
            try {
                return window.localStorage.getItem('adminSidebarDesktopCollapsed') === 'true';
            } catch (error) {
                return false;
            }
        }

        function setDesktopCollapsedPreference(isCollapsed) {
            try {
                window.localStorage.setItem('adminSidebarDesktopCollapsed', String(isCollapsed));
            } catch (error) {
                // Ignore storage failures and keep the UI functional.
            }
        }

        function updateToggleButton(isOpen) {
            var shouldShowToggle = isMobile() || wrapper.classList.contains('sidebar-collapsed');
            toggleButton.hidden = !shouldShowToggle;
            toggleButton.setAttribute('aria-expanded', String(isOpen));
        }

        function setSidebarOpen(isOpen, options) {
            var settings = options || {};

            wrapper.classList.remove('sidebar-open', 'sidebar-collapsed');

            if (isMobile()) {
                wrapper.classList.toggle('sidebar-open', isOpen);
                document.body.classList.toggle('admin-sidebar-lock', isOpen);
            } else {
                wrapper.classList.toggle('sidebar-collapsed', !isOpen);
                document.body.classList.remove('admin-sidebar-lock');

                if (!settings.skipPersist) {
                    setDesktopCollapsedPreference(!isOpen);
                }
            }

            updateToggleButton(isOpen);
        }

        function closeSidebar() {
            setSidebarOpen(false);
        }

        function isSidebarVisible() {
            return isMobile()
                ? wrapper.classList.contains('sidebar-open')
                : !wrapper.classList.contains('sidebar-collapsed');
        }

        function handleToggle() {
            if (isMobile()) {
                setSidebarOpen(!wrapper.classList.contains('sidebar-open'));
                return;
            }

            setSidebarOpen(wrapper.classList.contains('sidebar-collapsed'));
        }

        toggleButton.addEventListener('click', handleToggle);
        backdrop.addEventListener('click', closeSidebar);

        if (closeButton) {
            closeButton.addEventListener('click', closeSidebar);
        }

        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) closeSidebar();
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isSidebarVisible()) {
                closeSidebar();
            }
        });

        function syncSidebarState() {
            if (isMobile()) {
                wrapper.classList.remove('sidebar-collapsed');
                setSidebarOpen(false, { skipPersist: true });
                return;
            }

            setSidebarOpen(!getDesktopCollapsedPreference(), { skipPersist: true });
        }

        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', syncSidebarState);
        } else if (typeof mobileQuery.addListener === 'function') {
            mobileQuery.addListener(syncSidebarState);
        }

        syncSidebarState();
    })();
</script>
