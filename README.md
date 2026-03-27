# CBE-Pros Digital Banking System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A secure, feature-rich digital banking platform built with PHP and MySQL, providing a complete online banking experience with user accounts, money transfers, transaction history, and more.

## 🌟 Features

### Core Banking Features

- ✅ **User Registration & Authentication** with secure password hashing
- ✅ **Multi-Account Support** (Checking & Savings accounts)
- ✅ **Money Transfers** (by username, account number, or phone number)
- ✅ **Scheduled Transfers** with date/time scheduling
- ✅ **Transaction History** with filters and search
- ✅ **Account Balance Management** with real-time updates
- ✅ **Beneficiary Management** - Save and manage frequent recipients

### 🚀 Enterprise & Advanced Features (New)

- ✅ **Merchant & Business Hub** - Shop owners can register and accept store payments
- ✅ **Static Shop QR Poster** - Print and display permanent QRs for instant checkout
- ✅ **Bill Pay Hub** - Secure utility payments (Electricity, Water, Internet)
- ✅ **Savings Goals Tracker** - Visual targets for financial planning
- ✅ **Support Hub & Chat** - Real-time ticketing system for customer assistance
- ✅ **Official Forensic Receipts** - CBE-style branded receipts with IP/Device tracking
- ✅ **Account Statement Engine** - Branded PDF-ready official ledger generation
- ✅ **Progressive Web App (PWA)** - Installable on iPhone/Android as a standalone bank app

### Security Features

- 🔒 **Forensic Metadata Tracking** - IP Address and Browser Signature on every transaction
- 🔒 **CSRF Protection** on all forms
- 🔒 **SQL Injection Prevention** using prepared statements
- 🔒 **Password Hashing** with bcrypt/Argon2ID
- 🔒 **Session Management** with timeout and activity tracking
- 🔒 **Login Attempt Limiting** (5 attempts then 15-minute lockout)
- 🔒 **Audit Logging** for all security events
- 🔒 **Two-Factor Authentication (2FA)** ready
- 🔒 **Secure Data Export** - Download complete transaction history as CSV

### User Experience

- 📱 **Mobile PWA Support** - Add to Home Screen for a fluid app experience
- 💳 **Fee Calculator** - Real-time fee calculation for transfers
- 🔔 **Notification System** - Real-time alerts for transactions
- 📊 **Dashboard Overview** with account summaries and recent activity
- 📈 **Transaction Analytics** - Monthly summaries and statistics
- 👥 **Beneficiary Management** - Save and manage recipients

### Administrative Features

- 👑 **Admin Command Center** for user oversight and freezing accounts
- 📋 **System Audit Logs** - Track all security-critical user activities
- 📊 **Global Bank Analytics** - Total Volume and User stats for the manager
- 👥 **User Management** - Role assignment (User vs Admin) and status control

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Quick Installation](#quick-installation)
- [Detailed Setup Guide](#detailed-setup-guide)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [Folder Structure](#folder-structure)
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Security Best Practices](#security-best-practices)
- [Contributing](#contributing)
- [License](#license)

## 💻 System Requirements

### Software

- **XAMPP** v8.0+ (includes Apache, PHP 8.0+, MySQL 5.7+)
- **Web Browser** - Chrome, Firefox, Edge (latest versions)
- **Operating System** - Windows 10/11, Linux, or macOS

### PHP Extensions Required

- PDO
- PDO_MySQL
- OpenSSL
- JSON
- Session
- FileInfo (for file uploads)

### Hardware (Minimum)

- **RAM**: 4 GB
- **Storage**: 500 MB free space
- **Processor**: 1.5 GHz dual-core

## 🚀 Quick Installation

### Step 1: Install XAMPP

Download and install XAMPP from [Apache Friends](https://www.apachefriends.org/)

### Step 2: Clone the Repository

```bash
# Windows (Command Prompt)
cd C:\xampp\htdocs
git clone https://github.com/kidanenamhret/cbe-pros.git

# Linux (Terminal)
cd /opt/lampp/htdocs
sudo git clone https://github.com/kidanenamhret/cbe-pros.git

To run the application:
http://localhost/cbe-pros/index.php

To access the database:
http://localhost/phpmyadmin/

