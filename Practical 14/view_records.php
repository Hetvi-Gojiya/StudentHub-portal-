<?php
// view_records.php - Practical 11: Student Management CRUD with Search and Filter
session_start();
require_once 'db.php';

$message = "";
$messageType = "";

// -------------------------------------------------------------
// Intermediate Extension: Export Students to CSV
// -------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=studenthub_records_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'Status', 'Registered Date']);
    
    $csvQuery = $pdo->query("SELECT id, full_name, email, mobile, course, year, gender, status, created_at FROM students WHERE role != 'admin' ORDER BY id ASC");
    while ($row = $csvQuery->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------
// 1. Handle CREATE: Add New Student
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = intval($_POST['year'] ?? 1);
    $gender = trim($_POST['gender'] ?? 'Other');
    $password = $_POST['password'] ?? 'Student@123';

    if (empty($full_name) || empty($email) || empty($mobile) || empty($course)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "error";
    } else {
        // Check duplicate email with Prepared Statement
        $checkStmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->rowCount() > 0) {
            $message = "A student with this email address already exists.";
            $messageType = "error";
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO students (full_name, email, mobile, password, course, year, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            if ($insertStmt->execute([$full_name, $email, $mobile, $hashed_pass, $course, $year, $gender])) {
                $message = "Student record added successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to add student.";
                $messageType = "error";
            }
        }
    }
}

// -------------------------------------------------------------
// 2. Handle UPDATE: Edit Student
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = intval($_POST['year'] ?? 1);
    $gender = trim($_POST['gender'] ?? 'Other');
    $status = trim($_POST['status'] ?? 'active');

    if ($id <= 0 || empty($full_name) || empty($email)) {
        $message = "Invalid student details provided for update.";
        $messageType = "error";
    } else {
        // Check duplicate email for another student
        $dupCheck = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $dupCheck->execute([$email, $id]);
        if ($dupCheck->rowCount() > 0) {
            $message = "Email is already taken by another student.";
            $messageType = "error";
        } else {
            $updateStmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ?, status = ? WHERE id = ?");
            if ($updateStmt->execute([$full_name, $email, $mobile, $course, $year, $gender, $status, $id])) {
                $message = "Student record #$id updated successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to update student record.";
                $messageType = "error";
            }
        }
    }
}

// -------------------------------------------------------------
// 3. Handle DELETE / SOFT DELETE (Advanced Extension)
// -------------------------------------------------------------
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    
    // Soft Delete (Sets status to 'inactive')
    if ($_GET['action'] === 'soft_delete' && $id > 0) {
        $softStmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
        if ($softStmt->execute([$id])) {
            $message = "Student #$id soft-deleted (status set to inactive).";
            $messageType = "success";
        }
    }
    
    // Restore Soft Deleted Student
    if ($_GET['action'] === 'restore' && $id > 0) {
        $resStmt = $pdo->prepare("UPDATE students SET status = 'active' WHERE id = ?");
        if ($resStmt->execute([$id])) {
            $message = "Student #$id restored to active status.";
            $messageType = "success";
        }
    }

    // Permanent Delete
    if ($_GET['action'] === 'permanent_delete' && $id > 0) {
        $delStmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        if ($delStmt->execute([$id])) {
            $message = "Student #$id permanently removed from database.";
            $messageType = "success";
        }
    }
}

