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
$recentStudents = $pdo->query("SELECT id, full_name, email, role, last_login, created_at FROM students ORDER BY id DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Executive Admin Console</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Professional Executive Admin Dashboard Theme */
        :root {
            --admin-dark: #0f172a;
            --admin-slate: #1e293b;
            --admin-primary: #3b82f6;
            --admin-primary-dark: #1d4ed8;
            --admin-success: #10b981;
            --admin-card-bg: #ffffff;
            --admin-border: #e2e8f0;
            --admin-text-main: #0f172a;
            --admin-text-muted: #64748b;
        }

        body {
            background-color: #f1f5f9;
            color: var(--admin-text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .admin-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 16px 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .admin-header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-badge-icon {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #60a5fa;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .admin-header h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .admin-header p {
            margin: 2px 0 0;
            font-size: 13px;
            color: #94a3b8;
        }

        .admin-top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-outline-light {
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .btn-danger-soft {
            background: #ef4444;
            color: #ffffff;
        }

        .btn-danger-soft:hover {
            background: #dc2626;
        }

        .btn-primary-admin {
            background: #2563eb;
            color: white;
        }

        .btn-primary-admin:hover {
            background: #1d4ed8;
        }

        /* Container Layout */
        .admin-wrapper {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Hero Profile Strip */
        .admin-hero-card {
            background: white;
            border-radius: 12px;
            padding: 22px 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--admin-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }

        .admin-user-profile {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-avatar {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #60a5fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            border: 2px solid #e2e8f0;
        }

        .admin-user-details h2 {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
        }

        .admin-user-details p {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--admin-text-muted);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background: #10b981;
            border-radius: 50%;
        }

        /* KPI Metric Cards Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--admin-border);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .metric-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .metric-blue::before { background: #3b82f6; }
        .metric-purple::before { background: #8b5cf6; }
        .metric-emerald::before { background: #10b981; }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--admin-text-muted);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-icon {
            font-size: 20px;
        }

        .metric-value {
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
            margin: 12px 0 4px;
            line-height: 1;
        }

        .metric-footer {
            font-size: 12px;
            color: #64748b;
        }

        /* Data Panel & Table */
        .admin-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--admin-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header-bar {
            padding: 18px 24px;
            background: #ffffff;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-header-bar h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .table-container {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .admin-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 14px 20px;
            border-bottom: 1px solid var(--admin-border);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .admin-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .badge-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin {
            background-color: #ede9fe;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
        }

        .badge-student {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .time-tag {
            font-size: 12px;
            color: #64748b;
            font-family: monospace;
        }

        /* Quick Action Footer Strip */
        .quick-actions-bar {
            background: white;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
    </style>
</head>

<body>

    <!-- Professional Admin Top Navigation -->
    <header class="admin-header">
        <div class="admin-header-title">
            <div class="admin-badge-icon">🛡️</div>
            <div>
                <h1>StudentHub Console</h1>
                <p>Enterprise Administration & Role-Based Access Control</p>
            </div>
        </div>

        <div class="admin-top-actions">
            <a href="test_timeout.php" class="admin-btn btn-outline-light" title="Simulate session timeout for Practical 10">⏱️ Test Inactivity Timeout</a>
            <a href="logout.php" class="admin-btn btn-danger-soft">🚪 Sign Out</a>
        </div>
    </header>

    <div class="admin-wrapper">

        <!-- Executive Hero Profile Card -->
        <div class="admin-hero-card">
            <div class="admin-user-profile">
                <div class="admin-avatar">AD</div>
                <div class="admin-user-details">
                    <h2><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
                    <p><?php echo htmlspecialchars($_SESSION['user_email']); ?> • System Administrator</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="status-pill">
                    <span class="status-dot"></span>
                    Authenticated Session Active
                </span>
                <span style="font-size: 12px; color: var(--admin-text-muted);">
                    Role: <strong><?php echo strtoupper(htmlspecialchars($_SESSION['user_role'])); ?></strong>
                </span>
            </div>
        </div>

        <!-- KPI Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card metric-blue">
                <div class="metric-header">
                    <span>Registered Accounts</span>
                    <span class="metric-icon">👥</span>
                </div>
                <div class="metric-value"><?php echo $studentCount; ?></div>
                <div class="metric-footer">Total students & administrators</div>
            </div>

            <div class="metric-card metric-purple">
                <div class="metric-header">
                    <span>Campus Events</span>
                    <span class="metric-icon">📅</span>
                </div>
                <div class="metric-value"><?php echo $eventCount; ?></div>
                <div class="metric-footer">Active academic & cultural events</div>
            </div>

            <div class="metric-card metric-emerald">
                <div class="metric-header">
                    <span>Event Registrations</span>
                    <span class="metric-icon">🎟️</span>
                </div>
                <div class="metric-value"><?php echo $regCount; ?></div>
                <div class="metric-footer">Total participating students</div>
            </div>
        </div>

        <!-- Data Panel: User Management -->
        <div class="admin-card">
            <div class="card-header-bar">
                <div>
                    <h3>User Accounts & Security Auditing</h3>
                    <p style="margin: 3px 0 0; font-size: 13px; color: #64748b;">RBAC verified users with last active session timestamps</p>
                </div>
                <a href="view_records.php" class="admin-btn btn-primary-admin">View Complete Registry ➔</a>
            </div>

            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Access Role</th>
                            <th>Last Login Timestamp</th>
                            <th>Created On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentStudents as $s): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($s['id']); ?></strong></td>
                                <td style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($s['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td>
                                    <?php if ($s['role'] === 'admin'): ?>
                                        <span class="badge-role badge-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="badge-role badge-student">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['last_login'])): ?>
                                        <span class="time-tag">🕒 <?php echo htmlspecialchars($s['last_login']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="time-tag"><?php echo htmlspecialchars(substr($s['created_at'], 0, 10)); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Practical 10 Verification Quick Strip -->
        <div class="quick-actions-bar">
            <div>
                <strong style="font-size: 14px; color: #0f172a;">Practical 10 Verification Quick Tools:</strong>
                <span style="font-size: 13px; color: #64748b; margin-left: 8px;">Role-Based Access Control, Session Inactivity, and Termination</span>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="test_timeout.php" class="admin-btn btn-outline-light" style="color: #334155; border-color: #cbd5e1; background: #f8fafc;">⏱️ Trigger Timeout</a>
                <a href="logout.php" class="admin-btn btn-danger-soft">🚪 End Admin Session</a>
            </div>
        </div>

    </div>

</body>

</html>
