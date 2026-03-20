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
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_username (username),
    INDEX idx_users_role (role),
    INDEX idx_users_last_login (last_login)
);
-- Accounts Table (FIXED)
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL UNIQUE,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'ETB',
    account_type ENUM('checking', 'savings', 'business', 'credit') DEFAULT 'checking',
    status ENUM('active', 'frozen', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_accounts_user_id (user_id),
    INDEX idx_accounts_account_number (account_number),
    INDEX idx_accounts_status (status)
);
-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_account_id INT NULL,
    receiver_account_id INT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255),
    type ENUM('transfer', 'deposit', 'withdrawal') NOT NULL,
    status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
    reference_number VARCHAR(50) UNIQUE,
    fee DECIMAL(10, 2) DEFAULT 0.00,
    balance_after_sender DECIMAL(15, 2) NULL,
    balance_after_receiver DECIMAL(15, 2) NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_account_id) REFERENCES accounts(id) ON DELETE
    SET NULL,
        FOREIGN KEY (receiver_account_id) REFERENCES accounts(id) ON DELETE
    SET NULL,
        INDEX idx_transactions_sender (sender_account_id),
        INDEX idx_transactions_receiver (receiver_account_id),
        INDEX idx_transactions_created (created_at),
        INDEX idx_transactions_status (status),
        INDEX idx_transactions_type (type),
        INDEX idx_transactions_reference (reference_number)
);
-- User Profiles Table (FIXED - removed NOT NULL constraints that might break)
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) DEFAULT 'Addis Ababa',
    country VARCHAR(100) DEFAULT 'Ethiopia',
    profile_picture VARCHAR(255) NULL,
    birth_date DATE NULL,
    gender ENUM('male', 'female', 'other') DEFAULT 'other',
    id_number VARCHAR(50) NULL,
    id_type ENUM('passport', 'national_id', 'driver_license') DEFAULT 'national_id',
    id_front_image VARCHAR(255) NULL,
    id_back_image VARCHAR(255) NULL,
    bio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_profiles_phone (phone)
);
-- Audit Log Table (FIXED)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50),
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NULL,
    old_value JSON,
    new_value JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE
    SET NULL,
        INDEX idx_audit_log_user_id (user_id),
        INDEX idx_audit_log_created (created_at),
        INDEX idx_audit_log_action (action)
);
-- User Sessions Table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_token (session_token),
    INDEX idx_sessions_expires (expires_at)
);
-- Notifications Table (FIXED)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM(
        'transaction',
        'deposit',
        'promotion',
        'security',
        'withdrawal',
        'system'
    ) DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id, is_read, created_at)
);
-- Login Attempts Table (MISSING - ADD THIS)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    ip_address VARCHAR(45),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_attempts (user_id, attempt_time),
    INDEX idx_ip_attempts (ip_address, attempt_time)
);
-- Scheduled Transactions Table (MISSING - ADD THIS)
CREATE TABLE IF NOT EXISTS scheduled_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_account_id INT NOT NULL,
    receiver_account_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255),
    scheduled_date DATETIME NOT NULL,
    reference_number VARCHAR(50) UNIQUE,
    status ENUM('pending', 'processed', 'failed', 'cancelled') DEFAULT 'pending',
    failure_reason TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_account_id) REFERENCES accounts(id),
    FOREIGN KEY (receiver_account_id) REFERENCES accounts(id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
);
-- Beneficiaries Table (MISSING - ADD THIS)
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    beneficiary_user_id INT NULL,
    beneficiary_account VARCHAR(20) NOT NULL,
    beneficiary_name VARCHAR(100),
    nickname VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiary_user_id) REFERENCES users(id) ON DELETE
    SET NULL,
        UNIQUE KEY unique_beneficiary (user_id, beneficiary_account),
        INDEX idx_user_beneficiaries (user_id)
);
-- =============================================
-- ADD TRIGGERS
-- =============================================
DELIMITER // -- Trigger for auto-generating transaction reference numbers
CREATE TRIGGER generate_transaction_reference BEFORE
INSERT ON transactions FOR EACH ROW BEGIN IF NEW.reference_number IS NULL THEN
SET NEW.reference_number = CONCAT(
        'TXN',
        DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'),
        LPAD(FLOOR(RAND() * 1000), 3, '0')
    );
