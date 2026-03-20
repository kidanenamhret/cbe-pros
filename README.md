# CBE-Pros Setup Guide

Follow these steps to run the application on your local machine:

## 1. Prerequisites

- **XAMPP** (or any local server with PHP and MySQL)
- A web browser (Chrome, Edge, etc.)

## 2. Installation

1. Install **XAMPP** from [apachefriends.org](https://www.apachefriends.org/).
2. Copy the `cbe-pros` folder to the `htdocs` directory of your XAMPP installation (usually `C:\xampp\htdocs\`).
3. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.

## 3. Database Setup

1. Open your browser and go to `http://localhost/phpmyadmin/`.
2. Click on **New** in the left sidebar to create a new database.
3. Name it `cbe_pros` and click **Create**.
4. Click on the `cbe_pros` database, then go to the **Import** tab.
5. Click **Choose File** and select `database.sql` from your `cbe-pros` folder.
6. Scroll down and click **Import**.

## 4. Run the App

1. In your browser, navigate to: `http://localhost/cbe-pros/index.html`
2. Register a new account or use the test credentials if you added any.

## 5. Configuration (Optional)

If your MySQL username or password is not the default (`root` with no password), update the `php/db.php` file:

```php
$username = "your_username";
$password = "your_password";
```
