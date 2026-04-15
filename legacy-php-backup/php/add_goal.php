<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = htmlspecialchars($_POST['title']);
    $target = (float)$_POST['target'];
    $csrf = $_POST['csrf_token'];

    if ($csrf !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
        exit;
    }

    if (empty($title) || $target < 100) {
        echo json_encode(['success' => false, 'message' => 'Invalid Goal Title or Amount (Min ETB 100)']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO savings_goals (user_id, title, target_amount) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $target]);
        
        echo json_encode(['success' => true, 'message' => 'Goal successfully created!']);
    } catch (PDOException $e) {
        error_log("Error creating goal: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error occurred.']);
    }
}
?>
