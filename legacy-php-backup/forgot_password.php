<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesfin Digital Bank | Forgot Password</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
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
            word-wrap: break-word; /* Important to wrap long links safely */
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

        .error-message {
            color: var(--error);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <div class="glass-container">
        <div class="logo">
            <h1>MESFIN DIGITAL BANK</h1>
            <p>Reset Password</p>
        </div>

        <div class="alert alert-error" id="errorAlert">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
        </div>
        <div class="alert alert-success" id="successAlert">
            <i class="fas fa-check-circle"></i> <span id="successMessage"></span>
        </div>

        <form id="forgotForm">
            <div class="form-group">
                <label for="email">Enter your registered email address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="user@example.com" required autofocus>
                </div>
                <div class="error-message" id="emailError"></div>
            </div>

            <button type="submit" class="btn" id="resetBtn">
                <span>Send Reset Link</span>
                <i class="fas fa-paper-plane"></i>
            </button>

            <div class="footer-links">
                Remembered your password? <a href="index.php">Back to Login</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('forgotForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const resetBtn = document.getElementById('resetBtn');

            if (!email) {
                showError('Please enter your email address');
                return;
            }

            resetBtn.disabled = true;
            resetBtn.innerHTML = '<span class="loader"></span> Sending...';
            
            // clear alerts
            document.getElementById('errorAlert').classList.remove('show');
            document.getElementById('successAlert').classList.remove('show');

            try {
                const formData = new FormData();
                formData.append('email', email);

                const response = await fetch('php/reset_request.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                if (result.status === 'success') {
                    if (result.reset_link) {
                        showSuccess(result.message + '<br><br><a href="' + result.reset_link + '">Click here to reset your password</a>');
                    } else {
                        showSuccess(result.message);
                    }
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('An error occurred. Please try again later.');
            } finally {
                resetBtn.disabled = false;
                resetBtn.innerHTML = '<span>Send Reset Link</span><i class="fas fa-paper-plane"></i>';
            }
        });

        function showError(message) {
            document.getElementById('errorMessage').innerHTML = message;
            document.getElementById('errorAlert').classList.add('show');
        }

        function showSuccess(message) {
            document.getElementById('successMessage').innerHTML = message;
            document.getElementById('successAlert').classList.add('show');
        }

        document.getElementById('email').addEventListener('blur', function () {
            if (!this.value.trim()) {
                this.classList.add('error');
                document.getElementById('emailError').textContent = 'Email is required';
                document.getElementById('emailError').classList.add('show');
            } else {
                this.classList.remove('error');
                document.getElementById('emailError').classList.remove('show');
            }
        });
    </script>
</body>
</html>
