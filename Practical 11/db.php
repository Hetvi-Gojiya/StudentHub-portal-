<?php
// db.php - Database connection using PDO (Intermediate Extension)

$host = 'localhost';
$dbname = 'studenthub_db';
$username = 'root'; // default XAMPP username
$db_pass = '';     // default XAMPP password is empty

try {
    // 1. Create a PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Proper database error handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Prevent SQL injection by turning off emulation
    ];

    $pdo = new PDO($dsn, $username, $db_pass, $options);

} catch (PDOException $e) {
    // Stop execution and show error if connection fails
    die("Database Connection Failed: " . $e->getMessage());
}
?>
