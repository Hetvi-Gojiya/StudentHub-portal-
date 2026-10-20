<?php
session_start();
// process_register.php
$file_path = 'registered_students.csv';
$json_file_path = 'registered_students.json';

// Initialize response variables
$success = false;
$error = "";

// 1. Is the form submitted using POST?
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CSRF Token Validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {

    // 2. Sanitize inputs on the server side
    $full_name = htmlspecialchars(trim($_POST["full_name"] ?? ""));
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars(trim($_POST["mobile"] ?? ""));
    $course = htmlspecialchars(trim($_POST["course"] ?? ""));
    $year = htmlspecialchars(trim($_POST["year"] ?? ""));
    $gender = htmlspecialchars(trim($_POST["gender"] ?? ""));
    $password = $_POST["password"] ?? ""; 
    
    // 2. Validate inputs on the server side
    if (empty($full_name) || empty($email) || empty($mobile) || empty($course) || empty($year) || empty($gender) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
        $error = "Invalid mobile number.";
    } else {
        // 3. Save to MySQL Database (Practical 8)
        require_once 'db.php'; // Include database connection
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email is already registered.";
            } else {
                // Insert into students table using Prepared Statement
                $insertStmt = $pdo->prepare("INSERT INTO students (full_name, email, mobile, password, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($insertStmt->execute([$full_name, $email, $mobile, $password, $course, $year, $gender])) {
                    $success = true;
                } else {
                    $error = "Failed to save data to the database.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Status</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .msg-box { text-align: center; margin-top: 50px; padding: 20px; border-radius: 8px; background: var(--bg-color, #fff); }
        .success { color: green; font-size: 24px; }
        .error { color: red; font-size: 24px; }
    </style>
</head>
<body>
    <header><h1>StudentHub Portal</h1></header>
    <hr>
    <main class="container">
        <!-- 4. Display success and error responses clearly -->
        <section class="card msg-box">
            <?php if ($success): ?>
                <h2 class="success">Registration Successful!</h2>
                <p>Thank you, <strong><?php echo htmlspecialchars($full_name); ?></strong>. Your data has been securely saved to the database.</p>
                <br><br>
                <a href="login.html" style="margin-right: 15px;">Go to Login</a>
                <a href="view_records.php">View Stored Records</a>
            <?php else: ?>
                <h2 class="error">Registration Failed</h2>
                <p><?php echo $error; ?></p>
                <br><br>
                <a href="register.php">Go Back and Try Again</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
