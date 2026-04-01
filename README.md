# CBE-Pros Digital Banking System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Beta-yellow.svg)](https://github.com/kidanenamhret/cbe-pros)

CBE-Pros is a high-performance, secure digital banking platform built with PHP and MySQL. It delivers a full-spectrum financial experience, from core banking and money transfers to advanced merchant services and administrative control. Designed for elegance and security, it features a modern glassmorphism UI and forensic-level transaction tracking.

---

## 🌟 Key Features

### 🏦 Core Banking

- **User Authentication** - Secure onboarding with multi-factor ready session management.
- **Multi-Account Management** - Support for Checking, Savings, and Merchant accounts.
- **Instant Transfers** - Real-time P2P transfers via Account Number, Username, or Phone.
- **Scheduled Payments** - Automated recurring or future-dated transfers.
- **Beneficiary Hub** - Save and organize frequent recipients for 2-click transfers.

### 🎨 Advanced Modules

- **Telebirr Integration** - Seamless mobile money connectivity for transfers and airtime.
- **Smart Payroll Hub (New)** - **Admin-only** batch distribution via CSV for large-scale employee payments.
- **Merchant & Business Suite** - Merchants can onboard, track sales, and generate **Static Store QRs**.
- **Savings Goals Tracker** - Visual targets for financial planning with progress visualization.
- **Support & Ticketing** - Integrated communication channel for real-time customer assistance.
- **Bill Pay Central** - Direct utility payments (Electricity, Water, Internet, DSTV).

### 🔒 Security & Forensics

- **4-Digit Secure PIN** - Mandatory hashed PIN verification for every financial transaction.
- **Forensic Metadata Logging** - Every action is tagged with IP address and User-Agent signature.
- **Audit Trails** - Transparent logging for both users and administrators.
- **Hardened Backend** - Full protection against CSRF, SQL Injection, and XSS.
- **Lockout System** - Automated protection against brute-force login attempts.

### 📄 Documentation & Output

- **Official Ledger Engine** - Generate branded PDF-ready account statements.
- **Smart Receipts** - CBE-style branded transaction receipts generated instantly.
- **Analytics Dashboard** - Real-time visualization of spending and income patterns.

---

## 🛠️ Project Architecture

```ascia
cbe-pros/
├── admin_payroll.php      # Batch CSV distribution logic
├── merchant_dashboard.php # Business management suite
├── goals.php              # Financial planning & tracking
├── receipt.php            # Dynamic receipt generator
├── php/                   # Secure API & DB Logic
├── css/                   # Modern Styling (Vanilla CSS)
├── js/                    # Optimized AJAX Handlers
├── includes/              # Component Layouts
└── uploads/               # Secure Storage
```

---

## 🚀 Getting Started

### 1. Requirements

- **XAMPP v8.0+** (PHP 8.0+, MariaDB/MySQL 5.7+)
- **Modern Browser** (Chrome or Edge recommended for PWA support)

### 2. Installation

1. Clone into your `htdocs` directory:

   ```bash
   git clone https://github.com/kidanenamhret/cbe-pros.git
   ```

2. Import the database:

   - Open `phpMyAdmin`.
   - Create a database: `cbe_pros`.
   - Import `database_v2.sql`.

3. Configure `php/db.php`:

   ```php
   $host = "localhost";
   $db_name = "cbe_pros";
   $username = "root";  // default
   $password = "";      // default
   ```

4. Launch in Browser:

   Open your browser and navigate to:
   `http://localhost/cbe-pros/index.php`

### 3. Mobile Access (Home Network)

Access the bank on your phone while on the same WiFi:

1. Run `ipconfig` on your PC to find your IPv4 (e.g., `192.168.1.5`).
2. Browse to `http://192.168.1.5/cbe-pros/` on your mobile.

---

## 🔑 Default Demo Access

| Account Type    | Username   | Password   |
| :-------------- | :--------- | :--------- |
| **Super Admin** | `admin`    | `password` |
| **Demo User**   | `john_doe` | `password` |

---

## 🤝 Contributing

Join us in building the future of digital banking. Fork the repo, create your feature branch, and submit a PR.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
