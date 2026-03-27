<?php
require_once 'auth.php';
require_once 'db.php';

checkLogin();
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") exit();

$new_pin = $_POST['new_pin'] ?? '';

if (!preg_match('/^\d{4}$/', $new_pin)) {
    echo json_encode(["status" => "error", "message" => "PIN must be 4 digits"]);
    exit();
}

try {
    // Hash the PIN
    $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET transfer_pin = ? WHERE id = ?");
    $stmt->execute([$hashed_pin, $user_id]);
    
    echo json_encode(["status" => "success", "message" => "Transfer PIN set successfully!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
