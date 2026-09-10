/* =========================================================
   MASS DRAGON DOJO - STUDENT PORTAL
   File: assets/js/app.js
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* -----------------------------------------------------
       ELEMENTS
    ----------------------------------------------------- */
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const menuToggle = document.getElementById("menuToggle");
    const toast = document.getElementById("toast");

    /* -----------------------------------------------------
       OPEN MOBILE SIDEBAR
    ----------------------------------------------------- */
    function openSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add("open");
        overlay.classList.add("show");
        document.body.style.overflow = "hidden";
    }

    /* -----------------------------------------------------
       CLOSE MOBILE SIDEBAR
    ----------------------------------------------------- */
    function closeSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove("open");
        overlay.classList.remove("show");
        document.body.style.overflow = "";
    }

    /* -----------------------------------------------------
       MOBILE MENU BUTTON
    ----------------------------------------------------- */
    if (menuToggle) {
        menuToggle.addEventListener("click", function () {
            if (!sidebar) {
                return;
            }

            if (sidebar.classList.contains("open")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    /* -----------------------------------------------------
       OVERLAY CLICK
    ----------------------------------------------------- */
    if (overlay) {
        overlay.addEventListener("click", function () {
            closeSidebar();
        });
    }

    /* -----------------------------------------------------
       SIDEBAR NAVIGATION
    ----------------------------------------------------- */
    const navLinks = document.querySelectorAll(".nav a");

    navLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            navLinks.forEach(function (item) {
                item.classList.remove("active");
            });

            this.classList.add("active");

            if (window.innerWidth <= 900) {
                closeSidebar();
            }
        });
    });

    /* -----------------------------------------------------
       TOAST MESSAGE
    ----------------------------------------------------- */
    window.showToast = function (message) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add("show");

        clearTimeout(window.massDragonToastTimer);

        window.massDragonToastTimer = setTimeout(function () {
            toast.classList.remove("show");
        }, 2500);
    };

    /* -----------------------------------------------------
       BUTTON EVENTS
    ----------------------------------------------------- */

    const profileButtons = document.querySelectorAll(".edit-btn, .profile-btn");
    profileButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            window.showToast(
                "Profile editing will be connected to MySQL later."
            );
        });
    });

    const payButtons = document.querySelectorAll(".pay-btn");
    payButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            window.showToast(
                "Payment gateway will be connected later."
            );
        });
    });

    const registerButtons = document.querySelectorAll(".register");
    registerButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            window.showToast("Event registration selected.");
        });
    });

    const syllabusButtons = document.querySelectorAll(".dark-btn");
    syllabusButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            window.showToast("Full syllabus will be available soon.");
        });
    });

    /* -----------------------------------------------------
       INTERSECTION OBSERVER - CARD REVEAL ANIMATION
    ----------------------------------------------------- */
    const animatedElements = document.querySelectorAll(
        ".hero, .stat-card, .card"
    );

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            function (entries, observerObject) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add("visible");
                    observerObject.unobserve(entry.target);
                });
            },
            { threshold: 0.08 }
        );

        animatedElements.forEach(function (element) {
            observer.observe(element);
        });
    } else {
        animatedElements.forEach(function (element) {
            element.classList.add("visible");
        });
    }

    /* -----------------------------------------------------
       SMOOTH NAVIGATION
    ----------------------------------------------------- */
    navLinks.forEach(function (link) {
        link.addEventListener("click", function (event) {
            const targetId = this.getAttribute("href");

            if (!targetId || !targetId.startsWith("#")) {
                return;
            }

            const target = document.querySelector(targetId);

            if (!target) {
                return;
            }

            event.preventDefault();

            target.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        });
    });

    /* -----------------------------------------------------
       ACTIVE SECTION WHILE SCROLLING
    ----------------------------------------------------- */
    const sections = document.querySelectorAll(
        "#dashboard, #profile, #attendance, #belt, #fees, #events, #announcements, #settings"
    );

    if ("IntersectionObserver" in window && sections.length > 0) {
        const sectionObserver = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    const currentId = "#" + entry.target.id;

                    navLinks.forEach(function (link) {
                        link.classList.remove("active");

                        if (link.getAttribute("href") === currentId) {
                            link.classList.add("active");
                        }
                    });
                });
            },
            {
                rootMargin: "-25% 0px -60% 0px",
                threshold: 0
            }
        );

        sections.forEach(function (section) {
            sectionObserver.observe(section);
        });
    }

    /* -----------------------------------------------------
       ESCAPE KEY - CLOSE SIDEBAR
    ----------------------------------------------------- */
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeSidebar();
        }
    });

    /* -----------------------------------------------------
       WINDOW RESIZE
    ----------------------------------------------------- */
    window.addEventListener("resize", function () {
        if (window.innerWidth > 900) {
            closeSidebar();
        }
    });

    /* -----------------------------------------------------
       PREVENT DOUBLE CLICKING PAYMENT
    ----------------------------------------------------- */
    payButtons.forEach(function (button) {
        button.addEventListener("dblclick", function (event) {
            event.preventDefault();
        });
    });

    /* -----------------------------------------------------
       INITIAL PAGE MESSAGE
    ----------------------------------------------------- */
    console.log(
        "Mass Dragon Dojo Student Portal loaded successfully."
    );
});
