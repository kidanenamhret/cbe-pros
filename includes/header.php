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

// Fetch user profile image
$profile_image = null;
try {
    $stmt = $conn->prepare("SELECT profile_picture FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_profile && !empty($user_profile['profile_picture'])) {
        $profile_image = $user_profile['profile_picture'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch profile: " . $e->getMessage());
}
?>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
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
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
<aside class="sidebar">
            <h2>CBE-PROS</h2>
            <nav>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Overview</span>
                </a>
                <a href="transfer.php" class="<?php echo $current_page == 'transfer.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transfer</span>
                </a>
                <a href="accounts.php" class="<?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Accounts</span>
                </a>
                <a href="transactions.php" class="<?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Transactions</span>
                </a>
                <a href="beneficiaries.php" class="<?php echo $current_page == 'beneficiaries.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Beneficiaries</span>
                </a>
                <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
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
                    <div class="user-menu" style="cursor: pointer;" onclick="window.location.href='settings.php'">
                        <div class="avatar" style="overflow: hidden; width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; background: var(--primary); color: white;">
                            <?php if (!empty($profile_image)): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>
