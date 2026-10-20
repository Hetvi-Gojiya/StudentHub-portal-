<?php
// test_report.php — Automated Integration Test Report for StudentHub
session_start();

$results = [];
$passed  = 0;
$failed  = 0;

function test($name, $condition, $detail = '') {
    global $results, $passed, $failed;
    $status = $condition ? 'PASS' : 'FAIL';
    $condition ? $passed++ : $failed++;
    $results[] = ['name' => $name, 'status' => $status, 'detail' => $detail];
}

// ───────────────────────────────────────────────────────────────
// TEST GROUP 1: Database Connectivity
// ───────────────────────────────────────────────────────────────
$dbConnected = false;
$pdo = null;
try {
    require_once 'htmlfiles/db.php';
    $dbConnected = true;
    test('Database Connection', true, 'PDO connection to studenthub_db established.');
} catch (Exception $e) {
    test('Database Connection', false, $e->getMessage());
}

// ───────────────────────────────────────────────────────────────
// TEST GROUP 2: Table Structure
// ───────────────────────────────────────────────────────────────
if ($dbConnected) {
    $requiredTables = ['students', 'events', 'registrations', 'audit_logs'];
    $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($requiredTables as $table) {
        $exists = in_array($table, $existingTables);
        test("Table '$table' exists", $exists,
            $exists ? "Table $table found in database." : "Table $table is MISSING!");
    }

    // Check critical columns
    $studentCols = $pdo->query("DESCRIBE students")->fetchAll(PDO::FETCH_COLUMN);
    test('Students table has "password" column', in_array('password', $studentCols), 'Required for bcrypt hashing.');
    test('Students table has "role" column', in_array('role', $studentCols), 'Required for RBAC.');
    test('Students table has "status" column', in_array('status', $studentCols), 'Required for soft delete.');

    $eventCols = $pdo->query("DESCRIBE events")->fetchAll(PDO::FETCH_COLUMN);
    test('Events table has "category" column', in_array('category', $eventCols), 'Required for event filtering.');
    test('Events table has "poster" column', in_array('poster', $eventCols), 'Required for file uploads.');

    $auditCols = $pdo->query("DESCRIBE audit_logs")->fetchAll(PDO::FETCH_COLUMN);
    test('Audit logs has "action" column', in_array('action', $auditCols), 'Required for audit trail.');
    test('Audit logs has "ip_address" column', in_array('ip_address', $auditCols), 'Required for IP tracking.');
}

// ───────────────────────────────────────────────────────────────
// TEST GROUP 3: Data Integrity
// ───────────────────────────────────────────────────────────────
if ($dbConnected) {
    $studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    test('Students table has data', $studentCount > 0, "$studentCount student record(s) found.");

    $eventCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    test('Events table has data', $eventCount > 0, "$eventCount event record(s) found.");

    $auditCount = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    test('Audit logs has entries', $auditCount > 0, "$auditCount audit log entries found.");

    // Check admin account exists
    $admin = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'admin'")->fetchColumn();
    test('Admin account exists', $admin > 0, "$admin admin user(s) in database.");

    // Check password is hashed (not plain text)
    $pw = $pdo->query("SELECT password FROM students WHERE role = 'admin' LIMIT 1")->fetchColumn();
    $isHashed = (strlen($pw) >= 60 && strpos($pw, '$2y$') === 0);
    test('Admin password is bcrypt hashed', $isHashed,
        $isHashed ? 'Password uses bcrypt ($2y$ prefix).' : 'WARNING: Password may not be hashed!');
}

// ───────────────────────────────────────────────────────────────
// TEST GROUP 4: File Structure
// ───────────────────────────────────────────────────────────────
$requiredFiles = [
    'htmlfiles/db.php'              => 'Database connection',
    'htmlfiles/login.php'           => 'Login page',
    'htmlfiles/register.php'        => 'Registration page',
    'htmlfiles/process_login.php'   => 'Login handler',
    'htmlfiles/process_register.php'=> 'Registration handler',
    'htmlfiles/dashboard.php'       => 'Student dashboard',
    'htmlfiles/admin_dashboard.php' => 'Admin dashboard',
    'htmlfiles/view_records.php'    => 'Student CRUD',
    'htmlfiles/manage_events.php'   => 'Event management',
    'htmlfiles/api_events.php'      => 'JSON API endpoint',
    'htmlfiles/events_live.php'     => 'AJAX event manager',
    'htmlfiles/logout.php'          => 'Logout handler',
    'htmlfiles/style.css'           => 'Global stylesheet',
    'htmlfiles/script.js'           => 'Shared JavaScript',
    'htmlfiles/uploads/.htaccess'   => 'Upload security',
];

$baseDir = __DIR__ . '/';
foreach ($requiredFiles as $file => $desc) {
    $exists = file_exists($baseDir . $file);
    test("File: $file", $exists, $desc . ($exists ? ' — present.' : ' — MISSING!'));
}

// ───────────────────────────────────────────────────────────────
// TEST GROUP 5: API Endpoint
// ───────────────────────────────────────────────────────────────
$apiUrl = 'http://localhost/StudentHub/htmlfiles/api_events.php';
$apiResponse = @file_get_contents($apiUrl);
$apiWorks = false;
if ($apiResponse) {
    $json = json_decode($apiResponse, true);
    $apiWorks = (json_last_error() === JSON_ERROR_NONE && isset($json['status']));
}
test('API endpoint returns valid JSON', $apiWorks,
    $apiWorks ? "GET $apiUrl returned valid JSON with status field." : 'API did not return valid JSON.');

