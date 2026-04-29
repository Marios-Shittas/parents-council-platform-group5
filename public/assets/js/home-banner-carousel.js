// Arxeio: public\assets\js\home-banner-carousel.js
// Rolos: Xeirizetai dynamic kommatia tis arxikis selidas kai fernei dedomena apo backend services.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
document.addEventListener('DOMContentLoaded', function () {
    // Energopoiei to carousel tou homepage banner mono otan yparxoun polla slides.
    document.querySelectorAll('.home-school-banner__carousel').forEach(function (carousel) {
        var slides = carousel.querySelectorAll('.home-school-banner__image');

        if (slides.length <= 1) {
            return;
        }

        var currentIndex = 0;
        var intervalMs = parseInt(carousel.getAttribute('data-interval'), 10) || 15000;

        window.setInterval(function () {
            slides[currentIndex].classList.remove('is-active');
            currentIndex = (currentIndex + 1) % slides.length;
            slides[currentIndex].classList.add('is-active');
        }, intervalMs);
    });
});
