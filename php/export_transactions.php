<?php
require_once 'auth.php';
require_once 'db.php';

checkLogin();
$user_id = $_SESSION['user_id'];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="CBE_Pros_Statement_'.date('Y-m-d').'.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Reference', 'Description', 'Type', 'Amount (ETB)', 'Fee', 'Status']);

try {
    $stmt = $conn->prepare("
        SELECT t.*, 
               DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i') as date,
               CASE 
                   WHEN t.sender_account_id IN (SELECT id FROM accounts WHERE user_id = ?) THEN 'Debit'
                   ELSE 'Credit'
               END as entry_type
        FROM transactions t
        WHERE t.sender_account_id IN (SELECT id FROM accounts WHERE user_id = ?)
           OR t.receiver_account_id IN (SELECT id FROM accounts WHERE user_id = ?)
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $amount = ($row['entry_type'] == 'Debit' ? '-' : '+') . $row['amount'];
        fputcsv($output, [
            $row['date'],
            $row['reference_number'],
            $row['description'],
            $row['type'],
            $amount,
            $row['fee'],
            $row['status']
        ]);
    }
} catch (PDOException $e) {
    // Silent fail for CSV
}

fclose($output);
?>
