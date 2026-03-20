<?php
require_once 'db.php';
require_once 'auth.php';
checkLogin();
$sender_user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_username = $_POST['receiver_username'];
    $amount = (float) $_POST['amount'];
    $description = $_POST['description'] ?? 'Transfer';

    try {
        $conn->beginTransaction();

        // Get sender account
        $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$sender_user_id]);
        $sender_acc = $stmt->fetch();

        // Get receiver account
        $stmt = $conn->prepare("SELECT a.id FROM accounts a JOIN users u ON a.user_id = u.id WHERE u.username = ? OR u.email = ? FOR UPDATE");
        $stmt->execute([$receiver_username, $receiver_username]);
        $receiver_acc = $stmt->fetch();

        if (!$sender_acc)
            throw new Exception("Sender account not found.");
        if (!$receiver_acc)
            throw new Exception("Receiver not found.");
        if ($sender_acc['balance'] < $amount)
            throw new Exception("Insufficient balance.");

        // Debit Sender
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $sender_acc['id']]);

        // Credit Receiver
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $receiver_acc['id']]);

        // Log Transaction
        $stmt = $conn->prepare("INSERT INTO transactions (sender_account_id, receiver_account_id, amount, description, type) VALUES (?, ?, ?, ?, 'transfer')");
        $stmt->execute([$sender_acc['id'], $receiver_acc['id'], $amount, $description]);

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Transfer of ETB $amount successful!"]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>