// ───────────────────────────────────────────────────────────────
// TEST GROUP 6: Security Checks
// ───────────────────────────────────────────────────────────────
// Check .htaccess in uploads
$htaccessExists = file_exists($baseDir . 'htmlfiles/uploads/.htaccess');
test('Upload directory has .htaccess protection', $htaccessExists, 'Blocks direct PHP execution in uploads.');

// Check db.php uses PDO
$dbContent = file_get_contents($baseDir . 'htmlfiles/db.php');
test('db.php uses PDO', strpos($dbContent, 'new PDO') !== false, 'PDO is used for database connections.');
test('db.php uses ERRMODE_EXCEPTION', strpos($dbContent, 'ERRMODE_EXCEPTION') !== false, 'Proper error handling enabled.');
test('db.php uses prepared statements setting', strpos($dbContent, 'EMULATE_PREPARES') !== false, 'Emulated prepares disabled for security.');

// Check login uses password_verify
$loginContent = file_get_contents($baseDir . 'htmlfiles/process_login.php');
test('Login uses password_verify()', strpos($loginContent, 'password_verify') !== false, 'Secure password verification.');
test('Login uses session_regenerate_id()', strpos($loginContent, 'session_regenerate_id') !== false, 'Session fixation protection.');

// Check register uses password_hash
$regContent = file_get_contents($baseDir . 'htmlfiles/process_register.php');
test('Register uses password_hash()', strpos($regContent, 'password_hash') !== false, 'Secure password hashing.');

// ───────────────────────────────────────────────────────────────
// TEST GROUP 7: RBAC & Navigation
// ───────────────────────────────────────────────────────────────
$adminContent = file_get_contents($baseDir . 'htmlfiles/admin_dashboard.php');
test('Admin dashboard checks user_role', strpos($adminContent, "user_role") !== false, 'RBAC enforced on admin pages.');
test('Admin dashboard checks session timeout', strpos($adminContent, "last_activity") !== false, 'Session timeout implemented.');
test('Admin dashboard has audit logging', strpos($adminContent, "audit_logs") !== false, 'Audit trail functionality.');

$total = $passed + $failed;
$pct   = $total > 0 ? round(($passed / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub — Test Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f1117; --surface: #1a1d27; --surface2: #22263a;
            --border: #2a2f45; --text: #e2e8f0; --muted: #94a3b8;
            --green: #10b981; --red: #ef4444; --blue: #3b82f6;
            --accent: #6366f1; --yellow: #f59e0b;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 40px 20px; }

        .container { max-width: 900px; margin: 0 auto; }

        .header { text-align: center; margin-bottom: 36px; }
        .header h1 { font-size: 28px; font-weight: 800; margin-bottom: 6px;
            background: linear-gradient(90deg, #818cf8, #6366f1);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .header p { color: var(--muted); font-size: 14px; }

        .summary {
            display: flex; gap: 16px; justify-content: center; margin-bottom: 32px; flex-wrap: wrap;
        }
        .summary-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
            padding: 20px 28px; text-align: center; min-width: 130px;
        }
        .summary-card .num { font-size: 36px; font-weight: 800; line-height: 1; }
        .summary-card .label { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .num.green { color: var(--green); }
        .num.red   { color: var(--red); }
        .num.blue  { color: var(--blue); }
        .num.yellow { color: var(--yellow); }

        .progress-bar-bg {
            width: 100%; height: 8px; background: var(--surface2);
            border-radius: 8px; margin-bottom: 28px; overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%; border-radius: 8px; transition: width .5s;
        }

        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; overflow: hidden; margin-bottom: 20px;
        }
        .card-head {
            padding: 14px 20px; font-size: 14px; font-weight: 700;
            border-bottom: 1px solid var(--border); background: var(--surface2);
        }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 700;
            color: var(--muted); text-transform: uppercase; letter-spacing: .5px;
            background: var(--surface2); border-bottom: 1px solid var(--border);
        }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); }
        tbody tr:hover { background: rgba(99,102,241,.04); }
        tbody td { padding: 10px 16px; vertical-align: middle; }

        .badge-pass { background: rgba(16,185,129,.15); color: #34d399; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-fail { background: rgba(239,68,68,.15); color: #fca5a5; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        .footer { text-align: center; margin-top: 28px; color: var(--muted); font-size: 12px; }
        .footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h1>🧪 StudentHub — Test Report</h1>
        <p>Automated integration tests · Generated <?= date('d M Y, H:i:s') ?></p>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-card"><div class="num blue"><?= $total ?></div><div class="label">Total Tests</div></div>
        <div class="summary-card"><div class="num green"><?= $passed ?></div><div class="label">Passed</div></div>
        <div class="summary-card"><div class="num red"><?= $failed ?></div><div class="label">Failed</div></div>
        <div class="summary-card"><div class="num <?= $pct >= 90 ? 'green' : ($pct >= 70 ? 'yellow' : 'red') ?>"><?= $pct ?>%</div><div class="label">Score</div></div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar-bg">
        <div class="progress-bar-fill" style="width:<?= $pct ?>%; background:<?= $pct >= 90 ? 'var(--green)' : ($pct >= 70 ? 'var(--yellow)' : 'var(--red)') ?>;"></div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-head">📋 Test Results</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $i => $r): ?>
                <tr>
                    <td style="color:var(--muted);"><?= $i + 1 ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($r['name']) ?></td>
                    <td><span class="badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                    <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($r['detail']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>StudentHub Integration Test Suite · <a href="htmlfiles/login.php">← Back to App</a></p>
        <p style="margin-top:6px;">Environment: PHP <?= phpversion() ?> · MySQL via PDO · Apache/XAMPP</p>
    </div>

</div>
</body>
</html>
