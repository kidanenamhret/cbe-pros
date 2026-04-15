<?php
require_once 'php/auth.php';
require_once 'php/db.php';

// Check login first
checkLogin();

// If not merchant, redirect to onboard
if (!isset($_SESSION['is_merchant']) || !$_SESSION['is_merchant']) {
    header('Location: merchant_onboard.php');
    exit();
}

require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$business_name = $_SESSION['business_name'] ?? 'Your Business';

// Get merchant account
$stmt = $conn->prepare("SELECT id, account_number, balance FROM accounts WHERE user_id = ? AND account_number LIKE 'MER-%'");
$stmt->execute([$user_id]);
$merchant_acc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$merchant_acc) {
    die("Merchant account not found. Please contact support.");
}

$stmt = $conn->prepare("
    SELECT COUNT(*) as total_sales, SUM(amount) as total_volume 
    FROM transactions 
    WHERE receiver_account_id = ? AND status = 'completed'
");
$stmt->execute([$merchant_acc['id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="section active" style="display:block;">
    <div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="color: #333; margin-bottom: 5px;"><?php echo htmlspecialchars($business_name); ?> Dashboard</h1>
                <p style="color: #666; font-size: 14px;">Merchant Hub • Account No: <?php echo $merchant_acc['account_number']; ?></p>
            </div>
            <div style="background: white; border-radius: 12px; padding: 10px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
                <div style="text-align: right;">
                    <div style="font-size: 11px; color: #888; text-transform: uppercase;">Business Balance</div>
                    <div style="font-size: 18px; font-weight: 700; color: #800080;"><?php echo number_format($merchant_acc['balance'], 2); ?> ETB</div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 40px;">
            <div style="background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center;">
                <div style="color: #888; font-size: 14px; margin-bottom: 10px;">Total Sales (Count)</div>
                <div style="font-size: 32px; font-weight: 800; color: #333;"><?php echo $stats['total_sales'] ?? 0; ?></div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center;">
                <div style="color: #888; font-size: 14px; margin-bottom: 10px;">Total Volume</div>
                <div style="font-size: 32px; font-weight: 800; color: #333;"><?php echo number_format($stats['total_volume'] ?? 0, 2); ?> ETB</div>
            </div>
            <div style="background: #800080; padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px rgba(128,0,128,0.2); text-align: center; color: white;">
                <div style="font-size: 14px; margin-bottom: 10px; opacity: 0.8;">Today's Earnings</div>
                <div style="font-size: 32px; font-weight: 800;"><?php echo number_format(0, 2); ?> ETB</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <!-- Merchant Static QR -->
            <div style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center;">
                <h3 style="margin-bottom: 25px; color: #333;">Your Shop QR Code</h3>
                <div id="merchantQRBox" style="margin: 0 auto 30px auto; background: white; padding: 15px; display: inline-block; border: 1px dashed #ddd; border-radius: 12px;"></div>
                <p style="font-size: 12px; color: #666; width: 80%; margin: 0 auto 25px auto;">Print this QR code and display it at your store. Customers can scan it to pay you instantly.</p>
                <button onclick="window.print()" class="btn" style="width: 100%; padding: 14px; background: #667eea; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Print Shop Poster</button>
            </div>

            <!-- Recent Sales Table -->
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="color: #333; margin: 0;">Recent Business Payments</h3>
                    <a href="transactions.php" style="color: #800080; font-size: 13px; text-decoration: none; font-weight: 600;">Full Sales Report →</a>
                </div>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; background: #f8f9fa;">
                                <th style="padding: 12px; font-size: 12px; color: #666;">DATE</th>
                                <th style="padding: 12px; font-size: 12px; color: #666;">CUSTOMER</th>
                                <th style="padding: 12px; font-size: 12px; color: #666;">AMOUNT</th>
                                <th style="padding: 12px; font-size: 12px; color: #666;">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $conn->prepare("
                                SELECT t.*, u.fullname as customer_name, DATE_FORMAT(t.created_at, '%b %d, %H:%i') as date
                                FROM transactions t
                                JOIN accounts acc ON t.sender_account_id = acc.id
                                JOIN users u ON acc.user_id = u.id
                                WHERE t.receiver_account_id = ? AND t.status = 'completed'
                                ORDER BY t.created_at DESC LIMIT 8
                            ");
                            $stmt->execute([$merchant_acc['id']]);
                            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($sales)) {
                                echo '<tr><td colspan="4" style="padding: 40px; text-align: center; color: #888;">No sales recorded yet.</td></tr>';
                            } else {
                                foreach ($sales as $s) {
                                    echo "
                                    <tr style='border-bottom: 1px solid #f8f9fa;'>
                                        <td style='padding: 12px; font-size: 13px;'>{$s['date']}</td>
                                        <td style='padding: 12px; font-size: 13px; font-weight: 600;'>{$s['customer_name']}</td>
                                        <td style='padding: 12px; font-size: 13px; color: #38a169; font-weight: 700;'>+".number_format($s['amount'], 2)." ETB</td>
                                        <td style='padding: 12px;'><span style='background: #e6fffa; color: #234e52; padding: 4px 8px; border-radius: 5px; font-size: 11px;'>SUCCESS</span></td>
                                    </tr>
                                    ";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("merchantQRBox"), {
        text: "<?php echo $merchant_acc['account_number']; ?>",
        width: 200,
        height: 200,
        colorDark : "#800080",
        colorLight : "#ffffff"
    });
</script>

<?php require_once 'includes/footer.php'; ?>
