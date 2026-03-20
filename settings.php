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

                    <div style="margin-bottom: 30px;">
                        <h4 style="margin-bottom: 15px;">Security</h4>
                        <button class="btn" onclick="changePassword()" style="width: auto; margin-right: 10px;">Change
                            Password</button>
                        <button class="btn" onclick="enable2FA()"
                            style="width: auto; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">Enable
                            2FA</button>
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