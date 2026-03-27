<?php
// admin_users.php - View all registered users
session_start();
require_once 'php/db.php';
require_once 'php/auth.php';

// Check if user is logged in and is admin
checkLogin();
if ($_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admin only.");
}

// Get all users with their accounts
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               up.phone, up.city, up.country,
               COUNT(a.id) as account_count,
               SUM(a.balance) as total_balance
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN accounts a ON u.id = a.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Registered Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
        }

        .stat-box {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-admin {
            background: #dc3545;
            color: white;
        }

        .badge-user {
            background: #28a745;
            color: white;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1>Registered Users</h1>
                <p>List of all users in the CBE-Pros system</p>
            </div>
            <a href="admin_payroll.php" class="btn" style="background: #800080; color: white; padding: 12px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 10px 20px rgba(128,0,128,0.2);">
                <i class="fas fa-money-check-alt"></i> Execute Payroll
            </a>
        </div>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-number">
                    <?php echo count($users); ?>
                </div>
                <div>Total Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php
$admin_count = 0;
foreach ($users as $u) {
    if ($u['role'] == 'admin')
        $admin_count++;
}
echo $admin_count;
?>
                </div>
                <div>Admins</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php echo count($users) - $admin_count; ?>
                </div>
                <div>Regular Users</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Accounts</th>
                    <th>Total Balance</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?php echo $user['id']; ?>
                        </td>
                        <td><strong>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </strong></td>
                        <td>
                            <?php echo htmlspecialchars($user['fullname']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $user['account_count']; ?>
                        </td>
                        <td>
                            <?php echo number_format($user['total_balance'], 2); ?> ETB
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td>
                            <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-view">View</a>
                        </td>
                    </tr>
                <?php
endforeach; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="btn btn-back">Back to Dashboard</a>
    </div>
</body>

</html>