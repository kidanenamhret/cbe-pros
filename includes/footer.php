
        </main>
    </div>
<!-- Notifications Panel -->

    <div class="notifications-panel" id="notificationsPanel">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <i class="fas fa-times" onclick="toggleNotifications()" style="cursor: pointer;"></i>
        </div>
        <div id="notificationsList">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- Transaction PIN Authorization Modal -->
    <div id="transactionPinModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:white; padding:40px; border-radius:30px; width:400px; box-shadow:0 30px 80px rgba(0,0,0,0.4); text-align:center;">
            <div style="width:64px; height:64px; background:#800080; border-radius:50%; color:white; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:26px;">
                <i class="fas fa-key"></i>
            </div>
            <h3 style="margin-bottom:10px; color:#1a202c; font-weight:800; font-size:22px;">Security PIN</h3>
            <p style="color:#718096; font-size:14px; margin-bottom:25px;">Enter your 4-digit authorization PIN.</p>
            
            <input type="password" id="txnPinInput" maxlength="4" pattern="\d{4}" placeholder="••••" required 
                   style="width:100%; max-width:200px; padding:18px; border:2.5px solid #edf2f7; border-radius:15px; margin-bottom:25px; text-align:center; font-size:32px; letter-spacing:14px; background:#f7fafc; outline:none; transition:all 0.3s;"
                   oninput="checkAutoSubmitPin(this)">
            
            <div style="display:flex; gap:12px;">
                <button onclick="submitAuthorizedTransaction()" id="pinAuthBtn" class="btn" style="flex:2; background:#800080; padding:15px; font-weight:700;">Authorize</button>
                <button onclick="closeTxnPinModal()" class="btn" style="flex:1; background:#e2e8f0; color:#4a5568; padding:15px; font-weight:600;">Cancel</button>
            </div>
            <div id="pinError" style="margin-top:20px; color:#e53e3e; font-size:13px; font-weight:700; min-height:20px; transition:all 0.3s;"></div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
    <script>
        window.CSRF_TOKEN = '<?php echo $csrf_token; ?>';
        window.USER_ID = <?php echo $user_id; ?>;
        window.USER_FULLNAME = '<?php echo addslashes($fullname); ?>';
    </script>
    <script src="js/dashboard.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker Registered'))
                    .catch(err => console.log('Registration Failed', err));
            });
        }
    </script>
</body>
