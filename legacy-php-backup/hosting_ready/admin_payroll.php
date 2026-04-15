<?php
require_once 'includes/header.php';

// Only Admin access
if ($_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access. Admin only.");
}

$message = "";

// Handle Payroll Execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payroll_csv'])) {
    $file = $_FILES['payroll_csv']['tmp_name'];
    $handle = fopen($file, "r");
    fgetcsv($handle); // Skip header

    $success_count = 0;
    $total_disbursed = 0;

    $conn->beginTransaction();
    try {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $acc_num = trim($data[0]);
            $amount = floatval($data[1]);
            $note = trim($data[2]);

            // Find receiver
            $stmt = $conn->prepare("SELECT id, user_id, balance FROM accounts WHERE account_number = ? AND status = 'active'");
            $stmt->execute([$acc_num]);
            $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($receiver) {
                // Update balance
                $new_bal = $receiver['balance'] + $amount;
                $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
                $stmt->execute([$new_bal, $receiver['id']]);

                // Record transaction
                $ref = "PAY" . date('Ymd') . rand(1000, 9999);
                $stmt = $conn->prepare("INSERT INTO transactions (receiver_account_id, amount, description, type, status, reference_number) VALUES (?, ?, ?, 'transfer', 'completed', ?)");
                $stmt->execute([$receiver['id'], $amount, "Payroll: " . ($note ?: "Batch Payment"), $ref]);

                $success_count++;
                $total_disbursed += $amount;
            }
        }
        $conn->commit();
        $message = "🎉 Payroll Executed! Successfully paid $success_count users. Total disbursed: " . number_format($total_disbursed, 2) . " ETB";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "❌ Payroll Failed: " . $e->getMessage();
    }
}
?>

<div class="section active" style="display:block;">
    <div style="max-width: 800px; margin: 40px auto; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: rgba(128,0,128,0.1); color: #800080; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 32px;">
                <i class="fas fa-money-check-alt"></i>
            </div>
            <h2 style="color: #333;">Smart Payroll Hub</h2>
            <p style="color: #666; font-size: 14px;">Batch process salary and distributions for employees or groups.</p>
        </div>

        <?php if ($message): ?>
            <div style="padding: 15px; border-radius: 10px; background: #e6fffa; color: #234e52; text-align: center; margin-bottom: 30px; border: 1px solid #38a169;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px dashed #ccc; margin-bottom: 30px;">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">CSV Format Instructions</h4>
            <p style="font-size: 12px; color: #666;">Upload a CSV file with the following columns: <br><strong>Account_Number, Amount, Description</strong></p>
            <a href="#" style="font-size: 12px; color: #800080; display: block; margin-top: 10px; font-weight: 600;">Download Sample Template ↓</a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 10px;">Select Payroll File (CSV)</label>
                <input type="file" name="payroll_csv" accept=".csv" required style="width: 100%; padding: 15px; background: #fff; border: 2.5px solid #eee; border-radius: 15px; cursor: pointer; border-style: dotted;">
            </div>

            <button type="submit" class="btn" style="width: 100%; padding: 16px; background: #800080; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 20px rgba(128,0,128,0.2);">Execute Batch Payroll</button>
        </form>

        <a href="admin_users.php" style="display: block; text-align: center; margin-top: 30px; font-size: 13px; color: #888; text-decoration: none;">← Back to User Management</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
