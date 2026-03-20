<?php
require_once 'includes/header.php';

// Get user accounts for the account selector
try {
    $stmt = $conn->prepare("
        SELECT id, account_number, balance, currency, account_type, status
        FROM accounts 
        WHERE user_id = ? AND status = 'active'
        ORDER BY 
            CASE account_type 
                WHEN 'checking' THEN 1 
                WHEN 'savings' THEN 2 
                ELSE 3 
            END
    ");
    $stmt->execute([$user_id]);
    $user_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching accounts: " . $e->getMessage());
    $user_accounts = [];
}

?>
<div class="section active" style="display:block;">

            <div id="transfer-section" class="section">
                <div class="transfer-section">
                    <h3 style="margin-bottom: 20px;">Send Money</h3>

                    <!-- Transfer Tabs -->
                    <div class="transfer-tabs">
                        <div class="transfer-tab active" onclick="showTransferTab('username', event)">By Username</div>
                        <div class="transfer-tab" onclick="showTransferTab('account', event)">By Account</div>
                        <div class="transfer-tab" onclick="showTransferTab('phone', event)">By Phone</div>
                        <div class="transfer-tab" onclick="showTransferTab('scheduled', event)">Scheduled</div>
                    </div>

                    <!-- Transfer Forms -->
                    <div id="transfer-username" class="transfer-content active">
                        <form id="transferUsernameForm" onsubmit="handleTransfer(event, 'username')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="transfer_type" value="username">

                            <div class="form-group">
                                <label>From Account</label>
                                <select name="from_account" required>
                                    <?php foreach ($user_accounts as $account): ?>
                                        <option value="<?php echo $account['account_number']; ?>">
                                            <?php echo ucfirst($account['account_type']); ?> -
                                            <?php echo substr($account['account_number'], -4); ?>
                                            (<?php echo number_format($account['balance'], 2); ?> ETB)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Recipient Username or Email</label>
                                <input type="text" name="receiver_username" placeholder="Enter username or email"
                                    required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Amount (ETB)</label>
                                    <input type="number" name="amount" step="0.01" min="1" max="1000000" required
                                        onkeyup="calculateFees()" onchange="calculateFees()">
                                </div>
                                <div class="form-group">
                                    <label>Description (Optional)</label>
                                    <input type="text" name="description" placeholder="What's this for?">
                                </div>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" name="save_beneficiary" id="saveBeneficiary">
                                <label for="saveBeneficiary">Save as beneficiary</label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" name="urgent" id="urgentTransfer" onchange="calculateFees()">
                                <label for="urgentTransfer">Urgent transfer (additional fees apply)</label>
                            </div>

                            <!-- Fee Calculator -->
                            <div class="fee-calculator" id="feeCalculator" style="display: none;">
                                <h4 style="margin-bottom: 10px;">Fee Breakdown</h4>
                                <div class="fee-item">
                                    <span>Base Fee:</span>
                                    <span id="baseFee">5.00 ETB</span>
                                </div>
                                <div class="fee-item">
                                    <span>Percentage Fee:</span>
                                    <span id="percentageFee">0.00 ETB</span>
                                </div>
                                <div class="fee-item">
                                    <span>Urgent Fee:</span>
                                    <span id="urgentFee">0.00 ETB</span>
                                </div>
                                <div class="fee-total">
                                    <span>Total Fee:</span>
                                    <span id="totalFee">5.00 ETB</span>
                                </div>
                                <div class="fee-total">
                                    <span>Total Deduction:</span>
                                    <span id="totalDeduction">0.00 ETB</span>
                                </div>
                            </div>

                            <button type="submit" class="btn" id="transferBtn">Send Money</button>
                            <div id="transferMessage" style="margin-top: 15px;"></div>
                        </form>
                    </div>

                    <div id="transfer-account" class="transfer-content">
                        <form id="transferAccountForm" onsubmit="handleTransfer(event, 'account')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="transfer_type" value="account">

                            <div class="form-group">
                                <label>From Account</label>
                                <select name="from_account" required>
                                    <?php foreach ($user_accounts as $account): ?>
                                        <option value="<?php echo $account['account_number']; ?>">
                                            <?php echo ucfirst($account['account_type']); ?> -
                                            <?php echo substr($account['account_number'], -4); ?>
                                            (<?php echo number_format($account['balance'], 2); ?> ETB)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Recipient Account Number</label>
                                <input type="text" name="receiver_account" placeholder="Enter account number" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Amount (ETB)</label>
                                    <input type="number" name="amount" step="0.01" min="1" max="1000000" required>
                                </div>
                                <div class="form-group">
                                    <label>Description (Optional)</label>
                                    <input type="text" name="description" placeholder="What's this for?">
                                </div>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" name="save_beneficiary" id="saveBeneficiaryAccount">
                                <label for="saveBeneficiaryAccount">Save as beneficiary</label>
                            </div>

                            <button type="submit" class="btn">Send Money</button>
                        </form>
                    </div>

                    <div id="transfer-phone" class="transfer-content">
                        <form id="transferPhoneForm" onsubmit="handleTransfer(event, 'phone')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="transfer_type" value="phone">

                            <div class="form-group">
                                <label>From Account</label>
                                <select name="from_account" required>
                                    <?php foreach ($user_accounts as $account): ?>
                                        <option value="<?php echo $account['account_number']; ?>">
                                            <?php echo ucfirst($account['account_type']); ?> -
                                            <?php echo substr($account['account_number'], -4); ?>
                                            (<?php echo number_format($account['balance'], 2); ?> ETB)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Recipient Phone Number</label>
                                <input type="tel" name="receiver_phone" placeholder="+251911234567 or 0911234567"
                                    required>
                                <small style="color: var(--text-secondary);">Enter Ethiopian phone number</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Amount (ETB)</label>
                                    <input type="number" name="amount" step="0.01" min="1" max="1000000" required>
                                </div>
                                <div class="form-group">
                                    <label>Description (Optional)</label>
                                    <input type="text" name="description" placeholder="What's this for?">
                                </div>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" name="save_beneficiary" id="saveBeneficiaryPhone">
                                <label for="saveBeneficiaryPhone">Save as beneficiary</label>
                            </div>

                            <button type="submit" class="btn">Send Money</button>
                        </form>
                    </div>

                    <div id="transfer-scheduled" class="transfer-content">
                        <form id="transferScheduledForm" onsubmit="handleScheduledTransfer(event)">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="form-group">
                                <label>From Account</label>
                                <select name="from_account" required>
                                    <?php foreach ($user_accounts as $account): ?>
                                        <option value="<?php echo $account['account_number']; ?>">
                                            <?php echo ucfirst($account['account_type']); ?> -
                                            <?php echo substr($account['account_number'], -4); ?>
                                            (<?php echo number_format($account['balance'], 2); ?> ETB)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Recipient Username or Email</label>
                                <input type="text" name="receiver_username" placeholder="Enter username or email"
                                    required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Amount (ETB)</label>
                                    <input type="number" name="amount" step="0.01" min="1" max="1000000" required>
                                </div>
                                <div class="form-group">
                                    <label>Scheduled Date & Time</label>
                                    <input type="datetime-local" name="schedule_date"
                                        min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                                        max="<?php echo date('Y-m-d\TH:i', strtotime('+1 year')); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Description (Optional)</label>
                                <textarea name="description" rows="2"
                                    placeholder="Add a note for this scheduled transfer"></textarea>
                            </div>

                            <button type="submit" class="btn">Schedule Transfer</button>
                        </form>
                    </div>
                </div>

                <!-- Scheduled Transfers List -->
                <div class="transactions-section" style="margin-top: 20px;">
                    <h3 style="margin-bottom: 20px;">Scheduled Transfers</h3>
                    <div class="table-responsive">
                        <table id="scheduledTransfers">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="scheduledTransfersList">
                                <tr>
                                    <td colspan="5" style="text-align: center;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
</div>
<?php
require_once 'includes/footer.php';
?>