
/* ==========================================================
   StudentHub Portal
   Practical 4 + Practical 5 (Final Version)
   Beginner Friendly
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================
       Theme Switcher
    ===================================== */

    const themeBtn = document.getElementById("themeBtn");

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
       Mobile Menu
    ===================================== */

    const menuBtn = document.getElementById("menuBtn");
    const navbar = document.querySelector(".navbar");

    if (menuBtn && navbar) {

        menuBtn.addEventListener("click", function () {

            if (window.innerWidth <= 768) {

                navbar.classList.toggle("showMenu");

                menuBtn.textContent = navbar.classList.contains("showMenu")
                    ? "✖ Close"
                    : "☰ Menu";

            }

        });

    }

    /* =====================================
       Notification Banner
    ===================================== */

    const banner = document.getElementById("notificationBanner");
    const closeBanner = document.getElementById("closeBanner");

    if (banner && closeBanner) {

        closeBanner.addEventListener("click", function () {

            banner.style.display = "none";

        });

    }

    /* =====================================
       Dashboard Slider
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
       Modal Popup
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

        modal.addEventListener("click", function (e) {

            if (e.target === modal) {
                modal.style.display = "none";
            }

        });

    }

    /* =====================================
       Collapsible FAQ
    ===================================== */

    const faqButtons = document.querySelectorAll(".faqBtn");

    faqButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const answer = button.nextElementSibling;

            answer.style.display =
                answer.style.display === "block" ? "none" : "block";

        });

    });

    /* =====================================
       Registration Form
    ===================================== */

    const form = document.getElementById("registerForm");

    if (!form) return;

    const name = document.getElementById("name");
    const email = document.getElementById("email");
    const mobile = document.getElementById("mobile");
    const password = document.getElementById("regPassword");
    const confirmPassword = document.getElementById("confirmPassword");
    const course = document.getElementById("course");
    const year = document.getElementById("year");
    const terms = document.getElementById("terms");

    const strengthText = document.getElementById("strengthText");

    const nameRegex = /^[A-Za-z ]{3,}$/;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;
    const mobileRegex = /^[6-9][0-9]{9}$/;
    const passwordRegex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/;

    const error = (id, msg) => document.getElementById(id).textContent = msg;

    name.addEventListener("keyup", validateName);
    email.addEventListener("keyup", validateEmail);
    mobile.addEventListener("keyup", validateMobile);
    password.addEventListener("keyup", validatePassword);
    confirmPassword.addEventListener("keyup", validateConfirm);

    course.addEventListener("change", validateCourse);
    year.addEventListener("change", validateYear);
    const genderRadios = document.querySelectorAll('input[name="gender"]');
    genderRadios.forEach(radio => radio.addEventListener("change", validateGender));
    terms.addEventListener("change", validateTerms);

    let captchaCode = "";
    function generateCaptcha() {
        const canvas = document.getElementById("captchaCanvas");
        if (!canvas) return;
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = "20px Arial";
        ctx.fillStyle = "#333";
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789";
        captchaCode = "";
        for (let i = 0; i < 5; i++) {
            captchaCode += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        ctx.fillText(captchaCode, 20, 28);
    }
    
    generateCaptcha();

    form.addEventListener("submit", function (e) {

        e.preventDefault();

        const isCaptchaValid = validateCaptcha();

        const ok =
            validateName() &
            validateEmail() &
            validateMobile() &
            validatePassword() &
            validateConfirm() &
            validateCourse() &
            validateYear() &
            validateGender() &
            validateTerms() &
            isCaptchaValid;

        if (ok) {
            // If all frontend validations pass, submit the form to PHP
            form.submit();
        }

    });

    function validateName() {

        const v = name.value.trim();

        if (nameRegex.test(v)) {
            error("nameError", "");
            return true;
        }

        error("nameError", "Enter a valid name.");
        return false;

    }

    function validateEmail() {

        const v = email.value.trim().toLowerCase();

        if (emailRegex.test(v)) {
            error("emailError", "");
            return true;
        }

        error("emailError", "Enter a valid email.");
        return false;

    }

    function validateMobile() {

        if (mobileRegex.test(mobile.value.trim())) {
            error("mobileError", "");
            return true;
        }

        error("mobileError", "Enter a valid 10-digit mobile number.");
        return false;

    }

    function validatePassword() {

        const v = password.value;

        if (v.length < 8) {

            strengthText.textContent = "Weak Password";
            strengthText.style.color = "red";

        } else if (passwordRegex.test(v)) {

            strengthText.textContent = "Strong Password";
            strengthText.style.color = "green";

        } else {

            strengthText.textContent = "Medium Password";
            strengthText.style.color = "orange";

        }

        if (passwordRegex.test(v)) {

            error("passwordError", "");
            return true;

        }

        error("passwordError",
            "8+ characters, uppercase, lowercase and number required.");

        return false;

    }

    function validateConfirm() {

        if (
            confirmPassword.value !== "" &&
            confirmPassword.value === password.value
        ) {

            error("confirmError", "");
            return true;

        }

        error("confirmError", "Passwords do not match.");
        return false;

    }

    function validateCourse() {

        if (course.value !== "") {
            error("courseError", "");
            return true;
        }

        error("courseError", "Select a course.");
        return false;

    }

    function validateYear() {

        if (year.value !== "") {
            error("yearError", "");
            return true;
        }

        error("yearError", "Select a year.");
        return false;

    }

    function validateGender() {

        const gender = document.querySelector(
            'input[name="gender"]:checked'
        );

        if (gender) {
            error("genderError", "");
            return true;
        }

        error("genderError", "Select gender.");
        return false;

    }

    function validateTerms() {

        if (terms.checked) {
            error("termsError", "");
            return true;
        }

        error("termsError", "Accept terms first.");
        return false;

    }

    function validateCaptcha() {
        const input = document.getElementById("captchaInput");
        if (input && input.value === captchaCode) {
            error("captchaError", "");
            return true;
        }
        error("captchaError", "Invalid CAPTCHA.");
        generateCaptcha(); // Regenerate if incorrect
        if(input) input.value = "";
        return false;
    }

});

/* =====================================
   Login Page
===================================== */

function togglePassword() {

    const password = document.getElementById("password");

    if (!password) return;

    password.type = password.type === "password"
        ? "text"
        : "password";

}

function loginSuccess() {

    const username = document.getElementById("username");
    const password = document.getElementById("password");

    if (!username || !password) return;

    const email = username.value.trim().toLowerCase();
    const pass = password.value;

    if (email === "" || pass === "") {

        alert("Please fill all fields.");
        return;

    }

    const savedEmail = localStorage.getItem("studentEmail");
    const savedPassword = localStorage.getItem("studentPassword");

    if (!savedEmail || !savedPassword) {

        alert("No registered account found. Please register first.");
        window.location.href = "register.html";
        return;

    }

    if (email === savedEmail && pass === savedPassword) {

        alert("Login Successful!");
        window.location.href = "dashboard.html";

    } else {

        alert("Invalid Email or Password.");

    }

}
