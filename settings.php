<?php
require_once 'includes/header.php';
?>
<div class="section active" style="display:block;">

            <div id="settings-section" class="section">
                <div class="transactions-section">
                    <h3 style="margin-bottom: 20px;">Account Settings</h3>

                    <div style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px;">
                        <div class="avatar-large" style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; overflow: hidden; position: relative;">
                            <?php if (!empty($profile_image)): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" style="width: 100%; height: 100%; object-fit: cover;" id="settingsAvatarPreview">
                            <?php else: ?>
                                <span id="settingsAvatarInitials"><?php echo strtoupper(substr($fullname, 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form id="uploadProfileForm" enctype="multipart/form-data">
                                <label for="profileImageInput" class="btn" style="width: auto; padding: 8px 15px; cursor: pointer; display: inline-block;">Change Picture</label>
                                <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display: none;" onchange="uploadProfileImage(this)">
                            </form>
                            <div id="uploadMessage" style="font-size: 13px; margin-top: 5px;"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <h4 style="margin-bottom: 15px;">Profile Information</h4>
                        <form id="profileForm" onsubmit="updateProfile(event)">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" id="profileFullname" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="profileEmail" readonly>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" id="profilePhone" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Member Since</label>
                                    <input type="text" id="profileMemberSince" readonly>
                                </div>
                            </div>
                        </form>
                    </div>

                        <button class="btn" onclick="openPinModal()" style="width: auto; background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); margin-right: 10px;">Set Transfer PIN</button>
                        <button class="btn" onclick="openPasswordModal()" style="width: auto; margin-right: 10px;">Change Password</button>
                        <button class="btn" onclick="open2FAModal()" style="width: auto; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">Enable 2FA</button>
                    </div>

                    <!-- Transfer PIN Modal -->
                    <div id="pinModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                        <div class="modal-content" style="background:white; padding:40px; border-radius:20px; width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                            <h3 style="margin-bottom:20px; color:#333;">Set Security PIN</h3>
                            <p style="font-size:14px; color:#666; margin-bottom:20px;">Enter a 4-digit PIN for authorizing transactions.</p>
                            <form id="pinForm" onsubmit="handlePinUpdate(event)">
                                <input type="password" name="new_pin" maxlength="4" pattern="\d{4}" placeholder="4-digit PIN" required style="width:100%; padding:15px; border:2px solid #eee; border-radius:12px; margin-bottom:20px; text-align:center; font-size:24px; letter-spacing:10px;">
                                <div style="display:flex; gap:10px;">
                                    <button type="submit" class="btn" style="flex:2;">Save PIN</button>
                                    <button type="button" onclick="closePinModal()" class="btn" style="flex:1; background:#6c757d;">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Password Modal -->
                    <div id="passwordModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                        <div class="modal-content" style="background:white; padding:40px; border-radius:20px; width:450px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                            <h3 style="margin-bottom:20px; color:#333;">Change Password</h3>
                            <form id="passwordForm" onsubmit="handlePasswordUpdate(event)">
                                <input type="password" name="current_password" placeholder="Current Password" required style="width:100%; padding:12px; border:1.5px solid #eee; border-radius:10px; margin-bottom:15px;">
                                <input type="password" name="new_password" placeholder="New Password" required style="width:100%; padding:12px; border:1.5px solid #eee; border-radius:10px; margin-bottom:15px;">
                                <input type="password" name="confirm_password" placeholder="Confirm New Password" required style="width:100%; padding:12px; border:1.5px solid #eee; border-radius:10px; margin-bottom:20px;">
                                <div style="display:flex; gap:10px;">
                                    <button type="submit" class="btn" style="flex:2;">Update Password</button>
                                    <button type="button" onclick="closePasswordModal()" class="btn" style="flex:1; background:#6c757d;">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div>
                        <h4 style="margin-bottom: 15px;">Notification Preferences</h4>
                        <div class="checkbox-group">
                            <input type="checkbox" id="emailNotifications" checked>
                            <label for="emailNotifications">Email notifications for transactions</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="smsNotifications" checked>
                            <label for="smsNotifications">SMS alerts for transactions</label>
                        </div>
                        <button class="btn" onclick="savePreferences()" style="width: auto; margin-top: 15px;">Save
                            Preferences</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    
</div>
<?php
require_once 'includes/footer.php';
?>