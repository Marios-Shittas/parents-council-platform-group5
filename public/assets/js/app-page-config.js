(function () {
    // Metaferoume mikra PHP configs sto window xoris inline JavaScript.
    function applyConfig(script) {
        if (!script) return;

        var rawConfig = script.getAttribute('data-config') || '{}';
        var config = {};

        try {
            config = JSON.parse(rawConfig);
        } catch (error) {
            config = {};
        }

        Object.keys(config).forEach(function (key) {
            window[key] = config[key];
        });
    }

    applyConfig(document.currentScript);
})();
