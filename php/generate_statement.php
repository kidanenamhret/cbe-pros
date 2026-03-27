<?php
require_once 'php/auth.php';
require_once 'php/db.php';

checkLogin();
$user_id = $_SESSION['user_id'];
$account_number = $_GET['account'] ?? null;

if (!$account_number) {
    die("Error: Account number missing.");
}

try {
    // Verify account ownership
    $stmt = $conn->prepare("SELECT id, account_number, balance, currency, account_type FROM accounts WHERE account_number = ? AND user_id = ?");
    $stmt->execute([$account_number, $user_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        die("Unauthorized access to account.");
    }

    // Get transactions with forensic metadata
    $stmt = $conn->prepare("
        SELECT t.*, 
               DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i') as date,
               CASE 
                   WHEN t.sender_account_id = ? THEN 'Debit'
                   WHEN t.receiver_account_id = ? THEN 'Credit'
               END as entry_type
        FROM transactions t
        WHERE t.sender_account_id = ? OR t.receiver_account_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$account['id'], $account['id'], $account['id'], $account['id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CBE-Pros Official Statement - <?php echo $account_number; ?></title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f0f0f0; margin: 0; padding: 40px; }
        .statement-container { background: white; width: 900px; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; }
        
        /* Official Purple Header */
        .purple-header { background: #800080; color: white; padding: 30px; text-align: center; }
        .purple-header h1 { margin: 0; font-size: 28px; letter-spacing: 1px; }
        .purple-header p { margin: 5px 0 0 0; font-size: 16px; opacity: 0.9; }

        .content { padding: 40px; }

        /* Summary Grid */
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; border-bottom: 2px solid #800080; padding-bottom: 20px; }
        .summary-box h3 { font-size: 13px; color: #800080; text-transform: uppercase; margin-bottom: 10px; }
        .summary-box p { margin: 5px 0; font-size: 15px; }

        /* Transaction Table */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; color: #800080; text-align: left; padding: 12px; border-bottom: 2px solid #800080; font-size: 12px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; color: #444; }
        .debit { color: #e53e3e; font-weight: 600; }
        .credit { color: #38a169; font-weight: 600; }

        /* Stamp Overlay (subtle) */
        .stamp-overlay { position: absolute; top: 150px; right: 50px; transform: rotate(-15deg); opacity: 0.15; pointer-events: none; }
        .stamp-circle { border: 4px double #003399; border-radius: 50%; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; text-align: center; color: #003399; font-weight: 700; font-size: 10px; }

        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #888; }
        .no-print { position: fixed; bottom: 20px; right: 20px; }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } .statement-container { box-shadow: none; width: 100%; } }
    </style>
</head>
<body>

    <div class="statement-container">
        <div class="stamp-overlay">
            <div class="stamp-circle">CBE-PROS<br>OFFICIAL<br>LEDGER<br>ENTRY</div>
        </div>

        <div class="purple-header">
            <h1>CBE-Pros Digital Banking</h1>
            <p>Official Account Statement (Full History)</p>
        </div>

        <div class="content">
            <div class="summary-grid">
                <div class="summary-box">
                    <h3>Account Holder</h3>
                    <p><strong><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></strong></p>
                    <p>Customer ID: #<?php echo str_pad($user_id, 6, '0', STR_PAD_LEFT); ?></p>
                    <p>Email: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <div class="summary-box" style="text-align: right;">
                    <h3>Account Summary</h3>
                    <p>ACC NO: <strong><?php echo $account_number; ?></strong></p>
                    <p>Type: <?php echo ucfirst($account['account_type']); ?></p>
                    <p style="font-size: 20px; color: #800080; margin-top: 10px;">Balance: <?php echo number_format($account['balance'], 2); ?> <?php echo $account['currency']; ?></p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px;">No transaction records found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?php echo $t['date']; ?></td>
                            <td style="font-family: monospace; font-size: 11px;"><?php echo $t['reference_number'] ?? 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($t['description']); ?></td>
                            <td class="<?php echo strtolower($t['entry_type']); ?>"><?php echo $t['entry_type']; ?></td>
                            <td style="text-align: right;" class="<?php echo strtolower($t['entry_type']); ?>">
                                <?php echo ($t['entry_type'] == 'Debit' ? '-' : '+'); ?> 
                                <?php echo number_format($t['amount'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer">
                <p>This statement is electronically generated and verified by the CBE-Pros Digital Core Engine. <br>
                Generated on: <?php echo date('Y-m-d H:i:s'); ?> | Server IP: <?php echo $_SERVER['SERVER_ADDR']; ?></p>
                <h4 style="color: #800080; margin-top: 15px;">CBE-Pros: Modern Banking, Universal Trust.</h4>
            </div>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 15px 30px; background: #800080; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: 700; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">Print Full Ledger</button>
    </div>

</body>
</html>
