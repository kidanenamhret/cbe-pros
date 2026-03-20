<?php
// view_users_simple.php - Simple version to view registered users
require_once 'php/db.php';

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Registered Users</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #667eea; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .admin { color: red; font-weight: bold; }
    .user { color: green; }
</style>";
echo "</head><body>";

echo "<h1>Registered Users - CBE-Pros</h1>";

try {
    // Get all users
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.fullname, u.email, u.role, u.created_at,
               up.phone,
               COUNT(a.id) as account_count
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN accounts a ON u.id = a.user_id
        GROUP BY u.id
        ORDER BY u.id DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Username</th>";
    echo "<th>Full Name</th>";
    echo "<th>Email</th>";
    echo "<th>Phone</th>";
    echo "<th>Role</th>";
    echo "<th>Accounts</th>";
    echo "<th>Registered Date</th>";
    echo "</tr>";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . ($user['phone'] ? htmlspecialchars($user['phone']) : 'Not set') . "</td>";
        echo "<td class='" . $user['role'] . "'>" . $user['role'] . "</td>";
        echo "<td>" . $user['account_count'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<br>";
    echo "<p>Total Users: " . count($users) . "</p>";

}
catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
echo "</body></html>";
?>