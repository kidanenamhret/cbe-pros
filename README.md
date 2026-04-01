# CBE-Pros Digital Banking System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A secure, feature-rich digital banking platform built with PHP and MySQL, providing a complete online banking experience with user accounts, money transfers, transaction history, and advanced financial services.

## 🌟 Features

### Core Banking Features
- ✅ **User Registration & Authentication** with secure password hashing.
- ✅ **Multi-Account Support** (Checking & Savings accounts).
- ✅ **Money Transfers** (via Username, Account Number, or Phone Number).
- ✅ **Scheduled Transfers** with date/time automation.
- ✅ **Transaction History** with advanced filtering and CSV export.
- ✅ **Account Balance Management** with real-time updates.
- ✅ **Beneficiary Management** - Save and manage frequent recipients.

### 🚀 Advanced Financial Services
- ✅ **Telebirr Integration** - Seamless mobile money transfers and bill payments.
- ✅ **Merchant & Business Hub** - Merchants can register and accept store payments via Static QR.
- ✅ **Static Shop QR Poster** - Print and display permanent QRs for instant checkout.
- ✅ **Bill Pay Hub** - Secure utility payments (Electricity, Water, Internet).
- ✅ **Savings Goals Tracker** - Visual targets for financial planning.
- ✅ **Support Hub & Chat** - Real-time ticketing system for customer assistance.
- ✅ **Official Forensic Receipts** - CBE-style branded receipts with IP/Device tracking.
- ✅ **Account Statement Engine** - Branded PDF-ready official ledger generation.
- ✅ **Progressive Web App (PWA)** - Installable on iPhone/Android as a standalone bank app.

### 🔒 Security Features
- 🛡️ **Mandatory 4-Digit Transaction PIN** - Hashed PIN required for all outgoing transfers.
- 🔒 **Forensic Metadata Tracking** - IP Address and Browser Signature logged on every transaction.
- 🔒 **CSRF & SQL Injection Protection** - Secure forms and prepared statements.
- 🔒 **Password Hashing** - Uses Bcrypt/Argon2ID for credential security.
- 🔒 **Session Management** - Auto-timeout and activity tracking.
- 🔒 **Login Attempt Limiting** - 5 attempts followed by a 15-minute account lockout.
- 🔒 **Audit Logging** - Detailed logs for all security-critical events.

### 📊 Administrative Features
- 👑 **Admin Command Center** - Complete user oversight and account status control.
- 📋 **System Audit Logs** - Track all security-critical user activities globally.
- 📊 **Global Analytics** - Total Volume, User counts, and Transaction stats for managers.
- 👥 **Role-Based Access Control** - Distinct permissions for Users vs Admins.

---

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Quick Installation](#quick-installation)
3. [Configuration](#configuration)
4. [Mobile Access](#mobile-access)
5. [Default Credentials](#default-credentials)
6. [Folder Structure](#folder-structure)
7. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Software
- **XAMPP** v8.0+ (Apache, PHP 8.0+, MySQL 5.7+)
- **Web Browser** - Modern browser (Chrome, Firefox, Edge)
- **Git** (for cloning the repository)

### PHP Extensions
`PDO`, `PDO_MySQL`, `OpenSSL`, `JSON`, `Session`, `FileInfo`, `MBString`

---

## Quick Installation

### Step 1: Clone the Project
Open your terminal/command prompt and navigate to your `htdocs` directory:
```bash
cd C:\xampp\htdocs
git clone https://github.com/kidanenamhret/cbe-pros.git
```

### Step 2: Set up Database
1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
3. Create a new database named `cbe_pros`.
4. Select the `cbe_pros` database and go to the **Import** tab.
5. Choose the `database_v2.sql` file from the project root and click **Import**.

### Step 3: Configure Connection
Open `php/db.php` and verify the settings:
```php
$host = "localhost";
$db_name = "cbe_pros";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password (empty)
```

---

## Configuration

### Timezone
The system is preset to Ethiopia's timezone (`+03:00`). You can adjust this in `php/db.php`:
```php
$conn->exec("SET time_zone = '+03:00'");
```

### Security PIN Setup
New users must set a **4-digit Transfer PIN** upon first login or in Settings before they can perform any transactions. This PIN is hashed and stored securely.

---

## Mobile Access
To access the system from your mobile phone on the same Wi-Fi network:

1. Open CMD on your PC and type `ipconfig`.
2. Find your **IPv4 Address** (e.g., `192.168.1.10`).
3. On your phone's browser, enter: `http://192.168.1.10/cbe-pros/index.php`.
4. Ensure your Windows Firewall allows Apache access (Private networks).

---

## Default Credentials
For testing and development purposes:

| Role | Username | Password |
| :--- | :--- | :--- |
| **Admin** | `admin` | `password` |
| **User** | `john_doe` | `password` |

---

## Folder Structure
- `php/` - Core backend logic and API endpoints.
- `css/` - Styling and theme components.
- `js/` - Frontend interactivity and AJAX handlers.
- `includes/` - Shared layouts and helper functions.
- `receipt/` - Transaction receipt templates and assets.
- `uploads/` - Storage for profile pictures and IDs.
- `sql/` - (If applicable) database migration scripts.

---

## Troubleshooting

- **Login Failed:** Check if MySQL service is running. Verify `php/db.php` credentials.
- **PIN Verification Error:** Ensure the `transfer_pin` column exists in the `users` table (Import `database_v2.sql`).
- **PWA Not Installing:** Ensure the site is served over HTTPS or `localhost`.
- **Buttons Not Responding:** Check the browser console (F12) for JS errors or path issues.

---

## 🤝 Contributing
Contributions are welcome! Please fork the repository and submit a pull request for any enhancements or bug fixes.

## 📄 License
Distributed under the MIT License. See `LICENSE` for more information.


