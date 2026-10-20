<?php
session_start();
// process_login.php - Secure Login with Sessions, RBAC, and Timeout

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $remember_me = !empty($_POST["remember_me"]);

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=invalid");
        exit;
    }

    require_once 'db.php';
    
    try {
        // 1. Fetch user by email using Prepared Statement
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        
        // 2. Verify password securely using password_verify()
        // (Includes plain-text check as fallback for any older legacy records)
        $passwordValid = false;
        if ($student) {
            if (password_verify($password, $student['password'])) {
                $passwordValid = true;
            } elseif ($student['password'] === $password) {
                // If legacy plain-text matches, re-hash it to modern standard
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updatePass = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $updatePass->execute([$newHash, $student['id']]);
                $passwordValid = true;
            }
        }

        if ($passwordValid) {
            // 3. Security: Regenerate Session ID to prevent Session Fixation attacks
            session_regenerate_id(true);

            // 4. Store user information in Session
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['user_name'] = $student['full_name'];
            $_SESSION['user_email'] = $student['email'];
            $_SESSION['user_role'] = !empty($student['role']) ? $student['role'] : 'student';
            $_SESSION['last_activity'] = time(); // Timestamp for session timeout check

            // 5. Intermediate Extension: Record last login timestamp
            try {
                $updateLogin = $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?");
                $updateLogin->execute([$student['id']]);
            } catch (Exception $e) {
                // Ignore if column doesn't exist
            }

            // 6. Advanced Extension: Secure Remember-Me Token
            if ($remember_me) {
                $rawToken = bin2hex(random_bytes(32));
                $hashedToken = hash('sha256', $rawToken);

                // Save hashed token in DB
                try {
                    $tokenStmt = $pdo->prepare("UPDATE students SET remember_token = ? WHERE id = ?");
                    $tokenStmt->execute([$hashedToken, $student['id']]);
                    
                    // Set HTTP-only secure cookie for 7 days
                    setcookie('remember_token', $rawToken, [
                        'expires' => time() + (86400 * 7),
                        'path' => '/',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } catch (Exception $e) {
                    // Ignore if column doesn't exist
                }
            }

            // 7. Audit Log: Record login event
            try {
                $auditStmt = $pdo->prepare("
                    INSERT INTO audit_logs (admin_id, admin_name, action, target_type, details, ip_address, created_at)
                    VALUES (?, ?, 'LOGIN', 'session', ?, ?, NOW())
                ");
                $auditStmt->execute([
                    $student['id'],
                    $student['full_name'],
                    $student['full_name'] . ' logged in successfully',
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } catch (Exception $e) { /* Ignore if audit_logs table doesn't exist yet */ }

            // 8. Role-Based Access Control (RBAC) Redirection
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;

        } else {
            // Failed login: Redirect back with error indicator
            header("Location: login.php?error=invalid");
            exit;
        }

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit;
}
?>
