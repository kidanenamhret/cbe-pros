<?php
require_once 'includes/header.php';
?>
<div class="section active" style="display:block;">

            <div id="accounts-section" class="section">
                <div class="transactions-section">
                    <h3 style="margin-bottom: 20px;">My Detailed Accounts</h3>
                    <div class="accounts-grid" id="accountsGridFull">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

</div>

<!-- QR Modal -->
<div id="qrModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
    <div class="glass-container" style="max-width: 400px; text-align: center; color: white; padding: 30px;">
        <h3 style="margin-bottom: 20px;">Account QR Code</h3>
        <div id="accountQRCode" style="display: inline-block; padding: 15px; background: white; border-radius: 12px; margin-bottom: 20px;"></div>
        <p style="font-size: 18px; font-weight: 600; margin-bottom: 10px;" id="qrAccountText"></p>
        <p style="font-size: 14px; opacity: 0.8; margin-bottom: 25px;">Ready to receive payments</p>
        <button onclick="closeQRModal()" class="btn">Close</button>
    </div>
</div>
<?php
require_once 'includes/footer.php';
?>