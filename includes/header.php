<?php
/**
 * Global Header File
 * @package InspireShoes
 * @version 1.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$current_page  = basename($_SERVER['PHP_SELF']);
$public_pages  = ['login.php', 'register.php'];
$is_logged_in  = isset($_SESSION['user_id']);
$is_public_page = in_array($current_page, $public_pages);

$current_user = $is_logged_in ? getCurrentUser() : null;

$success_msg = getFlashMessage('success');
$error_msg   = getFlashMessage('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? h($page_title) . ' - Inspire Shoes' : 'Inspire Shoes'; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- ✅ FIXED: Always use CSS_URL constant, never relative paths -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">

    <style>
        :root {
            --primary-color: #1a2e4a;
            --secondary-color: #2c4a6e;
            --accent-color: #f5a623;
            --accent-hover: #d4941f;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --border-color: #dee2e6;
            --text-color: #333333;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }
        .flash-message {
            padding: 15px 20px;
            margin: 20px;
            border-radius: 8px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        .flash-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .flash-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php if ($is_logged_in): ?>
<!-- Sidebar Navigation -->
<nav class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-shoe-prints"></i> Inspire Shoes</h2>
        <p>Billing System</p>
    </div>

    <ul class="nav-menu">
        <li class="<?php echo ($current_page === 'index.php' && strpos($_SERVER['PHP_SELF'], '/auth/') === false) ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/index.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/products/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/products/list.php">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/customers/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/customers/list.php">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/invoices/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/invoices/list.php">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoices</span>
            </a>
        </li>
        <?php if (isAdmin()): ?>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/stock/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/stock/manage.php">
                <i class="fas fa-boxes"></i>
                <span>Stock</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/expenses/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/expenses/list.php">
                <i class="fas fa-receipt"></i>
                <span>Expenses</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/reports/sales.php">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/admin/staff.php">
                <i class="fas fa-users-cog"></i>
                <span>Staff</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/suppliers.php">
                <i class="fas fa-truck"></i>
                <span>Suppliers</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/settings.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-divider"></li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/change-password.php">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    <!-- Removed sidebar-footer — was covering logout button -->
</nav>

<!-- Main Content -->
<main class="main-content">
    <header class="top-bar">
        <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>
        <h1><?php echo isset($page_title) ? h($page_title) : 'Dashboard'; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo h($current_user['username'] ?? ''); ?></span>
            <i class="fas fa-user-circle"></i>
        </div>
    </header>

    <div class="content-wrapper">
<?php endif; ?>

<!-- Flash Messages -->
<?php if ($success_msg): ?>
    <div class="flash-message success">
        <i class="fas fa-check-circle"></i> <?php echo h($success_msg); ?>
    </div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="flash-message error">
        <i class="fas fa-exclamation-circle"></i> <?php echo h($error_msg); ?>
    </div>
<?php endif; ?>
