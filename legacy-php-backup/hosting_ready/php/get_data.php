<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

try {
    // Get user details with profile information
    $stmt = $conn->prepare("
        SELECT u.*, 
               up.phone, up.address, up.city, up.country,
               up.date_of_birth, up.id_number
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all accounts for this user (support multiple accounts)
    $stmt = $conn->prepare("
        SELECT id, account_number, balance, currency, 
               account_type, status, 
               CASE 
                   WHEN status = 'active' THEN 'Active'
                   WHEN status = 'frozen' THEN 'Frozen'
                   WHEN status = 'closed' THEN 'Closed'
               END as status_text
        FROM accounts 
        WHERE user_id = ? 
        ORDER BY 
            CASE status 
                WHEN 'active' THEN 1 
                WHEN 'frozen' THEN 2 
                WHEN 'closed' THEN 3 
            END,
            account_type
    ");
    $stmt->execute([$user_id]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total balance across all accounts
    $total_balance = 0;
    $active_accounts = 0;
    foreach ($accounts as $acc) {
        if ($acc['status'] == 'active') {
            $total_balance += $acc['balance'];
            $active_accounts++;
        }
    }

    // If no accounts found, create a default one
    if (empty($accounts)) {
        $account_number = '1000' . $user_id . rand(100, 999);
        $stmt = $conn->prepare("
            INSERT INTO accounts (user_id, account_number, balance, account_type, status) 
            VALUES (?, ?, 1000.00, 'checking', 'active')
        ");
        $stmt->execute([$user_id, $account_number]);

        // Fetch the new account
        $stmt = $conn->prepare("SELECT id, account_number, balance, currency, account_type, status FROM accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_balance = 1000.00;
        $active_accounts = 1;
    }

    // Get primary account (first active account)
    $primary_account = null;
    foreach ($accounts as $acc) {
        if ($acc['status'] == 'active') {
            $primary_account = $acc;
            break;
        }
    }

    if ($primary_account) {
        $account_id = $primary_account['id'];

        // Get recent transactions with enhanced details
        $stmt = $conn->prepare("
            SELECT 
                t.*,
                t.reference_number,
                t.fee,
                DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date,
                DATE_FORMAT(t.created_at, '%M %d, %Y') as display_date,
                TIME_FORMAT(t.created_at, '%h:%i %p') as display_time,
                CASE 
                    WHEN t.sender_account_id = ? THEN 'Debit'
                    WHEN t.receiver_account_id = ? THEN 'Credit'
                END as entry_type,
                CASE 
                    WHEN t.sender_account_id = ? THEN CONCAT('To: ', receiver.account_number)
                    WHEN t.receiver_account_id = ? THEN CONCAT('From: ', sender.account_number)
                END as transaction_with,
                COALESCE(receiver_user.fullname, sender_user.fullname) as other_party,
                CASE 
                    WHEN t.status = 'completed' THEN 'Completed'
                    WHEN t.status = 'pending' THEN 'Pending'
                    WHEN t.status = 'failed' THEN 'Failed'
                END as status_text,
                CASE 
                    WHEN t.status = 'completed' THEN 'success'
                    WHEN t.status = 'pending' THEN 'warning'
                    WHEN t.status = 'failed' THEN 'danger'
                END as status_color,
                t.balance_after_sender,
                t.balance_after_receiver
            FROM transactions t
            LEFT JOIN accounts sender ON t.sender_account_id = sender.id
            LEFT JOIN accounts receiver ON t.receiver_account_id = receiver.id
            LEFT JOIN users sender_user ON sender.user_id = sender_user.id
            LEFT JOIN users receiver_user ON receiver.user_id = receiver_user.id
            WHERE t.sender_account_id = ? OR t.receiver_account_id = ?
            ORDER BY t.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$account_id, $account_id, $account_id, $account_id, $account_id, $account_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate monthly summary
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as transaction_count,
                SUM(CASE WHEN sender_account_id = ? THEN amount ELSE 0 END) as total_sent,
                SUM(CASE WHEN receiver_account_id = ? THEN amount ELSE 0 END) as total_received,
                SUM(fee) as total_fees
            FROM transactions 
            WHERE (sender_account_id = ? OR receiver_account_id = ?)
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status = 'completed'
        ");
        $stmt->execute([$account_id, $account_id, $account_id, $account_id]);
        $monthly_summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get unread notifications count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get account statistics
        $stmt = $conn->prepare("
            SELECT 
                MIN(created_at) as oldest_transaction,
                MAX(created_at) as latest_transaction,
                COUNT(*) as total_transactions
            FROM transactions 
            WHERE sender_account_id = ? OR receiver_account_id = ?
        ");
        $stmt->execute([$account_id, $account_id]);
        $account_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = [
            "status" => "success",
            "data" => [
                "user" => [
                    "id" => $user['id'],
                    "username" => $user['username'],
                    "fullname" => $user['fullname'],
                    "email" => $user['email'],
                    "phone" => $user['phone'] ?? 'Not provided',
                    "address" => $user['address'] ?? 'Not provided',
                    "city" => $user['city'] ?? 'Not provided',
                    "country" => $user['country'] ?? 'Ethiopia',
                    "member_since" => date('F Y', strtotime($user['created_at'])),
                    "two_factor_enabled" => (bool)$user['two_factor_enabled']
                ],
                "accounts" => $accounts,
                "primary_account" => [
                    "id" => $primary_account['id'],
                    "account_number" => $primary_account['account_number'],
                    "balance" => number_format($primary_account['balance'], 2),
                    "currency" => $primary_account['currency'],
                    "account_type" => ucfirst($primary_account['account_type']),
                    "status" => $primary_account['status_text']
                ],
                "summary" => [
                    "total_balance" => number_format($total_balance, 2),
                    "active_accounts" => $active_accounts,
                    "total_accounts" => count($accounts),
                    "currency" => "ETB"
                ],
                "monthly_summary" => [
                    "transaction_count" => (int)($monthly_summary['transaction_count'] ?? 0),
                    "total_sent" => number_format($monthly_summary['total_sent'] ?? 0, 2),
                    "total_received" => number_format($monthly_summary['total_received'] ?? 0, 2),
                    "total_fees" => number_format($monthly_summary['total_fees'] ?? 0, 2),
                    "net_flow" => number_format(($monthly_summary['total_received'] ?? 0) - ($monthly_summary['total_sent'] ?? 0), 2)
                ],
                "recent_transactions" => array_map(function ($t) {
            return [
            "id" => $t['id'],
            "reference" => $t['reference_number'],
            "type" => $t['type'],
            "entry_type" => $t['entry_type'],
            "amount" => number_format($t['amount'], 2),
            "fee" => number_format($t['fee'], 2),
            "total" => number_format($t['amount'] + ($t['entry_type'] == 'Debit' ? $t['fee'] : 0), 2),
            "description" => $t['description'] ?? 'No description',
            "other_party" => $t['other_party'] ?? 'Unknown',
            "transaction_with" => $t['transaction_with'],
            "status" => $t['status_text'],
            "status_color" => $t['status_color'],
            "date" => $t['display_date'],
            "time" => $t['display_time'],
            "formatted_date" => $t['formatted_date'],
            "balance_after" => $t['entry_type'] == 'Debit' ? 
            number_format($t['balance_after_sender'], 2) :
            number_format($t['balance_after_receiver'], 2)
            ];
        }, $transactions),
                "notifications" => [
                    "unread_count" => (int)($notifications['unread_count'] ?? 0)
                ],
                "account_statistics" => [
                    "total_transactions" => (int)($account_stats['total_transactions'] ?? 0),
                    "oldest_transaction" => $account_stats['oldest_transaction'] ? 
                    date('M d, Y', strtotime($account_stats['oldest_transaction'])) : 'N/A',
                    "latest_transaction" => $account_stats['latest_transaction'] ? 
                    date('M d, Y', strtotime($account_stats['latest_transaction'])) : 'N/A'
                ]
            ]
        ];
    }
    else {
        $response = [
            "status" => "error",
            "message" => "No active account found.",
            "data" => [
                "user" => [
                    "fullname" => $user['fullname'],
                    "email" => $user['email']
                ],
                "accounts" => $accounts,
                "summary" => [
                    "total_balance" => "0.00",
                    "active_accounts" => 0,
                    "total_accounts" => count($accounts)
                ]
            ]
        ];
    }
}
catch (Exception $e) {
    error_log("Dashboard Error for user {$user_id}: " . $e->getMessage());
    $response = [
        "status" => "error",
        "message" => "An error occurred while loading your dashboard."
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>