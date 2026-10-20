<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? 'student') === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>StudentHub Portal - Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert-banner {
            padding: 12px 16px;
            margin: 15px auto;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
            max-width: 450px;
        }
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .alert-warning {
            background-color: #fff8e1;
            color: #f57f17;
            border: 1px solid #ffe082;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .remember-box {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <h1>Welcome to StudentHub Portal</h1>
        <button id="themeBtn">Theme</button>
    </header>

    <hr>

    <!-- Login Form -->
    <main class="container">

        <!-- Feedback alerts for Logout, Timeout, and Unauthorized Access -->
        <?php if ($msg === 'logout'): ?>
            <div class="alert-banner alert-success">
                ✅ You have been successfully logged out.
            </div>
        <?php elseif ($msg === 'timeout'): ?>
            <div class="alert-banner alert-warning">
                ⏱️ Session Expired: Your session timed out due to inactivity. Please log in again.
            </div>
        <?php elseif ($msg === 'unauthorized'): ?>
            <div class="alert-banner alert-error">
                🚫 Unauthorized Access: Please log in first to access the dashboard.
            </div>
        <?php endif; ?>

        <?php if ($error === 'invalid'): ?>
            <div class="alert-banner alert-error">
                ❌ Invalid Email or Password. Please try again.
            </div>
        <?php endif; ?>

        <section class="card">

            <h2><i>Sign in and see what's waiting for you !</i></h2>

            <form class="login-form" action="process_login.php" method="POST">

                <p>
                    <label>Email :</label>
                    <input type="email" name="username" id="username" placeholder="Enter your registered email" required>
                </p>

                <br>

                <p>
                    <label>Password :</label>
                    <input type="password" name="password" id="password" required>
                </p>

                <div class="remember-box">
                    <input type="checkbox" name="remember_me" id="remember_me" value="1">
                    <label for="remember_me" style="margin: 0; cursor: pointer;">Remember Me</label>
                </div>

                <br>

                <p class="login-options">
                    <a href="#">Forgot Password?</a>
                </p>

                <p class="register-link">
                    <a href="register.php">Create New Account</a>
                </p>

                <button type="submit" id="loginBtn">Login</button>

            </form>

            <div style="margin-top: 25px; padding: 12px; background: #f9f9f9; border-radius: 6px; font-size: 13px; color: #555;">
                <strong>Demo Accounts:</strong><br>
                • <strong>Student:</strong> (Register new or use <code>hetvi90233@gmail.com</code>)<br>
                • <strong>Admin:</strong> <code>admin@studenthub.com</code> / <code>Admin@123</code>
            </div>

        </section>

    </main>

    <script src="script.js"></script>

</body>

</html>

