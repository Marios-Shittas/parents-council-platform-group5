// Arxeio: public\assets\js\app-page-config.js
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
(function () {
    function trimTrailingSlashes(value) {
        return String(value || '').replace(/\/+$/, '');
    }

    function trimSlashes(value) {
        return String(value || '').replace(/^\/+|\/+$/g, '');
    }

    function joinUrl(base, path) {
        var normalizedBase = trimTrailingSlashes(base);
        var normalizedPath = trimSlashes(path);

        return normalizedPath ? normalizedBase + '/' + normalizedPath : normalizedBase;
    }

    function inferProjectUrl() {
        var path = window.location.pathname || '';
        var publicIndex = path.indexOf('/public/');
        var appIndex = path.indexOf('/app/');
        var basePath = '';

        if (publicIndex >= 0) {
            basePath = path.slice(0, publicIndex);
        } else if (appIndex >= 0) {
            basePath = path.slice(0, appIndex);
        }

        return trimTrailingSlashes(window.location.origin + basePath);
    }

    window.APP_PROJECT_URL = trimTrailingSlashes(window.APP_PROJECT_URL || inferProjectUrl());
    window.APP_PUBLIC_URL = trimTrailingSlashes(window.APP_PUBLIC_URL || joinUrl(window.APP_PROJECT_URL, 'public'));

    window.appProjectUrl = function (path) {
        return joinUrl(window.APP_PROJECT_URL, path || '');
    };

    window.appPublicUrl = function (path) {
        return joinUrl(window.APP_PUBLIC_URL, path || '');
    };

    window.appAssetUrl = function (path) {
        return window.appPublicUrl(joinUrl('assets', path || ''));
    };

    window.appServiceUrl = function (path) {
        return window.appProjectUrl(joinUrl('app/services', path || ''));
    };

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

    window.APP_PROJECT_URL = trimTrailingSlashes(window.APP_PROJECT_URL || inferProjectUrl());
    window.APP_PUBLIC_URL = trimTrailingSlashes(window.APP_PUBLIC_URL || joinUrl(window.APP_PROJECT_URL, 'public'));
})();
