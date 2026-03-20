<?php
// register.php - Place in root directory
// Force session to start with proper settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent fixation (optional)
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Debug: Display session info (remove in production)
echo "<!-- Session ID: " . session_id() . " -->";
echo "<!-- CSRF Token: " . $csrf_token . " -->";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE-Pros | Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            color: #a0aec0;
            font-size: 16px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            color: #a0aec0;
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .checkbox-group label {
            color: #666;
            font-size: 14px;
        }

        .checkbox-group a {
            color: #667eea;
            text-decoration: none;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
        }

        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border: 1px solid #9ae6b4;
        }

        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
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
    </style>
</head>

<body>
    <div class="glass-container">
        <div class="logo">
            <h1>CREATE ACCOUNT</h1>
            <p>Join CBE-Pros Digital Banking</p>
        </div>

        <div class="alert alert-error" id="errorAlert">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
        </div>

        <form id="registerForm" method="POST" action="php/register.php">
            <!-- CRITICAL: CSRF Token hidden field -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="fullname">Full Name *</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" placeholder="+251911234567 or 0911234567" required>
                </div>
                <small style="color: #666; font-size: 12px;">Format: +251911234567 or 0911234567</small>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Create a strong password"
                        required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                <small style="color: #666; font-size: 12px;">Minimum 8 characters with uppercase, lowercase, number, and
                    special character</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password"
                        placeholder="Re-enter your password" required>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                <label for="agree_terms">
                    I agree to the <a href="#" target="_blank">Terms and Conditions</a> and
                    <a href="#" target="_blank">Privacy Policy</a> *
                </label>
            </div>

            <button type="submit" class="btn" id="registerBtn">
                <span>Create Account</span>
                <i class="fas fa-arrow-right"></i>
            </button>

            <div class="footer-links">
                Already have an account? <a href="index.html">Login here</a>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        if (togglePassword) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Form validation before submit
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const agreeTerms = document.getElementById('agree_terms').checked;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            // Check if CSRF token exists
            if (!csrfToken) {
                e.preventDefault();
                showError('Security token missing. Please refresh the page.');
                return false;
            }

            // Validate password match
            if (password !== confirm) {
                e.preventDefault();
                showError('Passwords do not match');
                return false;
            }

            // Validate password strength
            if (password.length < 8) {
                e.preventDefault();
                showError('Password must be at least 8 characters');
                return false;
            }

            if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one uppercase letter');
                return false;
            }

            if (!/[a-z]/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one lowercase letter');
                return false;
            }

            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one number');
                return false;
            }

            if (!/[!@#$%^&*()\-_=+{};:,<.>]/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one special character');
                return false;
            }

            // Validate terms agreement
            if (!agreeTerms) {
                e.preventDefault();
                showError('You must agree to the Terms and Conditions');
                return false;
            }

            // Disable button and show loading
            const btn = document.getElementById('registerBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Creating Account...';
        });

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            const messageSpan = document.getElementById('errorMessage');
            messageSpan.innerHTML = message;
            alert.style.display = 'block';

            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    </script>
</body>

</html>