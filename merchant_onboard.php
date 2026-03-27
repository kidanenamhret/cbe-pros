<?php
require_once 'includes/header.php';

// If already merchant, redirect
if (isset($_SESSION['is_merchant']) && $_SESSION['is_merchant']) {
    header('Location: merchant_dashboard.php');
    exit();
}
?>

<div class="section active" style="display:block;">
    <div class="auth-card" style="max-width: 600px; margin: 40px auto; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: rgba(128,0,128,0.1); color: #800080; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 32px;">
                <i class="fas fa-store"></i>
            </div>
            <h2 style="color: #333; margin-bottom: 10px;">Grow Your Business with CBE-Pros Merchant</h2>
            <p style="color: #666; font-size: 14px;">Accept payments instantly via QR codes and manage your sales in real-time.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
            <div style="padding: 20px; background: #f8f9fa; border-radius: 12px; text-align: center;">
                <i class="fas fa-qrcode" style="color: #800080; font-size: 24px; margin-bottom: 10px;"></i>
                <h4 style="margin: 0 0 5px 0; font-size: 14px;">Static QR</h4>
                <p style="font-size: 12px; color: #666; margin: 0;">Print and display at your shop counter.</p>
            </div>
            <div style="padding: 20px; background: #f8f9fa; border-radius: 12px; text-align: center;">
                <i class="fas fa-chart-line" style="color: #800080; font-size: 24px; margin-bottom: 10px;"></i>
                <h4 style="margin: 0 0 5px 0; font-size: 14px;">Sales Analytics</h4>
                <p style="font-size: 12px; color: #666; margin: 0;">Track your daily earnings instantly.</p>
            </div>
        </div>

        <form id="merchantOnboardForm" onsubmit="handleMerchantOnboard(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 8px;">Business Display Name</label>
                <input type="text" name="business_name" required placeholder="e.g. Mesfin General Store" style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px; font-size: 14px;">
            </div>
            
            <div class="form-group" style="margin-bottom: 30px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 8px;">Business Category</label>
                <select name="business_type" required style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px; font-size: 14px;">
                    <option value="retail">Retail / Supermarket</option>
                    <option value="restaurant">Restaurant / Café</option>
                    <option value="service">Service Provider</option>
                    <option value="other">Other Business</option>
                </select>
            </div>

            <p style="font-size: 11px; color: #888; margin-bottom: 25px;">By upgrading, you agree to the CBE-Pros Merchant terms. A 1% merchant service fee applies to all incoming business payments.</p>

            <button type="submit" class="btn" style="width: 100%; padding: 14px; background: #800080; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Activate Merchant Account</button>
        </form>
        <div id="onboardMessage" style="margin-top: 20px; text-align: center; font-size: 14px;"></div>
    </div>
</div>

<script>
async function handleMerchantOnboard(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const msg = document.getElementById('onboardMessage');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Activating...';
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('php/merchant_onboard.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            msg.style.color = 'green';
            msg.innerHTML = '🎉 Success! Your Merchant Account is active. Redirecting...';
            setTimeout(() => window.location.href = 'merchant_dashboard.php', 2000);
        } else {
            msg.style.color = 'red';
            msg.innerHTML = result.message;
            btn.disabled = false;
            btn.innerHTML = 'Activate Merchant Account';
        }
    } catch (error) {
        msg.style.color = 'red';
        msg.innerHTML = 'An unexpected error occurred.';
        btn.disabled = false;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
