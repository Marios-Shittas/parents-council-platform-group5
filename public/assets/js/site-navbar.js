(function () {
    function bindNavbarToggle() {
        var toggler = document.querySelector('[data-target="#mainNavbar"]');
        var menu = document.getElementById('mainNavbar');
        var header = document.querySelector('.site-header');

        if (!toggler || !menu || toggler.dataset.siteNavbarBound === 'true') {
            return;
        }

        toggler.dataset.siteNavbarBound = 'true';

        function isMobileMenu() {
            return window.matchMedia('(max-width: 1199.98px)').matches;
        }

        function setOpen(shouldOpen) {
            if (!isMobileMenu()) {
                menu.classList.remove('site-navbar-collapsing');
                menu.style.maxHeight = '';
                menu.style.opacity = '';
                menu.style.pointerEvents = '';
                toggler.setAttribute('aria-expanded', 'false');
                return;
            }

            menu.classList.add('site-navbar-collapsing');
            toggler.classList.toggle('collapsed', !shouldOpen);
            toggler.setAttribute('aria-expanded', String(shouldOpen));

            if (shouldOpen) {
                menu.classList.add('show');
                menu.style.maxHeight = '0px';
                menu.style.opacity = '0';
                menu.style.pointerEvents = 'auto';

                window.requestAnimationFrame(function () {
                    menu.style.maxHeight = menu.scrollHeight + 'px';
                    menu.style.opacity = '1';
                });
                return;
            }

            menu.style.maxHeight = menu.scrollHeight + 'px';
            menu.style.opacity = '1';
            menu.style.pointerEvents = 'none';

            var closeMenuAfterTransition = function (event) {
                if (event.target !== menu || event.propertyName !== 'max-height') {
                    return;
                }

                menu.removeEventListener('transitionend', closeMenuAfterTransition);
                if (menu.style.maxHeight === '0px') {
                    menu.classList.remove('show');
                }
            };

            menu.addEventListener('transitionend', closeMenuAfterTransition);

            window.requestAnimationFrame(function () {
                menu.style.maxHeight = '0px';
                menu.style.opacity = '0';
            });
        }

        toggler.addEventListener('click', function (event) {
            if (!isMobileMenu()) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            setOpen(!menu.classList.contains('show'));
        });

        menu.addEventListener('click', function (event) {
            var link = event.target.closest('a');
            if (link && isMobileMenu()) {
                setOpen(false);
            }
        });

        document.addEventListener('click', function (event) {
            if (!isMobileMenu() || !menu.classList.contains('show')) {
                return;
            }

            if (header && !header.contains(event.target)) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && menu.classList.contains('show')) {
                setOpen(false);
                toggler.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (isMobileMenu() && menu.classList.contains('show')) {
                menu.style.maxHeight = menu.scrollHeight + 'px';
                return;
            }

            setOpen(false);
        });

        setOpen(false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindNavbarToggle);
        return;
    }

    bindNavbarToggle();
})();
