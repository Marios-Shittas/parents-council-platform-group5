(function () {
    var script = document.currentScript;
    if (!script || !script.dataset) {
        return;
    }

    window.APPROVAL_TOKEN = script.dataset.approvalToken || '';
})();
