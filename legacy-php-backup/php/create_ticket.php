<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $subject = htmlspecialchars($_POST['subject']);
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $message = htmlspecialchars($_POST['message']);
    $csrf = $_POST['csrf_token'];

    if ($csrf !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Create Ticket
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, category, priority) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $subject, $category, $priority]);
        $ticket_id = $conn->lastInsertId();

        // Add Initial Message
        $stmt = $conn->prepare("INSERT INTO support_messages (ticket_id, sender_id, sender_role, message) VALUES (?, ?, 'user', ?)");
        $stmt->execute([$ticket_id, $user_id, $message]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Ticket successfully created!']);
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error creating ticket: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    }
}
?>
