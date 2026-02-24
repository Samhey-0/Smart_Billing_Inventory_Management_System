<?php
/**
 * Products List Page
 * 
 * Display all products with search functionality
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Products';

// Handle search
$search = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$searchParam = "%$search%";

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE name LIKE ? OR brand LIKE ? OR color LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Products</h1>
    <div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Product
        </a>
    </div>
</div>

<!-- Search Form -->
<div class="search-container">
    <form method="GET" class="search-form">
        <input type="text" name="q" placeholder="Search by name, brand, or color..." 
               value="<?php echo h($search); ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search)): ?>
            <a href="list.php" class="btn-clear">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box"></i>
                <p>No products found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Size</th>
                            <th>Color</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo h((string)$product['id']); ?></td>
                                <td><strong><?php echo h($product['name']); ?></strong></td>
                                <td><?php echo h($product['brand']); ?></td>
                                <td><?php echo h($product['size']); ?></td>
                                <td><?php echo h($product['color']); ?></td>
                                <td><strong><?php echo formatCurrency($product['price']); ?></strong></td>
                                <td>
                                    <?php if ($product['stock_qty'] <= 10): ?>
                                        <span class="badge badge-warning"><?php echo h((string)$product['stock_qty']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?php echo h((string)$product['stock_qty']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo h((string)$product['id']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if (isAdmin()): ?>
                                        <a href="delete.php?id=<?php echo h((string)$product['id']); ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-container {
    margin-bottom: 20px;
}
.search-form {
    display: flex;
    gap: 10px;
    max-width: 500px;
}
.search-form input {
    flex: 1;
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
}
.search-form input:focus {
    outline: none;
    border-color: var(--primary-color);
}
.search-form button {
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
.search-form .btn-clear {
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
