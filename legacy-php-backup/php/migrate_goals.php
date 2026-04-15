<?php
require_once 'db.php';

try {
    // Create savings_goals table
    $sql = "
    CREATE TABLE IF NOT EXISTS savings_goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        target_amount DECIMAL(15,2) NOT NULL,
        current_amount DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('active', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";

    $conn->exec($sql);
    echo "Savings Goals table created successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
