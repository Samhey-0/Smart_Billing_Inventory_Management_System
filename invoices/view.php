<?php
/**
 * View Invoice Page
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$page_title = 'View Invoice';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('error', 'Invalid invoice ID.');
    header('Location: ' . BASE_URL . '/invoices/list.php');
    exit();
}

// ✅ FIXED: Fetch invoice FIRST before any permission checks
$stmt = $pdo->prepare("
    SELECT i.*, c.full_name AS customer_name, c.email AS customer_email,
           c.phone AS customer_phone, c.address AS customer_address,
           u.username AS user_name
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    setFlashMessage('error', 'Invoice not found.');
    header('Location: ' . BASE_URL . '/invoices/list.php');
    exit();
}

// Handle status update — Staff can only update invoices THEY created
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $canUpdate = isAdmin() || (int)$invoice['user_id'] === (int)$_SESSION['user_id'];
    if (!$canUpdate) {
        setFlashMessage('error', 'You can only update status of invoices you created.');
        header('Location: ' . BASE_URL . '/invoices/view.php?id=' . $id);
        exit();
    }
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $allowed_statuses = isAdmin() ? ['Unpaid', 'Paid', 'Cancelled'] : ['Unpaid', 'Paid'];
        $status = $_POST['status'] ?? '';
        if (in_array($status, $allowed_statuses)) {
            $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            setFlashMessage('success', 'Invoice status updated to ' . $status . '.');
        }
    }
    header('Location: ' . BASE_URL . '/invoices/view.php?id=' . $id);
    exit();
}

// Get invoice items
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = csrfToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Invoice <?php echo generateInvoiceNumber($invoice['id']); ?></h1>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo BASE_URL; ?>/invoices/list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="<?php echo BASE_URL; ?>/invoices/receipt.php?id=<?php echo h((string)$id); ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-print"></i> Print Receipt
        </a>
        <a href="<?php echo BASE_URL; ?>/invoices/export.php?id=<?php echo h((string)$id); ?>" class="btn btn-accent" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Invoice Details -->
<div class="card">
    <div class="card-header">
        <h3>Invoice Information</h3>
        <?php
        $status_class = match($invoice['status']) {
            'Paid'      => 'badge-success',
            'Unpaid'    => 'badge-warning',
            'Cancelled' => 'badge-danger',
            default     => ''
        };
        ?>
        <span class="badge <?php echo $status_class; ?>"><?php echo h($invoice['status']); ?></span>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div>
                <p><strong>Invoice #:</strong> <?php echo generateInvoiceNumber($invoice['id']); ?></p>
                <p><strong>Date:</strong> <?php echo formatDateTime($invoice['created_at']); ?></p>
                <p><strong>Created By:</strong> <?php echo h($invoice['user_name'] ?? 'Unknown'); ?></p>
            </div>
            <div>
                <p><strong>Customer:</strong> <?php echo h($invoice['customer_name'] ?? 'Unknown'); ?></p>
                <p><strong>Email:</strong> <?php echo h($invoice['customer_email'] ?? '-'); ?></p>
                <p><strong>Phone:</strong> <?php echo h($invoice['customer_phone'] ?? '-'); ?></p>
            </div>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div class="mt-2">
                <p><strong>Notes:</strong> <?php echo h($invoice['notes']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Update Status — Admin can update all, Staff can only update their own -->
        <?php
        $canUpdateStatus = isAdmin() || (int)$invoice['user_id'] === (int)$_SESSION['user_id'];
        ?>
        <?php if ($canUpdateStatus): ?>
        <div class="mt-3">
            <form method="POST" class="status-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                <label><strong>Update Status:</strong></label>
                <select name="status" class="form-control" style="width:auto; display:inline-block;">
                    <option value="Unpaid" <?php echo $invoice['status'] === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Paid"   <?php echo $invoice['status'] === 'Paid'   ? 'selected' : ''; ?>>Paid</option>
                    <?php if (isAdmin()): ?>
                    <option value="Cancelled" <?php echo $invoice['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <?php endif; ?>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="mt-3">
            <small class="text-muted"><i class="fas fa-lock"></i> Status can only be changed by the admin or the staff who created this invoice.</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Invoice Items -->
<div class="card">
    <div class="card-header"><h3>Invoice Items</h3></div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo h($item['product_name']); ?></td>
                            <td><?php echo h($item['size'] ?? '-'); ?></td>
                            <td><?php echo h((string)$item['quantity']); ?></td>
                            <td><?php echo formatCurrency($item['unit_price']); ?></td>
                            <td><strong><?php echo formatCurrency($item['line_total']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="invoice-totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span><?php echo formatCurrency($invoice['subtotal']); ?></span>
            </div>
            <div class="total-row">
                <span>Tax (<?php echo h((string)$invoice['tax_rate']); ?>%):</span>
                <span><?php echo formatCurrency($invoice['tax_amount']); ?></span>
            </div>
            <?php if ($invoice['discount'] > 0): ?>
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-<?php echo formatCurrency($invoice['discount']); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span><?php echo formatCurrency($invoice['grand_total']); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.detail-grid p { margin-bottom: 8px; }
@media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } }
.status-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.invoice-totals { max-width: 300px; margin-left: auto; margin-top: 20px; padding: 15px; background: var(--light-gray); border-radius: 8px; }
.total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border); }
.total-row:last-child { border-bottom: none; }
.total-row.grand-total { font-size: 18px; font-weight: bold; color: var(--primary); border-top: 2px solid var(--primary); margin-top: 10px; padding-top: 15px; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
