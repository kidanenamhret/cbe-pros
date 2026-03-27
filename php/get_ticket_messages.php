<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];
    $ticket_id = (int)$_GET['ticket_id'];

    try {
        // Verify ticket ownership
        $stmt = $conn->prepare("SELECT id FROM support_tickets WHERE id = ? AND user_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found or unauthorized access.']);
            exit;
        }

        // Fetch messages
        $stmt = $conn->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC");
        $stmt->execute([$ticket_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($messages);
    } catch (PDOException $e) {
        error_log("Error fetching messages: " . $e->getMessage());
        echo json_encode([]);
    }
}
?>
