<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $goal_id = (int)$_POST['goal_id'];
    $source_account = $_POST['source_account'];
    $amount = (float)$_POST['amount'];
    $csrf = $_POST['csrf_token'];

    if ($csrf !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
        exit;
    }

    if ($amount < 1) {
        echo json_encode(['success' => false, 'message' => 'Min amount is 1 ETB']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Check source account balance
        $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE account_number = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$source_account, $user_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account || $account['balance'] < $amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient funds in the source account!']);
            exit;
        }

        // Check goal existence
        $stmt = $conn->prepare("SELECT id, target_amount, current_amount FROM savings_goals WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$goal_id, $user_id]);
        $goal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$goal) {
            echo json_encode(['success' => false, 'message' => 'Savings Goal not found for this user.']);
            exit;
        }

        // Deduct from account
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$amount, $account['id']]);

        // Add to goal
        $new_current = $goal['current_amount'] + $amount;
        $status = ($new_current >= $goal['target_amount']) ? 'completed' : 'active';
        $stmt = $conn->prepare("UPDATE savings_goals SET current_amount = ?, status = ? WHERE id = ?");
        $stmt->execute([$new_current, $status, $goal['id']]);

        // Record pseudo transaction for record keeping
        $stmt = $conn->prepare("INSERT INTO transactions (sender_account_id, type, amount, description, status) VALUES (?, 'withdrawal', ?, ?, 'completed')");
        $desc = "Goal Funding: " . $goal['title'];
        $stmt->execute([$account['id'], $amount, $desc]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Savings pushed successfully!']);

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error contributing to goal: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Transaction failed. Check server logs.']);
    }
}
?>
