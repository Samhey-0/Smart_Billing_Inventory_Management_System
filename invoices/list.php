<?php
/**
 * Invoices List Page
 * 
 * Display all invoices
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Invoices';

// Get all invoices with customer name
$invoices = $pdo->query("
    SELECT i.*, c.full_name AS customer_name
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Invoices</h1>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Invoice
        </a>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($invoices)): ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice-dollar"></i>
                <p>No invoices yet.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
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
                                <td><?php echo h($invoice['customer_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo formatDate($invoice['created_at']); ?></td>
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
                                    <a href="view.php?id=<?php echo h((string)$invoice['id']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="receipt.php?id=<?php echo h((string)$invoice['id']); ?>" class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-print"></i> Print
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
