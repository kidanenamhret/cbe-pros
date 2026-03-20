<?php
require_once 'php/db.php';
require_once 'php/auth.php';

$username = 'nati123@gmail.com';
$password = 'password123'; // assuming they used something simple, but we don't know it. We just want to see if the user query fails.

try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               up.phone, up.address,
               l.login_attempts, l.locked_until
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as login_attempts, 
                   MAX(locked_until) as locked_until
            FROM login_attempts 
            WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            GROUP BY user_id
        ) l ON u.id = l.user_id
        WHERE u.username = ? OR u.email = ?
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "User NOT FOUND using query.\n";
    } else {
        echo "User FOUND: " . print_r($user, true) . "\n";
    }
} catch (PDOException $e) {
    echo "PDO Exception: " . $e->getMessage() . "\n";
}
?>
