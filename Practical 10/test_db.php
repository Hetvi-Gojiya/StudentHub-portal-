<?php
// test_db.php - Used for Connection Test Screenshot
require_once 'db.php'; // Include the database connection

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Connection Test</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .test-box { text-align: center; margin-top: 50px; padding: 30px; border-radius: 8px; background: var(--bg-color, #fff); }
        .success-text { color: green; font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <header><h1>Database Connection Test</h1></header>
    <hr>
    <main class="container">
        <section class="card test-box">
            
            <?php if (isset($pdo)): ?>
                <p class="success-text">✅ Database Connected Successfully using PDO!</p>
                <p>Database Name: <strong>studenthub_db</strong></p>
                
                <hr style="margin: 20px 0;">
                
                <h3>Testing Prepared Statement (Intermediate Extension)</h3>
                <?php
                // Prepared Statement test
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students");
                    $stmt->execute();
                    $result = $stmt->fetch();
                    echo "<p>Total Registered Students in Database: <strong>" . htmlspecialchars($result['total']) . "</strong></p>";
                } catch (Exception $e) {
                    echo "<p style='color:red;'>Prepared statement error: " . $e->getMessage() . "</p>";
                }
                ?>
                
            <?php endif; ?>

        </section>
    </main>
</body>
</html>
