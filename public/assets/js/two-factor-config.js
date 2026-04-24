(function () {
    var script = document.currentScript;
    if (!script || !script.dataset) {
        return;
    }

    window.initialTwoFactorSuccess = script.dataset.initialSuccess || '';
})();
