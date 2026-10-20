# 📚 StudentHub — Full-Stack Campus Management Portal

> A comprehensive full-stack web application for student and campus event management, built with PHP, MySQL, HTML5/CSS3/JavaScript, and deployed on XAMPP.

---

## 🚀 Features

| Module | Description |
| :--- | :--- |
| **User Authentication** | Secure registration & login with bcrypt password hashing (`password_hash` / `password_verify`). |
| **Session Management** | Auto session timeout (10 minutes of inactivity), `session_regenerate_id()` against fixation, remember-me token support. |
| **Role-Based Access Control (RBAC)** | Strict access separation between Admin and Student roles with automatic redirection. |
| **Admin Dashboard** | Dynamic statistics cards, live user counters, student status toggling (Active/Inactive), and Chart.js analytics. |
| **Student Record Management** | Full CRUD capabilities, search, filtering by department/status, soft deletion, and CSV export. |
| **Event Management** | Event creation and editing with secure poster file uploads (MIME validation, 2MB limit, unique file naming). |
| **Live RESTful JSON API** | Full REST API (`GET`, `POST`, `PUT`, `DELETE`) with standard HTTP status codes (`200`, `201`, `400`, `404`). |
| **AJAX Operations** | Asynchronous Fetch API CRUD operations without page refreshes, debounced search, and toast alerts. |
| **System Audit Logging** | Dedicated audit trail recording admin logins, logouts, status modifications with IP tracking and timestamps. |
| **Security Hardening** | 100% PDO prepared statements, XSS output sanitization (`htmlspecialchars`), and upload directory execution blocking (`.htaccess`). |

---

## 🛠 Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript (ES6+), Google Fonts (Inter) |
| **Data Visualization** | Chart.js 4.x |
| **Backend** | PHP 8.2+ (Object-Oriented & Procedural PDO) |
| **Database** | MySQL / MariaDB via XAMPP |
| **Web Server** | Apache 2.4 (XAMPP Environment) |
| **Version Control** | Git & GitHub |

---

## 📁 Project Structure

```text
StudentHub/
├── studenthub.sql                # Base database schema & initial seed data
├── studenthub_db_dump.sql        # Full database export with latest tables & logs
├── README.md                     # Comprehensive project documentation
├── test_report.php               # Automated integration test runner (43 tests)
│
└── htmlfiles/                    # Core application files
    ├── db.php                    # Centralized PDO database connection
    ├── style.css                 # Global modern UI stylesheet
    ├── script.js                 # Shared JavaScript utilities
    │
    ├── register.php              # User registration interface
    ├── process_register.php      # Registration processor with duplicate checks & bcrypt
    ├── login.php                 # Secure login interface
    ├── process_login.php         # Authentication handler with session & RBAC control
    ├── logout.php                # Session destruction and audit logging
    │
    ├── dashboard.php             # Protected student user dashboard
    ├── admin_dashboard.php       # Protected admin dashboard with analytics & audit trail
    ├── view_records.php          # Student CRUD management portal
    ├── manage_events.php         # Event management with secure file uploads
    │
    ├── api_events.php            # RESTful JSON API endpoint (GET/POST/PUT/DELETE)
    ├── events_live.php           # AJAX/Fetch-driven real-time event interface
    │
    ├── test_db.php               # Database connection verification script
    ├── test_timeout.php          # Session timeout testing utility
    │
    └── uploads/                  # Uploaded event posters
        └── .htaccess             # Security rule preventing direct PHP execution
