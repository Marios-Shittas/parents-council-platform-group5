(function () {
    var script = document.currentScript;
    var redirectUrl = (script && script.dataset && script.dataset.redirectUrl) || '';

    try {
        sessionStorage.clear();
        localStorage.clear();
    } catch (error) {
        // Ignore storage access failures.
    }

    for (var i = 0; i < 10; i++) {
        window.history.replaceState(null, null, window.location.href);
    }

    for (var j = 0; j < 50; j++) {
        window.history.pushState({ state: j }, null, window.location.href);
    }

    if (redirectUrl !== '') {
        window.location.replace(redirectUrl);
    }
})();
