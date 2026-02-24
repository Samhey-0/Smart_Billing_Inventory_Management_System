<?php
/**
 * Customer Profile Page
 * 
 * Display customer details and purchase history
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Customer Profile';

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

// Get customer invoices
$stmt = $pdo->prepare("
    SELECT i.*, u.username AS user_name
    FROM invoices i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.customer_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total purchases
$totalPurchases = 0;
$paidInvoices = 0;
foreach ($invoices as $inv) {
    $totalPurchases += $inv['grand_total'];
    if ($inv['status'] === 'Paid') {
        $paidInvoices++;
    }
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Customer Profile</h1>
    <div>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
        <a href="edit.php?id=<?php echo h((string)$id); ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>

<!-- Customer Details -->
<div class="profile-header">
    <div class="customer-avatar">
        <i class="fas fa-user"></i>
    </div>
    <div class="customer-info">
        <h2><?php echo h($customer['full_name']); ?></h2>
        <p><i class="fas fa-envelope"></i> <?php echo h($customer['email']); ?></p>
        <p><i class="fas fa-phone"></i> <?php echo h($customer['phone']); ?></p>
        <?php if ($customer['address']): ?>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo h($customer['address']); ?><?php echo $customer['city'] ? ', ' . h($customer['city']) : ''; ?></p>
        <?php endif; ?>
        <p><i class="fas fa-calendar"></i> Member since <?php echo formatDate($customer['created_at']); ?></p>
    </div>
    <div class="customer-stats">
        <div class="stat-box">
            <h3><?php echo h((string)count($invoices)); ?></h3>
            <p>Total Invoices</p>
        </div>
        <div class="stat-box">
            <h3><?php echo h((string)$paidInvoices); ?></h3>
            <p>Paid</p>
        </div>
        <div class="stat-box">
            <h3><?php echo formatCurrency($totalPurchases); ?></h3>
            <p>Total Spent</p>
        </div>
    </div>
</div>

<!-- Purchase History -->
<div class="card">
    <div class="card-header">
        <h3>Purchase History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($invoices)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <p>No purchases yet.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Created By</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td>
                                    <strong><?php echo generateInvoiceNumber($invoice['id']); ?></strong>
                                </td>
                                <td><?php echo formatDate($invoice['created_at']); ?></td>
                                <td><?php echo h($invoice['user_name'] ?? 'Unknown'); ?></td>
                                <td><strong><?php echo formatCurrency($invoice['grand_total']); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($invoice['status']) {
                                        case 'Paid': $status_class = 'badge-success'; break;
                                        case 'Unpaid': $status_class = 'badge-warning'; break;
                                        case 'Cancelled': $status_class = 'badge-danger'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo h($invoice['status']); ?></span>
                                </td>
                                <td>
                                    <a href="../invoices/view.php?id=<?php echo h((string)$invoice['id']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="../invoices/receipt.php?id=<?php echo h((string)$invoice['id']); ?>" class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-print"></i> Receipt
                                    </a>
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
.profile-header {
    display: flex;
    align-items: center;
    gap: 30px;
    background: var(--white);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--shadow);
    margin-bottom: 20px;
}

.customer-avatar {
    width: 100px;
    height: 100px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: var(--white);
}

.customer-info {
    flex: 1;
}

.customer-info h2 {
    margin-bottom: 10px;
    color: var(--primary);
}

.customer-info p {
    color: var(--text-light);
    margin-bottom: 5px;
}

.customer-info i {
    width: 20px;
    color: var(--accent);
}

.customer-stats {
    display: flex;
    gap: 20px;
}

.stat-box {
    text-align: center;
    padding: 15px 25px;
    background: var(--light-gray);
    border-radius: 8px;
}

.stat-box h3 {
    font-size: 24px;
    color: var(--primary);
}

.stat-box p {
    font-size: 12px;
    color: var(--text-light);
    text-transform: uppercase;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .customer-stats {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
