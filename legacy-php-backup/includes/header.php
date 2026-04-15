<?php
require_once 'php/auth.php';
require_once 'php/db.php';

// Check if user is logged in
checkLogin();

// Get user info from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'User';
$fullname = $_SESSION['user_fullname'] ?? $username;

// Check if transaction PIN is set
try {
    $stmt = $conn->prepare("SELECT transfer_pin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    $is_pin_set = !empty($u['transfer_pin']);
    $_SESSION['is_pin_set'] = $is_pin_set;
} catch (Exception $e) {
    $is_pin_set = false;
}

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
    <title>Mesfin Digital Bank | Digital Banking Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#020617">
    <style>
        .qr-thumb img { display: block; margin: auto; }
        .qr-card-overlay {
            background: rgba(255,255,255,0.05);
            border: 2px dashed rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 10px;
            transition: all 0.3s;
        }
        
        /* Modern Sidebar Glow */
        .sidebar a::after {
            content: '';
            position: absolute;
            left: 0;
            width: 4px;
            height: 0;
            background: var(--primary);
            transition: height 0.3s ease;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .sidebar a.active::after {
            height: 100%;
        }
    </style>
</head>
<body>
    <!-- Top Progress Bar for Page Transitions -->
    <div id="page-progress" style="position: fixed; top: 0; left: 0; height: 3px; background: var(--primary); width: 0%; z-index: 9999; transition: width 0.3s ease;"></div>

    <div class="dashboard-layout">
        <aside class="sidebar glass-panel">
            <div class="sidebar-brand">
                <i class="fas fa-microchip" style="color: var(--primary); font-size: 24px;"></i>
                <h2>MESFIN<span>BANK</span></h2>
            </div>
            
            <nav id="main-nav">
                <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" data-page="dashboard">
                    <i class="fas fa-chart-pie"></i>
                    <span>Overview</span>
                </a>
                <a href="transfer.php" class="nav-item <?php echo $current_page == 'transfer.php' ? 'active' : ''; ?>" data-page="transfer">
                    <i class="fas fa-paper-plane"></i>
                    <span>MoonPay Transfer</span>
                </a>
                <a href="accounts.php" class="nav-item <?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>" data-page="accounts">
                    <i class="fas fa-vault"></i>
                    <span>Digital Vaults</span>
                </a>
                <a href="transactions.php" class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>" data-page="transactions">
                    <i class="fas fa-list-ul"></i>
                    <span>Ledger History</span>
                </a>
                
                <div class="nav-label">Services</div>
                
                <a href="telebirr.php" class="nav-item <?php echo $current_page == 'telebirr.php' ? 'active' : ''; ?>" data-page="telebirr">
                    <i class="fas fa-mobile-screen-button"></i>
                    <span>Mobile Pay</span>
                </a>
                <a href="pay.php" class="nav-item <?php echo $current_page == 'pay.php' ? 'active' : ''; ?>" data-page="pay">
                    <i class="fas fa-qrcode"></i>
                    <span>Scan & Pay</span>
                </a>
                
                <div class="nav-label">Personal</div>
                
                <a href="goals.php" class="nav-item <?php echo $current_page == 'goals.php' ? 'active' : ''; ?>" data-page="goals">
                    <i class="fas fa-meteor"></i>
                    <span>Smart Goals</span>
                </a>
                <a href="support.php" class="nav-item <?php echo $current_page == 'support.php' ? 'active' : ''; ?>" data-page="support">
                    <i class="fas fa-circle-question"></i>
                    <span>Support Hub</span>
                </a>
                
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="nav-item admin-btn <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin Panel</span>
                </a>
                <?php endif; ?>

                <a href="php/logout.php" class="logout-link">
                    <i class="fas fa-power-off"></i>
                    <span>Sign Out</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="app-container">
            <header class="dashboard-header glass-panel">
                <div class="header-left">
                    <h1>Core <span>Interface</span></h1>
                    <p class="current-date"><?php echo date('l, F j'); ?></p>
                </div>
                
                <div class="header-right">
                    <div class="header-action-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="badge pulse" id="notificationCount">0</span>
                    </div>
                    
                    <div class="user-profile-widget" onclick="window.location.href='settings.php'">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                            <span class="user-status">Premium Tier</span>
                        </div>
                        <div class="avatar-glow">
                            <div class="avatar-inner">
                                <?php if (!empty($profile_image)): ?>
                                    <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

