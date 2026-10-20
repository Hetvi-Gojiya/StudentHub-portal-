<?php
// test_timeout.php - Helper script to demonstrate session timeout for Practical 10
session_start();

if (isset($_SESSION['user_id'])) {
    // Artificially age the last_activity timestamp by 20 minutes to trigger timeout
    $_SESSION['last_activity'] = time() - 1200;
}

// Redirect to dashboard, which evaluates the timeout and redirects to login.php?msg=timeout
header("Location: dashboard.php");
exit;
?>
