<?php
/**
 * Delete Expense
 * @package InspireShoes
 * @version 1.4
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    setFlashMessage('success', 'Expense deleted.');
} else {
    setFlashMessage('error', 'Invalid expense ID.');
}

header('Location: ' . BASE_URL . '/expenses/list.php');
exit();
