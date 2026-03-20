<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

try {
    // Get account and balance
    $stmt = $conn->prepare("SELECT id, account_number, balance FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();

    if ($account) {
        $account_id = $account['id'];
        // Get recent transactions
        $stmt = $conn->prepare("
            SELECT t.*, 
                   IF(t.sender_account_id = ?, 'Debit', 'Credit') as entry_type,
                   ua.fullname as other_party
            FROM transactions t
            LEFT JOIN accounts a ON (t.sender_account_id = a.id OR t.receiver_account_id = a.id)
            LEFT JOIN users ua ON (IF(t.sender_account_id = ?, t.receiver_account_id, t.sender_account_id) = a.id AND a.user_id = ua.id)
            WHERE t.sender_account_id = ? OR t.receiver_account_id = ?
            ORDER BY t.created_at DESC LIMIT 5
        ");
        $stmt->execute([$account_id, $account_id, $account_id, $account_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            "status" => "success",
            "data" => [
                "account_number" => $account['account_number'],
                "balance" => $account['balance'],
                "transactions" => $transactions
            ]
        ];
    } else {
        $response = ["status" => "error", "message" => "Account not found."];
    }
} catch (Exception $e) {
    $response = ["status" => "error", "message" => $e->getMessage()];
}

echo json_encode($response);
?>