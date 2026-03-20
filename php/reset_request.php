<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['id'];
        
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $conn->prepare("
            INSERT INTO password_resets (user_id, email, token, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $email, $token, $expires]);

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        // Path calculation up to the root cbe-pros directory
        $path = dirname($_SERVER['PHP_SELF'], 2) . '/reset_password.php'; 
        $reset_link = $protocol . $host . $path . "?token=" . $token . "&email=" . urlencode($email);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Reset link generated successfully! (SIMULATED EMAIL)',
            'reset_link' => $reset_link
        ]);
    } else {
        echo json_encode([
            'status' => 'success', 
            'message' => 'If an account exists with that email, a reset link was sent.',
            'reset_link' => ''
        ]);
    }

} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
}
?>
