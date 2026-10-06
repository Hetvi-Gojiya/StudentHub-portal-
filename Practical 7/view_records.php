<?php
// view_records.php (Intermediate Extension)
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registered Students - Records</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: var(--bg-color, #fff); }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; color: #333; }
        /* Simple dark mode table support */
        body.dark-mode th { background-color: #444; color: #fff; }
    </style>
</head>
<body>
    <header>
        <h1>StudentHub Records</h1>
        <button id="themeBtn">🌙 Dark</button>
    </header>
    <hr>
    <main class="container">
        <section class="card" style="overflow-x: auto;">
            <h2>Registered Students (Intermediate Extension)</h2>
            <p>This data is being read dynamically from the MySQL Database using PHP PDO.</p>
            
            <?php if (empty($students)): ?>
                <p style="color: red; margin-top: 15px;">No students have registered yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td><?php echo htmlspecialchars($student['year']); ?></td>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <br>
            <a href="register.php">Back to Registration</a>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
