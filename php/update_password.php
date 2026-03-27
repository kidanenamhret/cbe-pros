<?php
require_once 'auth.php';
require_once 'db.php';

checkLogin();
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") exit();

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($new !== $confirm) {
    echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
    exit();
}

try {
    // 1. Check current password
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($current, $user['password_hash'])) {
        echo json_encode(["status" => "error", "message" => "Current password incorrect"]);
        exit();
    }

    // 2. Hash and update
    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);

    echo json_encode(["status" => "success", "message" => "Password updated successfully"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
