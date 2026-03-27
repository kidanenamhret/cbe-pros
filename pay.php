<?php
require_once 'includes/header.php';
?>

<div class="section active" style="display:block;">
    <div id="pay-section" class="section">
        <div class="welcome-header">
            <div>
                <h2>Bill Payment Hub</h2>
                <p>Pay your utilities and services directly from your account.</p>
            </div>
        </div>

        <div class="pay-categories" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 25px;">
            <!-- Electricity -->
            <div class="account-card" style="text-align: center; cursor: pointer;" onclick="openPaymentModal('Electricity', 'power-off')">
                <i class="fas fa-bolt" style="font-size: 40px; color: #f6e05e; margin-bottom: 15px;"></i>
                <h3>Electricity</h3>
                <p style="font-size: 14px; opacity: 0.8;">EEU Prepaid/Postpaid</p>
                <button class="btn" style="margin-top: 15px; width: auto; padding: 10px 20px;">Pay Now</button>
            </div>

            <!-- Water -->
            <div class="account-card" style="text-align: center; cursor: pointer;" onclick="openPaymentModal('Water', 'tint')">
                <i class="fas fa-tint" style="font-size: 40px; color: #4299e1; margin-bottom: 15px;"></i>
                <h3>Water</h3>
                <p style="font-size: 14px; opacity: 0.8;">Addis Ababa Water Authority</p>
                <button class="btn" style="margin-top: 15px; width: auto; padding: 10px 20px;">Pay Now</button>
            </div>

            <!-- Internet/Tele -->
            <div class="account-card" style="text-align: center; cursor: pointer;" onclick="openPaymentModal('Internet', 'wifi')">
                <i class="fas fa-wifi" style="font-size: 40px; color: #4fd1c5; margin-bottom: 15px;"></i>
                <h3>Telecom / Internet</h3>
                <p style="font-size: 14px; opacity: 0.8;">Ethio Telecom / Safaricom</p>
                <button class="btn" style="margin-top: 15px; width: auto; padding: 10px 20px;">Pay Now</button>
            </div>
            
            <!-- School Fees -->
            <div class="account-card" style="text-align: center; cursor: pointer;" onclick="openPaymentModal('School Fees', 'graduation-cap')">
                <i class="fas fa-graduation-cap" style="font-size: 40px; color: #9f7aea; margin-bottom: 15px;"></i>
                <h3>School Fees</h3>
                <p style="font-size: 14px; opacity: 0.8;">Pay tuition for registered schools</p>
                <button class="btn" style="margin-top: 15px; width: auto; padding: 10px 20px;">Pay Now</button>
            </div>
        </div>
    </div>
</div>

<!-- bill Payment Modal -->
<div id="paymentModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
    <div class="glass-container" style="max-width: 450px; padding: 30px; position: relative;">
        <button onclick="closePaymentModal()" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.1); border: none; color: white; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
        <h3 style="margin-bottom: 10px;" id="billTitle">Pay Bill</h3>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">Enter your customer details to proceed.</p>
        <form id="payBillForm" onsubmit="handleBillPayment(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="bill_type" id="billTypeInput">
            
            <div class="form-group">
                <label>From Account</label>
                <select name="from_account" required>
                    <?php foreach ($user_accounts as $account): ?>
                        <option value="<?php echo $account['account_number']; ?>">
                            <?php echo ucfirst($account['account_type']); ?> - 
                            <?php echo substr($account['account_number'], -4); ?> 
                            (ETB <?php echo number_format($account['balance'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Customer/Contract Number</label>
                <input type="text" name="customer_id" required placeholder="Enter ID number">
            </div>
            
            <div class="form-group">
                <label>Amount (ETB)</label>
                <input type="number" name="amount" step="0.01" min="10" required placeholder="Enter amount">
            </div>

            <div class="security-badge" style="margin-bottom: 25px;">
                <i class="fas fa-shield-alt"></i> Instant Settlement Guaranteed
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn">Confirm Payment</button>
                <button type="button" class="btn" style="background: rgba(255,255,255,0.1);" onclick="closePaymentModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(type, icon) {
    document.getElementById('billTitle').innerHTML = `<i class="fas fa-${icon}"></i> Pay ${type}`;
    document.getElementById('billTypeInput').value = type;
    document.getElementById('paymentModal').style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

async function handleBillPayment(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const response = await fetch('php/process_bill.php', { method: 'POST', body: formData });
    const result = await response.json();
    if(result.success) {
        alert("Payment settled! Receipt ID: " + result.receipt);
        location.reload();
    } else {
        alert(result.message);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
