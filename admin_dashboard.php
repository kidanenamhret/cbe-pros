<?php
require_once 'includes/header.php';

// Only Admin access
if ($_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access. Admin only.");
}

// Fetch Master Stats
try {
    // 1. Total Liquidity (Sum of all account balances)
    $stmt = $conn->query("SELECT SUM(balance) as total FROM accounts");
    $total_liquidity = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 2. User Count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // 3. Daily Transaction Count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $daily_tx_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // 4. Failed Login Attempts (Total Security Risk)
    $stmt = $conn->query("SELECT SUM(login_attempts) as sum FROM users");
    $security_risk = $stmt->fetch(PDO::FETCH_ASSOC)['sum'] ?? 0;

} catch (PDOException $e) {
    die("Stats collection failed: " . $e->getMessage());
}
?>

<div class="section active" style="display:block; padding: 40px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="color: #333; margin-bottom: 5px;">Admin Command Dashboard</h1>
                <p style="color: #666; font-size: 14px;">Master oversight of the CBE-Pros platform.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="admin_users.php" class="btn" style="background: white; border: 1.5px solid #ddd; color: #333;">Manage Users</a>
                <a href="admin_payroll.php" class="btn">Execute Payroll</a>
            </div>
        </div>

        <!-- Metric Grid -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 50px;">
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 10px; font-weight: 700;">🏦 Total Bank Liquidity</div>
                <div style="font-size: 24px; font-weight: 800; color: #800080;"><?php echo number_format($total_liquidity, 2); ?> ETB</div>
            </div>
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 10px; font-weight: 700;">👥 Registered Customers</div>
                <div style="font-size: 24px; font-weight: 800; color: #333;"><?php echo $user_count; ?> Accounts</div>
            </div>
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 10px; font-weight: 700;">💸 Daily Transactions</div>
                <div style="font-size: 24px; font-weight: 800; color: #333;"><?php echo $daily_tx_count; ?> Today</div>
            </div>
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 10px; font-weight: 700;">🚨 Security Risk Score</div>
                <div style="font-size: 24px; font-weight: 800; color: #e53e3e;"><?php echo $security_risk; ?> Attempt(s)</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Global Ledger Excerpt -->
            <div style="background: white; padding: 35px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 25px; color: #333; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-receipt" style="color: #800080;"></i> Global Real-Time Ledger
                </h3>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; background: #f8f9fa;">
                                <th style="padding: 12px; font-size: 11px; color: #666;">REF</th>
                                <th style="padding: 12px; font-size: 11px; color: #666;">DESCRIPTION</th>
                                <th style="padding: 12px; font-size: 11px; color: #666;">AMOUNT</th>
                                <th style="padding: 12px; font-size: 11px; color: #666;">LOCATION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $conn->query("
                                SELECT t.*, DATE_FORMAT(t.created_at, '%H:%i:%s') as time
                                FROM transactions t 
                                ORDER BY t.created_at DESC LIMIT 10
                            ");
                            $ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($ledger as $l) {
                                $entry_type = $l['sender_account_id'] ? 'DEBIT' : 'CREDIT';
                                echo "
                                <tr style='border-bottom: 1px solid #f8f9fa;'>
                                    <td style='padding: 12px; font-size: 12px; font-family: monospace;'>{$l['reference_number']}</td>
                                    <td style='padding: 12px; font-size: 13px;'>{$l['description']}</td>
                                    <td style='padding: 12px; font-size: 13px; font-weight: 700;'>".number_format($l['amount'], 2)." ETB</td>
                                    <td style='padding: 12px; font-size: 11px; color: #888;'>{$l['ip_address']}</td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Server Health -->
            <div style="background: #1a202c; padding: 35px; border-radius: 24px; color: white; display: flex; flex-direction: column; justify-content: center;">
                <h3 style="margin-bottom: 20px; font-size: 18px; color: #800080;">Node Health</h3>
                <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 15px;">
                    <div style="font-size: 11px; color: #888; margin-bottom: 5px;">Server Time</div>
                    <div style="font-family: monospace;"><?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
                <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 15px;">
                    <div style="font-size: 11px; color: #888; margin-bottom: 5px;">Database Status</div>
                    <div style="color: #38a169;"><i class="fas fa-check-circle"></i> Connection Active</div>
                </div>
                <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 12px;">
                    <div style="font-size: 11px; color: #888; margin-bottom: 5px;">Engine Runtime</div>
                    <div style="font-family: monospace;">Apache/2.4.52 (XAMPP)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
