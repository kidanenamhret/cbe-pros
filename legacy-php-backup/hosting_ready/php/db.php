<?php
// Mesfin Digital Bank: InfinityFree Production Database Configuration
// Site: mesfinA6305.infinityfreeapp.com (if0_41469591)

$host = "sql313.infinityfree.com"; 
$db_name = "if0_41469591_mesfin_bank"; 
$username = "if0_41469591";         
$password = "Mesfin623"; // 👈 UPDATED WITH YOUR PASSWORD!

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 30
    ]);
    
    // Set Ethiopia timezone
    $conn->exec("SET time_zone = '+03:00'");
    
} catch (PDOException $e) {
    // Fail silently in production for security reasons
    die("Server Connection Failed. Please try again later.");
}
?>