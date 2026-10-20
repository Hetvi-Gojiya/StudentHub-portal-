<?php
session_start();

// 1. Restrict Unauthorized Access — redirect non-logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=unauthorized");
    exit;
}

// 2. Role-Based Access Control — admin only
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php?msg=access_denied");
    exit;
}

// 3. Session Timeout (10 minutes inactivity)
$timeout_duration = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

require_once 'db.php';

// ---------------------------------------------------------------
// Helper: Write Audit Log
// ---------------------------------------------------------------
function writeAuditLog($pdo, $action, $targetType = null, $targetId = null, $details = null) {
    $adminId   = $_SESSION['user_id']   ?? 0;
    $adminName = $_SESSION['user_name'] ?? 'Admin';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (admin_id, admin_name, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$adminId, $adminName, $action, $targetType, $targetId, $details, $ip]);
}

// ---------------------------------------------------------------
// Handle: Toggle User Active/Inactive Status (AJAX)
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    header('Content-Type: application/json');
    $userId    = (int)($_POST['user_id'] ?? 0);
    $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

    if ($userId > 0) {
        $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ? AND role != 'admin'");
        $stmt->execute([$newStatus, $userId]);

        // Fetch updated name for audit log
        $user = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
        $user->execute([$userId]);
        $u = $user->fetch();
        writeAuditLog($pdo, 'TOGGLE_STATUS', 'students', $userId,
            "Changed student '{$u['full_name']}' status to $newStatus");

        echo json_encode(['success' => true, 'new_status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    }
    exit;
}

// ---------------------------------------------------------------
// Log dashboard view
// ---------------------------------------------------------------
writeAuditLog($pdo, 'VIEW_DASHBOARD', 'admin_dashboard', null, 'Admin viewed dashboard');

// ---------------------------------------------------------------
// Fetch Dashboard Stats
// ---------------------------------------------------------------
$totalStudents  = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student'")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student' AND status = 'active'")->fetchColumn();
$totalEvents    = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcomingEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
$totalRegs      = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$totalAdmins    = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'admin'")->fetchColumn();

// Recent students (latest 8)
$recentStudents = $pdo->query("
    SELECT id, full_name, email, course, role, status, created_at, last_login
    FROM students
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll();

// Recent events (latest 5)
$recentEvents = $pdo->query("
    SELECT id, title, category, event_date, status
    FROM events
    ORDER BY event_date DESC
    LIMIT 5
")->fetchAll();

// Audit logs (latest 15)
$auditLogs = $pdo->query("
    SELECT id, admin_name, action, target_type, target_id, details, ip_address, created_at
    FROM audit_logs
    ORDER BY created_at DESC
    LIMIT 15
")->fetchAll();

// Chart data: Event registrations count by month (last 6 months)
$chartData = $pdo->query("
    SELECT DATE_FORMAT(event_date, '%b %Y') as month_label,
           COUNT(*) as event_count
    FROM events
    WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(event_date, '%Y-%m'), DATE_FORMAT(event_date, '%b %Y')
    ORDER BY MIN(event_date)
")->fetchAll();

$chartLabels = json_encode(array_column($chartData, 'month_label'));
$chartValues = json_encode(array_column($chartData, 'event_count'));

// Dynamic menu based on role
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub — Admin Console</title>
    <meta name="description" content="StudentHub Administrator Console — Manage students, events, and system access.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0f1117;
            --sidebar:  #13161f;
            --surface:  #1a1d27;
            --surface2: #22263a;
            --border:   #2a2f45;
            --accent:   #6366f1;
            --accent2:  #818cf8;
            --green:    #10b981;
            --red:      #ef4444;
            --yellow:   #f59e0b;
            --blue:     #3b82f6;
            --purple:   #a855f7;
            --text:     #e2e8f0;
            --muted:    #94a3b8;
            --radius:   14px;
            --shadow:   0 4px 24px rgba(0,0,0,.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ─── Sidebar ─── */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
        }
        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-brand h2 {
            font-size: 18px;
            font-weight: 800;
            background: linear-gradient(90deg, #818cf8, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .sidebar-brand p {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
        }

        .sidebar-section {
            padding: 16px 12px 4px;
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            margin: 2px 8px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
            transition: all .2s;
            cursor: pointer;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(99,102,241,.12);
            color: var(--accent2);
        }
        .nav-item.active { border-left: 3px solid var(--accent); padding-left: 13px; }
        .nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; }
        .nav-badge {
            margin-left: auto;
            background: var(--accent);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 12px;
            border-top: 1px solid var(--border);
        }
        .admin-avatar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--surface2);
            border-radius: 10px;
        }
        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            color: #fff;
            flex-shrink: 0;
        }
        .avatar-name { font-size: 13px; font-weight: 600; }
        .avatar-role { font-size: 11px; color: var(--muted); }

        /* ─── Main Content ─── */
        .main-content {
            flex: 1;
            padding: 28px 32px;
            overflow-x: hidden;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
        }
        .page-header .subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-top: 2px;
        }
        .header-actions { display: flex; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #5558e8; transform: translateY(-1px); }
        .btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text); border-color: var(--accent); }
        .btn-danger { background: rgba(239,68,68,.15); color: var(--red); border: 1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background: rgba(239,68,68,.25); }
        .btn-sm { padding: 5px 12px; font-size: 12px; }

        /* ─── Stats Grid ─── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.blue::before   { background: var(--blue); }
        .stat-card.green::before  { background: var(--green); }
        .stat-card.purple::before { background: var(--purple); }
        .stat-card.yellow::before { background: var(--yellow); }
        .stat-card.red::before    { background: var(--red); }
        .stat-card.indigo::before { background: var(--accent); }
        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
            display: block;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-card.blue   .stat-value { color: var(--blue); }
        .stat-card.green  .stat-value { color: var(--green); }
        .stat-card.purple .stat-value { color: var(--purple); }
        .stat-card.yellow .stat-value { color: var(--yellow); }
        .stat-card.red    .stat-value { color: var(--red); }
        .stat-card.indigo .stat-value { color: var(--accent2); }
        .stat-label { font-size: 12px; color: var(--muted); font-weight: 500; }
        .stat-sub   { font-size: 11px; color: var(--muted); margin-top: 6px; }

        /* ─── Dashboard Grid ─── */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-title {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-body { padding: 0; }

        /* ─── Table ─── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table thead th {
            padding: 11px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }
        .data-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,.04);
            transition: background .15s;
        }
        .data-table tbody tr:hover { background: rgba(99,102,241,.05); }
        .data-table tbody td { padding: 11px 16px; vertical-align: middle; }

        /* ─── Badges ─── */
        .badge {
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            white-space: nowrap;
        }
        .badge-active   { background: rgba(16,185,129,.15); color: #34d399; }
        .badge-inactive { background: rgba(239,68,68,.15);  color: #fca5a5; }
        .badge-admin    { background: rgba(99,102,241,.15); color: var(--accent2); }
        .badge-student  { background: rgba(59,130,246,.15); color: #93c5fd; }
        .badge-upcoming  { background: rgba(99,102,241,.15); color: var(--accent2); }
        .badge-ongoing   { background: rgba(16,185,129,.15); color: #34d399; }
        .badge-completed { background: rgba(148,163,184,.15); color: var(--muted); }
        .badge-cancelled { background: rgba(239,68,68,.15); color: #fca5a5; }
        .badge-open      { background: rgba(16,185,129,.15); color: #34d399; }

        /* ─── Toggle Switch ─── */
        .toggle-wrap { display: flex; align-items: center; gap: 8px; }
        .toggle-switch {
            position: relative;
            width: 40px;
            height: 22px;
            cursor: pointer;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: rgba(239,68,68,.3);
            border-radius: 20px;
            transition: .3s;
            border: 1px solid rgba(239,68,68,.5);
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 2px;
            top: 2px;
            background: var(--red);
            border-radius: 50%;
            transition: .3s;
        }
        input:checked + .toggle-slider { background: rgba(16,185,129,.3); border-color: rgba(16,185,129,.5); }
        input:checked + .toggle-slider::before { transform: translateX(18px); background: var(--green); }
        .toggle-label { font-size: 11px; font-weight: 600; min-width: 48px; }
        .toggle-label.active   { color: var(--green); }
        .toggle-label.inactive { color: var(--red); }

        /* ─── Audit Log ─── */
        .audit-entry {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,.04);
            font-size: 12px;
            align-items: flex-start;
        }
        .audit-entry:last-child { border-bottom: none; }
        .audit-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .audit-LOGIN        { background: rgba(16,185,129,.15); }
        .audit-LOGOUT       { background: rgba(239,68,68,.12); }
        .audit-VIEW_DASHBOARD  { background: rgba(99,102,241,.12); }
        .audit-VIEW_STUDENTS   { background: rgba(59,130,246,.12); }
        .audit-VIEW_EVENTS     { background: rgba(245,158,11,.12); }
        .audit-TOGGLE_STATUS   { background: rgba(168,85,247,.12); }
        .audit-default      { background: rgba(148,163,184,.1); }
        .audit-action { font-weight: 700; color: var(--text); margin-bottom: 2px; }
        .audit-details { color: var(--muted); line-height: 1.4; }
        .audit-time { color: var(--muted); font-size: 11px; white-space: nowrap; margin-left: auto; }

        /* ─── Chart ─── */
        .chart-container { padding: 20px; height: 220px; }

        /* ─── Recent Events list ─── */
        .event-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            border-bottom: 1px solid rgba(255,255,255,.04);
            font-size: 13px;
        }
        .event-row:last-child { border-bottom: none; }
        .event-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .dot-upcoming  { background: var(--accent2); }
        .dot-ongoing   { background: var(--green); }
        .dot-completed { background: var(--muted); }
        .dot-cancelled { background: var(--red); }
        .dot-open      { background: var(--green); }
        .event-name { flex: 1; font-weight: 600; }
        .event-meta { color: var(--muted); font-size: 11px; }

        /* ─── User info cell ─── */
        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-av {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }
        .user-name  { font-weight: 600; font-size: 13px; }
        .user-email { color: var(--muted); font-size: 11px; }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
    </style>
</head>
<body>

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2>⚡ StudentHub</h2>
        <p>Administrator Console</p>
    </div>

    <!-- Dynamic Menu based on admin role -->
    <div class="sidebar-section">Overview</div>
    <a href="admin_dashboard.php" class="nav-item active">
        <span class="nav-icon">🏠</span> Dashboard
    </a>

    <div class="sidebar-section">Management</div>
    <a href="view_records.php" class="nav-item">
        <span class="nav-icon">👥</span> Students
        <span class="nav-badge"><?= $totalStudents ?></span>
    </a>
    <a href="manage_events.php" class="nav-item">
        <span class="nav-icon">📅</span> Events
        <span class="nav-badge"><?= $totalEvents ?></span>
    </a>
    <a href="events_live.php" class="nav-item">
        <span class="nav-icon">⚡</span> Live Events API
    </a>

    <div class="sidebar-section">System</div>
    <a href="#audit-section" class="nav-item">
        <span class="nav-icon">📋</span> Audit Logs
        <span class="nav-badge"><?= count($auditLogs) ?>+</span>
    </a>
    <a href="test_timeout.php" class="nav-item">
        <span class="nav-icon">⏱️</span> Session Test
    </a>

    <div class="sidebar-footer">
        <div class="admin-avatar">
            <div class="avatar-circle"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
            <div>
                <div class="avatar-name"><?= htmlspecialchars($adminName) ?></div>
                <div class="avatar-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-danger btn-sm" style="width:100%;justify-content:center;margin-top:10px;">
            🚪 Sign Out
        </a>
    </div>
</aside>

<!-- ════════════════ MAIN ════════════════ -->
<main class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="subtitle">Welcome back, <?= htmlspecialchars($adminName) ?> · <?= date('l, d M Y') ?></p>
        </div>
        <div class="header-actions">
            <a href="view_records.php" class="btn btn-ghost">👥 Manage Students</a>
            <a href="manage_events.php" class="btn btn-primary">📅 Manage Events</a>
        </div>
    </div>

    <!-- ── Stats Cards ── -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <span class="stat-icon">👥</span>
            <div class="stat-value"><?= $totalStudents ?></div>
            <div class="stat-label">Total Students</div>
            <div class="stat-sub"><?= $activeStudents ?> active accounts</div>
        </div>
        <div class="stat-card green">
            <span class="stat-icon">✅</span>
            <div class="stat-value"><?= $activeStudents ?></div>
            <div class="stat-label">Active Students</div>
            <div class="stat-sub"><?= $totalStudents - $activeStudents ?> inactive</div>
        </div>
        <div class="stat-card purple">
            <span class="stat-icon">📅</span>
            <div class="stat-value"><?= $totalEvents ?></div>
            <div class="stat-label">Total Events</div>
            <div class="stat-sub"><?= $upcomingEvents ?> upcoming</div>
        </div>
        <div class="stat-card yellow">
            <span class="stat-icon">📝</span>
            <div class="stat-value"><?= $totalRegs ?></div>
            <div class="stat-label">Registrations</div>
            <div class="stat-sub">All time</div>
        </div>
        <div class="stat-card indigo">
            <span class="stat-icon">🔑</span>
            <div class="stat-value"><?= $totalAdmins ?></div>
            <div class="stat-label">Admin Users</div>
            <div class="stat-sub">Role: admin</div>
        </div>
        <div class="stat-card red">
            <span class="stat-icon">⚡</span>
            <div class="stat-value"><?= count($auditLogs) ?>+</div>
            <div class="stat-label">Audit Entries</div>
            <div class="stat-sub">Recent activity</div>
        </div>
    </div>

    <!-- ── Main Dashboard Grid ── -->
    <div class="dashboard-grid">

        <!-- Students Table with Toggle -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">👥 Recent Students <small style="font-weight:400;color:var(--muted);margin-left:6px;">(Toggle to activate/deactivate)</small></span>
                <a href="view_records.php" class="btn btn-ghost btn-sm">View All →</a>
            </div>
            <div class="card-body">
                <table class="data-table" id="students-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Status Toggle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentStudents as $s): ?>
                        <tr id="student-row-<?= $s['id'] ?>">
                            <td>
                                <div class="user-cell">
                                    <div class="user-av"><?= strtoupper(substr($s['full_name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($s['full_name']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($s['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($s['course'] ?? '—') ?></td>
                            <td>
                                <span class="badge badge-<?= $s['role'] ?>">
                                    <?= ucfirst($s['role']) ?>
                                </span>
                            </td>
                            <td style="color:var(--muted);font-size:12px;">
                                <?= date('d M Y', strtotime($s['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($s['role'] !== 'admin'): ?>
                                <div class="toggle-wrap">
                                    <label class="toggle-switch" title="Toggle student status">
                                        <input type="checkbox"
                                               <?= $s['status'] === 'active' ? 'checked' : '' ?>
                                               onchange="toggleStatus(<?= $s['id'] ?>, this)"
                                               id="toggle-<?= $s['id'] ?>">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="toggle-label <?= $s['status'] ?>" id="label-<?= $s['id'] ?>">
                                        <?= ucfirst($s['status']) ?>
                                    </span>
                                </div>
                                <?php else: ?>
                                    <span style="color:var(--muted);font-size:11px;">Admin (protected)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Recent Events -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📅 Recent Events</span>
                    <a href="manage_events.php" class="btn btn-ghost btn-sm">Manage →</a>
                </div>
                <div class="card-body">
                    <?php foreach ($recentEvents as $ev): ?>
                    <div class="event-row">
                        <div class="event-dot dot-<?= $ev['status'] ?>"></div>
                        <div>
                            <div class="event-name"><?= htmlspecialchars($ev['title']) ?></div>
                            <div class="event-meta"><?= htmlspecialchars($ev['category']) ?> · <?= date('d M Y', strtotime($ev['event_date'])) ?></div>
                        </div>
                        <span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentEvents)): ?>
                    <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">No events found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">⚡ Quick Actions</span>
                </div>
                <div style="padding:16px;display:flex;flex-direction:column;gap:8px;">
                    <a href="register.php" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;">➕ Add New Student</a>
                    <a href="manage_events.php" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;">📅 Add New Event</a>
                    <a href="events_live.php" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;">⚡ Open Live Events API</a>
                    <a href="view_records.php?export=csv" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;">📥 Export Students CSV</a>
                    <a href="test_timeout.php" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;">⏱️ Test Session Timeout</a>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Event Analytics Chart ── -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">📊 Event Analytics — Events by Month (Last 6 Months)</span>
        </div>
        <div class="chart-container">
            <canvas id="eventsChart"></canvas>
        </div>
    </div>

    <!-- ── Audit Log ── -->
    <div class="card" id="audit-section">
        <div class="card-header">
            <span class="card-title">📋 Audit Log <small style="font-weight:400;color:var(--muted);margin-left:8px;">Recent admin activity trail</small></span>
            <span style="font-size:12px;color:var(--muted);"><?= count($auditLogs) ?> recent entries</span>
        </div>
        <div class="card-body">
            <?php
            $auditIcons = [
                'LOGIN'          => '🔐',
                'LOGOUT'         => '🚪',
                'VIEW_DASHBOARD' => '🏠',
                'VIEW_STUDENTS'  => '👥',
                'VIEW_EVENTS'    => '📅',
                'TOGGLE_STATUS'  => '🔄',
            ];
            foreach ($auditLogs as $log):
                $icon = $auditIcons[$log['action']] ?? '📌';
                $cssClass = 'audit-' . (array_key_exists($log['action'], $auditIcons) ? $log['action'] : 'default');
            ?>
            <div class="audit-entry">
                <div class="audit-icon <?= $cssClass ?>"><?= $icon ?></div>
                <div style="flex:1;">
                    <div class="audit-action">
                        <?= htmlspecialchars($log['admin_name']) ?> —
                        <?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?>
                        <?php if ($log['target_type']): ?>
                            <span style="color:var(--muted);font-weight:400;">on <?= htmlspecialchars($log['target_type']) ?><?= $log['target_id'] ? " #" . $log['target_id'] : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="audit-details"><?= htmlspecialchars($log['details'] ?? '') ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;">🌐 <?= htmlspecialchars($log['ip_address'] ?? '') ?></div>
                </div>
                <div class="audit-time"><?= date('d M, H:i', strtotime($log['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($auditLogs)): ?>
            <div style="padding:30px;text-align:center;color:var(--muted);">No audit log entries yet.</div>
            <?php endif; ?>
        </div>
    </div>

</main>

<!-- Toast Notification -->
<div id="toast" style="
    display:none;position:fixed;bottom:24px;right:24px;z-index:9999;
    background:var(--surface);border:1px solid var(--border);
    border-radius:10px;padding:14px 20px;font-size:13px;
    min-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.5);
    border-left:4px solid var(--green);
    animation:slideUp .3s ease;">
    <strong id="toast-msg">Status updated!</strong>
</div>

<script>
// ── Chart.js — Events by Month ──────────────────────────────────
const labels = <?= $chartLabels ?: '["No data"]' ?>;
const values = <?= $chartValues ?: '[0]' ?>;

const ctx = document.getElementById('eventsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Events',
            data: values,
            backgroundColor: 'rgba(99,102,241,0.5)',
            borderColor: '#6366f1',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1d27',
                borderColor: '#2a2f45',
                borderWidth: 1,
                titleColor: '#e2e8f0',
                bodyColor: '#94a3b8',
                callbacks: { label: ctx => ` ${ctx.parsed.y} event(s)` }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,.04)' },
                ticks: { color: '#94a3b8', font: { size: 12 } }
            },
            y: {
                grid: { color: 'rgba(255,255,255,.04)' },
                ticks: { color: '#94a3b8', font: { size: 12 }, stepSize: 1 },
                beginAtZero: true
            }
        }
    }
});

// ── Toggle Student Status ─────────────────────────────────────────
async function toggleStatus(userId, checkbox) {
    const newStatus = checkbox.checked ? 'active' : 'inactive';
    const label     = document.getElementById('label-' + userId);

    // Optimistic UI update
    label.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    label.className = 'toggle-label ' + newStatus;

    try {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('user_id', userId);
        formData.append('new_status', newStatus);

        const res  = await fetch('admin_dashboard.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showToast(`Student status set to ${newStatus}`, data.new_status === 'active');
        } else {
            // Revert on failure
            checkbox.checked = !checkbox.checked;
            label.textContent = checkbox.checked ? 'Active' : 'Inactive';
            label.className   = 'toggle-label ' + (checkbox.checked ? 'active' : 'inactive');
            showToast('Update failed: ' + (data.error || 'Unknown error'), false);
        }
    } catch (err) {
        checkbox.checked  = !checkbox.checked;
        showToast('Network error. Check server.', false);
    }
}

// ── Toast ─────────────────────────────────────────────────────────
function showToast(msg, success = true) {
    const toast = document.getElementById('toast');
    const msgEl = document.getElementById('toast-msg');
    msgEl.textContent = (success ? '✅ ' : '❌ ') + msg;
    toast.style.borderLeftColor = success ? 'var(--green)' : 'var(--red)';
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

// ── Sidebar active link ───────────────────────────────────────────
document.querySelectorAll('.nav-item[href]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (this.getAttribute('href').startsWith('#')) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
</script>
</body>
</html>
