<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE-Pros | Secure Login</title>
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
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .alert-warning {
            background: #feebc8;
            color: #c05621;
            border: 1px solid #fbd38d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
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
            color: var(--text-dim);
            font-size: 16px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-wrapper input.error {
            border-color: var(--error);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            color: var(--text-dim);
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .error-message {
            color: var(--error);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .checkbox-group label {
            color: var(--text-secondary);
            font-size: 14px;
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
            transform: none;
        }

        .btn i {
            font-size: 18px;
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

        .footer-links {
            margin-top: 25px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .security-badge {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
            color: var(--text-dim);
            font-size: 12px;
        }

        .security-badge i {
            margin-right: 5px;
            color: var(--primary);
        }

        .attempts-left {
            font-size: 12px;
            color: var(--warning);
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="glass-container">
        <div class="logo">
            <h1>CBE-PROS</h1>
            <p>Secure Digital Banking</p>
        </div>

        <!-- Alert Messages -->
        <div class="alert alert-error" id="errorAlert">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
        </div>
        <div class="alert alert-success" id="successAlert">
            <i class="fas fa-check-circle"></i> <span id="successMessage"></span>
        </div>
        <div class="alert alert-warning" id="warningAlert">
            <i class="fas fa-exclamation-triangle"></i> <span id="warningMessage"></span>
        </div>

        <!-- Login Form -->
        <form id="loginForm" method="POST" action="php/login.php">
            <!-- CSRF Token will be added by PHP -->
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username or email" value=""
                        required autofocus>
                </div>
                <div class="error-message" id="usernameError"></div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                <div class="error-message" id="passwordError"></div>
                <div class="attempts-left" id="attemptsLeft"></div>
            </div>

            <div class="remember-forgot">
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember_me">
                    <label for="remember">Remember me</label>
                </div>
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn" id="loginBtn">
                <span>Login</span>
                <i class="fas fa-arrow-right"></i>
            </button>

            <div class="footer-links">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>

            <div class="security-badge">
                <span><i class="fas fa-shield-alt"></i> 256-bit SSL</span>
                <span><i class="fas fa-lock"></i> Secure Login</span>
                <span><i class="fas fa-mobile-alt"></i> 2FA Ready</span>
            </div>
        </form>
    </div>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const message = urlParams.get('message');
        const attempts_left = urlParams.get('attempts_left');
        const username = urlParams.get('username');

        // Pre-fill username if provided
        if (username) {
            document.getElementById('username').value = username;
        }

        // Show error messages based on URL parameters
        if (error) {
            let errorMsg = '';
            switch (error) {
                case 'empty_fields':
                    errorMsg = 'Please fill in all fields';
                    break;
                case 'invalid_credentials':
                    errorMsg = 'Invalid username or password';
                    break;
                case 'too_many_attempts':
                    errorMsg = 'Too many failed attempts. Please try again later.';
                    break;
                case 'session_expired':
                    errorMsg = 'Your session has expired. Please login again.';
                    break;
                case 'account_locked':
                    errorMsg = 'Account temporarily locked. Please try again after 15 minutes.';
                    break;
                default:
                    errorMsg = 'Login failed. Please try again.';
            }
            showError(errorMsg);
        }

        // Show message (e.g., logged out)
        if (message) {
            switch (message) {
                case 'logged_out':
                    showSuccess('You have been successfully logged out.');
                    break;
                case 'session_expired':
                    showWarning('Your session expired. Please login again.');
                    break;
                case 'inactivity_timeout':
                    showWarning('You were logged out due to inactivity.');
                    break;
                case 'password_changed':
                    showSuccess('Password changed successfully. Please login.');
                    break;
                case 'registration_success':
                    showSuccess('Registration successful! Please login.');
                    break;
            }
        }

        // Show attempts left
        if (attempts_left) {
            document.getElementById('attemptsLeft').textContent =
                `${attempts_left} attempt${attempts_left > 1 ? 's' : ''} remaining before account lock`;
        }

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');

            // Basic validation
            if (!username || !password) {
                e.preventDefault();
                showError('Please fill in all fields');
                return;
            }

            // Disable button and show loading
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="loader"></span> Logging in...';
        });

        // Helper functions for alerts
        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorAlert').classList.add('show');
            setTimeout(() => {
                document.getElementById('errorAlert').classList.remove('show');
            }, 5000);
        }

        function showSuccess(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successAlert').classList.add('show');
            setTimeout(() => {
                document.getElementById('successAlert').classList.remove('show');
            }, 5000);
        }

        function showWarning(message) {
            document.getElementById('warningMessage').textContent = message;
            document.getElementById('warningAlert').classList.add('show');
            setTimeout(() => {
                document.getElementById('warningAlert').classList.remove('show');
            }, 5000);
        }

        // Input validation on blur
        document.getElementById('username').addEventListener('blur', function () {
            if (!this.value.trim()) {
                this.classList.add('error');
                document.getElementById('usernameError').textContent = 'Username is required';
                document.getElementById('usernameError').classList.add('show');
            } else {
                this.classList.remove('error');
                document.getElementById('usernameError').classList.remove('show');
            }
        });

        document.getElementById('password').addEventListener('blur', function () {
            if (!this.value) {
                this.classList.add('error');
                document.getElementById('passwordError').textContent = 'Password is required';
                document.getElementById('passwordError').classList.add('show');
            } else {
                this.classList.remove('error');
                document.getElementById('passwordError').classList.remove('show');
            }
        });
    </script>
</body>

</html>