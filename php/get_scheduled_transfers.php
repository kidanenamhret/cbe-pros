<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT st.*, DATE_FORMAT(st.scheduled_date, '%Y-%m-%d %H:%i') as scheduled_date,
               u_rec.fullname as to_name, a_rec.account_number as to_account
        FROM scheduled_transactions st
        LEFT JOIN accounts a_rec ON st.receiver_account_id = a_rec.id
        LEFT JOIN users u_rec ON a_rec.user_id = u_rec.id
        WHERE st.user_id = ?
        ORDER BY st.scheduled_date ASC
    ");
    $stmt->execute([$user_id]);
    $scheduled = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $scheduled
    ]);
} catch (Exception $e) {
    error_log("Error in get_scheduled_transfers: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred."]);
}
?>
