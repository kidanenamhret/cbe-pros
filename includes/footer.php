
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
