(function () {
    var script = document.currentScript;
    if (!script || !script.dataset) {
        return;
    }

    try {
        window.SUBSCRIPTION_RESULT_PARAMS = JSON.parse(script.dataset.params || '{}');
    } catch (error) {
        window.SUBSCRIPTION_RESULT_PARAMS = {};
    }
})();
