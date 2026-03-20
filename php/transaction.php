<?php
// Start session at the very beginning
session_start();

require_once 'db.php';
require_once 'auth.php';

// Check if user is logged in
checkLogin();

// Set header for JSON response
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit();
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get and validate input
$transaction_type = $_POST['transaction_type'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');
$from_account = $_POST['from_account'] ?? '';
$to_account = $_POST['to_account'] ?? '';
$to_phone = $_POST['to_phone'] ?? '';
$scheduled_date = $_POST['scheduled_date'] ?? '';
$is_scheduled = isset($_POST['is_scheduled']) ? true : false;
$save_beneficiary = isset($_POST['save_beneficiary']) ? true : false;

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request origin"
    ]);
    exit();
}

// Basic validation
if (empty($transaction_type)) {
    echo json_encode([
        "status" => "error",
        "message" => "Transaction type is required"
    ]);
    exit();
}

if ($amount <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Amount must be greater than zero"
    ]);
    exit();
}

// Handle different transaction types
try {
    switch ($transaction_type) {
        case 'transfer':
            handleTransfer($conn, $user_id, $from_account, $to_account, $amount, $description, $save_beneficiary);
            break;

        case 'transfer_phone':
            handlePhoneTransfer($conn, $user_id, $from_account, $to_phone, $amount, $description, $save_beneficiary);
            break;

        case 'deposit':
            handleDeposit($conn, $user_id, $from_account, $amount, $description);
            break;

        case 'withdrawal':
            handleWithdrawal($conn, $user_id, $from_account, $amount, $description);
            break;

        case 'scheduled':
            handleScheduledTransfer($conn, $user_id, $from_account, $to_account, $amount, $description, $scheduled_date);
            break;

        default:
            echo json_encode([
                "status" => "error",
                "message" => "Invalid transaction type"
            ]);
    }
} catch (Exception $e) {
    error_log("Transaction error for user {$user_id}: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Transaction failed: " . $e->getMessage()
    ]);
}

/**
 * Handle transfer between accounts
 */
