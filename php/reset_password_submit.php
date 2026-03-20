<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    function redirectError($msg, $token, $email) {
        header("Location: ../reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email) . "&error=" . urlencode($msg));
        exit();
    }

    if (empty($token) || empty($email)) {
        redirectError("Invalid or missing reset token.", $token, $email);
    }
    
    if (empty($password) || strlen($password) < 8) {
        redirectError("Password must be at least 8 characters.", $token, $email);
    }
    
    if ($password !== $confirm_password) {
        redirectError("Passwords do not match.", $token, $email);
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
        $stmt->execute([$email, $token]);
        $reset_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset_record) {
            // Validate expiration in PHP to prevent MySQL timezone desync bugs
            if (strtotime($reset_record['expires_at']) < time()) {
                redirectError("The password reset link has expired.", $token, $email);
            }

            $user_id = $reset_record['user_id'];
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                header("Location: ../index.php?message=password_changed");
                exit();
            } else {
                redirectError("Failed to update password. Try again.", $token, $email);
            }
        } else {
            redirectError("The password reset link is invalid.", $token, $email);
        }

    } catch (PDOException $e) {
        error_log("Reset password submit error: " . $e->getMessage());
        redirectError("A database error occurred.", $token, $email);
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
