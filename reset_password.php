<?php
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = $_GET['error'] ?? '';

if (empty($token) || empty($email)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE-Pros | Choose New Password</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --error: #f56565;
            --dark: #1a202c;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --text-dim: #a0aec0;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .glass-container {
            background: var(--glass-bg);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 {
            font-size: 32px; font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; margin-bottom: 5px;
        }
        .logo p { color: var(--text-secondary); font-size: 14px; }
        .alert {
            padding: 15px; border-radius: 10px; margin-bottom: 20px;
            font-size: 14px; display: <?php echo empty($error) ? 'none' : 'block'; ?>;
        }
        .alert-error {
            background: #fed7d7; color: #c53030; border: 1px solid #fc8181;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500; font-size: 14px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i.icon-left { position: absolute; left: 15px; color: var(--text-dim); font-size: 16px; }
        .input-wrapper input {
            width: 100%; padding: 14px 15px 14px 45px; border: 2px solid #e2e8f0;
            border-radius: 12px; font-size: 15px; background: white;
        }
        .input-wrapper input:focus { outline: none; border-color: var(--primary); }
        .password-toggle {
            position: absolute; right: 15px; color: var(--text-dim); cursor: pointer;
            font-size: 16px; transition: color 0.3s;
        }
        .password-toggle:hover { color: var(--primary); }
        .btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: all 0.3s; display: flex; justify-content: center; gap: 10px;
            margin-top: 10px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <div class="glass-container">
        <div class="logo">
            <h1>CBE-PROS</h1>
            <p>New Password Entry</p>
        </div>

        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <span><?php echo htmlspecialchars($error); ?></span>
        </div>

        <form method="POST" action="php/reset_password_submit.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
                    <i class="fas fa-eye password-toggle toggle-btn"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Verify password" required>
                    <i class="fas fa-eye password-toggle toggle-btn"></i>
                </div>
            </div>

            <button type="submit" class="btn">
                <span>Save New Password</span>
            </button>
        </form>
    </div>

    <script>
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