// -------------------------------------------------------------
// 4. Handle SEARCH & FILTER with Prepared Statements
// -------------------------------------------------------------
$searchTerm = trim($_GET['search'] ?? '');
$filterCourse = trim($_GET['course'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM students WHERE role != 'admin'";
$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if (!empty($filterCourse)) {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY id DESC";

$queryStmt = $pdo->prepare($sql);
$queryStmt->execute($params);
$students = $queryStmt->fetchAll();

// Total count
$totalCount = count($students);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Management CRUD</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .crud-container {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 15px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filter-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        .filter-form input, .filter-form select {
            padding: 9px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-crud {
            padding: 9px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-search { background: #2563eb; color: white; }
        .btn-search:hover { background: #1d4ed8; }
        .btn-reset { background: #64748b; color: white; }
        .btn-reset:hover { background: #475569; }
        .btn-add { background: #10b981; color: white; font-size: 15px; }
        .btn-add:hover { background: #059669; }
        .btn-export { background: #7c3aed; color: white; }
        .btn-export:hover { background: #6d28d9; }
        
        .alert-crud {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .table-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            overflow-x: auto;
        }
        .records-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }
        .records-table th {
            background: #f8fafc;
            color: #475569;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 700;
        }
        .records-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .records-table tbody tr:hover { background: #f8fafc; }

        .badge-status {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .status-active { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-inactive { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .action-link {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            margin-right: 4px;
            display: inline-block;
        }
        .action-edit { background: #e0f2fe; color: #0284c7; }
        .action-edit:hover { background: #bae6fd; }
        .action-softdel { background: #fee2e2; color: #dc2626; }
        .action-softdel:hover { background: #fecaca; }
        .action-restore { background: #dcfce7; color: #15803d; }
        .action-restore:hover { background: #bbf7d0; }
        .action-permdel { background: #f1f5f9; color: #64748b; font-size: 11px; }
        .action-permdel:hover { background: #e2e8f0; color: #000; }

        /* Modal Styles */
        .crud-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .modal-content h3 { margin-top: 0; color: #0f172a; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #475569; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <header>
        <h1>StudentHub Records Management</h1>
        <button id="themeBtn">Theme</button>
    </header>

    <div class="crud-container">

        <!-- Top Navigation Strip -->
        <div class="header-actions">
            <div>
                <a href="dashboard.php" class="btn-crud btn-reset">⬅️ Dashboard</a>
                <a href="admin_dashboard.php" class="btn-crud btn-reset">👑 Admin Console</a>
            </div>
            <div>
                <button onclick="openAddModal()" class="btn-crud btn-add">➕ Add New Student</button>
                <a href="view_records.php?export=csv" class="btn-crud btn-export" title="Export student records to CSV">📥 Export to CSV</a>
            </div>
        </div>

        <!-- Feedback Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert-crud <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Card -->
        <div class="filter-card">
            <form method="GET" action="view_records.php" class="filter-form">
                <input type="text" name="search" placeholder="🔍 Search name or email..." value="<?php echo htmlspecialchars($searchTerm); ?>" style="flex: 2; min-width: 220px;">
                
                <select name="course" style="flex: 1; min-width: 140px;">
                    <option value="">All Courses</option>
                    <option value="B.Tech CE" <?php echo $filterCourse === 'B.Tech CE' ? 'selected' : ''; ?>>B.Tech CE</option>
                    <option value="B.Tech IT" <?php echo $filterCourse === 'B.Tech IT' ? 'selected' : ''; ?>>B.Tech IT</option>
                    <option value="B.Tech CSE" <?php echo $filterCourse === 'B.Tech CSE' ? 'selected' : ''; ?>>B.Tech CSE</option>
                </select>

                <select name="status" style="flex: 1; min-width: 130px;">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive (Soft-Deleted)</option>
                </select>

                <button type="submit" class="btn-crud btn-search">Apply Filter</button>
                <a href="view_records.php" class="btn-crud btn-reset">Clear</a>
            </form>
            <div style="margin-top: 10px; font-size: 13px; color: #64748b;">
                Showing <strong><?php echo $totalCount; ?></strong> matching student records in database.
            </div>
        </div>

        <!-- Records Table (Student CRUD View) -->
        <div class="table-card">
            <table class="records-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">
                                No student records found matching your search criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['id']); ?></strong></td>
                                <td style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td>Year <?php echo htmlspecialchars($row['year']); ?></td>
                                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                <td>
                                    <?php if (($row['status'] ?? 'active') === 'active'): ?>
                                        <span class="badge-status status-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-status status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <!-- Edit Trigger -->
                                    <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="action-link action-edit" style="border:none; cursor:pointer;">✏️ Edit</button>

                                    <!-- Soft Delete / Restore (Advanced Extension) -->
                                    <?php if (($row['status'] ?? 'active') === 'active'): ?>
                                        <a href="view_records.php?action=soft_delete&id=<?php echo $row['id']; ?>" class="action-link action-softdel" onclick="return confirm('Are you sure you want to deactivate (soft-delete) student <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>?');">🗑️ Soft Delete</a>
                                    <?php else: ?>
                                        <a href="view_records.php?action=restore&id=<?php echo $row['id']; ?>" class="action-link action-restore" onclick="return confirm('Restore student to active?');">♻️ Restore</a>
                                    <?php endif; ?>

                                    <!-- Permanent Delete -->
                                    <a href="view_records.php?action=permanent_delete&id=<?php echo $row['id']; ?>" class="action-link action-permdel" onclick="return confirm('Permanently delete student <?php echo htmlspecialchars(addslashes($row['full_name'])); ?> from database? This cannot be undone.');">❌ Purge</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- -------------------------------------------------------------
         CREATE MODAL: Add Student
    -------------------------------------------------------------- -->
    <div id="addModal" class="crud-modal">
        <div class="modal-content">
            <h3>➕ Add New Student Record</h3>
            <form method="POST" action="view_records.php">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g. Kushal Shah">
                </div>

                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required placeholder="kushal@example.com">
                </div>

                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" name="mobile" required placeholder="9876543210" pattern="[6-9][0-9]{9}">
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Course *</label>
                        <select name="course" required>
                            <option value="B.Tech CE">B.Tech CE</option>
                            <option value="B.Tech IT">B.Tech IT</option>
                            <option value="B.Tech CSE">B.Tech CSE</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Year</label>
                        <select name="year">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Initial Password (will be hashed automatically)</label>
                    <input type="password" name="password" value="Student@123">
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeAddModal()" class="btn-crud btn-reset">Cancel</button>
                    <button type="submit" class="btn-crud btn-add">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         UPDATE MODAL: Edit Student
    -------------------------------------------------------------- -->
    <div id="editModal" class="crud-modal">
        <div class="modal-content">
            <h3>✏️ Edit Student Details</h3>
            <form method="POST" action="view_records.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>

                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" name="mobile" id="edit_mobile" required pattern="[6-9][0-9]{9}">
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Course *</label>
                        <select name="course" id="edit_course" required>
                            <option value="B.Tech CE">B.Tech CE</option>
                            <option value="B.Tech IT">B.Tech IT</option>
                            <option value="B.Tech CSE">B.Tech CSE</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Year</label>
                        <select name="year" id="edit_year">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Gender</label>
                        <select name="gender" id="edit_gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Status (Soft Delete Control)</label>
                        <select name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn-crud btn-reset">Cancel</button>
                    <button type="submit" class="btn-crud btn-search">Update Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(student) {
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_name').value = student.full_name;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_mobile').value = student.mobile;
            document.getElementById('edit_course').value = student.course;
            document.getElementById('edit_year').value = student.year;
            document.getElementById('edit_gender').value = student.gender;
            document.getElementById('edit_status').value = student.status || 'active';
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target === document.getElementById('editModal')) {
                closeEditModal();
            }
        };
    </script>

    <script src="script.js"></script>
</body>

</html>
