# 📚 StudentHub — Full-Stack Campus Management Portal

> A complete full-stack web application for student and event management, built with PHP, MySQL, HTML/CSS/JS, and deployed on XAMPP.

---

## 🚀 Features

| Module                  | Description                                                      |
|-------------------------|------------------------------------------------------------------|
| **User Registration**   | Secure signup with password hashing (bcrypt), duplicate checks   |
| **Login & Sessions**    | Session management, session timeout (10 min), remember-me token  |
| **Role-Based Access**   | Admin & Student roles with RBAC-protected pages                  |
| **Admin Dashboard**     | Live stats, sidebar nav, student status toggle, Chart.js analytics |
| **Student CRUD**        | Add, edit, soft-delete, restore, search, filter, CSV export      |
| **Event Management**    | Full CRUD with poster upload, file validation (2MB, JPG/PNG/WebP)|
| **Live Events API**     | RESTful JSON API (GET/POST/PUT/DELETE) consumed by Fetch API     |
| **AJAX Operations**     | No-page-reload CRUD, debounced search, toast notifications       |
| **Audit Logging**       | Tracks login, logout, status changes with IP and timestamps      |
| **Security**            | PDO prepared statements, XSS prevention, CSRF, upload validation |

---

## 🛠 Technology Stack

| Layer        | Technology                        |
|--------------|-----------------------------------|
| Frontend     | HTML5, CSS3, JavaScript (ES6+)    |
| Backend      | PHP 8.2                           |
| Database     | MySQL (MariaDB via XAMPP)         |
| Server       | Apache (XAMPP)                    |
| Charts       | Chart.js 4.x                     |
| Fonts        | Google Fonts (Inter)              |

---

## 📁 Project Structure

```
StudentHub/
├── studenthub.sql                # Database schema + seed data
├── studenthub_db_dump.sql        # Full database dump (latest)
├── README.md                     # This file
├── test_report.php               # Automated test report page
│
└── htmlfiles/                    # All application files
    ├── db.php                    # PDO database connection
    ├── style.css                 # Global stylesheet
    ├── script.js                 # Shared JavaScript
    │
    ├── register.php              # Student registration form
    ├── process_register.php      # Registration handler (bcrypt)
    ├── login.php                 # Login page with alerts
    ├── process_login.php         # Login handler (sessions, RBAC)
    ├── logout.php                # Secure session termination
    │
    ├── dashboard.php             # Student dashboard
    ├── admin_dashboard.php       # Admin console (RBAC protected)
    ├── view_records.php          # Student CRUD with search/filter
    ├── manage_events.php         # Event CRUD with file upload
    │
    ├── api_events.php            # JSON API endpoint (REST)
    ├── events_live.php           # AJAX/Fetch-based event manager
    │
    ├── test_db.php               # Database connection test
    ├── test_timeout.php          # Session timeout demo
    │
    └── uploads/                  # Event poster uploads
        └── .htaccess             # Blocks direct PHP execution
```

---

## ⚡ Quick Start — Installation

### Prerequisites
- **XAMPP** installed with Apache and MySQL

### Step 1 — Clone or Copy
```bash
# Option A: Clone from GitHub
git clone https://github.com/YOUR_USERNAME/StudentHub.git

# Option B: Copy the StudentHub folder to XAMPP
# Copy entire StudentHub/ folder to C:\xampp\htdocs\
```

### Step 2 — Import Database
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Create a new database named: `studenthub_db`
3. Click **Import** tab
4. Select `studenthub_db_dump.sql` (or `studenthub.sql`)
5. Click **Go**

### Step 3 — Configure Database
Edit `htmlfiles/db.php` if needed:
```php
$host     = 'localhost';
$dbname   = 'studenthub_db';
$username = 'root';
$db_pass  = '';  // default XAMPP password is empty
```

### Step 4 — Start & Access
1. Open **XAMPP Control Panel**
2. Start **Apache** and **MySQL**
3. Open browser: http://localhost/StudentHub/htmlfiles/login.php

### Default Accounts
| Role    | Email                    | Password     |
|---------|--------------------------|--------------|
| Admin   | admin@studenthub.com     | Admin@123    |
| Student | hetvi90233@gmail.com     | (as registered) |

---

## 🔐 Security Checklist

- [x] Password hashing with `password_hash()` and `password_verify()` (bcrypt)
- [x] PDO Prepared Statements for all SQL queries (no SQL injection)
- [x] `htmlspecialchars()` on all user output (XSS prevention)
- [x] `session_regenerate_id(true)` after login (session fixation prevention)
- [x] Session timeout after 10 minutes of inactivity
- [x] File upload: MIME type + extension validation, 2MB size limit
- [x] Upload directory: `.htaccess` blocks PHP execution
- [x] Unique filenames for uploads (prevents overwrite attacks)
- [x] Role-based access control on admin pages
- [x] Audit logging of all admin actions with IP tracking

---

## ♿ Accessibility Checklist

- [x] Semantic HTML5 elements (`<header>`, `<main>`, `<nav>`, `<section>`)
- [x] Proper heading hierarchy (`<h1>` → `<h2>` → `<h3>`)
- [x] Form labels associated with inputs
- [x] Color contrast meets WCAG AA standards
- [x] Responsive design (mobile-friendly layout)
- [x] Keyboard-navigable forms and buttons
- [x] `alt` attributes on images
- [x] ARIA labels on interactive elements

---

## 🧪 Testing

Access the automated test report at:
```
http://localhost/StudentHub/test_report.php
```

This verifies:
- Database connectivity
- Table structure and integrity
- User authentication flow
- RBAC enforcement
- API endpoint responses
- File upload directory security

---

## 📊 Database Schema

```
students (id, full_name, email, mobile, password, course, year, gender, role, status, last_login, remember_token, created_at)
events (id, title, description, event_date, poster, venue, category, status, location, max_participants, created_at)
registrations (id, student_id, event_id, registered_at)
audit_logs (id, admin_id, admin_name, action, target_type, target_id, details, ip_address, created_at)
```

---

## 📋 Pages & Routes

| URL Path                          | Access   | Description                  |
|-----------------------------------|----------|------------------------------|
| `/htmlfiles/login.php`            | Public   | Login page                   |
| `/htmlfiles/register.php`         | Public   | Registration page            |
| `/htmlfiles/dashboard.php`        | Student  | Student dashboard            |
| `/htmlfiles/admin_dashboard.php`  | Admin    | Admin console                |
| `/htmlfiles/view_records.php`     | Admin    | Student CRUD management      |
| `/htmlfiles/manage_events.php`    | Admin    | Event CRUD + file upload     |
| `/htmlfiles/events_live.php`      | Admin    | AJAX event manager           |
| `/htmlfiles/api_events.php`       | API      | JSON REST API endpoint       |
| `/htmlfiles/logout.php`           | Auth     | Session termination          |

---

## 👩‍💻 Author

**Hetvi** — B.Tech Student  
Built as part of Web Development Lab (Semester Project)

---

## 📄 License

This project is for academic/educational purposes.
