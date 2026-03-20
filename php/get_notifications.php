<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

try {
    // Count unread
    $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmtUnread->execute([$user_id]);
    $unread_count = $stmtUnread->fetchColumn();

    // Get notifications
    $stmt = $conn->prepare("
        SELECT id, title, message, type, is_read, 
               DATE_FORMAT(created_at, '%M %d, %Y %h:%i %p') as created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "unread_count" => $unread_count,
            "notifications" => $notifications
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in get_notifications: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred."]);
}
?>