END IF;
END // -- Trigger to prevent negative balance
CREATE TRIGGER check_balance_before_update BEFORE
UPDATE ON accounts FOR EACH ROW BEGIN IF NEW.balance < 0 THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Balance cannot be negative';
END IF;
END // CREATE TRIGGER check_balance_before_insert BEFORE
INSERT ON accounts FOR EACH ROW BEGIN IF NEW.balance < 0 THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Balance cannot be negative';
END IF;
END // -- Audit log triggers for users table
CREATE TRIGGER audit_users_insert
AFTER
INSERT ON users FOR EACH ROW BEGIN
INSERT INTO audit_logs (
        user_id,
        username,
        action,
        table_name,
        record_id,
        new_value
    )
VALUES (
        NEW.id,
        NEW.username,
        'INSERT',
        'users',
        NEW.id,
        JSON_OBJECT(
            'username',
            NEW.username,
            'email',
            NEW.email,
            'role',
            NEW.role
        )
    );
END // CREATE TRIGGER audit_users_update
AFTER
UPDATE ON users FOR EACH ROW BEGIN
INSERT INTO audit_logs (
        user_id,
        username,
        action,
        table_name,
        record_id,
        old_value,
        new_value
    )
VALUES (
        NEW.id,
        NEW.username,
        'UPDATE',
        'users',
        NEW.id,
        JSON_OBJECT(
            'username',
            OLD.username,
            'email',
            OLD.email,
            'role',
            OLD.role
        ),
        JSON_OBJECT(
            'username',
            NEW.username,
            'email',
            NEW.email,
            'role',
            NEW.role
        )
    );
END // CREATE TRIGGER audit_users_delete BEFORE DELETE ON users FOR EACH ROW BEGIN
INSERT INTO audit_logs (
        user_id,
        username,
        action,
        table_name,
        record_id,
        old_value
    )
VALUES (
        OLD.id,
        OLD.username,
        'DELETE',
        'users',
        OLD.id,
        JSON_OBJECT(
            'username',
            OLD.username,
            'email',
            OLD.email,
            'role',
            OLD.role
        )
    );
END // -- Audit log triggers for accounts table
CREATE TRIGGER audit_accounts_insert
AFTER
INSERT ON accounts FOR EACH ROW BEGIN
INSERT INTO audit_logs (
        user_id,
        username,
        action,
        table_name,
        record_id,
        new_value
    )
VALUES (
        NEW.user_id,
        (
            SELECT username
            FROM users
            WHERE id = NEW.user_id
        ),
        'INSERT',
        'accounts',
        NEW.id,
        JSON_OBJECT(
            'account_number',
            NEW.account_number,
            'balance',
            NEW.balance,
            'type',
            NEW.account_type
        )
    );
END // CREATE TRIGGER audit_accounts_update
AFTER
UPDATE ON accounts FOR EACH ROW BEGIN
INSERT INTO audit_logs (
        user_id,
        username,
        action,
        table_name,
        record_id,
        old_value,
        new_value
    )
VALUES (
        NEW.user_id,
        (
            SELECT username
            FROM users
            WHERE id = NEW.user_id
        ),
        'UPDATE',
        'accounts',
        NEW.id,
        JSON_OBJECT('balance', OLD.balance, 'status', OLD.status),
        JSON_OBJECT('balance', NEW.balance, 'status', NEW.status)
    );
END // -- Trigger to update balance_after in transactions
CREATE TRIGGER update_balance_after_insert BEFORE
INSERT ON transactions FOR EACH ROW BEGIN
DECLARE sender_balance DECIMAL(15, 2);
DECLARE receiver_balance DECIMAL(15, 2);
-- Get sender balance if exists
IF NEW.sender_account_id IS NOT NULL THEN
SELECT balance INTO sender_balance
FROM accounts
WHERE id = NEW.sender_account_id;
SET NEW.balance_after_sender = sender_balance - NEW.amount - NEW.fee;
END IF;
-- Get receiver balance if exists
IF NEW.receiver_account_id IS NOT NULL THEN
SELECT balance INTO receiver_balance
FROM accounts
WHERE id = NEW.receiver_account_id;
SET NEW.balance_after_receiver = receiver_balance + NEW.amount;
END IF;
END //
DELIMITER ;
-- =============================================
-- ADD CONSTRAINTS (for MySQL 8.0.16+)
-- =============================================
ALTER TABLE accounts
ADD CONSTRAINT check_balance_non_negative CHECK (balance >= 0);
-- =============================================
-- INSERT SAMPLE DATA
-- =============================================
-- Insert sample admin user
INSERT IGNORE INTO users (
        username,
        fullname,
        email,
        password_hash,
        role,
        phone,
        two_factor_enabled
    )
