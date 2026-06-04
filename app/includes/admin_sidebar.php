<?php
require_once __DIR__ . '/site_context.php';
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadContactMessages = 0;
try {
    require_once __DIR__ . '/../services/EpikoinoniaService.php';
    $epikoinoniaService = new EpikoinoniaService();
    $unreadContactMessages = max(0, (int)$epikoinoniaService->getUnreadMessageCount());
} catch (Throwable $exception) {
    $unreadContactMessages = 0;
}

$pendingUserRegistrations = 0;
try {
    require_once __DIR__ . '/../services/UsersService.php';
    $usersService = new UsersService();
    $usersService->runScheduledMaintenance();
    $pendingUserRegistrations = max(0, (int)$usersService->getPendingRegistrationCount());
} catch (Throwable $exception) {
    $pendingUserRegistrations = 0;
}

$pendingApplicationSubmissions = 0;
try {
    require_once __DIR__ . '/../services/ApplicationsService.php';
    $applicationsService = new ApplicationsService();
    $pendingApplicationSubmissions = max(0, (int)$applicationsService->getWaitingSubmissionCount());
} catch (Throwable $exception) {
    $pendingApplicationSubmissions = 0;
}

$pendingPaidOrders = 0;
try {
    require_once __DIR__ . '/../services/OrdersService.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $ordersService = new OrdersService($conn);
        $pendingPaidOrders = max(0, (int)$ordersService->getPendingPaidOrdersCount());
    }
} catch (Throwable $exception) {
    $pendingPaidOrders = 0;
}

$epikoinoniaBadgeText = '';
if ($unreadContactMessages > 0) {
    $epikoinoniaBadgeText = $unreadContactMessages > 10 ? '10+' : (string)$unreadContactMessages;
}

$usersBadgeText = '';
if ($pendingUserRegistrations > 0) {
    $usersBadgeText = $pendingUserRegistrations > 10 ? '10+' : (string)$pendingUserRegistrations;
}

$applicationsBadgeText = '';
if ($pendingApplicationSubmissions > 0) {
    $applicationsBadgeText = $pendingApplicationSubmissions > 10 ? '10+' : (string)$pendingApplicationSubmissions;
}

$ordersBadgeText = '';
if ($pendingPaidOrders > 0) {
    $ordersBadgeText = $pendingPaidOrders > 10 ? '10+' : (string)$pendingPaidOrders;
}
?>
<script>
    (function () {
        var faviconHref = '<?php echo site_asset_url('img/primary-school-logo.png'); ?>';
        var head = document.head || document.getElementsByTagName('head')[0];
        if (!head) return;

        var img = new Image();
        img.onload = function () {
            var size = 128;
            var canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;

            var context = canvas.getContext('2d');
            if (!context) {
                return;
            }

            context.clearRect(0, 0, size, size);
            context.beginPath();
            context.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
            context.closePath();
            context.clip();
            context.drawImage(img, 0, 0, size, size);

            var existingIcons = head.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
            existingIcons.forEach(function (icon) {
                icon.parentNode.removeChild(icon);
            });

            var icon = document.createElement('link');
            icon.rel = 'icon';
            icon.type = 'image/png';
            icon.href = canvas.toDataURL('image/png');
            head.appendChild(icon);
        };

        img.src = faviconHref;
    })();
</script>

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

        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'programatismo-litourgion.php' ? 'active' : ''; ?>" href="programatismo-litourgion.php">
                <i class="fas fa-cogs"></i>
                <span class="admin-nav-label">Ενέργειες Συστήματος</span>
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

<script>
    (function () {
        if (window.__adminSidebarToggleBound) return;
        window.__adminSidebarToggleBound = true;

        var wrapper = document.querySelector('.admin-wrapper');
        var sidebar = document.getElementById('adminSidebar');
        var toggleButton = document.querySelector('[data-admin-sidebar-toggle]');
        var closeButton = document.querySelector('[data-admin-sidebar-close]');
        var backdrop = document.querySelector('[data-admin-sidebar-backdrop]');
        var mobileQuery = window.matchMedia('(max-width: 1199.98px), (max-height: 720px)');
        var usersBadgeText = <?php echo json_encode($usersBadgeText); ?>;

        if (!wrapper || !sidebar || !toggleButton || !backdrop) return;

        if (usersBadgeText) {
            var usersNavLink = sidebar.querySelector('a[href="users.php"]');
            if (usersNavLink && !usersNavLink.querySelector('.admin-notification-badge')) {
                var usersBadge = document.createElement('span');
                usersBadge.className = 'admin-notification-badge';
                usersBadge.setAttribute('aria-label', 'Νέες εγγραφές χρηστών: ' + usersBadgeText);
                usersBadge.textContent = usersBadgeText;
                usersNavLink.appendChild(usersBadge);
            }
        }

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
            var shouldShowToggle = isMobile()
                ? !wrapper.classList.contains('sidebar-open')
                : !isOpen;
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
