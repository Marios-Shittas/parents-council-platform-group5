// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    (function () {
        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function hasBootstrapCollapse() {
            return window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.collapse === 'function';
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function bindFallbackNavbarToggle() {
            if (hasBootstrapCollapse()) return;

            // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
            var toggler = document.querySelector('[data-target="#mainNavbar"]');
            var menu = document.getElementById('mainNavbar');
            if (!toggler || !menu || toggler.dataset.fallbackBound === 'true') return;

            toggler.dataset.fallbackBound = 'true';

            // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
            toggler.addEventListener('click', function (event) {
                // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
                if (hasBootstrapCollapse()) return;

                event.preventDefault();

                var isOpen = menu.classList.contains('show');
                menu.classList.toggle('show', !isOpen);
                toggler.classList.toggle('collapsed', isOpen);
                // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
                toggler.setAttribute('aria-expanded', String(!isOpen));
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindFallbackNavbarToggle);
            return;
        }

        bindFallbackNavbarToggle();
    })();
