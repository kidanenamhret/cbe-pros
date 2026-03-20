<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

try {
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($account_ids)) {
        echo json_encode(["status" => "success", "data" => ["transactions" => [], "has_more" => false]]);
        exit();
    }

    $inQuery = implode(',', array_fill(0, count($account_ids), '?'));
    
    // First parameter block: (t.sender_account_id IN (...) OR t.receiver_account_id IN (...))
    $params = array_merge($account_ids, $account_ids);
    $whereSQL = " WHERE (t.sender_account_id IN ($inQuery) OR t.receiver_account_id IN ($inQuery)) ";
    
    $whereParams = [];
    if ($type !== 'all') {
        $whereSQL .= " AND t.type = ? ";
        $whereParams[] = $type;
    }
    if (!empty($search)) {
        $whereSQL .= " AND (t.description LIKE ? OR t.reference_number LIKE ?) ";
        $whereParams[] = "%$search%";
        $whereParams[] = "%$search%";
    }
    if (!empty($date)) {
        $whereSQL .= " AND DATE(t.created_at) = ? ";
        $whereParams[] = $date;
    }

    $allParams = array_merge($params, $whereParams);

    // Count total matches
    $countQuery = "SELECT COUNT(*) FROM transactions t" . $whereSQL;
    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->execute($allParams);
    $total = $stmtCount->fetchColumn();

    // Get transactions
    $query = "
        SELECT t.*,
               DATE_FORMAT(t.created_at, '%Y-%m-%d') as date,
               TIME_FORMAT(t.created_at, '%h:%i %p') as time,
               t.reference_number as reference,
               CASE 
                   WHEN t.sender_account_id IN ($inQuery) THEN 'Debit'
                   ELSE 'Credit'
               END as entry_type,
               CASE 
                   WHEN t.status = 'completed' THEN 'success'
                   WHEN t.status = 'pending' THEN 'warning'
                   ELSE 'danger'
               END as status_color,
               CASE 
                   WHEN t.sender_account_id IN ($inQuery) THEN COALESCE(receiver_user.fullname, 'System')
                   ELSE COALESCE(sender_user.fullname, 'System')
               END as other_party
        FROM transactions t
        LEFT JOIN accounts sender ON t.sender_account_id = sender.id
        LEFT JOIN accounts receiver ON t.receiver_account_id = receiver.id
        LEFT JOIN users sender_user ON sender.user_id = sender_user.id
        LEFT JOIN users receiver_user ON receiver.user_id = receiver_user.id
        $whereSQL
        ORDER BY t.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $finalParams = array_merge($account_ids, $account_ids, $params, $whereParams);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($finalParams);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($transactions as &$t) {
        $t['balance_after'] = ($t['entry_type'] == 'Debit') ? $t['balance_after_sender'] : $t['balance_after_receiver'];
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "transactions" => $transactions,
            "has_more" => ($offset + $limit < $total)
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in get_transactions limit query: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred."]);
}
?>
