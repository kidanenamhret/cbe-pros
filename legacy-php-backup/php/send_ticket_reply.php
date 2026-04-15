<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $ticket_id = (int)$_POST['ticket_id'];
    $message = htmlspecialchars($_POST['message']);
    $csrf = $_POST['csrf_token'];

    if ($csrf !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
        exit;
    }

    try {
        // Verify ticket ownership
        $stmt = $conn->prepare("SELECT id FROM support_tickets WHERE id = ? AND user_id = ? AND status = 'open'");
        $stmt->execute([$ticket_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Unable to send message. Ticket might be closed or unauthorized.']);
            exit;
        }

        // Add Reply
        $stmt = $conn->prepare("INSERT INTO support_messages (ticket_id, sender_id, sender_role, message) VALUES (?, ?, 'user', ?)");
        $stmt->execute([$ticket_id, $user_id, $message]);

        echo json_encode(['success' => true, 'message' => 'Message successfully sent!']);
    } catch (PDOException $e) {
        error_log("Error sending ticket reply: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal Server Error.']);
    }
}
?>
