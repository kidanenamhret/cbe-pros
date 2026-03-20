<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Maximum login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 5);
// Lockout duration in seconds (15 minutes)
define('LOCKOUT_DURATION', 900);
// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

/**
 * Check if user is logged in, redirect if not
 */
function checkLogin()
{
    if (!isLoggedIn()) {
        // Log the unauthorized access attempt
        logUnauthorizedAccess();

        // Redirect to login page
        header("Location: ../index.php");
        exit();
    }

    // Check for session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Session expired
        logout();
        header("Location: ../index.php?timeout=1");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user is logged in (returns boolean)
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_name']);
}

/**
 * Get current user ID
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole()
{
    return $_SESSION['user_role'] ?? 'user';
}

/**
 * Get current user data
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'],
        'fullname' => $_SESSION['user_fullname'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Login user
 */
function login($conn, $username, $password, $ip_address = null, $user_agent = null)
{
    try {
        // Check if account is locked
        if (isAccountLocked($conn, $username)) {
            logLoginAttempt($conn, $username, $ip_address, 'locked');
            return [
                'success' => false,
                'message' => 'Account is temporarily locked. Please try again later.'
            ];
        }

        // Get user with profile information
        $stmt = $conn->prepare("
            SELECT u.*, 
                   up.phone, up.address,
                   l.login_attempts
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) as login_attempts
                FROM login_attempts 
                WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                GROUP BY user_id
            ) l ON u.id = l.user_id
            WHERE u.username = ? OR u.email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User not found
            logLoginAttempt($conn, null, $ip_address, 'failed', $username);
            return [
                'success' => false,
                'message' => 'Invalid username or password.'
            ];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Failed login - increment attempts
            $attempts = recordFailedLogin($conn, $user['id'], $ip_address);

            // Check if should lock account
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                lockAccount($conn, $user['id']);
                logLoginAttempt($conn, $user['id'], $ip_address, 'locked');
                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Account locked for 15 minutes.'
                ];
            }

            logLoginAttempt($conn, $user['id'], $ip_address, 'failed');
            return [
                'success' => false,
                'message' => 'Invalid username or password.',
                'attempts_left' => MAX_LOGIN_ATTEMPTS - $attempts
            ];
        }

        // Check if account is active
        if (isset($user['status']) && $user['status'] === 'inactive') {
            logLoginAttempt($conn, $user['id'], $ip_address, 'inactive');
            return [
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.'
            ];
        }

        // Successful login - set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_fullname'] = $user['fullname'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $ip_address ?? $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $user_agent ?? $_SERVER['HTTP_USER_AGENT'];

        // Clear failed attempts
        clearFailedAttempts($conn, $user['id']);

        // Update last login
        updateLastLogin($conn, $user['id'], $ip_address);

        // Log successful login
        logLoginAttempt($conn, $user['id'], $ip_address, 'success');

        // Create session record
        createUserSession($conn, $user['id'], session_id(), $ip_address, $user_agent);

        // Check for 2FA requirement
        if ($user['two_factor_enabled']) {
            $_SESSION['2fa_required'] = true;
            $_SESSION['2fa_user_id'] = $user['id'];
            // Don't fully authenticate yet
            unset($_SESSION['user_id']);
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'role' => $user['role'],
                'two_factor_enabled' => (bool)$user['two_factor_enabled']
            ]
        ];

    }
    catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during login. Please try again.'
        ];
    }
}

/**
 * Logout user
 */
function logout()
{
    global $conn;

    if (isset($_SESSION['user_id'])) {
        // End session in database
        endUserSession($conn, session_id());

        // Log logout
        logActivity($conn, $_SESSION['user_id'], 'logout', 'User logged out');
    }

    // Clear session
    $_SESSION = array();

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
}

/**
 * Check if account is locked
 */
function isAccountLocked($conn, $username)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts
        FROM login_attempts la
        JOIN users u ON la.user_id = u.id
        WHERE (u.username = ? OR u.email = ?)
        AND la.attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        AND la.attempt_time >= (
            SELECT MIN(attempt_time)
            FROM login_attempts
            WHERE user_id = u.id
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            HAVING COUNT(*) >= ?
        )
    ");
    $stmt->execute([$username, $username, MAX_LOGIN_ATTEMPTS]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($conn, $user_id, $ip_address)
{
    $stmt = $conn->prepare("
        INSERT INTO login_attempts (user_id, ip_address, attempt_time) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $ip_address]);

    // Count recent attempts
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE user_id = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['attempts'];
}

