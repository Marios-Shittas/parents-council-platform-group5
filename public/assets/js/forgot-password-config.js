(function () {
    var script = document.currentScript;
    if (!script || !script.dataset) {
        return;
    }

    window.FORGOT_PASSWORD_SERVICE_URL = script.dataset.serviceUrl || '';
})();
