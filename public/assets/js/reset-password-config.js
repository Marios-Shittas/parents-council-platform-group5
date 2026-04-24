(function () {
    var script = document.currentScript;
    if (!script || !script.dataset) {
        return;
    }

    window.RESET_EMAIL = script.dataset.resetEmail || '';
    window.RESET_TOKEN = script.dataset.resetToken || '';
    window.RESET_PASSWORD_SERVICE_URL = script.dataset.serviceUrl || '';
})();
