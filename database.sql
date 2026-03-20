-- CBE-Pros Database Schema
CREATE DATABASE IF NOT EXISTS cbe_pros;
USE cbe_pros;
-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Accounts Table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL UNIQUE,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'ETB',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_account_id INT,
    receiver_account_id INT,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255),
    type ENUM('transfer', 'deposit', 'withdrawal') NOT NULL,
    status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_account_id) REFERENCES accounts(id),
    FOREIGN KEY (receiver_account_id) REFERENCES accounts(id)
);
-- Sample Data (Optional)
-- INSERT INTO users (username, fullname, email, password_hash) VALUES ('admin', 'System Admin', 'admin@cbe.com', '$2y$10$7rGa6.X.Y.X.Y.X.Y.X.Y.X.Y.X.Y.X.Y.X.Y.X.Y.X.Y.');