<?php
/**
 * Customers List Page
 * 
 * Display all customers with search functionality
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Customers';

// Handle search
$search = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$searchParam = "%$search%";

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(i.id) AS total_invoices 
        FROM customers c
        LEFT JOIN invoices i ON i.customer_id = c.id
        WHERE c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $customers = $pdo->query("
        SELECT c.*, COUNT(i.id) AS total_invoices 
        FROM customers c
        LEFT JOIN invoices i ON i.customer_id = c.id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Customers</h1>
    <div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add Customer
        </a>
    </div>
</div>

<!-- Search Form -->
<div class="search-container">
    <form method="GET" class="search-form">
        <input type="text" name="q" placeholder="Search by name, email, or phone..." 
               value="<?php echo h($search); ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search)): ?>
            <a href="list.php" class="btn-clear">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($customers)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No customers found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Total Invoices</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo h((string)$customer['id']); ?></td>
                                <td>
                                    <a href="profile.php?id=<?php echo h((string)$customer['id']); ?>">
                                        <strong><?php echo h($customer['full_name']); ?></strong>
                                    </a>
                                </td>
                                <td><?php echo h($customer['email']); ?></td>
                                <td><?php echo h($customer['phone']); ?></td>
                                <td><?php echo h($customer['city'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo h((string)$customer['total_invoices']); ?></span>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo h((string)$customer['id']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if (isAdmin()): ?>
                                        <a href="delete.php?id=<?php echo h((string)$customer['id']); ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this customer?');">
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
