<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT id, beneficiary_user_id, beneficiary_account as account, beneficiary_name as name
        FROM beneficiaries
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $beneficiaries
    ]);
} catch (Exception $e) {
    error_log("Error in get_beneficiaries: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred."]);
}
?>
