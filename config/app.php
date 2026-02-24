<?php
/**
 * Application Configuration
 * 
 * Base paths and URL constants for Inspire Shoes Billing System
 * 
 * @package InspireShoes
 * @version 1.0
 */

// ─── Dynamic BASE_URL Detection ───────────────────────────────────────────────
// This automatically detects the correct base URL regardless of folder name,
// so you don't need to hardcode '/inspire-shoes' everywhere.

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // __DIR__ is config/, so dirname(__DIR__) is the project root
    // We figure out the web path by comparing docroot to project root
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $projectDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    // Sub-folder path relative to document root (e.g. /inspire-shoes)
    $subPath = str_replace($docRoot, '', $projectDir);

    define('BASE_URL',   $protocol . '://' . $host . $subPath);
    define('ASSETS_URL', BASE_URL . '/assets');
    define('CSS_URL',    ASSETS_URL . '/css');
    define('JS_URL',     ASSETS_URL . '/js');
}

// ─── Filesystem Paths ─────────────────────────────────────────────────────────
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH',    dirname(__DIR__));
    define('UPLOAD_PATH',  ROOT_PATH . '/assets/uploads/products');
    define('UPLOAD_URL',   ASSETS_URL . '/uploads/products');
}

// ─── Currency Settings (PKR) ──────────────────────────────────────────────────
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'Rs. ');
    define('CURRENCY_CODE',   'PKR');
}
