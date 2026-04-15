<?php
require_once 'auth.php';
require_once 'db.php';

checkLogin();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

$business_name = trim($_POST['business_name'] ?? '');
$business_type = trim($_POST['business_type'] ?? '');

if (empty($business_name)) {
    echo json_encode(["status" => "error", "message" => "Business name is required"]);
    exit();
}

try {
    $conn->beginTransaction();

    // Update user profile with merchant details
    $stmt = $conn->prepare("UPDATE users SET is_merchant = 1, business_name = ?, role = 'user' WHERE id = ?");
    $stmt->execute([$business_name, $user_id]);

    // Create a merchant account if they don't have one
    $merchant_acc_num = "MER-" . date('Ymd') . rand(100, 999);
    $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, account_type, balance, currency, status) VALUES (?, ?, 'checking', 0.00, 'ETB', 'active')");
    $stmt->execute([$user_id, $merchant_acc_num]);

    // Update session
    $_SESSION['is_merchant'] = 1;
    $_SESSION['business_name'] = $business_name;

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Merchant account activated"]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["status" => "error", "message" => "Activation failed: " . $e->getMessage()]);
}
?>
