<?php
require_once 'includes/header.php';
?>
<div class="section active" style="display:block;">

            <div id="overview-section" class="section active">
                <!-- Accounts Grid -->
                <div class="accounts-grid" id="accountsGrid">
                    <!-- Populated by JS -->
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