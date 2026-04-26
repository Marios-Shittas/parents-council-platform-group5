(function () {
        if (window.__adminSidebarToggleBound) return;
        window.__adminSidebarToggleBound = true;

        var wrapper = document.querySelector('.admin-wrapper');
        var sidebar = document.getElementById('adminSidebar');
        var toggleButton = document.querySelector('[data-admin-sidebar-toggle]');
        var closeButton = document.querySelector('[data-admin-sidebar-close]');
        var backdrop = document.querySelector('[data-admin-sidebar-backdrop]');
        var mobileQuery = window.matchMedia('(max-width: 1199.98px), (max-height: 720px)');
        var script = document.currentScript;
        var usersBadgeText = script ? (script.getAttribute('data-users-badge-text') || '') : '';

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

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function isMobile() {
            return mobileQuery.matches;
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function getDesktopCollapsedPreference() {
            try {
                return window.localStorage.getItem('adminSidebarDesktopCollapsed') === 'true';
            } catch (error) {
                return false;
            }
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function setDesktopCollapsedPreference(isCollapsed) {
            try {
                window.localStorage.setItem('adminSidebarDesktopCollapsed', String(isCollapsed));
            } catch (error) {
                // Agnoei sfalmata apothikefsis gia na paramenei leitourgiko to perivallon.
            }
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function updateToggleButton(isOpen) {
            var shouldShowToggle = isMobile()
                ? !wrapper.classList.contains('sidebar-open')
                : !isOpen;
            toggleButton.hidden = !shouldShowToggle;
            toggleButton.setAttribute('aria-expanded', String(isOpen));
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function closeSidebar() {
            setSidebarOpen(false);
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function isSidebarVisible() {
            return isMobile()
                ? wrapper.classList.contains('sidebar-open')
                : !wrapper.classList.contains('sidebar-collapsed');
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
