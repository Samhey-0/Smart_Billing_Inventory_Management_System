<?php
/**
 * Database Configuration File
 * 
 * PDO database connection for Inspire Shoes Billing System
 * Includes security settings and error handling
 * 
 * @package InspireShoes
 * @version 1.0
 */

// ─── Load app config first so BASE_URL is always defined ─────────────────────
require_once __DIR__ . '/app.php';

// ─── Database Credentials ────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'inspire_shoes');
define('DB_USER', 'root');
define('DB_PASS', '');   // Change this if your MySQL has a password

// ─── PDO Connection ───────────────────────────────────────────────────────────
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}

// ─── Session Security ─────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,   // Set true in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Karachi');
