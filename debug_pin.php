<?php
require_once 'php/db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT username, transfer_pin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
echo "User: " . $user['username'] . "\n";
echo "PIN HASH: " . ($user['transfer_pin'] ?? 'NULL') . "\n";
?>