VALUES (
        'admin',
        'System Admin',
        'admin@cbe.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin',
        '+251911111111',
        0
    );
-- Insert sample regular users
INSERT IGNORE INTO users (
        username,
        fullname,
        email,
        password_hash,
        role,
        phone,
        two_factor_enabled
    )
VALUES (
        'john_doe',
        'John Doe',
        'john@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'user',
        '+251911223344',
        0
    ),
    (
        'jane_smith',
        'Jane Smith',
        'jane@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'user',
        '+251922334455',
        0
    ),
    (
        'test_user',
        'Test User',
        'test@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'user',
        '+251933445566',
        0
    );
-- Insert user profiles
INSERT IGNORE INTO user_profiles (
        user_id,
        phone,
        address,
        city,
        country,
        id_number
    )
VALUES (
        1,
        '+251911111111',
        'Head Office',
        'Addis Ababa',
        'Ethiopia',
        'ADMIN001'
    ),
    (
        2,
        '+251911223344',
        'Bole Road',
        'Addis Ababa',
        'Ethiopia',
        'ET123456789'
    ),
    (
        3,
        '+251922334455',
        'Megenagna',
        'Addis Ababa',
        'Ethiopia',
        'ET987654321'
    ),
    (
        4,
        '+251933445566',
        'Piassa',
        'Addis Ababa',
        'Ethiopia',
        'ET456789123'
    );
-- Insert accounts
INSERT IGNORE INTO accounts (
        user_id,
        account_number,
        balance,
        account_type,
        status
    )
VALUES (
        1,
        '100000000001',
        50000.00,
        'checking',
        'active'
    ),
    (
        2,
        '100012345678',
        15000.00,
        'checking',
        'active'
    ),
    (2, '200012345678', 5000.00, 'savings', 'active'),
    (
        3,
        '100023456789',
        25000.00,
        'checking',
        'active'
    ),
    (3, '200023456789', 10000.00, 'savings', 'active'),
    (4, '100034567890', 1000.00, 'checking', 'active');
-- Insert sample transactions
INSERT INTO transactions (
        sender_account_id,
        receiver_account_id,
        amount,
        description,
        type,
        status,
        fee,
        created_at
    )
VALUES (
        2,
        4,
        1000.00,
        'Payment for services',
        'transfer',
        'completed',
        5.00,
        DATE_SUB(NOW(), INTERVAL 5 DAY)
    ),
    (
        4,
        1,
        2000.00,
        'Monthly subscription',
        'transfer',
        'completed',
        10.00,
        DATE_SUB(NOW(), INTERVAL 4 DAY)
    ),
    (
        NULL,
        2,
        3000.00,
        'Salary deposit',
        'deposit',
        'completed',
        0.00,
        DATE_SUB(NOW(), INTERVAL 3 DAY)
    ),
    (
        2,
        NULL,
        500.00,
        'ATM withdrawal',
        'withdrawal',
        'completed',
        20.00,
        DATE_SUB(NOW(), INTERVAL 2 DAY)
    );
-- Insert notifications
INSERT INTO notifications (user_id, title, message, type, is_read)
VALUES (
        2,
        'Welcome to CBE-Pros!',
        'Thank you for joining CBE-Pros Digital Banking.',
        'system',
        1
    ),
    (
        2,
        'Transfer Received',
        'You received 1000.00 ETB from John Doe',
        'transaction',
        0
    ),
    (
        3,
        'Transfer Sent',
        'You sent 1000.00 ETB to Jane Smith',
        'transaction',
        1
    ),
    (
        1,
        'Security Alert',
        'New admin login detected',
        'security',
        0
    );
-- =============================================
-- VERIFY SETUP
-- =============================================
SELECT 'Database Setup Complete!' as Status;
SELECT COUNT(*) as Total_Users
FROM users;
SELECT COUNT(*) as Total_Accounts
FROM accounts;
SELECT COUNT(*) as Total_Transactions
FROM transactions;
SELECT COUNT(*) as Total_Notifications
FROM notifications;