// Arxeio: public\assets\js\app-page-config.js
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
(function () {
    // Metaferoume mikra PHP configs sto xroniko perithorio xoris inline JavaScript.
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
