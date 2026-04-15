<?php
// Start session at the very beginning
session_start();

require_once 'db.php';
require_once 'auth.php';

// Get user info before logout (for logging)
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Unknown';
$session_id = session_id();

try {
    if ($user_id) {
        // Log the logout activity
        $stmt = $conn->prepare("
            INSERT INTO audit_log (user_id, username, action, table_name, ip_address, user_agent, new_values) 
            VALUES (?, ?, 'logout', 'users', ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $username,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode([
                'logout_time' => date('Y-m-d H:i:s'),
                'session_id' => $session_id
            ])
        ]);

        // Clear remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];

            // Delete token from database
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$token]);

            // Expire the cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }

        // Clear all user sessions for this session ID
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$session_id]);

        // Update last activity (optional)
        $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    // Clear all session variables
    $_SESSION = array();

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Clear any other cookies your app uses
    $cookies_to_clear = ['user_preferences', 'last_viewed', 'csrf_token'];
    foreach ($cookies_to_clear as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, '/', '', true, true);
        }
    }

    // Optional: Add a logout message to the session (using a new session)
    session_start();
    $_SESSION['logout_message'] = 'You have been successfully logged out.';
    $_SESSION['logout_time'] = date('Y-m-d H:i:s');

    // Determine redirect URL with optional message
    $redirect_url = "../index.php";

    // Check if there was a specific reason for logout
    if (isset($_GET['expired'])) {
        $redirect_url = "../index.php?message=session_expired";
    } elseif (isset($_GET['inactive'])) {
        $redirect_url = "../index.php?message=inactivity_timeout";
    } else {
        $redirect_url = "../index.php?message=logged_out";
    }

    // Redirect to login page
    header("Location: $redirect_url");
    exit();

} catch (PDOException $e) {
    // Log error but still logout
    error_log("Logout error for user {$user_id}: " . $e->getMessage());

    // Still clear session and redirect
    $_SESSION = array();
    session_destroy();

    header("Location: ../index.php?message=logout_complete");
    exit();
}