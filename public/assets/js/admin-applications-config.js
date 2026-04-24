(function () {
    var script = document.currentScript;
    var rawTemplates = (script && script.dataset && script.dataset.templatesData) || '[]';
    var todayUiDate = (script && script.dataset && script.dataset.todayUiDate) || '';

    var templatesData = [];
    try {
        templatesData = JSON.parse(rawTemplates);
        if (!Array.isArray(templatesData)) {
            templatesData = [];
        }
    } catch (error) {
        templatesData = [];
    }

    window.adminApplicationsConfig = {
        todayUiDate: todayUiDate,
        templatesData: templatesData,
    };
})();
