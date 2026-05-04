(function () {
    const slides = Array.from(document.querySelectorAll(".slide"));
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const counter = document.getElementById("slideCounter");
    const progressBar = document.getElementById("progressBar");
    const overviewBtn = document.getElementById("overviewBtn");
    const closeOverview = document.getElementById("closeOverview");
    const overviewPanel = document.getElementById("overviewPanel");
    const overviewGrid = document.getElementById("overviewGrid");
    const fullscreenBtn = document.getElementById("fullscreenBtn");
    const printBtn = document.getElementById("printBtn");

    slides.forEach((slide, index) => {
        slide.id = slide.id || `slide-${index + 1}`;
        slide.hidden = index !== 0;
    });

    let activeIndex = readHashIndex();

    function readHashIndex() {
        const match = window.location.hash.match(/^#slide-(\d+)$/);
        if (!match) {
            return 0;
        }

        const index = Number.parseInt(match[1], 10) - 1;
        return Number.isFinite(index) ? clamp(index, 0, slides.length - 1) : 0;
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function renderOverview() {
        overviewGrid.innerHTML = "";

        slides.forEach((slide, index) => {
            const button = document.createElement("button");
            button.type = "button";
            button.textContent = `${index + 1}. ${slide.dataset.title || "Slide"}`;
            button.className = index === activeIndex ? "is-current" : "";
            button.addEventListener("click", () => {
                goTo(index);
                closeOverviewPanel();
            });
            overviewGrid.appendChild(button);
        });
    }

    function updateUi() {
        slides.forEach((slide, index) => {
            const isActive = index === activeIndex;
            slide.hidden = !isActive;
            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", isActive ? "false" : "true");

            if (isActive) {
                slide.scrollTop = 0;
            }
        });

        counter.textContent = `${activeIndex + 1} / ${slides.length}`;
        progressBar.style.width = `${((activeIndex + 1) / slides.length) * 100}%`;
        prevBtn.disabled = activeIndex === 0;
        nextBtn.disabled = activeIndex === slides.length - 1;
        document.documentElement.dataset.slide = String(activeIndex + 1);

        const nextHash = `#slide-${activeIndex + 1}`;
        if (window.location.hash !== nextHash && window.history && window.history.replaceState) {
            window.history.replaceState(null, "", nextHash);
        }

        renderOverview();
    }

    function goTo(index) {
        activeIndex = clamp(index, 0, slides.length - 1);
        updateUi();
    }

    function next() {
        goTo(activeIndex + 1);
    }

    function previous() {
        goTo(activeIndex - 1);
    }

    function openOverviewPanel() {
        overviewPanel.classList.add("is-open");
        overviewPanel.setAttribute("aria-hidden", "false");
        closeOverview.focus();
    }

    function closeOverviewPanel() {
        overviewPanel.classList.remove("is-open");
        overviewPanel.setAttribute("aria-hidden", "true");
        overviewBtn.focus();
    }

    function updateFullscreenButton() {
        const isFullscreen = Boolean(document.fullscreenElement);
        fullscreenBtn.textContent = isFullscreen ? "Έξοδος πλήρους οθόνης" : "Πλήρης οθόνη";
        fullscreenBtn.setAttribute("aria-pressed", isFullscreen ? "true" : "false");
    }

    async function toggleFullscreen() {
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
                return;
            }

            await document.documentElement.requestFullscreen();
        } catch (error) {
            console.warn("Fullscreen mode is not available.", error);
        }
    }

    prevBtn.addEventListener("click", previous);
    nextBtn.addEventListener("click", next);
    overviewBtn.addEventListener("click", openOverviewPanel);
    closeOverview.addEventListener("click", closeOverviewPanel);
    fullscreenBtn.addEventListener("click", toggleFullscreen);
    document.addEventListener("fullscreenchange", updateFullscreenButton);
    printBtn.addEventListener("click", () => window.print());

    overviewPanel.addEventListener("click", (event) => {
        if (event.target === overviewPanel) {
            closeOverviewPanel();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (overviewPanel.classList.contains("is-open") && event.key === "Escape") {
            closeOverviewPanel();
            return;
        }

        if (event.key === "ArrowRight" || event.key === "PageDown" || event.key === " ") {
            event.preventDefault();
            next();
            return;
        }

        if (event.key === "ArrowLeft" || event.key === "PageUp") {
            event.preventDefault();
            previous();
            return;
        }

        if (event.key === "Home") {
            event.preventDefault();
            goTo(0);
            return;
        }

        if (event.key === "End") {
            event.preventDefault();
            goTo(slides.length - 1);
        }
    });

    window.addEventListener("hashchange", () => {
        const nextIndex = readHashIndex();
        if (nextIndex !== activeIndex) {
            activeIndex = nextIndex;
            updateUi();
        }
    });

    updateUi();
    updateFullscreenButton();
})();
