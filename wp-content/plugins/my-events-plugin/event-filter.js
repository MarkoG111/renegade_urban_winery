document.addEventListener("DOMContentLoaded", function () {
    let currentStatus = "";
    let currentCategory = "";

    const statusButtons = document.querySelectorAll(".status-filter .filter-btn");
    const categoryButtons = document.querySelectorAll(".category-filter .filter-btn");

    statusButtons.forEach(button => {
        button.addEventListener("click", function () {
            statusButtons.forEach(btn => btn.classList.remove("active"));

            this.classList.add("active");

            moveIndicator(this.parentElement);

            currentStatus = this.dataset.status;

            filterEvents(1);
        });
    });

    categoryButtons.forEach(button => {
        button.addEventListener("click", function () {
            categoryButtons.forEach(btn => btn.classList.remove("active"));

            this.classList.add("active");

            moveIndicator(this.parentElement);

            currentCategory = this.dataset.category;

            filterEvents(1);
        });
    });

    function filterEvents(page = 1) {
        fetch(eventFilter.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body:
                "action=filter_events"
                + "&status=" + currentStatus
                + "&category=" + currentCategory
                + "&page=" + page
        })
            .then(res => res.json())
            .then(data => {
                const grid = document.querySelector(".events-grid");
                const pagination = document.querySelector("#pagination");

                grid.style.opacity = 0;

                setTimeout(() => {
                    grid.innerHTML = data.cards;
                    pagination.innerHTML = data.pagination;
                    grid.style.opacity = 1;
                }, 200);
            });
    }

    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("reset-filters-btn")) {
            e.preventDefault();

            currentStatus = "";
            currentCategory = "";

            const statusContainer = document.querySelector(".status-filter");
            const categoryContainer = document.querySelector(".category-filter");

            const statusButtons = statusContainer.querySelectorAll(".filter-btn");
            const categoryButtons = categoryContainer.querySelectorAll(".filter-btn");

            statusButtons.forEach(btn => btn.classList.remove("active"));
            categoryButtons.forEach(btn => btn.classList.remove("active"));

            statusButtons[0].classList.add("active");
            categoryButtons[0].classList.add("active");

            moveIndicator(statusContainer);
            moveIndicator(categoryContainer);

            filterEvents(1);
        }
    });

    function moveIndicator(container) {
        const active = container.querySelector(".filter-btn.active");

        const indicator = container.querySelector(".filter-indicator");

        indicator.style.width = active.offsetWidth + "px";
        indicator.style.left = active.offsetLeft + "px";
    }

    window.addEventListener("load", function () {
        document.querySelectorAll(".events-filters").forEach(container => {
            moveIndicator(container);
        });
    });

    document.addEventListener("click", function (e) {
        const link = e.target.closest(".page-numbers");

        if (!link) 
            return;

        e.preventDefault();

        const href = link.getAttribute("href");

        if (!href) 
            return;

        const match = href.match(/page\/(\d+)/);

        const page = match ? match[1] : 1;

        filterEvents(page);
    })
});
