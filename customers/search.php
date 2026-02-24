<?php
/**
 * Customer Search API
 * 
 * AJAX endpoint for searching customers
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get search query
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Search customers
$sql = "SELECT id, full_name, email, phone, city 
        FROM customers 
        WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?
        ORDER BY full_name ASC
        LIMIT 10";

$customers = dbGetAll($sql, ["%$query%", "%$query%", "%$query%"]);

// Return JSON
echo json_encode($customers);