/**
 * Lock account
 */
function lockAccount($conn, $user_id)
{
    $stmt = $conn->prepare("
        UPDATE users 
        SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
}

/**
 * Clear failed attempts
 */
function clearFailedAttempts($conn, $user_id)
{
    $stmt = $conn->prepare("
        DELETE FROM login_attempts 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);

    $stmt = $conn->prepare("
        UPDATE users 
        SET locked_until = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
}

/**
 * Update last login
 */
function updateLastLogin($conn, $user_id, $ip_address)
{
    $stmt = $conn->prepare("
        UPDATE users 
        SET last_login = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
}

/**
 * Create user session record
 */
function createUserSession($conn, $user_id, $session_token, $ip_address, $user_agent)
{
    $stmt = $conn->prepare("
        INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
        ON DUPLICATE KEY UPDATE 
        expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    ");
    $stmt->execute([$user_id, $session_token, $ip_address, $user_agent]);
}

/**
 * End user session
 */
function endUserSession($conn, $session_token)
{
    $stmt = $conn->prepare("
        DELETE FROM user_sessions 
        WHERE session_token = ?
    ");
    $stmt->execute([$session_token]);
}

/**
 * Log login attempt
 */
function logLoginAttempt($conn, $user_id, $ip_address, $status, $username_attempted = null)
{
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_log (user_id, username, action, table_name, ip_address, user_agent, new_values) 
            VALUES (?, ?, 'login_attempt', 'users', ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $username_attempted ?? ($user_id ? 'user_' . $user_id : 'unknown'),
            $ip_address,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode(['status' => $status, 'time' => date('Y-m-d H:i:s')])
        ]);
    }
    catch (Exception $e) {
        // Just log error, don't interrupt login flow
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

/**
 * Log user activity
 */
function logActivity($conn, $user_id, $action, $details = null)
{
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_log (user_id, action, ip_address, user_agent, new_values) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $details ? json_encode(['details' => $details]) : null
        ]);
    }
    catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Require admin role
 */
function requireAdmin()
{
    checkLogin();

    if ($_SESSION['user_role'] !== 'admin') {
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(['status' => 'error', 'message' => 'Access denied. Admin only.']);
        exit();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        error_log("CSRF Error: No token in session");
        return false;
    }

    if (empty($token)) {
        error_log("CSRF Error: Empty token provided");
        return false;
    }

    // Use hash_equals for timing attack safe comparison
    $valid = hash_equals($_SESSION['csrf_token'], $token);

    if (!$valid) {
        error_log("CSRF Error: Token mismatch - Session: " . $_SESSION['csrf_token'] . " | Provided: " . $token);
    }

    return $valid;
}

/**
 * Check if user has permission
 */
function hasPermission($required_role)
{
    if (!isLoggedIn()) {
        return false;
    }

    $role_hierarchy = [
        'user' => 1,
        'admin' => 2
    ];

    $user_role_level = $role_hierarchy[$_SESSION['user_role']] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;

    return $user_role_level >= $required_level;
}

/**
 * Log unauthorized access attempt
 */
function logUnauthorizedAccess()
{
    try {
        global $conn;

        $stmt = $conn->prepare("
            INSERT INTO audit_log (action, ip_address, user_agent, new_values) 
            VALUES ('unauthorized_access', ?, ?, ?)
        ");
        $stmt->execute([
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode([
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ])
        ]);
    }
    catch (Exception $e) {
    // Silently fail
    }
}

/**
 * Refresh session
 */
function refreshSession()
{
    if (isLoggedIn()) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Update last activity
        $_SESSION['last_activity'] = time();

        // Update session in database
        global $conn;
        endUserSession($conn, session_id());
        createUserSession(
            $conn,
            $_SESSION['user_id'],
            session_id(),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
    }
}

/**
 * Get session timeout in minutes
 */
function getSessionTimeout()
{
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        return floor((SESSION_TIMEOUT - $inactive) / 60);
    }
    return SESSION_TIMEOUT / 60;
}