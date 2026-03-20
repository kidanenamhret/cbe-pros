
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

    
    <script>
        window.CSRF_TOKEN = '<?php echo $csrf_token; ?>';
        window.USER_ID = <?php echo $user_id; ?>;
    </script>
    <script src="js/dashboard.js"></script>
</body>
