<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $bill_type = $_POST['bill_type'];
    $customer_id = $_POST['customer_id'];
    $from_account = $_POST['from_account'];
    $amount = (float)$_POST['amount'];
    $csrf = $_POST['csrf_token'];

    if ($csrf !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
        exit;
    }

    if ($amount < 10) {
        echo json_encode(['success' => false, 'message' => 'Min payment amount is 10 ETB']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Check account ownership and balance
        $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE account_number = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$from_account, $user_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account || $account['balance'] < $amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient funds in the selected account!']);
            exit;
        }

        // Deduct balance
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$amount, $account['id']]);

        // Create transaction entry
        $receipt = "CBE-BILL-" . strtoupper(substr(md5(time().rand()), 0, 8));
        $desc = "Bill Pay: $bill_type (ID: $customer_id)";
        $stmt = $conn->prepare("INSERT INTO transactions (sender_account_id, type, amount, description, status, reference_number) VALUES (?, 'withdrawal', ?, ?, 'completed', ?)");
        $stmt->execute([$account['id'], $amount, $desc, $receipt]);

        $conn->commit();
        echo json_encode(['success' => true, 'receipt' => $receipt, 'message' => 'Utility payment settled successfully!']);

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error processing bill pay: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Transaction failed. Check logs.']);
    }
}
?>
