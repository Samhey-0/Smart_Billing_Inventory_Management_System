<?php
/**
 * Utility Functions for Inspire Shoes Billing System
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';   // also loads app.php → BASE_URL always defined

// ─── Authentication ───────────────────────────────────────────────────────────

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

// ✅ NEW: Require admin access - redirects staff away
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

function getUserRole(): string {
    return $_SESSION['role'] ?? 'staff';
}

function isAdmin(): bool {
    return getUserRole() === 'admin';
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return false;
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function adminExists(): bool {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    return $stmt->fetchColumn() > 0;
}

// ─── Database Helpers ─────────────────────────────────────────────────────────

function dbQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

function dbGetOne($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function dbGetAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function dbLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

function getCount($table, $condition = '', $params = []) {
    global $pdo;
    $sql = "SELECT COUNT(*) as count FROM {$table}";
    if ($condition) $sql .= " WHERE " . $condition;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ? (int)$result['count'] : 0;
}

function getSum($table, $column, $condition = '', $params = []) {
    global $pdo;
    $sql = "SELECT SUM({$column}) as total FROM {$table}";
    if ($condition) $sql .= " WHERE " . $condition;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return ($result && $result['total']) ? (float)$result['total'] : 0.0;
}

// ─── Formatting ───────────────────────────────────────────────────────────────

function h($string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function formatCurrency($amount, $symbol = null): string {
    // ✅ Uses CURRENCY_SYMBOL constant (Rs.) instead of hardcoded $
    $symbol = $symbol ?? (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : 'Rs. ');
    return $symbol . number_format((float)$amount, 2, '.', ',');
}

function formatDate($date, $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

function formatDateTime($datetime): string {
    return date('M d, Y h:i A', strtotime($datetime));
}

function generateInvoiceNumber($id): string {
    return 'INV-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────

function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token): bool {
    if (!isset($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Flash Messages ───────────────────────────────────────────────────────────

function setFlashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_' . $type] = $message;
}

function getFlashMessage($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

// ─── Navigation ───────────────────────────────────────────────────────────────

function isActivePage(string $page): string {
    return str_contains($_SERVER['REQUEST_URI'] ?? '', $page) ? 'active' : '';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

// ─── Validation ───────────────────────────────────────────────────────────────

function isValidEmail($email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone($phone): bool {
    return preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $phone) === 1;
}

// ─── File Upload ──────────────────────────────────────────────────────────────

function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'], $maxSize = 2097152): array {
    $result = ['success' => false, 'filename' => '', 'error' => ''];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'File upload error code: ' . $file['error'];
        return $result;
    }
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File size exceeds ' . ($maxSize / 1048576) . 'MB limit.';
        return $result;
    }

    // Use finfo + extension fallback for XAMPP compatibility
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extMimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    if (!in_array($mimeType, $allowedTypes) && isset($extMimeMap[$ext])) {
        $mimeType = $extMimeMap[$ext];
    }

    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Invalid file type. Only JPG, PNG, and WEBP are allowed.';
        return $result;
    }

    $extension   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetDir . $newFilename)) {
        $result['success']  = true;
        $result['filename'] = $newFilename;
    } else {
        $result['error'] = 'Failed to move uploaded file. Check folder permissions.';
    }

    return $result;
}

function deleteFile($filePath): bool {
    if ($filePath && file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// ─── Settings Functions ───────────────────────────────────────────────────────

function getSetting(string $key, string $default = ''): string {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function getAllSettings(): array {
    global $pdo;
    try {
        $rows = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        return $rows ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function updateSetting(string $key, string $value): bool {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    return $stmt->execute([$key, $value, $value]);
}

// ─── WhatsApp Helper ──────────────────────────────────────────────────────────

function generateWhatsAppLink(string $phone, string $message): string {
    // Clean phone number - remove spaces, dashes, plus
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Convert Pakistani local format 03xx to international 923xx
    if (str_starts_with($phone, '0')) {
        $phone = '92' . substr($phone, 1);
    }
    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
}
