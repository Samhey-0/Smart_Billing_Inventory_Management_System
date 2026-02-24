<?php
/**
 * Delete Product Page
 * 
 * Delete a product (admin only)
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

$page_title = 'Delete Product';

// Get product ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('error', 'Invalid product ID.');
    header('Location: list.php');
    exit();
}

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    setFlashMessage('error', 'Product not found.');
    header('Location: list.php');
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: CSRF token validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
    } else {
        // Delete product image if exists
        if ($product['image_path']) {
            $imagePath = __DIR__ . '/../assets/uploads/products/' . $product['image_path'];
            deleteFile($imagePath);
        }
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlashMessage('success', 'Product deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete product.');
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
    <h1>Delete Product</h1>
    <a href="list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            Are you sure you want to delete this product? This action cannot be undone.
        </div>
        
        <div class="product-details">
            <p><strong>Name:</strong> <?php echo h($product['name']); ?></p>
            <p><strong>Brand:</strong> <?php echo h($product['brand']); ?></p>
            <p><strong>Size:</strong> <?php echo h($product['size']); ?></p>
            <p><strong>Color:</strong> <?php echo h($product['color']); ?></p>
            <p><strong>Price:</strong> <?php echo formatCurrency($product['price']); ?></p>
            <p><strong>Stock:</strong> <?php echo h((string)$product['stock_qty']); ?></p>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            
            <div class="form-group">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Product
                </button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.product-details {
    margin: 20px 0;
    padding: 15px;
    background: var(--light-gray);
    border-radius: 8px;
}
.product-details p {
    margin-bottom: 10px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
