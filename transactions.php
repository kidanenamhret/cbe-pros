<?php
require_once 'includes/header.php';
?>
<div class="section active" style="display:block;">

            <div id="transactions-section" class="section">
                <div class="transactions-section">
                    <div class="transactions-header">
                        <h3>Transaction History</h3>
                        <div class="transactions-filters">
                            <select id="transactionTypeFilter" onchange="loadAllTransactions()">
                                <option value="all">All Types</option>
                                <option value="transfer">Transfers</option>
                                <option value="deposit">Deposits</option>
                                <option value="withdrawal">Withdrawals</option>
                            </select>
                            <input type="text" id="transactionSearch" placeholder="Search..."
                                onkeyup="loadAllTransactions()">
                            <input type="date" id="transactionDate" onchange="loadAllTransactions()">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="allTransactions">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Fee</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="allTransactionsList">
                                <tr>
                                    <td colspan="8" style="text-align: center;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 20px; text-align: center;">
                        <button class="btn" onclick="loadMoreTransactions()" style="width: auto; padding: 10px 30px;"
                            id="loadMoreBtn">Load More</button>
                    </div>
                </div>
            </div>

            
</div>
<?php
require_once 'includes/footer.php';
?>