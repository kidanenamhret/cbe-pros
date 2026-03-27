<?php
require_once 'includes/header.php';
?>
<div class="section active" style="display:block;">

            <div id="overview-section" class="section active">
                <?php if (!$is_pin_set): ?>
                <!-- Security Warning Banner -->
                <div style="background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); border: 1.5px solid #feb2b2; padding: 25px; border-radius: 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 25px rgba(229, 62, 62, 0.1);">
                    <div style="width: 60px; height: 60px; background: #e53e3e; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="color: #c53030; margin-bottom: 5px;">Action Required: Set Transaction PIN</h3>
                        <p style="color: #9b2c2c; font-size: 14px; margin-bottom: 12px;">Full access to sending money and payments is restricted until you establish your 4-digit security code.</p>
                        <a href="settings.php" class="btn" style="background: #e53e3e; color: white; border: none; padding: 10px 20px; font-weight: 700; width: auto; display: inline-block;">Gain Full Access Now</a>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Accounts Grid -->
                <div class="accounts-grid" id="accountsGrid">
                    <!-- Populated by JS -->
                </div>

                <!-- Currency Wallet Summary -->
                <div id="currencyWalletSection" style="margin: 20px 0;">
                    <h4 style="margin-bottom: 10px; font-size: 14px; color: var(--text-secondary);">Multi-Currency Portfolio</h4>
                    <div id="currencyWalletGrid" class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3 style="margin-bottom: 15px;">Quick Actions</h3>
                    <div class="actions-grid">
                        <div class="action-btn" onclick="window.location.href='transfer.php'">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send Money</span>
                        </div>
                        <div class="action-btn" onclick="window.location.href='telebirr.php'">
                            <i class="fas fa-bolt"></i>
                            <span>Telebirr Hub</span>
                        </div>
                        <div class="action-btn" onclick="window.location.href='transactions.php'">
                            <i class="fas fa-history"></i>
                            <span>History</span>
                        </div>
                        <div class="action-btn" onclick="window.location.href='beneficiaries.php'">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Beneficiary</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="transactions-section">
                    <div class="transactions-header">
                        <h3>Recent Transactions</h3>
                        <a href="javascript:void(0)" onclick="window.location.href='transactions.php'" style="color: var(--primary);">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table id="recentTransactions">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentTransactionsList">
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