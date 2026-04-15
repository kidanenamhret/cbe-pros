<?php
// Start session at the very beginning
session_start();

require_once 'db.php';
require_once 'auth.php';

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit();
}

// Get and sanitize input
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']) ? true : false;
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Basic validation
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username and password are required";
    header("Location: ../index.php?error=empty_fields");
    exit();
}

// Check if too many attempts from this IP
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$ip_address]);
    $ip_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['attempts'];

    if ($ip_attempts >= 20) { // Max 20 attempts per IP in 15 minutes
        error_log("Too many login attempts from IP: $ip_address");
        $_SESSION['login_error'] = "Too many login attempts. Please try again later.";
        header("Location: ../index.php?error=too_many_attempts");
        exit();
    }
}
catch (PDOException $e) {
    error_log("Failed to check IP attempts: " . $e->getMessage());
}

// Attempt login using the enhanced auth function
$result = login($conn, $username, $password, $ip_address, $user_agent);

if ($result['success']) {
    // Check if 2FA is required
    if (isset($result['user']['two_factor_enabled']) && $result['user']['two_factor_enabled']) {
        // Store temporary data for 2FA verification
        $_SESSION['2fa_user_id'] = $result['user']['id'];
        $_SESSION['2fa_username'] = $result['user']['username'];
        $_SESSION['2fa_remember'] = $remember_me;

        // Redirect to 2FA verification page
        header("Location: ../verify_2fa.php");
        exit();
    }

    // Set remember me cookie if requested
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days

        // Store remember token in database
        try {
            $stmt = $conn->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))
                ON DUPLICATE KEY UPDATE 
                session_token = VALUES(session_token),
                expires_at = FROM_UNIXTIME(?)
            ");
            $stmt->execute([$result['user']['id'], $token, $ip_address, $user_agent, $expires, $expires]);

            // Set cookie
            setcookie('remember_token', $token, $expires, '/', '', true, true);
        }
        catch (PDOException $e) {
            error_log("Failed to set remember me: " . $e->getMessage());
        }
    }

    // Log successful login
    error_log("Successful login for user: {$result['user']['username']} from IP: $ip_address");

    // Redirect to dashboard
    header("Location: ../dashboard.php");
    exit();

}
else {
    // Login failed
    $_SESSION['login_error'] = $result['message'];

    // Log failed attempt
    error_log("Failed login attempt for username: $username from IP: $ip_address");

    // Redirect back to login page with error
    $redirect_url = "../index.php?error=invalid_credentials";

    if (isset($result['attempts_left'])) {
        $redirect_url .= "&attempts_left=" . $result['attempts_left'];
    }

    header("Location: $redirect_url");
    exit();
}