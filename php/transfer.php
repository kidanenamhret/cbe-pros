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
$sender_user_id = $_SESSION['user_id'];

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request origin"
    ]);
    exit();
}

// Get and validate input
$receiver_username = trim($_POST['receiver_username'] ?? '');
$receiver_account = trim($_POST['receiver_account'] ?? '');
$receiver_phone = trim($_POST['receiver_phone'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');
$from_account = trim($_POST['from_account'] ?? '');
$transfer_type = $_POST['transfer_type'] ?? 'username'; // username, account, phone
$schedule_date = $_POST['schedule_date'] ?? '';
$is_scheduled = !empty($schedule_date);
$save_beneficiary = isset($_POST['save_beneficiary']) ? true : false;
$is_urgent = isset($_POST['urgent']) ? true : false;

// Basic validation
if (empty($receiver_username) && empty($receiver_account) && empty($receiver_phone)) {
    echo json_encode([
        "status" => "error",
        "message" => "Receiver information is required"
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

if ($amount > 1000000) {
    echo json_encode([
        "status" => "error",
        "message" => "Maximum transfer amount is 1,000,000 ETB"
    ]);
    exit();
}

try {
    // Handle scheduled transfer
    if ($is_scheduled) {
        handleScheduledTransfer($conn, $sender_user_id, $from_account, $receiver_username, $receiver_account, $receiver_phone, $amount, $description, $schedule_date, $transfer_type);
    } else {
        // Handle immediate transfer
        handleImmediateTransfer($conn, $sender_user_id, $from_account, $receiver_username, $receiver_account, $receiver_phone, $amount, $description, $transfer_type, $save_beneficiary, $is_urgent);
    }

} catch (Exception $e) {
    error_log("Transfer error for user {$sender_user_id}: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

/**
 * Handle immediate transfer
 */
function handleImmediateTransfer($conn, $sender_user_id, $from_account, $receiver_username, $receiver_account, $receiver_phone, $amount, $description, $transfer_type, $save_beneficiary, $is_urgent)
{

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Get sender account(s)
        $sender_accounts = getSenderAccounts($conn, $sender_user_id, $from_account);

        if (empty($sender_accounts)) {
            throw new Exception("No active account found for transfer");
        }

        // Use specified account or first active account
        $sender_account = $sender_accounts[0];

        // Find receiver based on transfer type
        $receiver = findReceiver($conn, $receiver_username, $receiver_account, $receiver_phone, $transfer_type);

        if (!$receiver) {
            throw new Exception("Receiver not found");
        }

        // Check if trying to transfer to self
        if ($sender_account['user_id'] == $receiver['user_id']) {
            throw new Exception("Cannot transfer to your own account");
        }

        // Calculate fees
        $fees = calculateTransferFees($amount, $is_urgent);
        $total_deduction = $amount + $fees['total_fee'];

        // Check sufficient balance
        if ($sender_account['balance'] < $total_deduction) {
            throw new Exception("Insufficient funds. Available: " . number_format($sender_account['balance'], 2) .
                " ETB, Required: " . number_format($total_deduction, 2) . " ETB");
        }

        // Daily limit check
        checkDailyLimit($conn, $sender_account['id'], $amount);

        // Update sender balance
        $new_sender_balance = $sender_account['balance'] - $total_deduction;
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_sender_balance, $sender_account['id']]);

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
                metadata,
                created_at
            ) VALUES (?, ?, ?, ?, 'transfer', 'completed', ?, ?, ?, ?, ?, NOW())
        ");

        $metadata = json_encode([
            'urgent' => $is_urgent,
            'transfer_type' => $transfer_type,
            'fees_breakdown' => $fees
        ]);

        $stmt->execute([
            $sender_account['id'],
            $receiver['id'],
            $amount,
            $description ?: "Transfer to " . ($receiver['fullname'] ?? $receiver['account_number']),
            $reference,
            $fees['total_fee'],
            $new_sender_balance,
            $new_receiver_balance,
            $metadata
        ]);

        $transaction_id = $conn->lastInsertId();

        // Create notifications
        createTransferNotifications($conn, $sender_user_id, $receiver['user_id'], [
            'amount' => $amount,
            'fee' => $fees['total_fee'],
            'from_account' => $sender_account['account_number'],
            'to_account' => $receiver['account_number'],
            'to_name' => $receiver['fullname'],
            'reference' => $reference,
            'description' => $description
        ]);

        // Save as beneficiary if requested
        if ($save_beneficiary) {
            saveBeneficiary($conn, $sender_user_id, $receiver['user_id'], $receiver['account_number'], $receiver['fullname']);
        }

        // Update daily transfer total
        updateDailyTransferTotal($conn, $sender_account['id'], $amount);

        // Log the transaction
        logTransferActivity($conn, $sender_user_id, $transaction_id, $reference);

        $conn->commit();

        // Return success response
        echo json_encode([
            "status" => "success",
            "message" => "Transfer completed successfully!",
            "data" => [
                "reference" => $reference,
                "amount" => number_format($amount, 2),
                "fee" => number_format($fees['total_fee'], 2),
                "total" => number_format($total_deduction, 2),
                "from_account" => maskAccountNumber($sender_account['account_number']),
                "to_account" => maskAccountNumber($receiver['account_number']),
                "to_name" => $receiver['fullname'],
                "new_balance" => number_format($new_sender_balance, 2),
                "transaction_id" => $transaction_id,
                "timestamp" => date('Y-m-d H:i:s'),
                "fees_breakdown" => $fees
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
function handleScheduledTransfer($conn, $sender_user_id, $from_account, $receiver_username, $receiver_account, $receiver_phone, $amount, $description, $schedule_date, $transfer_type)
{

    // Validate schedule date
    $scheduled_timestamp = strtotime($schedule_date);
    if ($scheduled_timestamp < strtotime('+1 hour')) {
        throw new Exception("Scheduled time must be at least 1 hour from now");
    }

    if ($scheduled_timestamp > strtotime('+1 year')) {
        throw new Exception("Cannot schedule transfers more than 1 year in advance");
    }

    $conn->beginTransaction();

    try {
        // Get sender account
        $sender_accounts = getSenderAccounts($conn, $sender_user_id, $from_account);
        if (empty($sender_accounts)) {
            throw new Exception("No active account found");
        }
        $sender_account = $sender_accounts[0];

        // Find receiver
        $receiver = findReceiver($conn, $receiver_username, $receiver_account, $receiver_phone, $transfer_type);
        if (!$receiver) {
            throw new Exception("Receiver not found");
        }

        // Check if trying to transfer to self
        if ($sender_account['user_id'] == $receiver['user_id']) {
            throw new Exception("Cannot schedule transfer to your own account");
        }

        // Check if sender has sufficient balance at scheduling time
        if ($sender_account['balance'] < $amount) {
            throw new Exception("Insufficient funds for scheduled transfer. Available: " .
                number_format($sender_account['balance'], 2) . " ETB");
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
                metadata,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");

        $metadata = json_encode([
            'transfer_type' => $transfer_type,
            'receiver_info' => [
                'username' => $receiver_username,
                'account' => $receiver_account,
                'phone' => $receiver_phone,
                'fullname' => $receiver['fullname']
            ]
        ]);

        $stmt->execute([
            $sender_user_id,
            $sender_account['id'],
            $receiver['id'],
            $amount,
            $description ?: "Scheduled transfer",
            date('Y-m-d H:i:s', $scheduled_timestamp),
            $reference,
            $metadata
        ]);

        $schedule_id = $conn->lastInsertId();

        // Create notification
        createScheduledNotification($conn, $sender_user_id, [
            'amount' => $amount,
            'to_name' => $receiver['fullname'],
            'to_account' => maskAccountNumber($receiver['account_number']),
            'date' => date('M d, Y H:i', $scheduled_timestamp),
            'reference' => $reference
        ]);

        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Transfer scheduled successfully",
            "data" => [
                "reference" => $reference,
                "amount" => number_format($amount, 2),
                "to_name" => $receiver['fullname'],
                "to_account" => maskAccountNumber($receiver['account_number']),
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
 * Get sender accounts
 */
function getSenderAccounts($conn, $user_id, $specific_account = '')
{
    if (!empty($specific_account)) {
        $stmt = $conn->prepare("
            SELECT id, account_number, balance, user_id, currency, account_type
            FROM accounts 
            WHERE account_number = ? AND user_id = ? AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute([$specific_account, $user_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        return $account ? [$account] : [];
    } else {
        $stmt = $conn->prepare("
            SELECT id, account_number, balance, user_id, currency, account_type
            FROM accounts 
            WHERE user_id = ? AND status = 'active'
            ORDER BY 
                CASE account_type 
                    WHEN 'checking' THEN 1 
                    WHEN 'savings' THEN 2 
                    ELSE 3 
                END
            FOR UPDATE
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Find receiver based on search criteria
 */
function findReceiver($conn, $username, $account, $phone, $type)
{
    $receiver = null;

    switch ($type) {
        case 'username':
            if (!empty($username)) {
                $stmt = $conn->prepare("
                    SELECT a.id, a.account_number, a.balance, a.user_id, 
                           u.fullname, u.email, u.username
                    FROM accounts a
                    JOIN users u ON a.user_id = u.id
                    WHERE (u.username = ? OR u.email = ?) 
                      AND a.status = 'active'
                    ORDER BY a.account_type = 'checking' DESC
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmt->execute([$username, $username]);
                $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;

        case 'account':
            if (!empty($account)) {
                $stmt = $conn->prepare("
                    SELECT a.id, a.account_number, a.balance, a.user_id, 
                           u.fullname, u.email, u.username
                    FROM accounts a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.account_number = ? AND a.status = 'active'
                    FOR UPDATE
                ");
                $stmt->execute([$account]);
                $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;

        case 'phone':
            if (!empty($phone)) {
                $phone = normalizePhoneNumber($phone);
                $stmt = $conn->prepare("
                    SELECT a.id, a.account_number, a.balance, a.user_id, 
                           u.fullname, u.email, u.username
                    FROM accounts a
                    JOIN users u ON a.user_id = u.id
                    JOIN user_profiles up ON u.id = up.user_id
                    WHERE up.phone = ? AND a.status = 'active'
                    ORDER BY a.account_type = 'checking' DESC
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmt->execute([$phone]);
                $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;
    }

    return $receiver;
}

/**
 * Calculate transfer fees
 */
function calculateTransferFees($amount, $is_urgent = false)
{
    $fees = [
        'base_fee' => 0,
        'urgent_fee' => 0,
        'percentage_fee' => 0,
        'total_fee' => 0
    ];

    // Base fee
    $fees['base_fee'] = 5; // 5 ETB base fee

    // Percentage fee (0.5% for amounts over 1000)
    if ($amount > 1000) {
        $fees['percentage_fee'] = min($amount * 0.005, 100); // Max 100 ETB
    }

    // Urgent fee (additional 1% for urgent transfers)
    if ($is_urgent) {
        $fees['urgent_fee'] = min($amount * 0.01, 200); // Max 200 ETB
    }

    $fees['total_fee'] = $fees['base_fee'] + $fees['percentage_fee'] + $fees['urgent_fee'];

    return $fees;
}

/**
 * Check daily transfer limit
 */
function checkDailyLimit($conn, $account_id, $amount)
{
    $daily_limit = 100000; // 100,000 ETB daily limit

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as daily_total
        FROM transactions
        WHERE sender_account_id = ?
          AND type = 'transfer'
          AND DATE(created_at) = CURDATE()
          AND status = 'completed'
    ");
    $stmt->execute([$account_id]);
    $daily_total = $stmt->fetch(PDO::FETCH_ASSOC)['daily_total'];

    if (($daily_total + $amount) > $daily_limit) {
        throw new Exception("Daily transfer limit of " . number_format($daily_limit, 2) .
            " ETB exceeded. Available today: " .
            number_format($daily_limit - $daily_total, 2) . " ETB");
    }
}

/**
 * Update daily transfer total
 */
function updateDailyTransferTotal($conn, $account_id, $amount)
{
    // This is handled by the transaction record, but we could update a cache if needed
}

/**
 * Create transfer notifications
 */
function createTransferNotifications($conn, $sender_id, $receiver_id, $data)
{
    // Notification for sender
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, metadata, created_at) 
        VALUES (?, 'Transfer Sent', ?, 'transaction', ?, NOW())
    ");

    $sender_message = "You sent {$data['amount']} ETB to {$data['to_name']} (Ref: {$data['reference']}). Fee: {$data['fee']} ETB";
    $sender_metadata = json_encode(['type' => 'transfer_sent', 'data' => $data]);
    $stmt->execute([$sender_id, $sender_message, $sender_metadata]);

    // Notification for receiver
    $receiver_message = "You received {$data['amount']} ETB from {$data['to_name']} (Ref: {$data['reference']})";
    $receiver_metadata = json_encode(['type' => 'transfer_received', 'data' => $data]);
    $stmt->execute([$receiver_id, $receiver_message, $receiver_metadata]);
}

/**
 * Create scheduled transfer notification
 */
function createScheduledNotification($conn, $user_id, $data)
{
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, metadata, created_at) 
        VALUES (?, 'Transfer Scheduled', ?, 'transaction', ?, NOW())
    ");

    $message = "Transfer of {$data['amount']} ETB to {$data['to_name']} scheduled for {$data['date']}";
    $metadata = json_encode(['type' => 'scheduled', 'data' => $data]);
    $stmt->execute([$user_id, $message, $metadata]);
}

/**
 * Save beneficiary
 */
function saveBeneficiary($conn, $user_id, $beneficiary_user_id, $account_number, $beneficiary_name)
{
    // Check if already saved
    $stmt = $conn->prepare("
        SELECT id FROM beneficiaries 
        WHERE user_id = ? AND beneficiary_account = ?
    ");
    $stmt->execute([$user_id, $account_number]);

    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("
            INSERT INTO beneficiaries (
                user_id, 
                beneficiary_user_id, 
                beneficiary_account, 
                beneficiary_name,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $beneficiary_user_id, $account_number, $beneficiary_name]);
    }
}

/**
 * Log transfer activity
 */
function logTransferActivity($conn, $user_id, $transaction_id, $reference)
{
    $stmt = $conn->prepare("
        INSERT INTO audit_log (
            user_id, 
            action, 
            table_name, 
            record_id, 
            ip_address, 
            user_agent,
            new_values,
            created_at
        ) VALUES (?, 'transfer', 'transactions', ?, ?, ?, ?, NOW())
    ");

    $new_values = json_encode([
        'transaction_id' => $transaction_id,
        'reference' => $reference,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    $stmt->execute([
        $user_id,
        $transaction_id,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $new_values
    ]);
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
 * Normalize phone number
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
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
?>