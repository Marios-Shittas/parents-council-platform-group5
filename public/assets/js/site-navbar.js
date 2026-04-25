// Fallback μόνο όταν τελειώσει το φόρτωμα και δεν υπάρχει καθόλου Bootstrap collapse.
    (function () {
        // Leitourgia hasBootstrapCollapse: krataei tin antistoixi symperifora tou UI.
        function hasBootstrapCollapse() {
            return window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.collapse === 'function';
        }

        // Leitourgia bindFallbackNavbarToggle: krataei tin antistoixi symperifora tou UI.
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
