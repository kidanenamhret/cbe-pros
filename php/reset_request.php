<?php
header('Content-Type: application/json');
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../libs/PHPMailer/Exception.php';
require '../libs/PHPMailer/PHPMailer.php';
require '../libs/PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['id'];

        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $conn->prepare("
            INSERT INTO password_resets (user_id, email, token, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $email, $token, $expires]);

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        
        // Mobile Hotspot Override! Replace localhost with the public LAN IP so mobile devices can reach it
        if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
            $host = '172.18.144.161';
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }

        $path = dirname($_SERVER['PHP_SELF'], 2) . '/reset_password.php';
        $reset_link = $protocol . $host . $path . "?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            // ==========================================
            // DEVELOPER CONFIGURATION: 
            // Enter your Google Address and App Password here!
            // ==========================================
            $mail->Username = 'ae847318@gmail.com';
            $mail->Password = 'odig myiu kusf xhkf
';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('no-reply@cbe-pros.com', 'CBE-Pros Support');
            $mail->addAddress($email, $user['fullname']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request | CBE-Pros';
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #667eea;'>CBE-Pros Support</h2>
                <p>Hello {$user['fullname']},</p>
                <p>We received a request to reset your digital banking password. Click the secure link below to choose a new password:</p>
                <p><a href='{$reset_link}' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0;'>Reset Your Password</a></p>
                <p>If you did not request a password reset, your account is perfectly safe and you can safely ignore this email.</p>
                <br>
                <p>Best regards,<br>The CBE-Pros Security Team</p>
            </div>";

            $mail->AltBody = "Hello {$user['fullname']},\n\nWe received a request to reset your CBE-Pros password. Copy and paste the following link into your browser to choose a new password:\n\n{$reset_link}\n\nIf you did not request a password reset, you can safely ignore this email.";

            $mail->send();

            echo json_encode([
                'status' => 'success',
                'message' => 'Success! Check your real email inbox for the reset link.'
            ]);

        }
        catch (Exception $e) {
            error_log("Mail sending failed: {$mail->ErrorInfo}");
            echo json_encode([
                'status' => 'error',
                'message' => "SMTP Error: Please check line 65 of reset_request.php and ensure you are using a 16-digit Google App Password. Error details: " . $mail->ErrorInfo
            ]);
        }

    }
    else {
        echo json_encode([
            'status' => 'success',
            'message' => 'If an account exists with that email, a real reset link was sent.'
        ]);
    }

}
catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected database error occurred.']);
}
?>
