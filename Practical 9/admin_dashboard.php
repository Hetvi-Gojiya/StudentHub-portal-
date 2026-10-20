<?php
session_start();

// Practical 10: 1. Restrict Unauthorized Access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=unauthorized");
    exit;
}

// Practical 10: 2. Role-Based Access Control (RBAC)
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    die("<h3>Access Denied:</h3><p>You do not have Administrator permissions. <a href='dashboard.php'>Back to Student Dashboard</a></p>");
}

// Practical 10: 3. Session Timeout
$timeout_duration = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

require_once 'db.php';
// Fetch stats for admin overview
$studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$eventCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$regCount = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$recentStudents = $pdo->query("SELECT id, full_name, email, role, last_login FROM students ORDER BY id DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>StudentHub Portal - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-banner {
            background: #2e7d32;
            color: white;
            padding: 15px 20px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h4 { margin: 0; color: #555; font-size: 14px; text-transform: uppercase; }
        .stat-card p { margin: 10px 0 0; font-size: 32px; font-weight: bold; color: #1b5e20; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #388e3c; color: white; }
    </style>
</head>

<body>

    <header>
        <h1>StudentHub Portal - Admin Panel</h1>
        <button id="themeBtn">Theme</button>
    </header>

    <hr>

    <main class="container">

        <div class="admin-banner">
            <div>
                <h2 style="margin: 0; color: white;">👑 Administrator Control Panel</h2>
                <p style="margin: 5px 0 0; font-size: 14px;">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Role: <strong><?php echo htmlspecialchars($_SESSION['user_role']); ?></strong>)</p>
            </div>
            <div>
                <a href="test_timeout.php" style="color: #c8e6c9; margin-right: 15px; font-size: 13px;">⏱️ Test Timeout</a>
                <a href="logout.php" style="background: #c62828; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-weight: bold;">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Users</h4>
                <p><?php echo $studentCount; ?></p>
            </div>
            <div class="stat-card">
                <h4>Campus Events</h4>
                <p><?php echo $eventCount; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Registrations</h4>
                <p><?php echo $regCount; ?></p>
            </div>
        </div>

        <section class="card">
            <h3>Registered Users & Last Login (Practical 10 RBAC)</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentStudents as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['id']); ?></td>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><span style="background: <?php echo $s['role'] === 'admin' ? '#c8e6c9' : '#e3f2fd'; ?>; padding: 3px 8px; border-radius: 4px; font-weight: bold;"><?php echo htmlspecialchars($s['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($s['last_login'] ?? 'Never'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <a href="view_records.php">View All Records</a>
        </section>

    </main>

    <script src="script.js"></script>
</body>

</html>
