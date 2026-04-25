(function () {
    // Krataei to palio login lifecycle hook se ksexoristo asset.
    window.addEventListener('popstate', function () {});

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && window.location.pathname.indexOf('login.php') !== -1) {
            return;
        }
    });
})();
