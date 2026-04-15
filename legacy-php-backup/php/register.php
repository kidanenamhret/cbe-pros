<?php
// php/register.php
// Force session start with proper settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove the debug output that might break headers
// Don't echo anything before header checks

// Check if this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../register.php");
    exit();
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    die("Error: CSRF token missing from form. Please refresh the page. <a href='../register.php'>Go back</a>");
}

if (!isset($_SESSION['csrf_token'])) {
    die("Error: No CSRF token in session. Please refresh the page. <a href='../register.php'>Go back</a>");
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: CSRF token mismatch. Please refresh the page. <a href='../register.php'>Go back</a>");
}

// Include database connection
require_once 'db.php';

// Check if connection was successful
if (!isset($conn) || $conn === null) {
    die("Error: Database connection failed. <a href='../register.php'>Go back</a>");
}

// Function to sanitize input
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get and sanitize form data
$fullname = sanitizeInput($_POST['fullname'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$agree_terms = isset($_POST['agree_terms']) ? true : false;

// Simple validation
$errors = [];

if (empty($fullname))
    $errors[] = "Full name is required";
if (empty($email))
    $errors[] = "Email is required";
if (empty($phone))
    $errors[] = "Phone number is required";
if (empty($password))
    $errors[] = "Password is required";
if (strlen($password) < 8)
    $errors[] = "Password must be at least 8 characters";
if (!preg_match("/[A-Z]/", $password))
    $errors[] = "Password must contain at least one uppercase letter";
if (!preg_match("/[a-z]/", $password))
    $errors[] = "Password must contain at least one lowercase letter";
if (!preg_match("/[0-9]/", $password))
    $errors[] = "Password must contain at least one number";
if ($password !== $confirm_password)
    $errors[] = "Passwords do not match";
if (!$agree_terms)
    $errors[] = "You must agree to the Terms and Conditions";

// Format phone number
if (!empty($phone)) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) == '0') {
        $phone = '+251' . substr($phone, 1);
    }
    elseif (substr($phone, 0, 3) == '251') {
        $phone = '+' . $phone;
    }
    elseif (substr($phone, 0, 4) != '+251') {
        $phone = '+251' . $phone;
    }
}

if (!empty($errors)) {
    echo "<h3>Registration Failed:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<a href='../register.php'>Go back</a>";
    exit();
}

try {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die("Email already registered. <a href='../index.php'>Login here</a>");
    }

    // Check if phone exists
    $stmt = $conn->prepare("SELECT user_id FROM user_profiles WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        die("Phone number already registered. <a href='../index.php'>Login here</a>");
    }

    // Generate username
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullname));
    $username = $base_username;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch())
            break;
        $username = $base_username . $counter;
        $counter++;
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction
    $conn->beginTransaction();

    // Insert User
    $stmt = $conn->prepare("
        INSERT INTO users (username, fullname, email, password_hash, role, created_at) 
        VALUES (?, ?, ?, ?, 'user', NOW())
    ");
    $stmt->execute([$username, $fullname, $email, $password_hash]);
    $user_id = $conn->lastInsertId();

    // Create user profile
    $stmt = $conn->prepare("
        INSERT INTO user_profiles (user_id, phone, city, country, created_at) 
        VALUES (?, ?, 'Addis Ababa', 'Ethiopia', NOW())
    ");
    $stmt->execute([$user_id, $phone]);

    // Create checking account
    $account_number = "1000" . str_pad($user_id, 6, '0', STR_PAD_LEFT) . rand(100, 999);
    $stmt = $conn->prepare("
        INSERT INTO accounts (user_id, account_number, balance, account_type, status, created_at) 
        VALUES (?, ?, 1000.00, 'checking', 'active', NOW())
    ");
    $stmt->execute([$user_id, $account_number]);

    // Create savings account
    $savings_number = "2000" . str_pad($user_id, 6, '0', STR_PAD_LEFT) . rand(100, 999);
    $stmt = $conn->prepare("
        INSERT INTO accounts (user_id, account_number, balance, account_type, status, created_at) 
        VALUES (?, ?, 0.00, 'savings', 'active', NOW())
    ");
    $stmt->execute([$user_id, $savings_number]);

    // Create welcome notification
    $welcome_message = "Dear $fullname, welcome to Mesfin Digital Bank Digital Banking. Your username is: $username";
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, created_at) 
        VALUES (?, 'Welcome to Mesfin Digital Bank!', ?, 'system', NOW())
    ");
    $stmt->execute([$user_id, $welcome_message]);

    // Commit transaction
    $conn->commit();

    // Clear CSRF token
    unset($_SESSION['csrf_token']);

    // Success page (HTML output)
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Registration Successful - Mesfin Digital Bank</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .container {
                background: white;
                padding: 40px;
                border-radius: 20px;
                max-width: 500px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }

            h1 {
                color: #28a745;
            }

            .checkmark {
                font-size: 60px;
                color: #28a745;
            }

            .details {
                text-align: left;
                margin: 20px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 10px;
            }

            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
            }

            .username {
                color: #667eea;
                font-weight: bold;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="checkmark">✓</div>
            <h1>Registration Successful!</h1>
            <div class="details">
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
                <p><strong>Username:</strong> <span class="username"><?php echo htmlspecialchars($username); ?></span></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                <p><strong>Checking Account:</strong> <?php echo htmlspecialchars($account_number); ?></p>
                <p><strong>Initial Balance:</strong> 1,000.00 ETB</p>
            </div>
            <a href="../index.php" class="btn">Proceed to Login</a>
        </div>
    </body>

    </html>
    <?php

}
catch (PDOException $e) {
    if (isset($conn))
        $conn->rollBack();
    echo "<h3>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='../register.php'>Go back</a>";
}
?>