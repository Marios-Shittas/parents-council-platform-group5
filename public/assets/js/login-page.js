// Arxeio: public\assets\js\login-page.js
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.
(function () {
    // Krataei to palio login lifecycle hook se ksexoristo asset.
    window.addEventListener('popstate', function () {});

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && window.location.pathname.indexOf('login.php') !== -1) {
            return;
        }
    });
})();
