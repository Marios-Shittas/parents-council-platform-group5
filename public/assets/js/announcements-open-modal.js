document.addEventListener('DOMContentLoaded', function () {
    var params = new URLSearchParams(window.location.search);
    var openId = params.get('open');

    if (!openId) {
        return;
    }

    var modalElement = document.getElementById('announcementModal' + openId);
    if (!modalElement || typeof window.jQuery === 'undefined') {
        return;
    }

    window.jQuery(modalElement).modal('show');
});
