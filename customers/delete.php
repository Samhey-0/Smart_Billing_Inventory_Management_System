<?php
/**
 * Delete Customer Page
 * 
 * Delete a customer (admin only)
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

// Admin only
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    header('Location: ../index.php');
    exit();
}

$page_title = 'Delete Customer';

// Get customer ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('error', 'Invalid customer ID.');
    header('Location: list.php');
    exit();
}

// Get customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    setFlashMessage('error', 'Customer not found.');
    header('Location: list.php');
    exit();
}

// Check if customer has invoices
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ?");
$stmt->execute([$id]);
$invoiceCount = $stmt->fetchColumn();

if ($invoiceCount > 0) {
    setFlashMessage('error', 'Cannot delete customer. Customer has existing invoices.');
    header('Location: list.php');
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: CSRF token validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
    } else {
        // Delete customer
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlashMessage('success', 'Customer deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete customer.');
        }
    }
    header('Location: list.php');
    exit();
}

// Generate CSRF token
$csrf_token = csrfToken();

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Delete Customer</h1>
    <a href="list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Customers
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            Are you sure you want to delete this customer? This action cannot be undone.
        </div>
        
        <div class="customer-details">
            <p><strong>Name:</strong> <?php echo h($customer['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo h($customer['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo h($customer['phone']); ?></p>
            <p><strong>Address:</strong> <?php echo h($customer['address'] ?? '-'); ?></p>
            <p><strong>City:</strong> <?php echo h($customer['city'] ?? '-'); ?></p>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            
            <div class="form-group">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Customer
                </button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.customer-details {
    margin: 20px 0;
    padding: 15px;
    background: var(--light-gray);
    border-radius: 8px;
}
.customer-details p {
    margin-bottom: 10px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
