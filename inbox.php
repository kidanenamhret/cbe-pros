<?php
session_start();
require_once 'php/db.php';

// Fetch all simulated emails (password resets)
try {
    $stmt = $conn->query("
        SELECT pr.*, u.fullname 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        ORDER BY pr.expires_at DESC
    ");
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']) . '/reset_password.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE-Pros | Simulated Developer Inbox</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .inbox-container {
            max-width: 800px;
            margin: 40px auto;
            position: relative;
            z-index: 1;
            padding: 0 20px;
        }
        .email-item {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-left: 6px solid var(--primary);
            border-top: 1px solid var(--glass-border);
            border-right: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
        }
        .email-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            color: var(--text-secondary);
            font-size: 14px;
        }
        .email-body {
            color: var(--text-primary);
            font-size: 15px;
            line-height: 1.6;
        }
        .btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--primary-glow);
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    <div class="inbox-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 40px;">
            <h1 style="color: white; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-inbox"></i> Sandbox Mailbox</h1>
            <a href="index.php" style="color: white; background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 20px; text-decoration: none; backdrop-filter: blur(5px);"><i class="fas fa-arrow-left"></i> Back to App</a>
        </div>

        <?php if (empty($emails)): ?>
            <div class="email-item" style="text-align: center; color: var(--text-secondary); padding: 50px;">
                <i class="fas fa-envelope-open" style="font-size: 60px; margin-bottom: 20px; color: var(--text-dim);"></i>
                <h3 style="color: var(--text-primary);">Inbox is empty</h3>
                <p>Request a password reset on the app to see the simulated email appear here!</p>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): 
                $reset_link = $protocol . $host . $path . "?token=" . $email['token'] . "&email=" . urlencode($email['email']);
            ?>
                <div class="email-item">
                    <div class="email-header">
                        <strong>To: <?php echo htmlspecialchars($email['fullname']); ?> &lt;<?php echo htmlspecialchars($email['email']); ?>&gt;</strong>
                        <span>Expires: <?php echo htmlspecialchars($email['expires_at']); ?></span>
                    </div>
                    <div class="email-body">
                        <strong style="font-size: 20px; margin-bottom: 15px; display: block; color: var(--primary-dark);">Subject: Password Reset Request | CBE-Pros</strong>
                        <p>Hello <b><?php echo htmlspecialchars($email['fullname']); ?></b>,</p>
                        <br>
                        <p>We received a simulated request to reset your digital banking password.</p>
                        <a href="<?php echo htmlspecialchars($reset_link); ?>" class="btn-reset"><i class="fas fa-key"></i> Securely Reset Password</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
