<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Authentication & CSRF
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token or session expired']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING); // 'airtime' or 'wallet'
$account_id = filter_input(INPUT_POST, 'account_id', FILTER_VALIDATE_INT);
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$transaction_pin = $_POST['transaction_pin'] ?? '';

// Transaction PIN Verification
try {
    $stmt = $conn->prepare("SELECT transfer_pin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($user['transfer_pin'])) {
        echo json_encode(["status" => "error", "message" => "Transaction PIN not set. Please set it in Settings."]);
        exit();
    }

    if (!password_verify($transaction_pin, $user['transfer_pin'])) {
        echo json_encode(["status" => "error", "message" => "Invalid Transaction PIN."]);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Security verification failed."]);
    exit();
}

if (!$account_id || empty($phone) || !$amount || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all fields correctly with valid amounts.']);
    exit;
}

// Basic phone number format check for Ethiopia (09xxxxxxxx or 07xxxxxxxx)
if (!preg_match('/^(09|07)\d{8}$/', $phone) && !preg_match('/^\+251(9|7)\d{8}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Ethiopian phone number. Must be 09XXXXXXXX or 07XXXXXXXX.']);
    exit;
}

if ($amount < 5 && $type === 'airtime') {
    echo json_encode(['status' => 'error', 'message' => 'Minimum airtime recharge is 5 ETB.']);
    exit;
}

if ($amount < 10 && $type === 'wallet') {
    echo json_encode(['status' => 'error', 'message' => 'Minimum wallet transfer is 10 ETB.']);
    exit;
}

try {
    $conn->beginTransaction();

    // Verify account belongs to user and has sufficient funds
    $stmt = $conn->prepare("SELECT balance, currency FROM accounts WHERE id = ? AND user_id = ? AND status = 'active' FOR UPDATE");
    $stmt->execute([$account_id, $user_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception("Account not found or is inactive.");
    }

    if ($account['balance'] < $amount) {
        throw new Exception("Insufficient balance to complete the Telebirr request. Available: " . $account['balance'] . " ETB.");
    }

    // Deduct balance
    $new_balance = $account['balance'] - $amount;
    $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
    $stmt->execute([$new_balance, $account_id]);

    // Record the transaction
    $description = $type === 'airtime' ? "Telebirr Airtime Recharge to $phone" : "Telebirr Wallet Deposit to $phone";
    $reference = 'TB-' . strtoupper(uniqid());

    $stmt = $conn->prepare("
        INSERT INTO transactions 
        (sender_account_id, amount, description, type, status, reference_number, balance_after_sender) 
        VALUES (?, ?, ?, 'withdrawal', 'completed', ?, ?)
    ");
    $stmt->execute([$account_id, $amount, $description, $reference, $new_balance]);

    // Construct a simulated metadata log
    $metadata = json_encode([
        'telebirr_tx_id' => bin2hex(random_bytes(8)),
        'phone_number' => $phone,
        'service_type' => $type
    ]);

    $tx_id = $conn->lastInsertId();
    $stmt = $conn->prepare("UPDATE transactions SET metadata = ? WHERE id = ?");
    $stmt->execute([$metadata, $tx_id]);

    $conn->commit();

    // Emulate API latency for realism
    sleep(1);

    echo json_encode([
        'status' => 'success',
        'message' => "Successfully processed <strong>$amount ETB</strong> for <strong>$description</strong>.<br><br><small style='color:#666;'>Reference: $reference</small>",
        'new_balance' => $new_balance
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Telebirr API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
