# Inspire Shoes Billing System - Setup Instructions

## Overview
Inspire Shoes is a complete billing system for shoe shops with product management, customer management, and invoicing capabilities.

## Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache Web Server
- XAMPP, WAMP, or LAMP stack

## Installation Steps

### Step 1: Place the Files
1. Copy the `inspire-shoes` folder to your web server's document root:
   - For XAMPP: `C:\xampp\htdocs\inspire-shoes\`
   - For WAMP: `C:\wamp64\www\inspire-shoes\`
   - For LAMP: `/var/www/html/inspire-shoes/`

### Step 2: Import the Database
1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database named `inspire_shoes` with collation `utf8mb4_unicode_ci`
3. Click on the "Import" tab
4. Select the file `database/inspire_shoes.sql`
5. Click "Go" to import

The database includes:
- Admin user: Username: `admin`, Password: `Admin@1234`
- 5 sample products
- 3 sample customers
- 2 sample invoices

### Step 3: Configure Database Connection
1. Open `config/db.php` in a text editor
2. Verify the database credentials:
```
php
define('DB_HOST', 'localhost');
define('DB_NAME', 'inspire_shoes');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP has no password
```
3. If your MySQL has a password, update `DB_PASS` accordingly

### Step 4: Set File Permissions
For Linux/Mac:
```
bash
chmod -R 755 assets/uploads/products/
```

For Windows (if needed), ensure the folder is writable.

### Step 5: Access the System
1. Open your browser and navigate to:
   
```
   http://localhost/inspire-shoes/
   
```
2. You should be redirected to the login page

### Step 6: First-Time Setup
1. Click "Create Admin Account" on the login page
2. Fill in the registration form:
   - Username: Choose a username (min 3 characters)
   - Email: Enter a valid email
   - Password: Min 8 characters with uppercase, lowercase, number, and special character
3. Click "Create Admin Account"
4. Login with your new admin account

## Default Login Credentials
- **Username:** admin
- **Password:** Admin@1234

## Features

### Product Management
- Add, edit, and delete products
- Upload product images
- Track stock quantities
- Search and filter products

### Customer Management
- Add, edit, and delete customers
- View customer purchase history
- Track total purchases per customer

### Invoice/Billing System
- Create POS-style invoices
- Search and add products to cart
- Select customers
- Automatic tax calculation (default 16%)
- Apply discounts
- Print receipts

### Dashboard
- View statistics (total products, customers, invoices today, revenue today)
- See recent invoices
- Low stock alerts
- Quick action buttons

## Security Features Implemented
- PDO Prepared Statements (SQL Injection prevention)
- CSRF Tokens on all forms
- Password hashing with bcrypt
- Session security settings
- XSS prevention (htmlspecialchars)
- File upload validation

## Troubleshooting

### Blank Page
- Check PHP error logs
- Ensure database connection is correct
- Verify all files are uploaded

### Upload Errors
- Check folder permissions
- Verify PHP upload limits in php.ini:
  
```
  upload_max_filesize = 2M
  post_max_size = 8M
  
```

### Database Connection Errors
- Verify MySQL is running
- Check database credentials in config/db.php
- Ensure the database exists

### Session Errors
- Ensure PHP sessions are working
- Check session save path permissions

## File Structure
```
inspire-shoes/
├── index.php              # Dashboard
├── config/db.php          # Database configuration
├── auth/                  # Authentication
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── products/              # Product management
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   ├── delete.php
│   └── search.php
├── customers/             # Customer management
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   ├── delete.php
│   ├── profile.php
│   └── search.php
├── invoices/              # Invoice management
│   ├── list.php
│   ├── create.php
│   ├── view.php
│   └── receipt.php
├── assets/                # Static assets
│   ├── css/
│   ├── js/
│   └── uploads/
├── includes/              # Reusable components
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── database/              # Database files
│   └── inspire_shoes.sql
└── SETUP.md               # This file
```

## Support
For issues or questions, please refer to the code comments or contact the system administrator.

---

**Version:** 1.0  
**Author:** Inspire Shoes Development Team  
**License:** SAIM
