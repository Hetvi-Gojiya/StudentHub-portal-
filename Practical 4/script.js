/* ==========================================
   StudentHub Portal - Practical 4
   JavaScript DOM, Event Handling & UI
   Beginner Friendly
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================
       1. HAMBURGER MENU
    ===================================== */

    const menuBtn = document.getElementById("menuBtn");
    const navbar = document.querySelector(".navbar");

    if (menuBtn && navbar) {

        menuBtn.addEventListener("click", function () {

            navbar.classList.toggle("hiddenMenu");

            if (navbar.classList.contains("hiddenMenu")) {
                menuBtn.textContent = "☰ Menu";
            } else {
                menuBtn.textContent = "✖ Close";
            }

        });

    }

    /* =====================================
       2. DARK/LIGHT THEME
    ===================================== */

    const themeBtn = document.getElementById("themeBtn");

    // Apply saved theme
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark-mode");

        if (themeBtn) {
            themeBtn.textContent = "☀ Light";
        }
    }

    if (themeBtn) {

        themeBtn.addEventListener("click", function () {

            document.body.classList.toggle("dark-mode");

            if (document.body.classList.contains("dark-mode")) {

                localStorage.setItem("theme", "dark");
                themeBtn.textContent = "☀ Light";

            } else {

                localStorage.setItem("theme", "light");
                themeBtn.textContent = "🌙 Dark";

            }

        });

    }

    /* =====================================
       3. NOTIFICATION BANNER
    ===================================== */

    const banner = document.getElementById("notificationBanner");
    const closeBanner = document.getElementById("closeBanner");

    if (banner && closeBanner) {

        closeBanner.addEventListener("click", function () {

            banner.style.display = "none";

        });

    }

    /* =====================================
       4. CONTENT SLIDER
    ===================================== */

    const slideText = document.getElementById("slideText");
    const nextSlide = document.getElementById("nextSlide");

    const slides = [
        "Hackathon Registration - Friday",
        "Semester Examination Form Available",
        "Cultural Fest Coming Soon"
    ];

    let slideIndex = 0;

    if (slideText && nextSlide) {

        nextSlide.addEventListener("click", function () {

            slideIndex++;

            if (slideIndex >= slides.length) {
                slideIndex = 0;
            }

            slideText.textContent = slides[slideIndex];

        });

    }

    /* =====================================
       5. MODAL POPUP
    ===================================== */

    const openModal = document.getElementById("openModal");
    const modal = document.getElementById("modal");
    const closeModal = document.getElementById("closeModal");

    if (openModal && modal && closeModal) {

        openModal.addEventListener("click", function () {

            modal.style.display = "flex";

        });

        closeModal.addEventListener("click", function () {

            modal.style.display = "none";

        });

        // Close when clicking outside the box
        modal.addEventListener("click", function (event) {

            if (event.target === modal) {
                modal.style.display = "none";
            }

        });

    }

    /* =====================================
       6. COLLAPSIBLE FAQ
    ===================================== */

    const faqButtons = document.querySelectorAll(".faqBtn");

    faqButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const answer = button.nextElementSibling;

            if (answer.style.display === "block") {

                answer.style.display = "none";

            } else {

                answer.style.display = "block";

            }

        });

    });

});


/* =====================================
   LOGIN PAGE FUNCTIONS
===================================== */

// Show or Hide Password
function togglePassword() {

    const password = document.getElementById("password");

    if (password) {

        if (password.type === "password") {

            password.type = "text";

        } else {

            password.type = "password";

        }

    }

}


// Login Validation
function loginSuccess() {

    const username = document.getElementById("username");
    const password = document.getElementById("password");

    if (!username || !password) return;

    if (username.value.trim() === "" || password.value.trim() === "") {

        alert("Please fill all fields.");

        return;

    }

    alert("Login Successful!");

    window.location.href = "dashboard.html";

}