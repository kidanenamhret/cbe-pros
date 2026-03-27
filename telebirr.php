<?php
require_once 'includes/header.php';
$primary_balance = !empty($user_accounts) ? number_format($user_accounts[0]['balance'], 2) . ' ' . $user_accounts[0]['currency'] : '0.00 ETB';
?>
<div class="section active" style="display:block;">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h2 style="color: var(--text-primary);"><i class="fas fa-bolt" style="color:var(--primary);"></i> Telebirr Services</h2>
        <div class="balance-badge" style="background:var(--primary); color:white; padding:10px 20px; border-radius:10px; font-weight:bold; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);">
            Primary Balance: <span id="telebirrAvailBalance"><?php echo htmlspecialchars($primary_balance); ?></span>
        </div>
    </div>

    <!-- Alert Box -->
    <div id="telebirrAlert" class="alert" style="display:none; margin-bottom:20px; padding:15px; border-radius:10px; font-size: 15px;"></div>

    <div class="transfer-container" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:25px;">
        
        <!-- Buy Airtime Card -->
        <div class="transfer-card" style="background:var(--glass-bg); padding:30px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.05); border: 1px solid var(--glass-border);">
            <h3 style="margin-bottom:25px; border-bottom:2px solid rgba(0,0,0,0.05); padding-bottom:15px; color: var(--text-primary);">
                <i class="fas fa-mobile-alt" style="color: var(--primary);"></i> Buy Airtime
            </h3>
            <form id="airtimeForm">
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color: var(--text-primary);">Funding Account</label>
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-wallet" style="position:absolute; left:15px; top:14px; color:var(--text-dim);"></i>
                        <select name="account_id" id="airtimeAccount" required style="width:100%; padding:14px 14px 14px 45px; border:2px solid #e2e8f0; border-radius:12px; font-family:inherit; background: white; font-size: 15px;">
                            <option value="">-- Choose Account --</option>
                            <?php foreach($user_accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars(ucfirst($acc['account_type']) . " - " . $acc['account_number'] . " (" . number_format($acc['balance'], 2) . " " . $acc['currency'] . ")"); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color: var(--text-primary);">Phone Number</label>
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-phone" style="position:absolute; left:15px; top:14px; color:var(--text-dim);"></i>
                        <input type="text" id="airtimePhone" placeholder="09XX XX XX XX" required style="width:100%; padding:14px 14px 14px 45px; border:2px solid #e2e8f0; border-radius:12px; font-size: 15px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:25px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color: var(--text-primary);">Amount (ETB)</label>
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-money-bill" style="position:absolute; left:15px; top:14px; color:var(--text-dim);"></i>
                        <input type="number" id="airtimeAmount" placeholder="Min 5 ETB" min="5" required style="width:100%; padding:14px 14px 14px 45px; border:2px solid #e2e8f0; border-radius:12px; font-size: 15px;">
                    </div>
                </div>
                <button type="submit" class="btn" style="width:100%; padding:15px; background:linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color:white; border:none; border-radius:12px; font-weight:bold; cursor:pointer; font-size: 16px; transition: transform 0.2s, box-shadow 0.2s;">
                    Recharge Airtime <i class="fas fa-bolt" style="margin-left: 5px;"></i>
                </button>
            </form>
        </div>

        <!-- Transfer to Telebirr Wallet Card -->
        <div class="transfer-card" style="background:var(--glass-bg); padding:30px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.05); border: 1px solid var(--glass-border);">
            <h3 style="margin-bottom:25px; border-bottom:2px solid rgba(0,0,0,0.05); padding-bottom:15px; color: var(--text-primary);">
                <i class="fas fa-exchange-alt" style="color: #10b981;"></i> Send to Wallet
            </h3>
            <form id="walletForm">
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color: var(--text-primary);">Funding Account</label>
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-wallet" style="position:absolute; left:15px; top:14px; color:var(--text-dim);"></i>
                        <select name="account_id" id="walletAccount" required style="width:100%; padding:14px 14px 14px 45px; border:2px solid #e2e8f0; border-radius:12px; font-family:inherit; background: white; font-size: 15px;">
                            <option value="">-- Choose Account --</option>
                            <?php foreach($user_accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars(ucfirst($acc['account_type']) . " - " . $acc['account_number'] . " (" . number_format($acc['balance'], 2) . " " . $acc['currency'] . ")"); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color: var(--text-primary);">Telebirr Number</label>
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-mobile" style="position:absolute; left:15px; top:14px; color:var(--text-dim);"></i>
                        <input type="text" id="walletPhone" placeholder="09XX XX XX XX" required style="width:100%; padding:14px 14px 14px 45px; border:2px solid #e2e8f0; border-radius:12px; font-size: 15px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:25px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color: var(--text-primary);">Amount (ETB)</label>
                    <div class="input-wrapper" style="position:relative;">
                        <i class="fas fa-money-bill-wave" style="position:absolute; left:15px; top:14px; color:var(--text-dim);"></i>
                        <input type="number" id="walletAmount" placeholder="Min 10 ETB" min="10" required style="width:100%; padding:14px 14px 14px 45px; border:2px solid #e2e8f0; border-radius:12px; font-size: 15px;">
                    </div>
                </div>
                <button type="submit" class="btn" style="width:100%; padding:15px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:white; border:none; border-radius:12px; font-weight:bold; cursor:pointer; font-size: 16px; transition: transform 0.2s, box-shadow 0.2s;">
                    Transfer Funds <i class="fas fa-paper-plane" style="margin-left: 5px;"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function processTelebirrTransaction(e, type) {
    e.preventDefault();
    
    const prefix = type === 'airtime' ? 'airtime' : 'wallet';
    const accountId = document.getElementById(prefix + 'Account').value;
    const phone = document.getElementById(prefix + 'Phone').value;
    const amount = document.getElementById(prefix + 'Amount').value;

    const btn = e.target.querySelector('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('type', type);
    formData.append('account_id', accountId);
    formData.append('phone', phone);
    formData.append('amount', amount);
    // Grab CSRF from PHP embedded variable
    formData.append('csrf_token', '<?php echo $csrf_token; ?>');

    fetch('php/telebirr_api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertBox = document.getElementById('telebirrAlert');
        alertBox.style.display = 'block';
        if(data.status === 'success') {
            alertBox.style.backgroundColor = '#d1fae5';
            alertBox.style.color = '#065f46';
            alertBox.style.border = '1px solid #34d399';
            alertBox.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            
            // Optionally update the balance visually if the user used the primary account
            const primaryAccId = '<?php echo !empty($user_accounts) ? $user_accounts[0]['id'] : 0; ?>';
            if(accountId === primaryAccId) {
                document.getElementById('telebirrAvailBalance').innerText = parseFloat(data.new_balance).toFixed(2) + ' ETB';
            }
            
            e.target.reset(); // clear form
        } else {
            alertBox.style.backgroundColor = '#fee2e2';
            alertBox.style.color = '#b91c1c';
            alertBox.style.border = '1px solid #f87171';
            alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
        }
    })
    .catch(err => {
        const alertBox = document.getElementById('telebirrAlert');
        alertBox.style.display = 'block';
        alertBox.style.backgroundColor = '#fee2e2';
        alertBox.style.color = '#b91c1c';
        alertBox.style.border = '1px solid #f87171';
        alertBox.innerHTML = '<i class="fas fa-wifi"></i> Connection Error to Server';
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
}

document.getElementById('airtimeForm').addEventListener('submit', (e) => processTelebirrTransaction(e, 'airtime'));
document.getElementById('walletForm').addEventListener('submit', (e) => processTelebirrTransaction(e, 'wallet'));

// Initial style hover injections
document.querySelectorAll('.btn').forEach(b => {
    b.addEventListener('mouseover', function() { this.style.transform = 'translateY(-2px)'; this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)'; });
    b.addEventListener('mouseout', function() { this.style.transform = 'translateY(0)'; this.style.boxShadow = 'none'; });
});
</script>

<?php
require_once 'includes/footer.php';
?>
