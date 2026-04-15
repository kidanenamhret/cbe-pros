<?php
require_once 'db.php';

try {
    // Add forensic columns to transactions table
    $sql = "
    ALTER TABLE transactions 
    ADD COLUMN ip_address VARCHAR(45) AFTER reference_number,
    ADD COLUMN user_agent TEXT AFTER ip_address,
    ADD COLUMN session_id VARCHAR(100) AFTER user_agent;
    ";

    $conn->exec($sql);
    echo "Forensic Metadata columns added successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
