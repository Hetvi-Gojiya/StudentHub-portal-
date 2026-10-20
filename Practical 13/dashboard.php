<?php
session_start();

// Practical 10: 1. Restrict Unauthorized Dashboard Access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=unauthorized");
    exit;
}

// Practical 10: 2. Role-Based Access Control (Admins redirected to admin dashboard)
if (($_SESSION['user_role'] ?? 'student') === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

// Practical 10: 3. Session Timeout (10 minutes = 600s)
$timeout_duration = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time(); // Refresh last active timestamp
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>StudentHub Portal - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-greeting-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            background: #e3f2fd;
            border-left: 5px solid #1976d2;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .user-greeting-box p {
            margin: 0;
            color: #0d47a1;
            font-size: 16px;
        }
        .logout-btn-header {
            background-color: #d32f2f;
            color: white;
            padding: 6px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }
        .logout-btn-header:hover {
            background-color: #b71c1c;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <h1>StudentHub Portal</h1>

        <button id="menuBtn">☰ Menu</button>
        <button id="themeBtn">Theme</button>

        <nav class="navbar">
            <a href="dashboard.php">Dashboard</a> |
            <a href="myschedule.html">My Schedule</a> |
            <a href="bunkmeter.html">Bunk Meter</a> |
            <a href="studyspace.html">Study Space</a> |
            <a href="campusbuddy.html">Campus Buddy</a> |
            <a href="taskplanner.html">Task Planner</a> |
            <a href="myprogress.html">My Progress</a> |
            <a href="leavetrack.html">Leave Track</a> |
            <a href="feedesk.html">Fee Desk</a> |
            <a href="myprofile.html">My Profile</a> |
            <a href="logout.php" style="color: #ff5252; font-weight: bold;">Logout</a>
        </nav>

        <hr>
    </header>

    <!-- Main Content -->
    <main class="container">

        <!-- Practical 10: Dynamic User Greeting & Session Status -->
        <div class="user-greeting-box">
            <p>
                👤 Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! 
                <span style="font-size: 13px; color: #555;">(Role: <strong><?php echo htmlspecialchars($_SESSION['user_role']); ?></strong> | Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?>)</span>
            </p>
            <div>
                <a href="test_timeout.php" style="font-size: 12px; color: #666; margin-right: 15px;" title="Click to test session timeout for Practical 10">⏱️ Test Timeout</a>
                <a href="logout.php" class="logout-btn-header">🚪 Logout</a>
            </div>
        </div>

        <h2>Dashboard</h2>

        <div id="notificationBanner" class="card">
            Semester Examination Form is now available.
            <button id="closeBanner">X</button>
        </div>

        <!-- Next Class -->
        <section class="card">
            <h3>Next Class</h3>
            <table border="1">
                <tr>
                    <th>Subject</th>
                    <th>Time</th>
                    <th>Room</th>
                </tr>
                <tr>
                    <td>Java</td>
                    <td>10:30 AM</td>
                    <td>323</td>
                </tr>
            </table>
        </section>

        <br>

        <!-- Attendance -->
        <section class="card">
            <h3>Attendance</h3>
            <table border="1">
                <tr>
                    <th>Current Attendance</th>
                </tr>
                <tr>
                    <td>82%</td>
                </tr>
            </table>
        </section>

        <br>

        <!-- Today's Task -->
        <section class="card">
            <h3>Today's Task</h3>
            <ul>
                <li>Complete Java Assignment</li>
                <li>Read DBMS Notes</li>
                <li>Practice DSA Questions</li>
            </ul>
        </section>

        <br>

        <!-- Upcoming Event -->
        <section class="card">
            <h3>Upcoming Event</h3>
            <p id="slideText">Hackathon Registration - Friday</p>
            <button id="nextSlide">Next</button>
        </section>

        <br>

        <!-- Campus Announcement -->
        <section class="card">
            <h3>Campus Announcement</h3>
            <p>Semester Examination Form is now available.</p>
            <button id="openModal">View Details</button>

            <div id="modal" class="modal">
                <div class="modal-box">
                    <h3>Announcement</h3>
                    <p>Semester Examination Form is now available.</p>
                    <button id="closeModal">Close</button>
                </div>
            </div>
        </section>

    </main>

    <hr>

    <!-- Footer -->
    <footer class="footer">
        <p>StudentHub Portal</p>
    </footer>

    <script src="script.js"></script>
</body>

</html>
