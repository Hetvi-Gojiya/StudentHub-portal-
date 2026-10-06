<?php
$file_path = 'registered_students.csv';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        echo "<script>alert('Please fill all fields.'); window.history.back();</script>";
        exit;
    }

    require_once 'db.php';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        
        if ($student && $student['password'] === $password) {
            echo "<script>alert('Login Successful!'); window.location.href = 'dashboard.html';</script>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }

    echo "<script>alert('Invalid Email or Password or Not Registered.'); window.history.back();</script>";
}
?>