function handleTransfer($conn, $user_id, $from_account, $to_account, $amount, $description, $save_beneficiary)
{
    // Validate input
    if (empty($from_account) || empty($to_account)) {
        throw new Exception("Both source and destination accounts are required");
    }

    if ($from_account === $to_account) {
        throw new Exception("Cannot transfer to the same account");
    }

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Check if from_account belongs to user and is active
        $stmt = $conn->prepare("
            SELECT id, balance, account_number, status 
            FROM accounts 
            WHERE account_number = ? AND user_id = ? AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute([$from_account, $user_id]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sender) {
            throw new Exception("Source account not found or inactive");
        }

        // Check if sender has sufficient balance
        if ($sender['balance'] < $amount) {
            throw new Exception("Insufficient funds. Available balance: " . number_format($sender['balance'], 2) . " ETB");
        }

        // Check if receiver account exists and is active
        $stmt = $conn->prepare("
            SELECT a.id, a.balance, a.account_number, a.user_id, u.fullname, u.email
            FROM accounts a
            JOIN users u ON a.user_id = u.id
            WHERE a.account_number = ? AND a.status = 'active'
            FOR UPDATE
        ");
        $stmt->execute([$to_account]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receiver) {
            throw new Exception("Destination account not found or inactive");
        }

        // Calculate fee (0.5% for transfers, max 50 ETB)
        $fee = min($amount * 0.005, 50);
        $total_deduction = $amount + $fee;

        // Update sender balance
        $new_sender_balance = $sender['balance'] - $total_deduction;
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_sender_balance, $sender['id']]);

        // Update receiver balance
        $new_receiver_balance = $receiver['balance'] + $amount;
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_receiver_balance, $receiver['id']]);

        // Generate reference number
        $reference = generateReferenceNumber();

        // Record transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                sender_account_id, 
                receiver_account_id, 
                amount, 
                description, 
                type, 
                status, 
                reference_number,
                fee,
                balance_after_sender,
                balance_after_receiver,
                created_at
            ) VALUES (?, ?, ?, ?, 'transfer', 'completed', ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $sender['id'],
            $receiver['id'],
            $amount,
            $description ?: "Transfer to account " . substr($to_account, -4),
            $reference,
            $fee,
            $new_sender_balance,
            $new_receiver_balance
        ]);

        $transaction_id = $conn->lastInsertId();

        // Create notifications for both parties
        createNotification($conn, $user_id, 'transfer_sent', [
            'amount' => $amount,
            'to_account' => $to_account,
            'to_name' => $receiver['fullname'],
            'reference' => $reference,
            'fee' => $fee
        ]);

        createNotification($conn, $receiver['user_id'], 'transfer_received', [
            'amount' => $amount,
            'from_account' => $from_account,
            'from_name' => $_SESSION['user_fullname'],
            'reference' => $reference
        ]);

        // Save as beneficiary if requested
        if ($save_beneficiary) {
            saveBeneficiary($conn, $user_id, $receiver['user_id'], $receiver['account_number']);
        }

        // Log the transaction
        logTransaction($conn, $user_id, $transaction_id, 'transfer');

        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Transfer completed successfully",
            "data" => [
                "reference" => $reference,
                "amount" => number_format($amount, 2),
                "fee" => number_format($fee, 2),
                "total" => number_format($total_deduction, 2),
                "from_account" => maskAccountNumber($from_account),
                "to_account" => maskAccountNumber($to_account),
                "to_name" => $receiver['fullname'],
                "new_balance" => number_format($new_sender_balance, 2),
                "transaction_id" => $transaction_id,
                "timestamp" => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Handle phone transfer (to phone number)
 */
function handlePhoneTransfer($conn, $user_id, $from_account, $to_phone, $amount, $description, $save_beneficiary)
{
    // Validate phone number
    if (!preg_match("/^(\+251[0-9]{9})|(0[0-9]{9})$/", $to_phone)) {
        throw new Exception("Invalid phone number format");
    }

    // Normalize phone number
    $to_phone = normalizePhoneNumber($to_phone);

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Find account associated with phone number
        $stmt = $conn->prepare("
            SELECT a.id, a.account_number, a.balance, a.user_id, u.fullname
            FROM accounts a
            JOIN user_profiles up ON a.user_id = up.user_id
            JOIN users u ON a.user_id = u.id
            WHERE up.phone = ? AND a.status = 'active'
            ORDER BY a.account_type = 'checking' DESC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$to_phone]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receiver) {
            throw new Exception("No active account found for phone number: " . $to_phone);
        }

        // Now do transfer using the found account
        handleTransfer(
            $conn,
            $user_id,
            $from_account,
            $receiver['account_number'],
            $amount,
            $description ?: "Transfer to " . $receiver['fullname'],
            $save_beneficiary
        );

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Handle deposit to own account
 */
function handleDeposit($conn, $user_id, $to_account, $amount, $description)
{
    if (empty($to_account)) {
        throw new Exception("Account number is required");
    }

    $conn->beginTransaction();

    try {
        // Verify account belongs to user and is active
        $stmt = $conn->prepare("
            SELECT id, balance, account_number 
            FROM accounts 
            WHERE account_number = ? AND user_id = ? AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute([$to_account, $user_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception("Account not found or inactive");
        }

        // Update balance
        $new_balance = $account['balance'] + $amount;
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $account['id']]);

        // Generate reference
        $reference = generateReferenceNumber();

        // Record transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                receiver_account_id,
                amount,
                description,
                type,
                status,
                reference_number,
                balance_after_receiver,
                created_at
            ) VALUES (?, ?, ?, 'deposit', 'completed', ?, ?, NOW())
        ");

        $stmt->execute([
            $account['id'],
            $amount,
            $description ?: "Cash deposit",
            $reference,
            $new_balance
        ]);

        $transaction_id = $conn->lastInsertId();

        // Create notification
        createNotification($conn, $user_id, 'deposit', [
            'amount' => $amount,
            'account' => maskAccountNumber($to_account),
            'reference' => $reference,
            'new_balance' => $new_balance
        ]);

        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Deposit completed successfully",
            "data" => [
                "reference" => $reference,
                "amount" => number_format($amount, 2),
                "account" => maskAccountNumber($to_account),
                "new_balance" => number_format($new_balance, 2),
                "transaction_id" => $transaction_id,
                "timestamp" => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Handle withdrawal from account
 */
function handleWithdrawal($conn, $user_id, $from_account, $amount, $description)
{
    if (empty($from_account)) {
        throw new Exception("Account number is required");
    }

    $conn->beginTransaction();

    try {
        // Verify account belongs to user and is active
        $stmt = $conn->prepare("
            SELECT id, balance, account_number 
            FROM accounts 
            WHERE account_number = ? AND user_id = ? AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute([$from_account, $user_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception("Account not found or inactive");
        }

        // Check sufficient balance (withdrawal fee applies)
        $fee = 20; // Fixed withdrawal fee of 20 ETB
        $total_deduction = $amount + $fee;

        if ($account['balance'] < $total_deduction) {
            throw new Exception("Insufficient funds. Available: " . number_format($account['balance'], 2) .
                " ETB, Required: " . number_format($total_deduction, 2) . " ETB");
        }

        // Update balance
        $new_balance = $account['balance'] - $total_deduction;
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $account['id']]);

        // Generate reference
        $reference = generateReferenceNumber();

        // Record transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                sender_account_id,
                amount,
                description,
                type,
                status,
                reference_number,
                fee,
                balance_after_sender,
                created_at
            ) VALUES (?, ?, ?, 'withdrawal', 'completed', ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $account['id'],
            $amount,
            $description ?: "Cash withdrawal",
            $reference,
            $fee,
            $new_balance
        ]);

        $transaction_id = $conn->lastInsertId();

        // Create notification
        createNotification($conn, $user_id, 'withdrawal', [
            'amount' => $amount,
            'fee' => $fee,
            'total' => $total_deduction,
            'account' => maskAccountNumber($from_account),
            'reference' => $reference,
            'new_balance' => $new_balance
        ]);

        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Withdrawal completed successfully",
            "data" => [
                "reference" => $reference,
                "amount" => number_format($amount, 2),
                "fee" => number_format($fee, 2),
                "total" => number_format($total_deduction, 2),
                "account" => maskAccountNumber($from_account),
                "new_balance" => number_format($new_balance, 2),
                "transaction_id" => $transaction_id,
                "timestamp" => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Handle scheduled transfer
 */
function handleScheduledTransfer($conn, $user_id, $from_account, $to_account, $amount, $description, $scheduled_date)
{
    if (empty($scheduled_date)) {
        throw new Exception("Scheduled date is required");
    }

    // Validate date is in the future
    $scheduled_timestamp = strtotime($scheduled_date);
    if ($scheduled_timestamp < time()) {
        throw new Exception("Scheduled date must be in the future");
    }

    $conn->beginTransaction();

    try {
        // Verify accounts exist (but don't deduct balance yet)
        $stmt = $conn->prepare("
            SELECT id, account_number, balance 
            FROM accounts 
            WHERE account_number = ? AND user_id = ? AND status = 'active'
        ");
        $stmt->execute([$from_account, $user_id]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sender) {
            throw new Exception("Source account not found or inactive");
        }

        // Check if sender has sufficient balance (will be checked at execution time)
        if ($sender['balance'] < $amount) {
            throw new Exception("Insufficient funds for scheduled transfer");
        }

        $stmt = $conn->prepare("
            SELECT id, account_number 
            FROM accounts 
            WHERE account_number = ? AND status = 'active'
        ");
        $stmt->execute([$to_account]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receiver) {
            throw new Exception("Destination account not found or inactive");
        }

        // Generate reference
        $reference = generateReferenceNumber('SCH');

        // Create scheduled transaction
        $stmt = $conn->prepare("
            INSERT INTO scheduled_transactions (
                user_id,
                sender_account_id,
                receiver_account_id,
                amount,
                description,
                scheduled_date,
                reference_number,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->execute([
            $user_id,
            $sender['id'],
            $receiver['id'],
            $amount,
            $description ?: "Scheduled transfer",
            $scheduled_date,
            $reference
        ]);

        $schedule_id = $conn->lastInsertId();

        // Create notification
        createNotification($conn, $user_id, 'scheduled', [
            'amount' => $amount,
            'to_account' => maskAccountNumber($to_account),
            'date' => date('M d, Y', $scheduled_timestamp),
            'reference' => $reference
        ]);

        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Transfer scheduled successfully",
            "data" => [
                "reference" => $reference,
                "amount" => number_format($amount, 2),
                "to_account" => maskAccountNumber($to_account),
                "scheduled_date" => date('Y-m-d H:i:s', $scheduled_timestamp),
                "schedule_id" => $schedule_id
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Generate unique reference number
 */
function generateReferenceNumber($prefix = 'TXN')
{
    return $prefix . date('Ymd') . strtoupper(substr(uniqid(), -6)) . rand(100, 999);
}

/**
 * Mask account number for display
 */
function maskAccountNumber($account_number)
{
    return '****' . substr($account_number, -4);
}

/**
 * Normalize phone number to international format
 */
function normalizePhoneNumber($phone)
{
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Convert to +251 format
    if (substr($phone, 0, 1) == '0') {
        $phone = '+251' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) == '251') {
        $phone = '+' . $phone;
    }

    return $phone;
}

/**
 * Create notification for user
 */
function createNotification($conn, $user_id, $type, $data)
{
    $titles = [
        'transfer_sent' => 'Transfer Sent',
        'transfer_received' => 'Transfer Received',
        'deposit' => 'Deposit Successful',
        'withdrawal' => 'Withdrawal Successful',
        'scheduled' => 'Transfer Scheduled'
    ];

    $messages = [
        'transfer_sent' => "You sent {$data['amount']} ETB to {$data['to_name']} (Ref: {$data['reference']}). Fee: {$data['fee']} ETB",
        'transfer_received' => "You received {$data['amount']} ETB from {$data['from_name']} (Ref: {$data['reference']})",
        'deposit' => "Deposit of {$data['amount']} ETB to account {$data['account']}. New balance: {$data['new_balance']} ETB",
        'withdrawal' => "Withdrawal of {$data['amount']} ETB (Fee: {$data['fee']} ETB) from account {$data['account']}",
        'scheduled' => "Transfer of {$data['amount']} ETB to {$data['to_account']} scheduled for {$data['date']}"
    ];

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, created_at) 
        VALUES (?, ?, ?, 'transaction', NOW())
    ");

    $stmt->execute([$user_id, $titles[$type], $messages[$type]]);
}

/**
 * Log transaction for audit
 */
function logTransaction($conn, $user_id, $transaction_id, $action)
{
    $stmt = $conn->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, ip_address, user_agent, created_at) 
        VALUES (?, ?, 'transactions', ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $user_id,
        $action,
        $transaction_id,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Save beneficiary
 */
function saveBeneficiary($conn, $user_id, $beneficiary_user_id, $account_number)
{
    // Check if already saved
    $stmt = $conn->prepare("
        SELECT id FROM beneficiaries 
        WHERE user_id = ? AND beneficiary_account = ?
    ");
    $stmt->execute([$user_id, $account_number]);

    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("
            INSERT INTO beneficiaries (user_id, beneficiary_user_id, beneficiary_account, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $beneficiary_user_id, $account_number]);
    }
}