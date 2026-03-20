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
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
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

        // Simulated success!
        echo json_encode([
            'status' => 'success', 
            'message' => 'Simulated email sent! Click [Open Mailbox] below or go to inbox.php to read it.'
        ]);

    } else {
        echo json_encode([
            'status' => 'success', 
            'message' => 'If an account exists with that email, a reset email was simulated.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
}
?>
