(function () {
    var script = document.currentScript;
    var payload = (script && script.dataset && script.dataset.calendarPayload) || '{}';

    try {
        window.adminCalendarData = JSON.parse(payload);
    } catch (error) {
        window.adminCalendarData = {};
    }
})();
