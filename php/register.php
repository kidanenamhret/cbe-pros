<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $username = strtolower(str_replace(' ', '', $fullname)) . rand(10, 99);

    try {
        $conn->beginTransaction();

        // Insert User
        $stmt = $conn->prepare("INSERT INTO users (username, fullname, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $fullname, $email, $password_hash]);
        $user_id = $conn->lastInsertId();

        // Create initial account with some test balance
        $account_number = "CBE-" . rand(100000, 999999);
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $account_number, 1000.00]); // Initial 1000 ETB for testing

        $conn->commit();
        echo "Account created successfully! Your username is: <strong>$username</strong>. <a href='../index.html'>Login here</a>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>