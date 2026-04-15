<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

try {
    // Verify it belongs to user and is pending
    $stmt = $conn->prepare("SELECT id FROM scheduled_transactions WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Transaction not found or already processed."]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE scheduled_transactions SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "An error occurred."]);
}
?>
