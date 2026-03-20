<?php
// Database configuration
$host = "localhost";
$db_name = "cbe_pros";
$username = "root";
$password = "";

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);

    // Set PDO attributes
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Optional: Set timezone
    $conn->exec("SET time_zone = '+03:00'"); // Ethiopia time zone

    // For debugging - uncomment to test connection
    // echo "Database connected successfully!";

} catch (PDOException $e) {
    // Log error
    error_log("Database Connection Error: " . $e->getMessage());

    // For debugging - shows error
    die("Connection failed: " . $e->getMessage());

    // For production - use this instead
    // die("Database connection failed. Please try again later.");
}
?>