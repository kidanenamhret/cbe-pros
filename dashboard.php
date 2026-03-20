<?php
require_once 'php/auth.php';
require_once 'php/db.php';

// Check if user is logged in
checkLogin();

// Get user info from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'User';
$fullname = $_SESSION['user_fullname'] ?? $username;

// Generate CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

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
} catch (Exception $e) {
    $user_accounts = [];
    error_log("Failed to fetch accounts: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE-Pros | Digital Banking Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --warning: #ecc94b;
            --error: #f56565;
            --dark: #1a202c;
            --light: #f7fafc;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --text-dim: #a0aec0;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-primary);
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(26, 32, 44, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px 20px;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 40px;
            padding-left: 15px;
            background: linear-gradient(135deg, #fff 0%, #ffd700 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--text-dim);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar nav a.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .sidebar nav a i {
            width: 20px;
        }

        .sidebar .logout-btn {
            margin-top: 50px;
            color: var(--error) !important;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        /* Header Styles */
        .dashboard-header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header-left h1 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .header-left p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .notification-badge i {
            font-size: 20px;
            color: var(--text-secondary);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--error);
            color: white;
            font-size: 11px;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Account Cards */
        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .account-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            cursor: pointer;
            border: 1px solid var(--glass-border);
        }

        .account-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .account-card.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .account-card.primary .account-number,
        .account-card.primary .account-type {
            color: rgba(255, 255, 255, 0.9);
        }

        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .account-type {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .account-number {
            font-family: monospace;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .account-balance {
            font-size: 28px;
            font-weight: 700;
        }

        .account-currency {
            font-size: 14px;
            color: var(--text-secondary);
            margin-left: 5px;
        }

        .account-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: rgba(72, 187, 120, 0.1);
            color: var(--success);
        }

        /* Quick Actions */
        .quick-actions {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .action-btn i {
            font-size: 24px;
        }

        /* Transfer Form */
        .transfer-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .transfer-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .transfer-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .transfer-tab.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .transfer-content {
            display: none;
        }

        .transfer-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
            width: 100%;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Transactions Table */
        .transactions-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
        }

        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .transactions-filters {
            display: flex;
            gap: 10px;
        }

        .transactions-filters select,
        .transactions-filters input {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f8fafc;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .transaction-credit {
            color: var(--success);
            font-weight: 600;
        }

        .transaction-debit {
            color: var(--error);
            font-weight: 600;
        }

        .badge-success {
            background: rgba(72, 187, 120, 0.1);
            color: var(--success);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .badge-warning {
            background: rgba(236, 201, 75, 0.1);
            color: var(--warning);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .badge-error {
            background: rgba(245, 101, 101, 0.1);
            color: var(--error);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Notifications Panel */
        .notifications-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s;
            z-index: 1000;
            padding: 20px;
            overflow-y: auto;
        }

        .notifications-panel.open {
            right: 0;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #ebf8ff;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .notification-meta {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Loader */
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 20px 10px;
            }

            .sidebar h2 {
                font-size: 14px;
                padding-left: 0;
                text-align: center;
            }

            .sidebar nav a span {
                display: none;
            }

            .main-content {
                margin-left: 80px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>CBE-PROS</h2>
            <nav>
                <a href="#" class="active" onclick="showSection('overview')">
                    <i class="fas fa-home"></i>
                    <span>Overview</span>
                </a>
                <a href="#" onclick="showSection('transfer')">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transfer</span>
                </a>
                <a href="#" onclick="showSection('accounts')">
                    <i class="fas fa-wallet"></i>
                    <span>Accounts</span>
                </a>
                <a href="#" onclick="showSection('transactions')">
                    <i class="fas fa-history"></i>
                    <span>Transactions</span>
                </a>
                <a href="#" onclick="showSection('beneficiaries')">
                    <i class="fas fa-users"></i>
                    <span>Beneficiaries</span>
                </a>
                <a href="#" onclick="showSection('settings')">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="php/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h1>
                    <p><?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="header-right">
                    <div class="notification-badge" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="badge" id="notificationCount">0</span>
                    </div>
                    <div class="user-menu">
                        <div class="avatar">
                            <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Overview Section -->
            <div id="overview-section" class="section active">
                <!-- Accounts Grid -->
                <div class="accounts-grid" id="accountsGrid">
                    <!-- Populated by JS -->
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3 style="margin-bottom: 15px;">Quick Actions</h3>
                    <div class="actions-grid">
                        <div class="action-btn" onclick="showSection('transfer')">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send Money</span>
                        </div>
                        <div class="action-btn" onclick="showTransferTab('phone')">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Send to Phone</span>
                        </div>
                        <div class="action-btn" onclick="showSection('transactions')">
                            <i class="fas fa-history"></i>
                            <span>History</span>
                        </div>
                        <div class="action-btn" onclick="showSection('beneficiaries')">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Beneficiary</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="transactions-section">
                    <div class="transactions-header">
                        <h3>Recent Transactions</h3>
                        <a href="#" onclick="showSection('transactions')" style="color: var(--primary);">View All</a>
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

            <!-- Transfer Section -->
            <div id="transfer-section" class="section">
                <div class="transfer-section">
                    <h3 style="margin-bottom: 20px;">Send Money</h3>

                    <!-- Transfer Tabs -->
                    <div class="transfer-tabs">
                        <div class="transfer-tab active" onclick="showTransferTab('username')">By Username</div>
                        <div class="transfer-tab" onclick="showTransferTab('account')">By Account</div>
                        <div class="transfer-tab" onclick="showTransferTab('phone')">By Phone</div>
                        <div class="transfer-tab" onclick="showTransferTab('scheduled')">Scheduled</div>
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

            <!-- Transactions Section -->
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

            <!-- Beneficiaries Section -->
            <div id="beneficiaries-section" class="section">
                <div class="transactions-section">
                    <h3 style="margin-bottom: 20px;">My Beneficiaries</h3>
                    <div id="beneficiariesList"
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="settings-section" class="section">
                <div class="transactions-section">
                    <h3 style="margin-bottom: 20px;">Account Settings</h3>

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
        // Global variables
        let currentPage = 1;
        let loadingMore = false;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function () {
            loadDashboardData();
            loadNotifications();
            loadScheduledTransfers();
            loadBeneficiaries();
            loadUserProfile();

            // Refresh data every 30 seconds
            setInterval(loadDashboardData, 30000);
            setInterval(loadNotifications, 30000);
        });

        // Show selected section
        function showSection(section) {
            // Update active state in sidebar
            document.querySelectorAll('.sidebar nav a').forEach(link => {
                link.classList.remove('active');
            });
            event.target.closest('a').classList.add('active');

            // Show selected section
            document.querySelectorAll('.section').forEach(s => {
                s.classList.remove('active');
            });
            document.getElementById(section + '-section').classList.add('active');

            // Load section-specific data
            if (section === 'transactions') {
                loadAllTransactions();
            } else if (section === 'beneficiaries') {
                loadBeneficiaries();
            }
        }

        // Show transfer tab
        function showTransferTab(tab) {
            document.querySelectorAll('.transfer-tab').forEach(t => {
                t.classList.remove('active');
            });
            event.target.classList.add('active');

            document.querySelectorAll('.transfer-content').forEach(c => {
                c.classList.remove('active');
            });
            document.getElementById('transfer-' + tab).classList.add('active');
        }

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('php/get_data.php');
                const result = await response.json();

                if (result.status === 'success') {
                    updateAccountsGrid(result.data.accounts);
                    updateRecentTransactions(result.data.recent_transactions);
                    updateSummary(result.data.summary);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Update accounts grid
        function updateAccountsGrid(accounts) {
            const grid = document.getElementById('accountsGrid');
            grid.innerHTML = '';

            accounts.forEach((account, index) => {
                const cardClass = index === 0 ? 'account-card primary' : 'account-card';
                grid.innerHTML += `
                    <div class="${cardClass}" onclick="viewAccountDetails('${account.account_number}')">
                        <div class="account-header">
                            <span class="account-type">${account.account_type}</span>
                            <span class="account-status status-${account.status}">${account.status}</span>
                        </div>
                        <div class="account-number">${maskAccountNumber(account.account_number)}</div>
                        <div class="account-balance">${formatCurrency(account.balance)} <span class="account-currency">${account.currency}</span></div>
                    </div>
                `;
            });
        }

        // Update recent transactions
        function updateRecentTransactions(transactions) {
            const list = document.getElementById('recentTransactionsList');

            if (!transactions || transactions.length === 0) {
                list.innerHTML = '<tr><td colspan="5" style="text-align: center;">No recent transactions</td></tr>';
                return;
            }

            list.innerHTML = '';
            transactions.slice(0, 5).forEach(t => {
                const amountClass = t.entry_type === 'Credit' ? 'transaction-credit' : 'transaction-debit';
                const amountPrefix = t.entry_type === 'Credit' ? '+' : '-';

                list.innerHTML += `
                    <tr>
                        <td>${t.date}</td>
                        <td>${t.description || 'Transfer'}</td>
                        <td><small>${t.reference || 'N/A'}</small></td>
                        <td class="${amountClass}">${amountPrefix} ${formatCurrency(t.amount)}</td>
                        <td><span class="badge-${t.status_color}">${t.status}</span></td>
                    </tr>
                `;
            });
        }

        // Update summary
        function updateSummary(summary) {
            document.getElementById('totalBalance').textContent = formatCurrency(summary.total_balance);
            document.getElementById('activeAccounts').textContent = summary.active_accounts;
        }

        // Load all transactions with filters
        async function loadAllTransactions(reset = true) {
            if (reset) {
                currentPage = 1;
                document.getElementById('allTransactionsList').innerHTML = '<tr><td colspan="8" style="text-align: center;">Loading...</td></tr>';
            }

            const type = document.getElementById('transactionTypeFilter').value;
            const search = document.getElementById('transactionSearch').value;
            const date = document.getElementById('transactionDate').value;

            try {
                const response = await fetch(`php/get_transactions.php?page=${currentPage}&type=${type}&search=${encodeURIComponent(search)}&date=${date}`);
                const result = await response.json();

                if (result.status === 'success') {
                    displayTransactions(result.data.transactions, reset);

                    if (result.data.has_more) {
                        document.getElementById('loadMoreBtn').style.display = 'inline-block';
                    } else {
                        document.getElementById('loadMoreBtn').style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            }
        }

        // Display transactions
        function displayTransactions(transactions, reset) {
            const list = document.getElementById('allTransactionsList');

            if (reset) {
                list.innerHTML = '';
            }

            if (!transactions || transactions.length === 0) {
                if (reset) {
                    list.innerHTML = '<tr><td colspan="8" style="text-align: center;">No transactions found</td></tr>';
                }
                return;
            }

            transactions.forEach(t => {
                const amountClass = t.entry_type === 'Credit' ? 'transaction-credit' : 'transaction-debit';
                const amountPrefix = t.entry_type === 'Credit' ? '+' : '-';

                list.innerHTML += `
                    <tr>
                        <td>${t.date} ${t.time}</td>
                        <td><small>${t.reference || 'N/A'}</small></td>
                        <td>${t.description || '-'}</td>
                        <td>${t.type}</td>
                        <td class="${amountClass}">${amountPrefix} ${formatCurrency(t.amount)}</td>
                        <td>${t.fee ? formatCurrency(t.fee) : '-'}</td>
                        <td>${t.balance_after ? formatCurrency(t.balance_after) : '-'}</td>
                        <td><span class="badge-${t.status_color}">${t.status}</span></td>
                    </tr>
                `;
            });
        }

        // Load more transactions
        function loadMoreTransactions() {
            if (!loadingMore) {
                loadingMore = true;
                currentPage++;
                loadAllTransactions(false).then(() => {
                    loadingMore = false;
                });
            }
        }

        // Load scheduled transfers
        async function loadScheduledTransfers() {
            try {
                const response = await fetch('php/get_scheduled_transfers.php');
                const result = await response.json();

                const list = document.getElementById('scheduledTransfersList');

                if (!result.data || result.data.length === 0) {
                    list.innerHTML = '<tr><td colspan="5" style="text-align: center;">No scheduled transfers</td></tr>';
                    return;
                }

                list.innerHTML = '';
                result.data.forEach(t => {
                    list.innerHTML += `
                        <tr>
                            <td>${t.scheduled_date}</td>
                            <td>${t.to_name || t.to_account}</td>
                            <td>${formatCurrency(t.amount)}</td>
                            <td><span class="badge-${t.status === 'pending' ? 'warning' : 'success'}">${t.status}</span></td>
                            <td>
                                ${t.status === 'pending' ?
                            `<button onclick="cancelScheduledTransfer(${t.id})" style="background: none; border: none; color: var(--error); cursor: pointer;">
                                        <i class="fas fa-times"></i>
                                    </button>` : ''}
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Error loading scheduled transfers:', error);
            }
        }

        // Load beneficiaries
        async function loadBeneficiaries() {
            try {
                const response = await fetch('php/get_beneficiaries.php');
                const result = await response.json();

                const list = document.getElementById('beneficiariesList');

                if (!result.data || result.data.length === 0) {
                    list.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No saved beneficiaries</p>';
                    return;
                }

                list.innerHTML = '';
                result.data.forEach(b => {
                    list.innerHTML += `
                        <div class="account-card" style="cursor: default;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <i class="fas fa-user-circle" style="font-size: 40px; color: var(--primary);"></i>
                                <button onclick="removeBeneficiary(${b.id})" style="background: none; border: none; color: var(--error); cursor: pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div style="font-weight: 600; margin-bottom: 5px;">${b.name || 'Unknown'}</div>
                            <div style="font-size: 14px; color: var(--text-secondary);">${maskAccountNumber(b.account)}</div>
                            <button onclick="useBeneficiary('${b.account}')" class="btn" style="margin-top: 15px; padding: 8px;">Send Money</button>
                        </div>
                    `;
                });
            } catch (error) {
                console.error('Error loading beneficiaries:', error);
            }
        }

        // Load user profile
        async function loadUserProfile() {
            try {
                const response = await fetch('php/get_profile.php');
                const result = await response.json();

                if (result.status === 'success') {
                    document.getElementById('profileFullname').value = result.data.fullname;
                    document.getElementById('profileEmail').value = result.data.email;
                    document.getElementById('profilePhone').value = result.data.phone || 'Not set';
                    document.getElementById('profileMemberSince').value = result.data.member_since;
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
        }

        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('php/get_notifications.php');
                const result = await response.json();

                // Update badge
                document.getElementById('notificationCount').textContent = result.data.unread_count;

                // Update panel
                const list = document.getElementById('notificationsList');

                if (!result.data.notifications || result.data.notifications.length === 0) {
                    list.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">No notifications</p>';
                    return;
                }

                list.innerHTML = '';
                result.data.notifications.forEach(n => {
                    const unreadClass = !n.is_read ? 'unread' : '';
                    list.innerHTML += `
                        <div class="notification-item ${unreadClass}" onclick="markNotificationRead(${n.id})">
                            <div class="notification-title">${n.title}</div>
                            <div style="font-size: 13px; margin-bottom: 5px;">${n.message}</div>
                            <div class="notification-meta">${n.created_at}</div>
                        </div>
                    `;
                });
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        // Handle transfer submission
        async function handleTransfer(event, type) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('transfer_type', type);

            const btn = form.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('transferMessage');

            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Processing...';
            messageDiv.innerHTML = '';

            try {
                const response = await fetch('php/transfer.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showMessage(messageDiv, result.message, 'success');
                    form.reset();
                    loadDashboardData();
                    loadBeneficiaries();

                    // Show success notification
                    alert('Transfer completed successfully!\nReference: ' + result.data.reference);
                } else {
                    showMessage(messageDiv, result.message, 'error');
                }
            } catch (error) {
                showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Send Money';
            }
        }

        // Handle scheduled transfer
        async function handleScheduledTransfer(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('is_scheduled', 'true');

            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Scheduling...';

            try {
                const response = await fetch('php/transfer.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert('Transfer scheduled successfully!\nReference: ' + result.data.reference);
                    form.reset();
                    loadScheduledTransfers();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Schedule Transfer';
            }
        }

        // Calculate fees
        function calculateFees() {
            const amount = parseFloat(document.querySelector('input[name="amount"]').value) || 0;
            const urgent = document.getElementById('urgentTransfer').checked;

            if (amount <= 0) {
                document.getElementById('feeCalculator').style.display = 'none';
                return;
            }

            document.getElementById('feeCalculator').style.display = 'block';

            // Base fee
            const baseFee = 5;

            // Percentage fee (0.5% for amounts over 1000, max 100)
            let percentageFee = 0;
            if (amount > 1000) {
                percentageFee = Math.min(amount * 0.005, 100);
            }

            // Urgent fee (1% for urgent, max 200)
            let urgentFee = 0;
            if (urgent) {
                urgentFee = Math.min(amount * 0.01, 200);
            }

            const totalFee = baseFee + percentageFee + urgentFee;
            const totalDeduction = amount + totalFee;

            document.getElementById('baseFee').textContent = formatCurrency(baseFee);
            document.getElementById('percentageFee').textContent = formatCurrency(percentageFee);
            document.getElementById('urgentFee').textContent = formatCurrency(urgentFee);
            document.getElementById('totalFee').textContent = formatCurrency(totalFee);
            document.getElementById('totalDeduction').textContent = formatCurrency(totalDeduction);
        }

        // Toggle notifications panel
        function toggleNotifications() {
            const panel = document.getElementById('notificationsPanel');
            panel.classList.toggle('open');
        }

        // Mark notification as read
        async function markNotificationRead(id) {
            try {
                await fetch('php/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                loadNotifications();
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // Cancel scheduled transfer
        async function cancelScheduledTransfer(id) {
            if (!confirm('Are you sure you want to cancel this scheduled transfer?')) {
                return;
            }

            try {
                const response = await fetch('php/cancel_scheduled.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert('Scheduled transfer cancelled');
                    loadScheduledTransfers();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // Remove beneficiary
        async function removeBeneficiary(id) {
            if (!confirm('Are you sure you want to remove this beneficiary?')) {
                return;
            }

            try {
                const response = await fetch('php/remove_beneficiary.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    loadBeneficiaries();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // Use beneficiary
        function useBeneficiary(account) {
            showSection('transfer');
            showTransferTab('account');
            document.querySelector('input[name="receiver_account"]').value = account;
        }

        // View account details
        function viewAccountDetails(accountNumber) {
            // Implement account details view
            alert('Viewing account: ' + maskAccountNumber(accountNumber));
        }

        // Update profile
        async function updateProfile(event) {
            event.preventDefault();
            // Implement profile update
        }

        // Change password
        function changePassword() {
            // Implement password change
            alert('Password change functionality will be implemented here');
        }

        // Enable 2FA
        function enable2FA() {
            // Implement 2FA setup
            alert('2FA setup will be implemented here');
        }

        // Save preferences
        function savePreferences() {
            // Implement preferences save
            alert('Preferences saved');
        }

        // Show message helper
        function showMessage(element, message, type) {
            element.innerHTML = message;
            element.style.color = type === 'success' ? 'var(--success)' : 'var(--error)';
            setTimeout(() => {
                element.innerHTML = '';
            }, 5000);
        }

        // Format currency helper
        function formatCurrency(amount) {
            return 'ETB ' + parseFloat(amount).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Mask account number helper
        function maskAccountNumber(number) {
            return '****' + number.slice(-4);
        }
    </script>
</body>

</html>